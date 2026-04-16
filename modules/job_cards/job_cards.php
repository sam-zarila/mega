<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Job Cards
Description: IPMS Job Card Management for production routing and tracking.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('JC_MODULE_NAME', 'job_cards');
define('JC_VERSION', '1.0.0');

register_activation_hook(JC_MODULE_NAME, 'jc_module_activation');

hooks()->add_action('admin_init', 'jc_init_menu_items');
hooks()->add_action('app_admin_head', 'jc_add_head_assets');
hooks()->add_action('app_admin_footer', 'jc_add_footer_assets');
hooks()->add_action('ipms_document_approved', 'jc_on_proposal_approved');
hooks()->add_action('after_cron_run', 'jc_check_overdue_cards');

register_language_files(JC_MODULE_NAME, [JC_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(JC_MODULE_NAME . '/job_cards');

function jc_module_activation()
{
    $CI = &get_instance();
    require_once module_dir_path(JC_MODULE_NAME, 'install.php');
}

/**
 * Determine if current request is for job cards admin pages.
 *
 * @return bool
 */
function jc_is_job_cards_page()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

    return $uri !== '' && stripos($uri, '/admin/job_cards') !== false;
}

/**
 * Resolve current staff role name.
 *
 * @return string
 */
function jc_get_current_staff_role()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return '';
    }

    $staffId = (int) get_staff_user_id();
    if ($staffId < 1) {
        return '';
    }

    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'roles.name as role_name');
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');
    $CI->db->where(db_prefix() . 'staff.staffid', $staffId);
    $row = $CI->db->get()->row();

    return $row && isset($row->role_name) ? (string) $row->role_name : '';
}

/**
 * Map a role name to one or more job card departments.
 *
 * @param string $roleName
 * @return array
 */
function jc_get_departments_for_role($roleName)
{
    $roleName = (string) $roleName;

    $map = [
        jc_setting('jc_studio_role', 'Studio/Production')          => ['studio'],
        jc_setting('jc_stores_role', 'Storekeeper/Stores Clerk')   => ['stores'],
        jc_setting('jc_store_manager_role', 'Store Manager')        => ['stores'],
        jc_setting('jc_field_team_role', 'Field Team')              => ['field_team'],
        jc_setting('jc_warehouse_role', 'Storekeeper/Stores Clerk') => ['warehouse'],
    ];

    return $map[$roleName] ?? [];
}

function jc_init_menu_items()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    $badgeCount = 0;
    $roleName   = jc_get_current_staff_role();
    $departments = jc_get_departments_for_role($roleName);

    if (!empty($departments)) {
        $CI = &get_instance();
        $CI->db->from(db_prefix() . 'ipms_job_cards');
        $CI->db->where_in('status', [1, 2, 3, 4]);
        $CI->db->group_start();
        foreach ($departments as $dept) {
            $CI->db->or_where('FIND_IN_SET(' . $CI->db->escape($dept) . ', department_routing) >', 0, false);
        }
        $CI->db->group_end();
        $badgeCount = (int) $CI->db->count_all_results();
    }

    $item = [
        'name'     => 'Job Cards',
        'href'     => admin_url('job_cards'),
        'icon'     => 'fa fa-clipboard',
        'position' => 37,
    ];

    if ($badgeCount > 0) {
        $item['badge'] = [
            'value' => (string) $badgeCount,
            'type'  => 'warning',
        ];
    }

    add_module_menu_item('ipms-job-cards', $item);
}

function jc_add_head_assets()
{
    if (!jc_is_job_cards_page()) {
        return;
    }

    echo '<link href="' . module_dir_url(JC_MODULE_NAME, 'assets/css/job_cards.css') . '" rel="stylesheet" type="text/css" />';
}

function jc_add_footer_assets()
{
    if (!jc_is_job_cards_page()) {
        return;
    }

    echo '<script src="' . module_dir_url(JC_MODULE_NAME, 'assets/js/job_cards.js') . '"></script>';
}

function jc_on_proposal_approved($data)
{
    if (!is_array($data) || !isset($data['type'], $data['id'])) {
        return;
    }

    if ((string) $data['type'] !== 'quotation') {
        return;
    }

    if ((string) jc_setting('jc_auto_create_on_approval', '1') !== '1') {
        return;
    }

    jc_auto_create_from_proposal((int) $data['id']);
}

function jc_check_overdue_cards()
{
    $CI = &get_instance();
    $CI->db->from(db_prefix() . 'ipms_job_cards');
    $CI->db->where('deadline IS NOT NULL', null, false);
    $CI->db->where('deadline <', date('Y-m-d'));
    $CI->db->where_not_in('status', [6, 7]);
    $rows = $CI->db->get()->result();

    if (empty($rows)) {
        return;
    }

    $gmStaff = jc_get_staff_by_role('General Manager');

    foreach ($rows as $jc) {
        if ((int) $jc->assigned_sales_id > 0) {
            add_notification([
                'description' => 'Job Card ' . $jc->jc_ref . ' is overdue',
                'touserid'    => (int) $jc->assigned_sales_id,
                'fromuserid'  => 0,
                'link'        => 'job_cards/view/' . (int) $jc->id,
            ]);
        }

        if (!empty($gmStaff)) {
            foreach ($gmStaff as $gm) {
                add_notification([
                    'description' => 'Job Card ' . $jc->jc_ref . ' is overdue',
                    'touserid'    => (int) $gm->staffid,
                    'fromuserid'  => 0,
                    'link'        => 'job_cards/view/' . (int) $jc->id,
                ]);
            }
        }

        if (function_exists('log_activity')) {
            log_activity('Job Card ' . $jc->jc_ref . ' is overdue');
        } else {
            log_message('info', 'Job Card ' . $jc->jc_ref . ' is overdue');
        }
    }
}

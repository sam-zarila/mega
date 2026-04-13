<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: IPMS Approvals
Description: Multi-stage approvals for quotations, credit notes, journals, payments, and purchase requisitions.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('APPROVALS_MODULE_NAME', 'approvals');
define('APPROVALS_VERSION', '1.0.0');

register_activation_hook(APPROVALS_MODULE_NAME, 'approvals_module_activation_hook');

hooks()->add_action('admin_init', 'approvals_module_init_menu_items');
hooks()->add_action('app_admin_head', 'approvals_add_head_assets');
hooks()->add_action('app_admin_head', 'approvals_inject_navbar_badge');
hooks()->add_action('app_admin_footer', 'approvals_add_footer_assets');
hooks()->add_action('after_cron_run', 'approvals_check_sla_escalations');

register_language_files(APPROVALS_MODULE_NAME, [APPROVALS_MODULE_NAME]);

function approvals_module_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

function approvals_module_init_menu_items()
{
    if (!is_staff_logged_in()) {
        return;
    }

    if (is_admin()) {
        $isAllowed = true;
    } else {
    $role = get_staff_role(get_staff_user_id());
    $allowed = ['General Manager', 'Finance Manager', 'Sales Manager'];
    $isAllowed = in_array($role, $allowed, true);
    }
    if (!$isAllowed) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('approvals/approvals_model');

    $sid     = get_staff_user_id();
    $pending = $CI->approvals_model->get_pending_count_for_approver($sid);
    $pending += (int) $CI->db->where('current_approver_id', $sid)
        ->where('status', 'escalated')
        ->count_all_results(db_prefix() . 'ipms_approval_requests');

    $item = [
        'name'     => _l('approvals'),
        'href'     => admin_url('approvals'),
        'icon'     => 'fa fa-check-circle',
        'position' => 36,
    ];

    if ($pending > 0) {
        $item['badge'] = [
            'value' => (string) $pending,
            'type'  => 'danger',
        ];
    }

    add_module_menu_item('ipms-approvals', $item);
}

function approvals_add_head_assets()
{
}

/**
 * Live pending-approval badge, title prefix, polling, notifications (admin only).
 */
function approvals_inject_navbar_badge()
{
    if (!is_staff_logged_in()) {
        return;
    }

    if (is_admin()) {
        $isAllowed = true;
    } else {
    $role = get_staff_role(get_staff_user_id());
    $allowed = ['General Manager', 'Finance Manager', 'Sales Manager'];
    $isAllowed = in_array($role, $allowed, true);
    }
    if (!$isAllowed) {
        return;
    }

    $CI = &get_instance();
    $CI->load->language('approvals/approvals', 'english');
    $CI->load->view('approvals/includes/navbar_badge', [
        'approvals_pending_count_url' => admin_url('approvals/pending_count?nocache=1'),
        'approvals_view_base_url'     => admin_url('approvals/view/'),
        'i18n'                        => [
            'notif_title'   => _l('approvals'),
            'notif_body'    => _l('approval_nav_notif_body'),
            'toast_view'    => _l('approval_toast_view'),
            'toast_new'     => _l('approval_toast_new_prefix'),
        ],
    ]);
}

function approvals_add_footer_assets()
{
}

function approvals_check_sla_escalations()
{
    if (!class_exists('ApprovalService', false)) {
        require_once module_dir_path(APPROVALS_MODULE_NAME, 'libraries/ApprovalService.php');
    }

    try {
        $service = new ApprovalService();
        $service->process_sla_escalations();
    } catch (Throwable $e) {
        log_message('error', 'approvals_check_sla_escalations: ' . $e->getMessage());
    }
}

$CI = &get_instance();
$CI->load->helper(APPROVALS_MODULE_NAME . '/approvals');

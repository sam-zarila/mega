<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: IPMS Quotations
Description: Integrated Production Management — quotations workflow for MW.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('QUOTATIONS_MODULE_NAME', 'quotations');
define('QUOTATIONS_VERSION', '1.0.0');
define('QUOTATIONS_VAT_RATE', 16.5);

register_activation_hook(QUOTATIONS_MODULE_NAME, 'quotations_module_activation_hook');

hooks()->add_action('admin_init', 'quotations_init_menu_items', 20);
hooks()->add_action('app_admin_head', 'quotations_add_assets_head');
hooks()->add_action('app_admin_footer', 'quotations_add_assets_footer');
hooks()->add_filter('before_set_estimate_statuses', 'quotations_register_custom_statuses', 10, 1);
hooks()->add_action('ipms_document_approved', 'quotations_on_document_approved');
hooks()->add_action('ipms_document_rejected', 'quotations_on_document_rejected');

register_language_files(QUOTATIONS_MODULE_NAME, [QUOTATIONS_MODULE_NAME]);

function quotations_module_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

/**
 * @param array $statuses
 *
 * @return array
 */
function quotations_register_custom_statuses($statuses)
{
    if (!is_array($statuses)) {
        $statuses = [];
    }

    if (!in_array(6, $statuses, true)) {
        $statuses[] = 6;
    }

    return $statuses;
}

function quotations_init_menu_items()
{
    if (!is_staff_logged_in()) {
        return;
    }

    // Match Quotations controller access: anyone who can view estimates (or admin / named roles) sees the menu.
    $canEstimates = staff_can('view', 'estimates') || staff_can('view_own', 'estimates');
    if ($canEstimates || is_admin()) {
        $isAllowed = true;
    } else {
        $role    = function_exists('get_staff_role') ? get_staff_role(get_staff_user_id()) : '';
        $allowed = [
            'Sales Executive',
            'Sales Representative',
            'Sales Manager',
            'Finance Manager',
            'General Manager',
            'GM',
            'System Administrator',
            'Administrator',
        ];
        $isAllowed = in_array($role, $allowed, true);
    }

    if (!$isAllowed) {
        return;
    }

    $CI = &get_instance();

    $badgeCount = 0;
    if ($CI->db->table_exists(db_prefix() . 'ipms_quotations')) {
        $CI->db->where('created_by', get_staff_user_id());
        $CI->db->where_in('status', ['draft', 'submitted']);
        $badgeCount = (int) $CI->db->count_all_results(db_prefix() . 'ipms_quotations');
    }

    $item = [
        'slug'     => 'ipms-quotations',
        'name'     => 'quotations_module_name',
        'href'     => admin_url('quotations'),
        'icon'     => 'fa fa-file-text',
        'position' => 12,
        'badge'    => [],
    ];

    if ($badgeCount > 0) {
        $item['badge'] = [
            'value' => (string) $badgeCount,
            'type'  => 'warning',
        ];
    }

    $CI->app_menu->add_sidebar_children_item('sales', $item);
}

function quotations_add_assets_head()
{
}

function quotations_add_assets_footer()
{
}

/**
 * Sync ipms_quotations status when Approvals marks a quotation approved.
 *
 * @param array<string, mixed> $data keys: type, id
 */
function quotations_on_document_approved($data)
{
    if (!is_array($data) || ($data['type'] ?? '') !== 'quotation') {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('quotations/quotations_model');
    $CI->quotations_model->mark_approved((int) ($data['id'] ?? 0));
}

/**
 * Sync ipms_quotations status when Approvals marks a quotation rejected.
 *
 * @param array<string, mixed> $data keys: type, id, reason
 */
function quotations_on_document_rejected($data)
{
    if (!is_array($data) || ($data['type'] ?? '') !== 'quotation') {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('quotations/quotations_model');
    $CI->quotations_model->mark_rejected((int) ($data['id'] ?? 0));
}

$CI = &get_instance();
$CI->load->helper(QUOTATIONS_MODULE_NAME . '/quotations');

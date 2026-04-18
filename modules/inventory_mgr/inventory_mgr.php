<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: IPMS Inventory Manager
Description: Core inventory engine — stock master, WAC, GRN, movements, adjustments.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('INV_MGR_MODULE_NAME', 'inventory_mgr');
define('INV_MGR_VERSION', '1.0.0');

register_activation_hook(INV_MGR_MODULE_NAME, 'inv_mgr_activation');

hooks()->add_action('admin_init', 'inv_mgr_init_menu_items');
hooks()->add_action('app_admin_head', 'inv_mgr_add_head_assets');
hooks()->add_action('app_admin_footer', 'inv_mgr_add_footer_assets');
hooks()->add_action('after_cron_run', 'inv_mgr_check_low_stock_alerts');

register_language_files(INV_MGR_MODULE_NAME, [INV_MGR_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(INV_MGR_MODULE_NAME . '/inventory_mgr');

/**
 * Run install migrations on module activation.
 */
function inv_mgr_activation()
{
    $CI = &get_instance();
    require_once module_dir_path(INV_MGR_MODULE_NAME, 'install.php');
}

/**
 * True when the current request targets this module in admin.
 *
 * @return bool
 */
function inv_mgr_is_inventory_mgr_page()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

    return $uri !== '' && stripos($uri, '/admin/inventory_mgr') !== false;
}

/**
 * Sidebar: Inventory parent + children for authorized roles.
 */
function inv_mgr_init_menu_items()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    if (!inv_mgr_staff_can_access_inventory()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('ipms-inventory-mgr', [
        'name'     => 'Inventory',
        'icon'     => 'fa fa-cubes',
        'position' => 58,
    ]);

    $CI->app_menu->add_sidebar_children_item('ipms-inventory-mgr', [
        'slug'     => 'ipms-inv-items',
        'name'     => 'Items / Stock Master',
        'icon'     => 'fa fa-list menu-icon',
        'href'     => admin_url('inventory_mgr/items'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('ipms-inventory-mgr', [
        'slug'     => 'ipms-inv-add-item',
        'name'     => 'Add New Item',
        'icon'     => 'fa fa-plus menu-icon',
        'href'     => admin_url('inventory_mgr/add_item'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('ipms-inventory-mgr', [
        'slug'     => 'ipms-inv-grn',
        'name'     => 'Goods Receipt (GRN)',
        'icon'     => 'fa fa-truck menu-icon',
        'href'     => admin_url('inventory_mgr/grn'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('ipms-inventory-mgr', [
        'slug'     => 'ipms-inv-adjustments',
        'name'     => 'Stock Adjustments',
        'icon'     => 'fa fa-sliders menu-icon',
        'href'     => admin_url('inventory_mgr/adjustments'),
        'position' => 4,
    ]);

    $CI->app_menu->add_sidebar_children_item('ipms-inventory-mgr', [
        'slug'     => 'ipms-inv-movements',
        'name'     => 'Stock Movements',
        'icon'     => 'fa fa-history menu-icon',
        'href'     => admin_url('inventory_mgr/movements'),
        'position' => 5,
    ]);
}

function inv_mgr_add_head_assets()
{
    if (!inv_mgr_is_inventory_mgr_page()) {
        return;
    }

    $href = module_dir_url(INV_MGR_MODULE_NAME, 'assets/css/inventory_mgr.css?v=' . rawurlencode(INV_MGR_VERSION));
    echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

function inv_mgr_add_footer_assets()
{
    if (!inv_mgr_is_inventory_mgr_page()) {
        return;
    }

    $src = module_dir_url(INV_MGR_MODULE_NAME, 'assets/js/inventory_mgr.js?v=' . rawurlencode(INV_MGR_VERSION));
    echo '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}

/**
 * Cron: notify Store Managers about items at or below reorder level.
 */
function inv_mgr_check_low_stock_alerts()
{
    if ((string) inv_mgr_setting('low_stock_alert_enabled', '1') !== '1') {
        return;
    }

    $items = inv_mgr_get_low_stock_items();
    if (empty($items)) {
        return;
    }

    $managers = inv_mgr_get_staff_by_role('Store Manager');
    if (empty($managers)) {
        return;
    }

    $notifiedUserIds = [];

    foreach ($items as $row) {
        $id          = isset($row['id']) ? (int) $row['id'] : 0;
        $code        = isset($row['commodity_code']) ? (string) $row['commodity_code'] : '';
        $description = isset($row['description']) ? (string) $row['description'] : '';
        $totalQty    = isset($row['total_qty']) ? (string) $row['total_qty'] : '';
        $minLevel    = isset($row['inventory_number_min']) ? (string) $row['inventory_number_min'] : '';

        $msg = 'Low stock: ' . ($code !== '' ? $code . ' — ' : '') . $description
            . ' (qty ' . $totalQty . ', reorder min ' . $minLevel . ')';

        foreach ($managers as $staff) {
            $sid = (int) $staff->staffid;
            if ($sid < 1) {
                continue;
            }

            if (function_exists('add_notification')) {
                add_notification([
                    'description' => $msg,
                    'touserid'    => $sid,
                    'fromuserid'  => 0,
                    'link'        => 'inventory_mgr/items',
                ]);
            }
            $notifiedUserIds[] = $sid;
        }
    }

    if (!empty($notifiedUserIds) && function_exists('pusher_trigger_notification')) {
        pusher_trigger_notification(array_values(array_unique($notifiedUserIds)));
    }
}

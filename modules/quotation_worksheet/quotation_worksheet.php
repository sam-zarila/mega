<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Quotation Worksheet
Description: Inject MW worksheet tabs into native proposals form and preview flow.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('QT_MODULE_NAME', 'quotation_worksheet');
define('QT_VERSION', '1.0.0');
define('QT_VAT_RATE', 16.5);

register_activation_hook(QT_MODULE_NAME, 'qt_module_activation_hook');

hooks()->add_action('admin_init', 'qt_init_menu_items');
hooks()->add_action('app_admin_head', 'qt_inject_head_assets');
hooks()->add_action('app_admin_footer', 'qt_inject_footer_assets');
hooks()->add_filter('before_create_proposal', 'qt_on_proposal_create', 10, 1);
hooks()->add_filter('before_proposal_updated', 'qt_on_proposal_update', 10, 2);

register_language_files(QT_MODULE_NAME, [QT_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(QT_MODULE_NAME . '/quotation_worksheet');

/**
 * True on native admin proposal create/edit screen (not quotations list).
 * Uses REQUEST_URI first (works with subfolders / index.php), then router as fallback.
 */
function qt_should_load_proposal_worksheet_assets()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return false;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if ($uri !== '' && stripos($uri, '/admin/quotations') !== false) {
        return false;
    }
    if ($uri !== '' && stripos($uri, 'proposals/proposal') !== false) {
        return true;
    }

    $CI = &get_instance();
    if ($CI && $CI->router) {
        $class  = strtolower((string) $CI->router->fetch_class());
        $method = strtolower((string) $CI->router->fetch_method());

        return $class === 'proposals' && $method === 'proposal';
    }

    return false;
}

function qt_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}

function qt_init_menu_items()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
        ],
    ];

    register_staff_capabilities('quotation_worksheet', $capabilities, _l('quotation_worksheet'));
}

function qt_inject_head_assets()
{
    if (!qt_should_load_proposal_worksheet_assets()) {
        return;
    }

    $config = [
        'vat_rate'           => (float) QT_VAT_RATE,
        'default_markup'     => (float) qt_setting('qt_default_markup', 25),
        'ajax_url'           => admin_url('quotation_worksheet/'),
        'currency'           => 'MWK',
        'discount_threshold' => (float) qt_setting('qt_discount_approval_threshold', 0),
    ];

    echo '<link href="' . module_dir_url(QT_MODULE_NAME, 'assets/css/worksheet.css') . '" rel="stylesheet" type="text/css" />';
    echo '<script>var qt_config = ' . json_encode($config) . ';</script>';
}

function qt_inject_footer_assets()
{
    if (!qt_should_load_proposal_worksheet_assets()) {
        return;
    }

    $jsUrl    = module_dir_url(QT_MODULE_NAME, 'assets/js/worksheet.js');
    $ajaxBase = admin_url('quotation_worksheet/');

    echo '<script src="' . $jsUrl . '"></script>';
    echo '<script>';
    echo 'jQuery(function($) {';
    echo 'function qtMountWorksheetPanel() {';
    echo 'if (!$("#proposal-form").length && !$(".proposal-form").length) { return; }';
    echo 'var qtBoot = function() { if (typeof qt_init_worksheet === "function") { qt_init_worksheet(); } };';
    echo 'if ($("#qt-worksheet-panel").length) { qtBoot(); return; }';
    echo 'if (typeof qt_config === "undefined") { return; }';
    echo 'var pid = 0;';
    echo 'var pathMatch = window.location.pathname.match(/proposals\\/proposal\\/(\\d+)/);';
    echo 'if (pathMatch && pathMatch[1]) { pid = parseInt(pathMatch[1], 10) || 0; }';
    echo 'if (!pid) { var qm = window.location.search.match(/[?&]id=(\\d+)/); if (qm && qm[1]) { pid = parseInt(qm[1], 10) || 0; } }';
    echo 'if (!pid) { var hid = $("input[name=\\"isedit\\"]").val() || $("input[name=\\"id\\"]").val() || $("input[name=\\"proposal_id\\"]").val(); pid = parseInt(hid || 0, 10) || 0; }';
    echo '$.get(' . json_encode($ajaxBase) . ' + "get_panel/" + pid, function(html) {';
    echo 'if ($("#qt-worksheet-panel").length) { return; }';
    echo 'if (typeof html !== "string" || html.indexOf("qt-worksheet-panel") === -1) { console.error("QT: get_panel returned unexpected response"); return; }';
    echo 'var $hr = $("hr.hr-panel-separator").first();';
    echo 'if ($hr.length) { $hr.before(html); } else {';
    echo 'var $t = $("input[name=\\"subject\\"]").closest(".panel-body").first();';
    echo 'if (!$t.length) { $t = $(".content.accounting-template.proposal .panel_s .panel-body").first(); }';
    echo 'if (!$t.length) { $t = $("#proposal-form, .proposal-form").first(); }';
    echo 'if ($t.length) { $t.append(html); }';
    echo '}';
    echo 'qtBoot();';
    echo '}).fail(function(xhr) { console.error("QT get_panel failed", xhr.status, xhr.responseText); });';
    echo '}';
    echo 'setTimeout(qtMountWorksheetPanel, 0);';
    echo '});';
    echo '</script>';
}

function qt_on_proposal_create($hook_data)
{
    $CI = &get_instance();
    $CI->session->set_userdata('qt_pending_init', 1);

    return $hook_data;
}

function qt_on_proposal_update($hook_data, $proposal_id)
{
    $CI          = &get_instance();
    $qtWorksheet = $CI->input->post('qt_worksheet');

    if (!empty($qtWorksheet)) {
        qt_sync_worksheet_to_proposal($proposal_id, $_POST);

        $grandTotal      = (float) $CI->input->post('qt_grand_total');
        $discountPercent = (float) $CI->input->post('qt_discount_percent');
        $discountTotal   = (float) $CI->input->post('qt_discount_total');
        $subtotal        = (float) $CI->input->post('qt_subtotal');

        $CI->db->where('id', $proposal_id);
        $CI->db->update(db_prefix() . 'proposals', [
            'subtotal'         => $subtotal > 0 ? $subtotal : $grandTotal,
            'total'            => $grandTotal,
            'discount_percent' => $discountPercent,
            'discount_total'   => $discountTotal,
        ]);
    }

    return $hook_data;
}

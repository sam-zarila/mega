<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: IPMS Billing
Description: Finance inbox, invoice/credit-note meta, approvals, and GL hooks for MW IPMS.
Version: 1.0.0
Requires at least: 2.3.*
Author: IPMS
*/

define('BILLING_MODULE_NAME', 'billing');
define('BILLING_VERSION', '1.0.0');

register_activation_hook(BILLING_MODULE_NAME, 'billing_module_activation');

hooks()->add_action('admin_init', 'billing_init_menu');
hooks()->add_action('app_admin_head', 'billing_add_head_assets');
hooks()->add_action('app_admin_footer', 'billing_add_footer_assets');
hooks()->add_filter('before_invoice_added', 'billing_before_invoice_added', 10, 1);
hooks()->add_action('before_render_invoice_template', 'billing_invoice_form_hidden_ipms_fields');
hooks()->add_action('after_invoice_added', 'billing_after_invoice_added');
hooks()->add_action('after_payment_added', 'billing_after_payment_added');
hooks()->add_action('invoice_status_changed', 'billing_on_invoice_status_changed');
hooks()->add_filter('before_create_credit_note', 'billing_before_cn_created', 10, 1);
hooks()->add_action('after_create_credit_note', 'billing_after_cn_created');
hooks()->add_action('after_admin_invoice_preview_template_tab_menu_last_item', 'billing_inject_invoice_tab_menu');
hooks()->add_action('after_admin_invoice_preview_template_tab_content_last_item', 'billing_inject_invoice_tab_pane');
hooks()->add_action('before_invoice_preview_more_menu_button', 'billing_inject_invoice_actions');
hooks()->add_action('ipms_document_approved', 'billing_on_document_approved');

register_language_files(BILLING_MODULE_NAME, [BILLING_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(BILLING_MODULE_NAME . '/billing');
if (file_exists(module_dir_path('approvals', 'helpers/approvals_helper.php'))) {
    $CI->load->helper('approvals/approvals');
}

function billing_module_activation()
{
    $CI = &get_instance();
    require_once module_dir_path(BILLING_MODULE_NAME, 'install.php');
}

/**
 * @return bool
 */
function billing_is_invoice_or_credit_note_admin_page()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($uri === '') {
        return false;
    }

    if (stripos($uri, '/admin/invoices') !== false || stripos($uri, '/admin/invoice') !== false) {
        return true;
    }

    if (stripos($uri, '/admin/credit_notes') !== false || stripos($uri, '/admin/credit_note') !== false) {
        return true;
    }

    if (stripos($uri, '/admin/billing') !== false) {
        return true;
    }

    return false;
}

/**
 * @return bool
 */
function billing_staff_can_bypass_finance_only_edit()
{
    if (function_exists('is_admin') && is_admin()) {
        return true;
    }

    $role = '';
    if (function_exists('get_staff_role')) {
        $role = (string) get_staff_role(get_staff_user_id());
    } elseif (function_exists('is_staff_logged_in') && is_staff_logged_in()) {
        $CI = &get_instance();
        $CI->db->select(db_prefix() . 'roles.name as role_name');
        $CI->db->from(db_prefix() . 'staff');
        $CI->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');
        $CI->db->where(db_prefix() . 'staff.staffid', (int) get_staff_user_id());
        $row = $CI->db->get()->row();
        $role = $row && isset($row->role_name) ? (string) $row->role_name : '';
    }

    return in_array($role, ['Finance Manager', 'General Manager'], true);
}

/**
 * @return int
 */
function billing_count_dns_awaiting_invoice()
{
    $CI = &get_instance();
    $p  = db_prefix();
    if (!$CI->db->table_exists($p . 'ipms_delivery_notes')) {
        return 0;
    }

    $CI->db->from($p . 'ipms_delivery_notes');
    $CI->db->group_start();
    $CI->db->where('invoice_id IS NULL', null, false);
    $CI->db->or_where('invoice_id', 0);
    $CI->db->group_end();
    $CI->db->where('status', 'signed_confirmed');
    if ($CI->db->field_exists('invoice_triggered', $p . 'ipms_delivery_notes')) {
        $CI->db->where('invoice_triggered', 1);
    }

    return (int) $CI->db->count_all_results();
}

/**
 * @return int
 */
function billing_count_pending_cn_approvals()
{
    $CI = &get_instance();
    $p  = db_prefix();
    if (!$CI->db->table_exists($p . 'ipms_credit_note_meta')) {
        return 0;
    }

    $CI->db->from($p . 'ipms_credit_note_meta');
    $CI->db->where('gm_approval_status', 'pending');

    return (int) $CI->db->count_all_results();
}

function billing_init_menu()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    if (!staff_can('view', 'invoices') && !staff_can('view_own', 'invoices')) {
        return;
    }

    $badge = billing_count_dns_awaiting_invoice() + billing_count_pending_cn_approvals();

    $item = [
        'slug'     => 'billing_finance_inbox',
        'name'     => 'Finance Inbox',
        'href'     => admin_url('billing/finance_inbox'),
        'icon'     => 'fa fa-inbox',
        'position' => 16,
    ];

    if ($badge > 0) {
        $item['badge'] = [
            'value' => (string) $badge,
            'type'  => 'warning',
        ];
    }

    $CI = &get_instance();
    $CI->app_menu->add_sidebar_children_item('sales', $item);
}

/**
 * @param object|null $invoice
 */
function billing_invoice_form_hidden_ipms_fields($invoice)
{
    if ($invoice) {
        return;
    }

    $CI  = &get_instance();
    $ctx = $CI->session->userdata('billing_invoice_add_context');
    if (!is_array($ctx) || empty($ctx)) {
        return;
    }

    echo form_hidden('dn_id', (int) ($ctx['dn_id'] ?? 0));
    echo form_hidden('jc_id', (int) ($ctx['jc_id'] ?? 0));
    echo form_hidden('proposal_id', (int) ($ctx['proposal_id'] ?? 0));
    echo form_hidden('dn_ref', (string) ($ctx['dn_ref'] ?? ''));
    echo form_hidden('jc_ref', (string) ($ctx['jc_ref'] ?? ''));
    echo form_hidden('qt_ref', (string) ($ctx['qt_ref'] ?? ''));
    if (!empty($ctx['is_proforma'])) {
        echo form_hidden('is_proforma', 1);
    }
}

function billing_add_head_assets()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    if (!billing_is_invoice_or_credit_note_admin_page()) {
        return;
    }

    echo '<link href="' . module_dir_url(BILLING_MODULE_NAME, 'assets/css/billing.css') . '?v=' . BILLING_VERSION . '" rel="stylesheet" type="text/css" />';

    $config = [
        'vat_rate'          => (float) billing_setting('vat_rate', '16.5'),
        'payment_threshold' => (float) billing_setting('payment_threshold_gm', '5000000'),
        'ajax_url'          => admin_url('billing/'),
        'vat_reg'           => (string) billing_setting('vat_registration_number', ''),
    ];

    echo '<script>var billing_config = ' . json_encode($config) . ';</script>';
}

function billing_add_footer_assets()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    if (!billing_is_invoice_or_credit_note_admin_page()) {
        return;
    }

    $CI = &get_instance();
    $pf = $CI->session->flashdata('billing_dn_prefill');
    if (is_string($pf) && $pf !== '') {
        echo '<script>window.BILLING_DN_PREFILL = ' . $pf . ';</script>';
    }

    echo '<script src="' . module_dir_url(BILLING_MODULE_NAME, 'assets/js/billing.js') . '?v=' . BILLING_VERSION . '"></script>';
}

/**
 * @param object $invoice
 */
function billing_inject_invoice_tab_pane($invoice)
{
    if (!is_object($invoice) || empty($invoice->id)) {
        return;
    }

    if (!billing_get_invoice_meta((int) $invoice->id)) {
        return;
    }

    $id = (int) $invoice->id;

    echo '<div role="tabpanel" class="tab-pane ptop10" id="tab_ipms_billing" data-invoice-id="' . $id . '">';
    echo '<div class="billing-tab-loading text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Loading…</div>';
    echo '</div>';
}

/**
 * @param array $hook_data keys: data, items
 * @return array
 */
function billing_before_invoice_added($hook_data)
{
    if (!is_array($hook_data) || !isset($hook_data['data']) || !is_array($hook_data['data'])) {
        return $hook_data;
    }

    $CI   = &get_instance();
    $data = $hook_data['data'];

    if ((string) billing_setting('finance_only_edit', '0') === '1' && !billing_staff_can_bypass_finance_only_edit()) {
        access_denied('invoices');
    }

    $dnId       = (int) ($data['dn_id'] ?? 0);
    $jcId       = (int) ($data['jc_id'] ?? 0);
    $proposalId = (int) ($data['proposal_id'] ?? 0);

    $ctx = $CI->session->userdata('billing_invoice_add_context');
    if (is_array($ctx)) {
        if ($dnId < 1) {
            $dnId = (int) ($ctx['dn_id'] ?? 0);
        }
        if ($jcId < 1) {
            $jcId = (int) ($ctx['jc_id'] ?? 0);
        }
        if ($proposalId < 1) {
            $proposalId = (int) ($ctx['proposal_id'] ?? 0);
        }
        if (empty($data['dn_ref']) && !empty($ctx['dn_ref'])) {
            $data['dn_ref'] = (string) $ctx['dn_ref'];
        }
        if (empty($data['jc_ref']) && !empty($ctx['jc_ref'])) {
            $data['jc_ref'] = (string) $ctx['jc_ref'];
        }
        if (empty($data['qt_ref']) && !empty($ctx['qt_ref'])) {
            $data['qt_ref'] = (string) $ctx['qt_ref'];
        }
        if (empty($data['is_proforma']) && !empty($ctx['is_proforma'])) {
            $data['is_proforma'] = (int) $ctx['is_proforma'];
        }
    }

    if ($dnId > 0 || $jcId > 0) {
        $ctx = [
            'dn_id'        => $dnId,
            'jc_id'        => $jcId,
            'proposal_id'  => $proposalId,
            'dn_ref'       => isset($data['dn_ref']) ? (string) $data['dn_ref'] : '',
            'jc_ref'       => isset($data['jc_ref']) ? (string) $data['jc_ref'] : '',
            'qt_ref'       => isset($data['qt_ref']) ? (string) $data['qt_ref'] : '',
            'is_proforma'  => !empty($data['is_proforma']) || !empty($data['ipms_is_proforma']) ? 1 : 0,
        ];
        $CI->session->set_userdata('billing_invoice_add_context', $ctx);
    }

    foreach (['dn_id', 'jc_id', 'proposal_id', 'dn_ref', 'jc_ref', 'qt_ref', 'is_proforma', 'ipms_is_proforma'] as $k) {
        unset($data[$k]);
    }

    $hook_data['data'] = $data;

    return $hook_data;
}

/**
 * @param int $invoice_id
 */
function billing_after_invoice_added($invoice_id)
{
    $invoice_id = (int) $invoice_id;
    if ($invoice_id < 1) {
        return;
    }

    $CI  = &get_instance();
    $ctx = $CI->session->userdata('billing_invoice_add_context');
    if (!is_array($ctx)) {
        $ctx = [];
    }

    $p = db_prefix();

    $existing = billing_get_invoice_meta($invoice_id);
    if ($existing) {
        $CI->session->unset_userdata('billing_invoice_add_context');

        return;
    }

    $dnId       = (int) ($ctx['dn_id'] ?? 0);
    $jcId       = (int) ($ctx['jc_id'] ?? 0);
    $proposalId = (int) ($ctx['proposal_id'] ?? 0);
    $dnRef      = (string) ($ctx['dn_ref'] ?? '');
    $jcRef      = (string) ($ctx['jc_ref'] ?? '');
    $qtRef      = (string) ($ctx['qt_ref'] ?? '');
    $isProforma = (int) ($ctx['is_proforma'] ?? 0);

    if ($dnId > 0 && $dnRef === '' && $CI->db->table_exists($p . 'ipms_delivery_notes')) {
        $row = $CI->db->get_where($p . 'ipms_delivery_notes', ['id' => $dnId])->row();
        if ($row && isset($row->dn_ref)) {
            $dnRef = (string) $row->dn_ref;
        }
    }

    if ($jcId > 0 && $jcRef === '' && $CI->db->table_exists($p . 'ipms_job_cards')) {
        $row = $CI->db->get_where($p . 'ipms_job_cards', ['id' => $jcId])->row();
        if ($row && isset($row->jc_ref)) {
            $jcRef = (string) $row->jc_ref;
        }
        if ($proposalId < 1 && isset($row->proposal_id)) {
            $proposalId = (int) $row->proposal_id;
        }
    }

    if ($proposalId > 0 && $qtRef === '') {
        $row = $CI->db->get_where($p . 'proposals', ['id' => $proposalId])->row();
        if ($row && isset($row->qt_ref)) {
            $qtRef = (string) $row->qt_ref;
        }
    }

    $vatReg = (string) billing_setting('vat_registration_number', '');
    if ($vatReg === '' && function_exists('get_option')) {
        $vatReg = (string) get_option('vat_registration_number');
    }

    $vatRate = (float) billing_setting('vat_rate', '16.5');

    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get($invoice_id);
    $vatAmt  = $invoice ? (float) $invoice->total_tax : 0.0;

    $insert = [
        'invoice_id'          => $invoice_id,
        'dn_id'               => $dnId > 0 ? $dnId : null,
        'dn_ref'              => $dnRef !== '' ? $dnRef : null,
        'job_card_id'         => $jcId > 0 ? $jcId : null,
        'jc_ref'              => $jcRef !== '' ? $jcRef : null,
        'proposal_id'         => $proposalId > 0 ? $proposalId : null,
        'qt_ref'              => $qtRef !== '' ? $qtRef : null,
        'is_proforma'         => $isProforma,
        'proforma_ref'        => null,
        'vat_registration_no' => $vatReg !== '' ? $vatReg : null,
        'vat_rate'            => $vatRate,
        'vat_amount'          => $vatAmt,
    ];

    $CI->db->insert($p . 'ipms_invoice_meta', $insert);
    $metaId = (int) $CI->db->insert_id();

    if ($metaId > 0) {
        $CI->db->where('id', $invoice_id);
        $CI->db->update($p . 'invoices', ['ipms_meta_id' => $metaId]);
    }

    if ($isProforma !== 1) {
        if ($dnId > 0 && $CI->db->table_exists($p . 'ipms_delivery_notes')) {
            $CI->db->where('id', $dnId);
            $CI->db->update($p . 'ipms_delivery_notes', ['invoice_id' => $invoice_id]);
        }

        if ($jcId > 0 && $CI->db->table_exists($p . 'ipms_job_cards')) {
            $CI->db->where('id', $jcId);
            $CI->db->update($p . 'ipms_job_cards', [
                'invoice_id' => $invoice_id,
                'status'       => 7,
            ]);
        }
    }

    if ($isProforma === 1 && $metaId > 0) {
        $pref = billing_generate_proforma_ref();
        $CI->db->where('id', $metaId);
        $CI->db->update($p . 'ipms_invoice_meta', ['proforma_ref' => $pref]);
    }

    $CI->session->unset_userdata('billing_invoice_add_context');

    if ($isProforma !== 1 && billing_accounting_is_active()) {
        billing_post_invoice_gl($invoice_id);
    }
}

/**
 * @param int $payment_id
 */
function billing_after_payment_added($payment_id)
{
    $payment_id = (int) $payment_id;
    if ($payment_id < 1) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('payments_model');

    $payment = $CI->payments_model->get($payment_id);
    if (!$payment) {
        return;
    }

    if (billing_get_payment_meta($payment_id)) {
        return;
    }

    $p = db_prefix();

    $form = $CI->session->userdata('billing_payment_form');
    if (!is_array($form)) {
        $form = [];
    }

    $detail = (string) $CI->input->post('billing_payment_method_detail');
    if ($detail === '' && isset($form['billing_payment_method_detail'])) {
        $detail = (string) $form['billing_payment_method_detail'];
    }
    if ($detail === '') {
        $detail = 'cash';
    }

    $allowed = ['cash', 'bank_transfer', 'cheque', 'airtel_money', 'tnm_mpamba', 'other'];
    if (!in_array($detail, $allowed, true)) {
        $detail = 'cash';
    }

    $refNo = (string) $CI->input->post('billing_reference_number');
    if ($refNo === '' && isset($form['billing_reference_number'])) {
        $refNo = (string) $form['billing_reference_number'];
    }

    $threshold = (float) billing_setting('payment_threshold_gm', '5000000');
    $amount    = (float) ($payment->amount ?? 0);
    $gmReq     = $amount > $threshold ? 1 : 0;

    $invoiceId = (int) ($payment->invoiceid ?? 0);

    $insert = [
        'payment_id'             => $payment_id,
        'invoice_id'             => $invoiceId,
        'payment_method_detail'  => $detail,
        'reference_number'       => $refNo !== '' ? $refNo : null,
        'received_by'            => (int) get_staff_user_id(),
        'gm_approval_required'   => $gmReq,
        'gm_approved_by'         => null,
        'gm_approved_at'         => null,
        'approval_request_id'    => null,
        'is_unallocated'         => 0,
        'suspense_cleared_at'    => null,
    ];

    $CI->db->insert($p . 'ipms_payment_meta', $insert);
    $metaRowId = (int) $CI->db->insert_id();

    if ($metaRowId > 0) {
        $CI->db->where('id', $payment_id);
        $CI->db->update($p . 'invoicepaymentrecords', ['ipms_meta_id' => $metaRowId]);
    }

    if ($gmReq === 1) {
        $CI->load->library('approvals/ApprovalService', null, 'approvalservice');
        $reqId = $CI->approvalservice->submit(
            'payment',
            $payment_id,
            'PMT-' . $payment_id,
            $amount,
            (int) get_staff_user_id()
        );
        if ($reqId) {
            $CI->db->where('payment_id', $payment_id);
            $CI->db->update($p . 'ipms_payment_meta', ['approval_request_id' => (int) $reqId]);
        }
    }
}

/**
 * Perfex passes ['invoice_id' => id, 'status' => new_status].
 *
 * @param array $data
 */
function billing_on_invoice_status_changed($data)
{
    if (!is_array($data) || !isset($data['invoice_id'], $data['status'])) {
        return;
    }

    $invoiceId = (int) $data['invoice_id'];
    $newStatus = (int) $data['status'];

    $CI = &get_instance();
    $CI->load->model('invoices_model');

    $invoice = $CI->invoices_model->get($invoiceId);
    if (!$invoice) {
        return;
    }

    $invNo = format_invoice_number($invoiceId);

    if ($newStatus === Invoices_model::STATUS_OVERDUE) {
        foreach (billing_get_staff_by_role_name('Finance Manager') as $u) {
            add_notification([
                'description' => 'Invoice ' . $invNo . ' is overdue',
                'touserid'    => (int) $u->staffid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'invoices/list_invoices/' . $invoiceId,
            ]);
        }

        $sid = (int) $invoice->sale_agent;
        if ($sid > 0) {
            add_notification([
                'description' => 'Invoice ' . $invNo . ' is overdue',
                'touserid'    => $sid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'invoices/list_invoices/' . $invoiceId,
            ]);
        }
    }

    if ($newStatus === Invoices_model::STATUS_PAID) {
        $sid = (int) $invoice->sale_agent;
        if ($sid > 0) {
            add_notification([
                'description' => 'Invoice ' . $invNo . ' has been fully paid',
                'touserid'    => $sid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'invoices/list_invoices/' . $invoiceId,
            ]);
        }

        $meta = billing_get_invoice_meta($invoiceId);
        if ($meta && (int) $meta->job_card_id > 0 && $CI->db->table_exists(db_prefix() . 'ipms_job_cards')) {
            $CI->db->where('id', (int) $meta->job_card_id);
            $CI->db->update(db_prefix() . 'ipms_job_cards', [
                'invoice_id' => $invoiceId,
            ]);
        }
    }
}

/**
 * @param array $hook_data keys: data, items
 * @return array
 */
function billing_before_cn_created($hook_data)
{
    $CI = &get_instance();

    $ctx = $CI->session->userdata('cn_context');
    if (!is_array($ctx)) {
        $ctx = [];
    }

    if (empty($ctx['original_invoice_id']) || empty($ctx['reason_category'])) {
        $ctx['original_invoice_id'] = (int) $CI->input->post('ipms_original_invoice_id');
        $ctx['reason_category']     = (string) $CI->input->post('ipms_reason_category');
        $ctx['reason_detail']       = (string) $CI->input->post('ipms_reason_detail');
        $CI->session->set_userdata('cn_context', $ctx);
    }

    return $hook_data;
}

/**
 * @param int $credit_note_id
 */
function billing_after_cn_created($credit_note_id)
{
    $credit_note_id = (int) $credit_note_id;
    if ($credit_note_id < 1) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('credit_notes_model');

    $cn = $CI->credit_notes_model->get($credit_note_id);
    if (!$cn) {
        return;
    }

    if (billing_get_cn_meta($credit_note_id)) {
        return;
    }

    $ctx = $CI->session->userdata('cn_context');
    if (!is_array($ctx)) {
        $ctx = [];
    }

    $origInvId = (int) ($ctx['original_invoice_id'] ?? 0);
    $reasonCat = (string) ($ctx['reason_category'] ?? 'goodwill');
    $allowed   = ['return_of_goods', 'billing_error', 'pricing_adjustment', 'goodwill'];
    if (!in_array($reasonCat, $allowed, true)) {
        $reasonCat = 'goodwill';
    }

    $reasonDetail = (string) ($ctx['reason_detail'] ?? '');

    if ($origInvId < 1) {
        log_message('error', 'billing_after_cn_created: missing original_invoice_id for CN ' . $credit_note_id);

        return;
    }

    $origRef = format_invoice_number($origInvId);

    $p = db_prefix();

    $insert = [
        'credit_note_id'          => $credit_note_id,
        'original_invoice_id'     => $origInvId,
        'original_invoice_ref'    => $origRef,
        'reason_category'         => $reasonCat,
        'reason_detail'           => $reasonDetail !== '' ? $reasonDetail : null,
        'gm_approval_status'      => 'pending',
        'gm_approval_request_id'  => null,
        'gm_approved_by'          => null,
        'gm_approved_at'          => null,
        'gm_rejection_reason'     => null,
        'vat_adjusted'            => 0,
        'vat_adjustment_amount'   => 0,
        'gl_posted'               => 0,
        'gl_posted_at'            => null,
    ];

    $CI->db->insert($p . 'ipms_credit_note_meta', $insert);
    $metaId = (int) $CI->db->insert_id();

    if ($metaId > 0) {
        $CI->db->where('id', $credit_note_id);
        $CI->db->update($p . 'creditnotes', ['ipms_meta_id' => $metaId]);
    }

    $CI->load->library('approvals/ApprovalService', null, 'approvalservice');
    $CI->load->helper('credit_notes');

    $total = (float) $cn->total;
    $reqId = $CI->approvalservice->submit(
        'credit_note',
        $credit_note_id,
        format_credit_note_number($credit_note_id),
        $total,
        (int) get_staff_user_id()
    );

    if ($reqId) {
        $CI->db->where('credit_note_id', $credit_note_id);
        $CI->db->update($p . 'ipms_credit_note_meta', ['gm_approval_request_id' => (int) $reqId]);
    }

    $CI->db->where('id', $credit_note_id);
    $CI->db->update($p . 'creditnotes', ['status' => 3]);

    $CI->session->unset_userdata('cn_context');
}

/**
 * @param array $data keys: type, id
 */
function billing_on_document_approved($data)
{
    if (!is_array($data) || empty($data['type']) || empty($data['id'])) {
        return;
    }

    $type = (string) $data['type'];
    $id   = (int) $data['id'];
    $CI   = &get_instance();
    $p    = db_prefix();

    if ($type === 'credit_note') {
        $CI->db->where('id', $id);
        $CI->db->update($p . 'creditnotes', ['status' => 1]);

        $CI->db->where('credit_note_id', $id);
        $CI->db->update($p . 'ipms_credit_note_meta', [
            'gm_approval_status' => 'approved',
            'gm_approved_by'     => (int) get_staff_user_id(),
            'gm_approved_at'     => date('Y-m-d H:i:s'),
        ]);

        foreach (billing_get_staff_by_role_name('Finance Manager') as $u) {
            add_notification([
                'description' => 'Credit Note approved by GM. You may now apply or refund.',
                'touserid'    => (int) $u->staffid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'credit_notes/credit_note/' . $id,
            ]);
        }

        billing_post_cn_gl($id);
    }

    if ($type === 'payment') {
        $CI->db->where('payment_id', $id);
        $CI->db->update($p . 'ipms_payment_meta', [
            'gm_approved_by' => (int) get_staff_user_id(),
            'gm_approved_at' => date('Y-m-d H:i:s'),
        ]);

        if (billing_accounting_is_active()) {
            $CI->load->model('accounting/accounting_model');
            $CI->accounting_model->automatic_payment_conversion($id);
        }
    }
}

/**
 * @param object $invoice
 */
function billing_inject_invoice_tab_menu($invoice)
{
    if (!is_object($invoice) || empty($invoice->id)) {
        return;
    }

    $meta = billing_get_invoice_meta((int) $invoice->id);
    if (!$meta) {
        return;
    }

    echo '<li role="presentation">';
    echo '<a href="#tab_ipms_billing" aria-controls="tab_ipms_billing" role="tab" data-toggle="tab">';
    echo '<i class="fa fa-file-text-o"></i> Billing Info';
    echo '</a></li>';
}

/**
 * @param object $invoice
 */
function billing_inject_invoice_actions($invoice)
{
    if (!is_object($invoice) || empty($invoice->id)) {
        return;
    }

    $meta = billing_get_invoice_meta((int) $invoice->id);
    if (!$meta) {
        return;
    }

    if (!empty($meta->dn_id)) {
        $url = admin_url('warehouse/view_delivery/' . (int) $meta->dn_id);
        echo '<a href="' . html_escape($url) . '" class="btn btn-default btn-sm mright5" target="_blank">';
        echo '<i class="fa fa-truck"></i> View Delivery Note';
        echo '</a>';
    }

    if ((int) $meta->is_proforma === 1) {
        echo form_open(admin_url('billing/convert_proforma_to_invoice/' . (int) $invoice->id), ['class' => 'inline-block']);
        echo '<button type="submit" class="btn btn-info btn-sm mright5">';
        echo '<i class="fa fa-exchange"></i> Convert to Invoice';
        echo '</button>';
        echo form_close();
    }
}

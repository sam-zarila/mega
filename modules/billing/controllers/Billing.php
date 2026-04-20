<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Billing extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_staff_logged_in()) {
            redirect(admin_url('authentication'));
        }

        $this->load->model('billing/billing_model');
        $this->load->model('invoices_model');
        $this->load->model('payments_model');
        $this->load->model('credit_notes_model');
        $this->load->model('clients_model');
        $this->load->model('payment_modes_model');
        $this->load->model('taxes_model');
        $this->load->helper('billing/billing');
        $this->load->helper('credit_notes');
        $this->lang->load('billing/billing', $GLOBALS['language'] ?? 'english');
    }

    public function finance_inbox()
    {
        $this->require_finance_or_gm();

        $dns              = $this->billing_model->get_dns_awaiting_invoice();
        $pending_cn       = $this->billing_model->get_pending_cn_approvals();
        $pending_payments = $this->billing_model->get_pending_large_payments();
        $overdue          = $this->billing_model->get_overdue_invoices();

        $data['title']              = 'Finance Inbox';
        $data['dns_awaiting']       = $dns;
        $data['pending_cn']        = $pending_cn;
        $data['pending_payments']  = $pending_payments;
        $data['overdue_invoices']  = $overdue;
        $data['is_gm']             = is_admin() || $this->staff_is_general_manager();
        $data['inbox_stats']       = $this->build_finance_inbox_stats($dns, $pending_cn, $pending_payments, $overdue);

        $this->load->view('billing/finance_inbox', $data);
    }

    public function create_from_dn($dn_id = '')
    {
        $this->require_finance_or_gm();

        $dn_id = (int) $dn_id;
        if ($dn_id < 1) {
            set_alert('danger', 'Invalid delivery note.');
            redirect(admin_url('billing/finance_inbox'));
        }

        $p  = db_prefix();
        $dn = $this->db->get_where($p . 'ipms_delivery_notes', ['id' => $dn_id])->row();
        if (!$dn || (string) $dn->status !== 'signed_confirmed') {
            set_alert('danger', 'Delivery note not found or not confirmed.');
            redirect(admin_url('billing/finance_inbox'));
        }

        $populate = billing_populate_from_dn_and_quotation($dn_id);
        if ($populate === false) {
            set_alert('danger', 'Could not build invoice data from this delivery note.');
            redirect(admin_url('billing/finance_inbox'));
        }

        $ctx = [
            'dn_id'       => $dn_id,
            'jc_id'       => (int) ($populate['jc_id'] ?? 0),
            'proposal_id' => (int) ($populate['proposal_id'] ?? 0),
            'dn_ref'      => (string) ($populate['dn_ref'] ?? ''),
            'jc_ref'      => (string) ($populate['jc_ref'] ?? ''),
            'qt_ref'      => (string) ($populate['qt_ref'] ?? ''),
            'is_proforma' => 0,
        ];
        $this->session->set_userdata('billing_invoice_add_context', $ctx);

        $prefill = $this->build_invoice_prefill_payload($populate);
        $this->session->set_flashdata('billing_dn_prefill', json_encode($prefill));

        redirect(admin_url('invoices/invoice?customer_id=' . (int) $populate['client_id']));
    }

    public function create_proforma($dn_id = '')
    {
        $this->require_finance_manager_only();

        if ($this->input->post()) {
            $this->handle_proforma_post();

            return;
        }

        $dn_id = (int) ($dn_id !== '' ? $dn_id : $this->input->get('dn_id'));
        $data  = [
            'title'            => 'Create Proforma Invoice',
            'dn_id'            => $dn_id,
            'populate'         => false,
            'taxes'            => $this->taxes_model->get(),
            'default_line_tax' => $this->default_vat_tax_descriptor(),
        ];

        if ($dn_id > 0) {
            $populate = billing_populate_from_dn_and_quotation($dn_id);
            if ($populate !== false) {
                $data['populate'] = $populate;
            } else {
                set_alert('warning', 'Could not load quotation data for this DN.');
            }
        }

        $this->load->view('billing/proforma_form', $data);
    }

    public function convert_proforma_to_invoice($invoice_id = '')
    {
        $this->require_finance_or_gm();

        $invoice_id = (int) $invoice_id;
        if ($invoice_id < 1 || !$this->input->post()) {
            show_404();
        }

        $invoice = $this->invoices_model->get($invoice_id);
        if (!$invoice || !user_can_view_invoice($invoice_id)) {
            access_denied('invoices');
        }

        $meta = billing_get_invoice_meta($invoice_id);
        if (!$meta || (int) $meta->is_proforma !== 1) {
            set_alert('danger', 'This invoice is not a proforma.');
            redirect(admin_url('invoices/list_invoices/' . $invoice_id));
        }

        if ((int) $invoice->status !== Invoices_model::STATUS_DRAFT) {
            set_alert('warning', 'Only draft proforma invoices can be converted.');
            redirect(admin_url('invoices/list_invoices/' . $invoice_id));
        }

        $this->db->where('id', $invoice_id);
        $this->db->update(db_prefix() . 'invoices', [
            'status' => Invoices_model::STATUS_UNPAID,
        ]);

        $this->invoices_model->change_invoice_number_when_status_draft($invoice_id);
        $this->invoices_model->save_formatted_number($invoice_id);
        update_invoice_status($invoice_id, true);

        $this->db->where('invoice_id', $invoice_id);
        $this->db->update(db_prefix() . 'ipms_invoice_meta', ['is_proforma' => 0]);

        billing_post_invoice_gl($invoice_id);

        set_alert('success', 'Proforma converted to a live invoice.');
        redirect(admin_url('invoices/list_invoices/' . $invoice_id));
    }

    public function record_payment($invoice_id = '')
    {
        $this->require_finance_or_gm();

        $invoice_id = (int) $invoice_id;
        $invoice    = $this->invoices_model->get($invoice_id);
        if (!$invoice || !user_can_view_invoice($invoice_id)) {
            access_denied('invoices');
        }

        if ($this->input->post()) {
            $amount = (float) $this->input->post('amount');
            $left   = (float) get_invoice_total_left_to_pay($invoice_id, (float) $invoice->total);

            if ($amount <= 0 || $amount > $left + 0.0001) {
                set_alert('danger', 'Invalid payment amount.');
                redirect(admin_url('billing/record_payment/' . $invoice_id));
            }

            $methodKey = (string) $this->input->post('billing_payment_method_detail');
            if ($methodKey === '') {
                $methodKey = 'cash';
            }
            $refTrim = trim((string) $this->input->post('reference_number'));
            if ($methodKey !== 'cash' && $refTrim === '') {
                set_alert('danger', 'Reference number is required for the selected payment method.');
                redirect(admin_url('billing/record_payment/' . $invoice_id));
            }

            $modeId    = $this->resolve_payment_mode_id_for_ipms_method($methodKey);

            $this->session->set_userdata('billing_payment_form', [
                'billing_payment_method_detail' => $methodKey !== '' ? $methodKey : 'cash',
                'billing_reference_number'      => (string) $this->input->post('reference_number'),
            ]);

            $noteParts = array_filter([
                (string) $this->input->post('notes'),
                $methodKey ? 'Method: ' . $methodKey : '',
            ]);
            $note = implode("\n", $noteParts);

            $paymentData = [
                'invoiceid'      => $invoice_id,
                'amount'         => $amount,
                'paymentmode'    => $modeId,
                'date'           => $this->input->post('payment_date'),
                'transactionid'  => (string) $this->input->post('reference_number'),
                'note'           => $note,
                'do_not_send_email_template' => $this->input->post('do_not_send_email') ? true : false,
            ];

            $paymentId = $this->payments_model->add($paymentData);
            $this->session->unset_userdata('billing_payment_form');

            if ($paymentId) {
                $threshold = (float) billing_setting('payment_threshold_gm', '5000000');
                if ($amount > $threshold) {
                    set_alert('success', 'Payment recorded and sent to General Manager for approval.');
                } else {
                    set_alert('success', _l('added_successfully', _l('payment')));
                }
                redirect(admin_url('invoices/list_invoices/' . $invoice_id));
            }

            set_alert('danger', 'Unable to record payment.');
            redirect(admin_url('billing/record_payment/' . $invoice_id));
        }

        $data['title']            = 'Record Payment';
        $data['invoice']         = $invoice;
        $data['balance_due']     = (float) get_invoice_total_left_to_pay($invoice_id, (float) $invoice->total);
        $data['payment_modes']   = $this->payment_modes_model->get('', ['expenses_only !=' => 1]);
        $data['gm_threshold']    = (float) billing_setting('payment_threshold_gm', '5000000');
        $data['ipms_methods']    = $this->ipms_payment_method_options();
        $data['invoice_number']  = format_invoice_number($invoice_id);

        $paymentsRaw = $this->payments_model->get_invoice_payments($invoice_id);
        $totalPaid   = 0.0;
        $previous    = [];
        foreach ($paymentsRaw as $pmt) {
            $totalPaid += (float) ($pmt['amount'] ?? 0);
            $pid = (int) ($pmt['paymentid'] ?? 0);
            $meta = $pid ? billing_get_payment_meta($pid) : false;
            $detailKey = $meta && !empty($meta->payment_method_detail) ? (string) $meta->payment_method_detail : '';
            $methodDisplay = '';
            if ($detailKey !== '' && isset($data['ipms_methods'][$detailKey])) {
                $methodDisplay = $data['ipms_methods'][$detailKey];
            } elseif ($detailKey !== '') {
                $methodDisplay = ucwords(str_replace('_', ' ', $detailKey));
            }
            $previous[] = [
                'date'            => $pmt['date'] ?? '',
                'amount'          => $pmt['amount'] ?? 0,
                'name'            => $pmt['name'] ?? '',
                'method_display'  => $methodDisplay,
                'transactionid'   => $pmt['transactionid'] ?? '',
                'received_by'     => $meta ? (int) $meta->received_by : 0,
            ];
        }
        $data['total_paid']         = $totalPaid;
        $data['previous_payments'] = $previous;

        $this->load->view('billing/record_payment', $data);
    }

    public function approve_payment($payment_id = '')
    {
        $this->require_gm_only();

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $payment_id = (int) $payment_id;
        $meta       = billing_get_payment_meta($payment_id);
        if (!$meta || (int) $meta->gm_approval_required !== 1 || !empty($meta->gm_approved_by)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment for approval.']);

            return;
        }

        $this->db->where('payment_id', $payment_id);
        $this->db->update(db_prefix() . 'ipms_payment_meta', [
            'gm_approved_by' => (int) get_staff_user_id(),
            'gm_approved_at' => date('Y-m-d H:i:s'),
        ]);

        hooks()->do_action('ipms_document_approved', [
            'type' => 'payment',
            'id'   => $payment_id,
        ]);

        foreach (billing_get_staff_by_role_name('Finance Manager') as $u) {
            add_notification([
                'description' => 'Payment approved by GM',
                'touserid'    => (int) $u->staffid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'payments/payment/' . $payment_id,
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Payment approved and posted']);
    }

    public function create_credit_note($invoice_id = '')
    {
        $this->require_finance_manager_only();

        $invoice_id = (int) $invoice_id;
        $invoice    = $this->invoices_model->get($invoice_id);
        if (!$invoice || !user_can_view_invoice($invoice_id)) {
            access_denied('invoices');
        }

        if ($this->input->post()) {
            $reasonCat = (string) $this->input->post('reason_category');
            $reasonDet = trim((string) $this->input->post('reason_detail'));
            $allowed   = ['return_of_goods', 'billing_error', 'pricing_adjustment', 'goodwill'];
            if (!in_array($reasonCat, $allowed, true) || $reasonDet === '') {
                set_alert('danger', 'Reason category and detail are required.');
                redirect(admin_url('billing/create_credit_note/' . $invoice_id));
            }

            if (strlen($reasonDet) < 20) {
                set_alert('danger', 'Please provide a detailed reason (at least 20 characters) for the General Manager.');
                redirect(admin_url('billing/create_credit_note/' . $invoice_id));
            }

            $newitemsRaw = $this->input->post('newitems');
            if (!is_array($newitemsRaw)) {
                $newitemsRaw = [];
            }
            $newitems = [];
            $order    = 1;
            foreach ($newitemsRaw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $qty = isset($row['qty']) ? (float) $row['qty'] : 0;
                if ($qty <= 0) {
                    continue;
                }
                unset($row['invoice_item_id']);
                $row['order'] = $order++;
                if (!isset($row['long_description'])) {
                    $row['long_description'] = '';
                }
                $newitems[] = $row;
            }

            if (count($newitems) < 1) {
                set_alert('danger', 'Select at least one line item with a quantity greater than zero.');
                redirect(admin_url('billing/create_credit_note/' . $invoice_id));
            }

            $vatAdjusted = $this->input->post('vat_adjusted') ? 1 : 0;

            $this->session->set_userdata('cn_context', [
                'original_invoice_id' => $invoice_id,
                'reason_category'     => $reasonCat,
                'reason_detail'       => $reasonDet,
                'vat_adjusted'        => $vatAdjusted,
            ]);

            $dateInput = (string) $this->input->post('date');
            $cnDateSql = $dateInput !== '' ? to_sql_date($dateInput) : date('Y-m-d');

            $cnData = $this->build_credit_note_data_from_invoice($invoice, $newitems, $cnDateSql);

            $cnId = $this->credit_notes_model->add($cnData);
            if ($cnId) {
                if ($vatAdjusted === 1) {
                    $vatAdjAmt = (float) $this->input->post('vat_adjustment_amount');
                    if ($vatAdjAmt <= 0) {
                        $vatAdjAmt = (float) $invoice->total_tax;
                    }
                    $this->db->where('credit_note_id', (int) $cnId);
                    $this->db->update(db_prefix() . 'ipms_credit_note_meta', [
                        'vat_adjusted'            => 1,
                        'vat_adjustment_amount'   => $vatAdjAmt,
                    ]);
                }

                set_alert('success', 'Credit note created and sent to GM for approval.');
                redirect(admin_url('credit_notes/list_credit_notes/' . $cnId));
            }

            set_alert('danger', 'Unable to create credit note.');
            redirect(admin_url('billing/create_credit_note/' . $invoice_id));
        }

        $data['title']          = 'Create Credit Note';
        $data['invoice']        = $invoice;
        $data['client']         = $invoice->client;
        $data['balance_due']    = (float) $invoice->total_left_to_pay;
        $data['total_paid']     = 0.0;
        if (!empty($invoice->payments) && is_array($invoice->payments)) {
            foreach ($invoice->payments as $p) {
                $data['total_paid'] += (float) ($p['amount'] ?? 0);
            }
        }
        $data['invoice_items']  = $invoice->items;
        $data['vat_rate_default'] = (float) billing_setting('vat_rate', '16.5');

        $this->load->view('billing/cn_form', $data);
    }

    public function approve_cn($credit_note_id = '')
    {
        $this->require_gm_only();

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $credit_note_id = (int) $credit_note_id;
        $meta           = billing_get_cn_meta($credit_note_id);
        if (!$meta || (string) $meta->gm_approval_status !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Invalid credit note for approval.']);

            return;
        }

        $this->db->where('id', $credit_note_id);
        $this->db->update(db_prefix() . 'creditnotes', ['status' => 1]);

        $this->db->where('credit_note_id', $credit_note_id);
        $this->db->update(db_prefix() . 'ipms_credit_note_meta', [
            'gm_approval_status' => 'approved',
            'gm_approved_by'     => (int) get_staff_user_id(),
            'gm_approved_at'     => date('Y-m-d H:i:s'),
        ]);

        billing_post_cn_gl($credit_note_id);

        $ref = format_credit_note_number($credit_note_id);
        foreach (billing_get_staff_by_role_name('Finance Manager') as $u) {
            add_notification([
                'description' => 'Credit Note ' . $ref . ' approved by GM. Ready to apply or refund.',
                'touserid'    => (int) $u->staffid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'credit_notes/list_credit_notes/' . $credit_note_id,
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Credit Note approved']);
    }

    public function reject_cn($credit_note_id = '')
    {
        $this->require_gm_only();

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $reason = trim((string) $this->input->post('rejection_reason'));
        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);

            return;
        }

        $credit_note_id = (int) $credit_note_id;
        $meta           = billing_get_cn_meta($credit_note_id);
        if (!$meta) {
            echo json_encode(['success' => false, 'message' => 'Credit note meta not found.']);

            return;
        }

        $this->db->where('credit_note_id', $credit_note_id);
        $this->db->update(db_prefix() . 'ipms_credit_note_meta', [
            'gm_approval_status'  => 'rejected',
            'gm_rejection_reason' => $reason,
        ]);

        $this->db->where('id', $credit_note_id);
        $this->db->update(db_prefix() . 'creditnotes', ['status' => 3]);

        $ref = format_credit_note_number($credit_note_id);
        foreach (billing_get_staff_by_role_name('Finance Manager') as $u) {
            add_notification([
                'description' => 'Credit Note ' . $ref . ' rejected by GM: ' . $reason,
                'touserid'    => (int) $u->staffid,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'credit_notes/list_credit_notes/' . $credit_note_id,
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Credit Note rejected']);
    }

    public function get_invoice_billing_tab($invoice_id = '')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $invoice_id = (int) $invoice_id;
        if ($invoice_id < 1 || !user_can_view_invoice($invoice_id)) {
            echo '<p class="text-danger">Access denied.</p>';

            return;
        }

        $meta = billing_get_invoice_meta($invoice_id);
        $data = [
            'invoice_id' => $invoice_id,
            'meta'       => $meta,
        ];

        if ($meta && (int) $meta->dn_id > 0 && $this->db->table_exists(db_prefix() . 'ipms_delivery_notes')) {
            $data['dn'] = $this->db->get_where(db_prefix() . 'ipms_delivery_notes', ['id' => (int) $meta->dn_id])->row();
        }
        if ($meta && (int) $meta->job_card_id > 0 && $this->db->table_exists(db_prefix() . 'ipms_job_cards')) {
            $data['jc'] = $this->db->get_where(db_prefix() . 'ipms_job_cards', ['id' => (int) $meta->job_card_id])->row();
        }

        $this->load->view('billing/partials/invoice_billing_tab', $data);
    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('invoices');
        }

        if ($this->input->post()) {
            $existing = $this->billing_model->get_all_settings();

            $vatRate = trim((string) $this->input->post('vat_rate'));
            if ($vatRate === '') {
                $vatRate = $existing['vat_rate'] ?? '16.5';
            }

            $thrRaw = preg_replace('/[^0-9.\-]/', '', (string) $this->input->post('payment_threshold_gm'));
            $thr    = $thrRaw !== '' ? max(0, (float) $thrRaw) : (float) ($existing['payment_threshold_gm'] ?? 5000000);

            $save = [
                'vat_rate'                 => $vatRate,
                'vat_registration_number'  => trim((string) $this->input->post('vat_registration_number')),
                'payment_threshold_gm'   => (string) $thr,
                'finance_only_edit'      => $this->input->post('finance_only_edit') === '1' ? '1' : '0',
                'invoice_terms'          => (string) $this->input->post('invoice_terms'),
                'invoice_footer'         => trim((string) $this->input->post('invoice_footer')),
            ];

            foreach (['proforma_prefix', 'proforma_next_number', 'auto_populate_from_dn', 'cn_always_requires_gm'] as $k) {
                $v = $this->input->post($k);
                $save[$k] = $v !== null && $v !== '' ? (string) $v : ($existing[$k] ?? '');
            }

            $this->billing_model->save_settings($save);
            set_alert('success', 'Billing settings updated.');
            redirect(admin_url('billing/settings'));
        }

        $data['title']             = 'Billing & Finance Settings';
        $data['settings']         = $this->billing_model->get_all_settings();
        $data['setup_checklist']   = $this->build_billing_setup_checklist();

        $this->load->view('billing/settings', $data);
    }

    public function get_balance_due()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $invoice_id = (int) $this->input->get('invoice_id');
        $invoice    = $this->invoices_model->get($invoice_id);
        if (!$invoice || !user_can_view_invoice($invoice_id)) {
            echo json_encode(['success' => false, 'message' => 'Not found']);

            return;
        }

        $total = (float) $invoice->total;
        $paid  = 0.0;
        foreach ($this->payments_model->get_invoice_payments($invoice_id) as $p) {
            $paid += isset($p['amount']) ? (float) $p['amount'] : 0.0;
        }

        $balance = (float) get_invoice_total_left_to_pay($invoice_id, $total);
        $label   = billing_get_invoice_status_label((int) $invoice->status);

        echo json_encode([
            'success'       => true,
            'total'         => $total,
            'paid'          => $paid,
            'balance_due'   => $balance,
            'status'        => (int) $invoice->status,
            'status_label'  => $label['label'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function handle_proforma_post()
    {
        $dn_id = (int) $this->input->post('dn_id');
        if ($dn_id < 1) {
            set_alert('danger', 'Delivery note is required.');
            redirect(admin_url('billing/create_proforma'));
        }

        $populate = billing_populate_from_dn_and_quotation($dn_id);
        if ($populate === false) {
            set_alert('danger', 'Invalid delivery note or worksheet data.');
            redirect(admin_url('billing/create_proforma'));
        }

        $ctx = [
            'dn_id'       => $dn_id,
            'jc_id'       => (int) ($populate['jc_id'] ?? 0),
            'proposal_id' => (int) ($populate['proposal_id'] ?? 0),
            'dn_ref'      => (string) ($populate['dn_ref'] ?? ''),
            'jc_ref'      => (string) ($populate['jc_ref'] ?? ''),
            'qt_ref'      => (string) ($populate['qt_ref'] ?? ''),
            'is_proforma' => 1,
        ];
        $this->session->set_userdata('billing_invoice_add_context', $ctx);

        $newitems = $this->input->post('newitems');
        if (!is_array($newitems) || count($newitems) < 1) {
            set_alert('danger', 'At least one invoice line is required.');
            redirect(admin_url('billing/create_proforma/' . $dn_id));
        }

        $clientId = (int) $populate['client_id'];
        $details  = $this->clients_model->get_customer_billing_and_shipping_details($clientId);
        $bd       = isset($details[0]) ? $details[0] : [];

        $invoiceData = [
            'clientid'                 => $clientId,
            'project_id'               => 0,
            'number'                   => '',
            'date'                     => _d(date('Y-m-d')),
            'currency'                 => $this->clients_model->get_customer_default_currency($clientId),
            'sale_agent'               => get_staff_user_id(),
            'status'                   => Invoices_model::STATUS_DRAFT,
            'save_as_draft'            => true,
            'newitems'                 => $newitems,
            'billing_street'           => $bd['billing_street'] ?? '',
            'billing_city'             => $bd['billing_city'] ?? '',
            'billing_state'            => $bd['billing_state'] ?? '',
            'billing_zip'              => $bd['billing_zip'] ?? '',
            'billing_country'          => (int) ($bd['billing_country'] ?? 0),
            'include_shipping'         => 1,
            'show_shipping_on_invoice' => 1,
            'terms'                    => (string) ($populate['terms'] ?? billing_setting('invoice_terms', '')),
            'clientnote'               => '',
            'adminnote'                => 'IPMS Proforma',
            'allowed_payment_modes'    => [],
            'shipping_street'          => $bd['shipping_street'] ?? '',
            'shipping_city'            => $bd['shipping_city'] ?? '',
            'shipping_state'           => $bd['shipping_state'] ?? '',
            'shipping_zip'             => $bd['shipping_zip'] ?? '',
            'shipping_country'         => (int) ($bd['shipping_country'] ?? 0),
        ];

        $invoiceId = $this->invoices_model->add($invoiceData);
        if (!$invoiceId) {
            set_alert('danger', 'Could not create proforma invoice.');
            redirect(admin_url('billing/create_proforma/' . $dn_id));
        }

        set_alert('success', 'Proforma invoice saved as draft.');
        $data['title']            = 'Proforma Created';
        $data['invoice']          = $this->invoices_model->get($invoiceId);
        $data['meta']             = billing_get_invoice_meta($invoiceId);
        $data['default_line_tax'] = $this->default_vat_tax_descriptor();
        $data['populate']         = false;
        $data['dn_id']            = 0;
        $this->load->view('billing/proforma_form', $data);
    }

    /**
     * @param array $populate
     * @return array<string, mixed>
     */
    protected function build_invoice_prefill_payload(array $populate)
    {
        $lines = [];
        if (!empty($populate['lines']) && is_array($populate['lines'])) {
            foreach ($populate['lines'] as $tabLines) {
                if (!is_array($tabLines)) {
                    continue;
                }
                foreach ($tabLines as $line) {
                    $desc = isset($line->description) ? strip_tags((string) $line->description) : 'Line';
                    $qty  = isset($line->quantity) ? (float) $line->quantity : 1.0;
                    $rate = isset($line->sell_price) ? (float) $line->sell_price : 0.0;
                    $tax  = [];
                    if (isset($line->is_taxable) && (int) $line->is_taxable === 1) {
                        $t = $this->default_vat_tax_descriptor();
                        if ($t !== '') {
                            $tax[] = $t;
                        }
                    }
                    $lines[] = [
                        'description'      => $desc,
                        'long_description' => '',
                        'qty'              => $qty,
                        'rate'             => $rate,
                        'taxname'          => $tax,
                    ];
                }
            }
        }

        return [
            'client_id' => (int) ($populate['client_id'] ?? 0),
            'terms'     => (string) ($populate['terms'] ?? ''),
            'lines'     => $lines,
        ];
    }

    /**
     * @return string e.g. "VAT|16.50"
     */
    protected function default_vat_tax_descriptor()
    {
        $taxes = $this->taxes_model->get();
        foreach ($taxes as $tax) {
            if (abs((float) $tax['taxrate'] - 16.5) < 0.01) {
                return $tax['name'] . '|' . $tax['taxrate'];
            }
        }
        if (count($taxes) > 0) {
            return $taxes[0]['name'] . '|' . $taxes[0]['taxrate'];
        }

        return '';
    }

    /**
     * @param object $invoice
     * @param array  $newitems
     * @return array
     */
    protected function build_credit_note_data_from_invoice($invoice, array $newitems, $cnDateSql = null)
    {
        $data                       = [];
        $data['clientid']           = (int) $invoice->clientid;
        $data['date']               = $cnDateSql && $cnDateSql !== '' ? $cnDateSql : date('Y-m-d');
        $data['currency']          = $invoice->currency;
        $data['show_quantity_as']  = $invoice->show_quantity_as;
        $data['billing_street']     = clear_textarea_breaks($invoice->billing_street);
        $data['billing_city']       = $invoice->billing_city;
        $data['billing_state']      = $invoice->billing_state;
        $data['billing_zip']        = $invoice->billing_zip;
        $data['billing_country']    = $invoice->billing_country;
        $data['shipping_street']    = clear_textarea_breaks($invoice->shipping_street);
        $data['shipping_city']      = $invoice->shipping_city;
        $data['shipping_state']     = $invoice->shipping_state;
        $data['shipping_zip']       = $invoice->shipping_zip;
        $data['shipping_country']   = $invoice->shipping_country;
        $data['reference_no']       = format_invoice_number($invoice->id);
        $data['clientnote']         = '';
        $data['terms']              = get_option('predefined_terms_credit_note');
        $data['adminnote']          = '';
        $data['newitems']           = $newitems;
        $data['discount_percent']   = $invoice->discount_percent;
        $data['discount_total']     = $invoice->discount_total;
        $data['discount_type']      = $invoice->discount_type;
        $data['adjustment']          = $invoice->adjustment;
        $data['show_shipping_on_credit_note'] = $invoice->show_shipping_on_invoice ?? 0;

        return $data;
    }

    /**
     * @param string $methodKey
     * @return int|string
     */
    protected function resolve_payment_mode_id_for_ipms_method($methodKey)
    {
        $map = [
            'cash'           => ['Cash'],
            'bank_transfer'  => ['Bank Transfer (EFT)', 'Bank Transfer', 'EFT'],
            'cheque'         => ['Cheque', 'Check'],
            'airtel_money'   => ['Airtel Money'],
            'tnm_mpamba'     => ['TNM Mpamba', 'TNM Mpamba'],
            'other'          => ['Other'],
        ];

        $candidates = $map[$methodKey] ?? ['Cash'];
        foreach ($candidates as $name) {
            $this->db->where('name', $name);
            $this->db->where('active', 1);
            $row = $this->db->get(db_prefix() . 'payment_modes')->row();
            if ($row) {
                return (int) $row->id;
            }
        }

        $this->load->model('payment_modes_model');
        $this->payment_modes_model->add([
            'name'                 => $candidates[0],
            'description'          => 'IPMS billing module',
            'active'               => 1,
            'invoices_only'        => 1,
            'expenses_only'        => 0,
            'show_on_pdf'          => 1,
            'selected_by_default'  => 0,
        ]);

        $this->db->where('name', $candidates[0]);
        $row = $this->db->get(db_prefix() . 'payment_modes')->row();

        return $row ? (int) $row->id : 0;
    }

    /**
     * @return array<string, string>
     */
    protected function ipms_payment_method_options()
    {
        return [
            'cash'           => 'Cash',
            'bank_transfer'  => 'Bank Transfer (EFT)',
            'cheque'         => 'Cheque',
            'airtel_money'   => 'Airtel Money',
            'tnm_mpamba'     => 'TNM Mpamba',
            'other'          => 'Other',
        ];
    }

    /**
     * Perfex one-time setup hints (prefixes, payment modes, VAT) for the settings checklist.
     *
     * @return array<int, array{ok: bool, title: string, detail: string, url: string}>
     */
    protected function build_billing_setup_checklist()
    {
        $p = db_prefix();
        $items = [];

        $invPrefix = strtoupper(trim((string) get_option('invoice_prefix')));
        $invFmt    = (string) get_option('invoice_number_format');
        $invOk     = $invPrefix === 'INV' && $invFmt === '2';
        $items[]   = [
            'ok'     => $invOk,
            'title'  => 'Set invoice prefix',
            'detail' => $invOk
                ? 'Invoice prefix is INV and number format is Year Based (e.g. INV-2026-00001).'
                : 'Set "Invoice Number Prefix" to INV and "Invoice Number Format" to Year Based. Current prefix: '
                    . ($invPrefix !== '' ? $invPrefix : '(empty)')
                    . '; format code: ' . ($invFmt !== '' ? $invFmt : '—') . ' (2 = year based).',
            'url'    => admin_url('settings?group=sales'),
        ];

        $cnPrefix = strtoupper(trim((string) get_option('credit_note_prefix')));
        $cnOk     = $cnPrefix === 'CN';
        $items[]  = [
            'ok'     => $cnOk,
            'title'  => 'Set credit note prefix',
            'detail' => $cnOk
                ? 'Credit note prefix is CN.'
                : 'On the same Sales settings page, set "Credit Note Number Prefix" to CN. Current: ' . ($cnPrefix !== '' ? $cnPrefix : '(empty)') . '.',
            'url'    => admin_url('settings?group=sales'),
        ];

        $airtelOk = (int) $this->db->where('name', 'Airtel Money')->where('active', 1)->count_all_results($p . 'payment_modes') > 0;
        $items[]  = [
            'ok'     => $airtelOk,
            'title'  => 'Add Airtel Money payment mode',
            'detail' => $airtelOk
                ? 'An active payment mode named "Airtel Money" exists.'
                : 'Create a new payment mode named exactly "Airtel Money" and mark it active.',
            'url'    => admin_url('paymentmodes'),
        ];

        $this->db->from($p . 'payment_modes');
        $this->db->where('active', 1);
        $this->db->group_start();
        $this->db->where('name', 'TNM Mpamba');
        $this->db->or_like('name', 'TNM');
        $this->db->group_end();
        $tnmOk  = (int) $this->db->count_all_results() > 0;
        $items[] = [
            'ok'     => $tnmOk,
            'title'  => 'Add TNM Mpamba payment mode',
            'detail' => $tnmOk
                ? 'A TNM / Mpamba payment mode is configured.'
                : 'Create a payment mode named "TNM Mpamba" (or include TNM in the name) and mark it active.',
            'url'    => admin_url('paymentmodes'),
        ];

        $cashOk = (int) $this->db->where('name', 'Cash (MWK)')->where('active', 1)->count_all_results($p . 'payment_modes') > 0;
        $items[] = [
            'ok'     => $cashOk,
            'title'  => 'Add Cash payment mode',
            'detail' => $cashOk
                ? 'An active payment mode named "Cash (MWK)" exists.'
                : 'Create a payment mode named exactly "Cash (MWK)" and mark it active (or align names with IPMS billing resolver).',
            'url'    => admin_url('paymentmodes'),
        ];

        $this->load->model('taxes_model');
        $has165      = false;
        $vatInDefault = false;
        foreach ($this->taxes_model->get() as $t) {
            if (abs((float) ($t['taxrate'] ?? 0) - 16.5) < 0.02) {
                $has165 = true;
                break;
            }
        }
        $rawDef = get_option('default_tax');
        if ($rawDef) {
            $defTaxes = @unserialize($rawDef);
            if (is_array($defTaxes)) {
                foreach ($defTaxes as $token) {
                    $parts = array_map('trim', explode('|', (string) $token));
                    if (isset($parts[1]) && abs((float) $parts[1] - 16.5) < 0.02) {
                        $vatInDefault = true;
                        break;
                    }
                }
            }
        }
        $taxOk = $has165 && $vatInDefault;
        $items[] = [
            'ok'     => $taxOk,
            'title'  => 'Set VAT tax rate (16.5%) as default',
            'detail' => $taxOk
                ? 'A 16.5% tax exists and is included in default taxes for new invoices.'
                : ($has165
                    ? 'A ~16.5% tax exists, but it is not in Settings → Default tax for invoices. Add it under Sales defaults.'
                    : 'Under Taxes, create a tax (e.g. name "VAT", rate 16.5%) and set it as a default tax on the Sales settings page.'),
            'url'    => admin_url('taxes'),
        ];

        return $items;
    }

    protected function require_finance_or_gm()
    {
        if (is_admin()) {
            return;
        }
        $role = $this->current_staff_role_name();
        if (!in_array($role, ['Finance Manager', 'General Manager'], true)) {
            access_denied('invoices');
        }
    }

    protected function require_finance_manager_only()
    {
        if (is_admin()) {
            return;
        }
        if ($this->current_staff_role_name() !== 'Finance Manager') {
            access_denied('invoices');
        }
    }

    protected function require_gm_only()
    {
        if (is_admin()) {
            return;
        }
        if ($this->current_staff_role_name() !== 'General Manager') {
            access_denied('invoices');
        }
    }

    /**
     * @return string
     */
    protected function current_staff_role_name()
    {
        if (function_exists('get_staff_role')) {
            return (string) get_staff_role(get_staff_user_id());
        }

        $this->db->select(db_prefix() . 'roles.name as role_name');
        $this->db->from(db_prefix() . 'staff');
        $this->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');
        $this->db->where(db_prefix() . 'staff.staffid', (int) get_staff_user_id());
        $row = $this->db->get()->row();

        return $row && isset($row->role_name) ? (string) $row->role_name : '';
    }

    /**
     * @return bool
     */
    protected function staff_is_general_manager()
    {
        return $this->current_staff_role_name() === 'General Manager';
    }

    /**
     * @param array $dns
     * @param array $pending_cn
     * @param array $pending_payments
     * @param array $overdue
     * @return array<string, float|int>
     */
    protected function build_finance_inbox_stats($dns, $pending_cn, $pending_payments, $overdue)
    {
        $dnTotal = 0.0;
        foreach ($dns as $dn) {
            $dnTotal += $this->finance_inbox_dn_approved_value($dn);
        }
        $payTotal = 0.0;
        foreach ($pending_payments as $p) {
            $payTotal += (float) ($p->amount ?? 0);
        }
        $overdueTotal = 0.0;
        foreach ($overdue as $inv) {
            $overdueTotal += (float) ($inv->total ?? 0);
        }

        return [
            'dn_count'       => count($dns),
            'dn_total_mwk'   => $dnTotal,
            'cn_pending'     => count($pending_cn),
            'pay_pending'    => count($pending_payments),
            'pay_total_mwk'  => $payTotal,
            'overdue_count'  => count($overdue),
            'overdue_total'  => $overdueTotal,
        ];
    }

    /**
     * @param object $dn
     * @return float
     */
    protected function finance_inbox_dn_approved_value($dn)
    {
        foreach (['approved_value', 'grand_total', 'total_sell', 'total_value', 'dn_total', 'total_amount'] as $f) {
            if (isset($dn->$f) && is_numeric($dn->$f)) {
                return (float) $dn->$f;
            }
        }

        return 0.0;
    }
}

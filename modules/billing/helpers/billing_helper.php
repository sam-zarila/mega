<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('billing_setting')) {
    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    function billing_setting($key, $default = '')
    {
        $CI = &get_instance();
        $CI->db->where('setting_key', (string) $key);

        $r = $CI->db->get(db_prefix() . 'ipms_billing_settings')->row();

        return $r ? $r->setting_value : $default;
    }
}

if (!function_exists('billing_generate_proforma_ref')) {
    /**
     * @return string
     */
    function billing_generate_proforma_ref()
    {
        $CI     = &get_instance();
        $prefix = (string) billing_setting('proforma_prefix', 'PROF');
        $next   = (int) billing_setting('proforma_next_number', 1);
        $year   = date('Y');
        $ref    = $prefix . '-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        $CI->db->where('setting_key', 'proforma_next_number');
        $CI->db->set('setting_value', (string) ($next + 1));
        $CI->db->update(db_prefix() . 'ipms_billing_settings');

        return $ref;
    }
}

if (!function_exists('billing_format_mwk')) {
    /**
     * @param float|int|string|null $amount
     * @return string
     */
    function billing_format_mwk($amount)
    {
        return 'MWK ' . number_format((float) $amount, 2, '.', ',');
    }
}

if (!function_exists('billing_get_invoice_meta')) {
    /**
     * @param int $invoice_id
     * @return object|false
     */
    function billing_get_invoice_meta($invoice_id)
    {
        $invoice_id = (int) $invoice_id;
        if ($invoice_id < 1) {
            return false;
        }

        $CI = &get_instance();

        return $CI->db->get_where(db_prefix() . 'ipms_invoice_meta', ['invoice_id' => $invoice_id])->row() ?: false;
    }
}

if (!function_exists('billing_get_payment_meta')) {
    /**
     * @param int $payment_id
     * @return object|false
     */
    function billing_get_payment_meta($payment_id)
    {
        $payment_id = (int) $payment_id;
        if ($payment_id < 1) {
            return false;
        }

        $CI = &get_instance();

        return $CI->db->get_where(db_prefix() . 'ipms_payment_meta', ['payment_id' => $payment_id])->row() ?: false;
    }
}

if (!function_exists('billing_get_cn_meta')) {
    /**
     * @param int $credit_note_id
     * @return object|false
     */
    function billing_get_cn_meta($credit_note_id)
    {
        $credit_note_id = (int) $credit_note_id;
        if ($credit_note_id < 1) {
            return false;
        }

        $CI = &get_instance();

        return $CI->db->get_where(db_prefix() . 'ipms_credit_note_meta', ['credit_note_id' => $credit_note_id])->row() ?: false;
    }
}

if (!function_exists('billing_accounting_is_active')) {
    /**
     * @return bool
     */
    function billing_accounting_is_active()
    {
        if (!file_exists(module_dir_path('accounting', 'models/Accounting_model.php'))) {
            return false;
        }

        $CI = &get_instance();

        return $CI->db->table_exists(db_prefix() . 'acc_account_history');
    }
}

if (!function_exists('billing_post_invoice_gl')) {
    /**
     * Aligns with the Accounting module: uses native automatic invoice conversion when possible,
     * then marks IPMS meta as posted when acc history exists for the invoice.
     *
     * @param int $invoice_id
     * @return bool
     */
    function billing_post_invoice_gl($invoice_id)
    {
        $invoice_id = (int) $invoice_id;
        if ($invoice_id < 1 || !billing_accounting_is_active()) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->model('accounting/accounting_model');

        $meta = billing_get_invoice_meta($invoice_id);
        if (!$meta || (int) $meta->gl_posted === 1) {
            return (bool) ($meta && (int) $meta->gl_posted === 1);
        }

        $debtors = (string) get_option('acc_invoice_payment_account');
        $revenue = (string) get_option('acc_invoice_deposit_to');
        if ($debtors === '' || $revenue === '') {
            log_message('warning', 'billing_post_invoice_gl: missing acc_invoice_payment_account or acc_invoice_deposit_to');

            return false;
        }

        $CI->accounting_model->automatic_invoice_conversion($invoice_id);

        $cnt = (int) total_rows(db_prefix() . 'acc_account_history', [
            'rel_id'   => $invoice_id,
            'rel_type' => 'invoice',
        ]);

        if ($cnt < 1) {
            log_message('warning', 'billing_post_invoice_gl: no accounting history created for invoice ' . $invoice_id);

            return false;
        }

        $CI->db->where('invoice_id', $invoice_id);
        $CI->db->update(db_prefix() . 'ipms_invoice_meta', [
            'gl_posted'    => 1,
            'gl_posted_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}

if (!function_exists('billing_post_cn_gl')) {
    /**
     * @param int $credit_note_id
     * @return bool
     */
    function billing_post_cn_gl($credit_note_id)
    {
        $credit_note_id = (int) $credit_note_id;
        if ($credit_note_id < 1 || !billing_accounting_is_active()) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->model('credit_notes_model');
        $CI->load->model('accounting/accounting_model');
        $CI->load->helper('credit_notes');

        $cn = $CI->credit_notes_model->get($credit_note_id);
        if (!$cn) {
            return false;
        }

        $meta = billing_get_cn_meta($credit_note_id);
        if (!$meta || (int) $meta->gl_posted === 1) {
            return (bool) ($meta && (int) $meta->gl_posted === 1);
        }

        $debtors = (string) get_option('acc_invoice_payment_account');
        $revenue = (string) get_option('acc_invoice_deposit_to');
        $vatOut  = (string) get_option('acc_vat_output_account');
        if ($vatOut === '') {
            $vatOut = (string) get_option('acc_tax_deposit_to');
        }

        if ($debtors === '' || $revenue === '') {
            log_message('warning', 'billing_post_cn_gl: missing AR or revenue account mapping');

            return false;
        }

        $total = (float) $cn->total;
        $tax   = (float) $cn->total_tax;

        if ((int) $meta->vat_adjusted === 1) {
            $tax = (float) $meta->vat_adjustment_amount;
        }

        $revenueDebit = max(0, round($total - $tax, 2));

        $lines   = [];
        $lines[] = [(string) $revenue, $revenueDebit, 0, 'Credit note ' . format_credit_note_number($credit_note_id)];

        if ($tax > 0.0001 && $vatOut !== '') {
            $lines[] = [(string) $vatOut, $tax, 0, 'VAT adjustment CN ' . format_credit_note_number($credit_note_id)];
        } elseif ($tax > 0.0001 && $vatOut === '') {
            log_message('warning', 'billing_post_cn_gl: VAT amount > 0 but no VAT output account configured');
        }

        $lines[] = [(string) $debtors, 0, $total, 'Credit note ' . format_credit_note_number($credit_note_id)];

        $journal = [];
        foreach ($lines as $line) {
            $journal[] = [$line[0], (float) $line[1], (float) $line[2], $line[3]];
        }

        $number = $CI->accounting_model->get_journal_entry_next_number();
        $jdata  = [
            'number'         => (string) $number,
            'description'    => 'IPMS Credit Note ' . format_credit_note_number($credit_note_id),
            'journal_date'   => date('d/m/Y', strtotime((string) $cn->date)),
            'amount'         => $total,
            'journal_entry'  => json_encode($journal),
        ];

        $ok = $CI->accounting_model->add_journal_entry($jdata);
        if ($ok !== true) {
            log_message('error', 'billing_post_cn_gl: add_journal_entry failed for CN ' . $credit_note_id);

            return false;
        }

        $CI->db->where('credit_note_id', $credit_note_id);
        $CI->db->update(db_prefix() . 'ipms_credit_note_meta', [
            'gl_posted'    => 1,
            'gl_posted_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}

if (!function_exists('billing_calculate_vat')) {
    /**
     * @param float|int|string $subtotal
     * @param float|null       $rate
     * @return float
     */
    function billing_calculate_vat($subtotal, $rate = null)
    {
        if ($rate === null) {
            $rate = (float) billing_setting('vat_rate', '16.5');
        } else {
            $rate = (float) $rate;
        }

        return round((float) $subtotal * ($rate / 100), 2);
    }
}

if (!function_exists('billing_get_invoice_status_label')) {
    /**
     * @param int $status
     * @return array{label:string,class:string,color:string}
     */
    function billing_get_invoice_status_label($status)
    {
        $CI = &get_instance();
        $CI->load->model('invoices_model');

        $status = (int) $status;
        $map    = [
            Invoices_model::STATUS_UNPAID    => ['label' => 'Unpaid', 'class' => 'label-warning', 'color' => '#fd7e14'],
            Invoices_model::STATUS_PAID      => ['label' => 'Paid', 'class' => 'label-success', 'color' => '#28a745'],
            Invoices_model::STATUS_PARTIALLY => ['label' => 'Partially Paid', 'class' => 'label-info', 'color' => '#17a2b8'],
            Invoices_model::STATUS_OVERDUE   => ['label' => 'Overdue', 'class' => 'label-danger', 'color' => '#dc3545'],
            Invoices_model::STATUS_CANCELLED => ['label' => 'Cancelled', 'class' => 'label-default', 'color' => '#6c757d'],
            Invoices_model::STATUS_DRAFT     => ['label' => 'Draft', 'class' => 'label-default', 'color' => '#6c757d'],
        ];

        return $map[$status] ?? ['label' => 'Unknown', 'class' => 'label-default', 'color' => '#6c757d'];
    }
}

if (!function_exists('billing_get_invoice_status_badge')) {
    /**
     * @param int $status
     * @return string
     */
    function billing_get_invoice_status_badge($status)
    {
        $m = billing_get_invoice_status_label($status);

        return '<span class="label ' . html_escape($m['class']) . '" style="background-color:' . html_escape($m['color']) . '">'
            . html_escape($m['label']) . '</span>';
    }
}

if (!function_exists('billing_get_staff_by_role_name')) {
    /**
     * @param string $role_name
     * @return array
     */
    function billing_get_staff_by_role_name($role_name)
    {
        $CI        = &get_instance();
        $role_name = (string) $role_name;
        if ($role_name === '') {
            return [];
        }

        $CI->db->select(db_prefix() . 'staff.staffid');
        $CI->db->from(db_prefix() . 'staff');
        $CI->db->join(
            db_prefix() . 'roles',
            db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role',
            'left'
        );
        $CI->db->where(db_prefix() . 'staff.active', 1);
        $CI->db->where(db_prefix() . 'roles.name', $role_name);

        return $CI->db->get()->result();
    }
}

if (!function_exists('billing_populate_from_dn_and_quotation')) {
    /**
     * @param int $dn_id
     * @return array|false
     */
    function billing_populate_from_dn_and_quotation($dn_id)
    {
        $dn_id = (int) $dn_id;
        if ($dn_id < 1) {
            return false;
        }

        $CI = &get_instance();
        $p  = db_prefix();

        if (!$CI->db->table_exists($p . 'ipms_delivery_notes')) {
            return false;
        }

        $dn = $CI->db->get_where($p . 'ipms_delivery_notes', ['id' => $dn_id])->row();
        if (!$dn) {
            return false;
        }

        $dnStatus = isset($dn->status) ? (string) $dn->status : '';
        if ($dnStatus !== 'signed_confirmed') {
            return false;
        }

        $jcId = (int) ($dn->job_card_id ?? 0);
        if ($jcId < 1 || !$CI->db->table_exists($p . 'ipms_job_cards')) {
            return false;
        }

        $jc = $CI->db->get_where($p . 'ipms_job_cards', ['id' => $jcId])->row();
        if (!$jc) {
            return false;
        }

        $proposalId = (int) ($jc->proposal_id ?? 0);
        if ($proposalId < 1) {
            return false;
        }

        $proposal = $CI->db->get_where($p . 'proposals', ['id' => $proposalId])->row();
        if (!$proposal) {
            return false;
        }

        $worksheet = $CI->db->get_where($p . 'ipms_qt_worksheets', ['proposal_id' => $proposalId])->row();
        if (!$worksheet) {
            return false;
        }

        $lines = $CI->db->where('proposal_id', $proposalId)
            ->order_by('tab', 'asc')
            ->order_by('line_order', 'asc')
            ->get($p . 'ipms_qt_lines')
            ->result();

        $grouped = [];
        foreach ($lines as $line) {
            $tab = isset($line->tab) ? (string) $line->tab : '_';
            if (!isset($grouped[$tab])) {
                $grouped[$tab] = [];
            }
            $grouped[$tab][] = $line;
        }

        $clientId = (int) ($dn->client_id ?? 0);
        if ($clientId < 1) {
            return false;
        }

        $CI->load->model('clients_model');
        $client = $CI->clients_model->get($clientId);
        if (!$client) {
            return false;
        }

        return [
            'client_id'      => (int) $client->userid,
            'client_name'    => (string) $client->company,
            'dn_ref'         => isset($dn->dn_ref) ? (string) $dn->dn_ref : '',
            'jc_ref'         => (string) $jc->jc_ref,
            'qt_ref'         => isset($proposal->qt_ref) ? (string) $proposal->qt_ref : '',
            'proposal_id'    => $proposalId,
            'dn_id'          => $dn_id,
            'jc_id'          => $jcId,
            'lines'          => $grouped,
            'subtotal'       => (float) $worksheet->total_sell,
            'vat_amount'     => (float) $worksheet->vat_amount,
            'grand_total'    => (float) $worksheet->grand_total,
            'discount'       => (float) $worksheet->discount_amount,
            'billing_street' => (string) $client->billing_street,
            'billing_city'   => (string) $client->billing_city,
            'terms'          => (string) billing_setting('invoice_terms', 'Payment due within 30 days of invoice date.'),
        ];
    }
}

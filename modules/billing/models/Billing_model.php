<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Billing_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array<int, object>
     */
    public function get_dns_awaiting_invoice()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'ipms_delivery_notes')) {
            return [];
        }

        $this->db->select('dn.*, jc.jc_ref, c.company as client_company');
        $this->db->from($p . 'ipms_delivery_notes AS dn');
        $this->db->join($p . 'ipms_job_cards AS jc', 'jc.id = dn.job_card_id', 'left');
        $this->db->join($p . 'clients AS c', 'c.userid = dn.client_id', 'left');
        $this->db->where('dn.status', 'signed_confirmed');
        $this->db->group_start();
        $this->db->where('dn.invoice_id IS NULL', null, false);
        $this->db->or_where('dn.invoice_id', 0);
        $this->db->group_end();

        if ($this->db->field_exists('invoice_triggered', $p . 'ipms_delivery_notes')) {
            $this->db->where('dn.invoice_triggered', 1);
        }

        return $this->db->get()->result();
    }

    /**
     * @return array<int, object>
     */
    public function get_pending_cn_approvals()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'ipms_credit_note_meta')) {
            return [];
        }

        $select = 'm.*, cn.number, cn.prefix, cn.date AS cn_date, cn.datecreated AS cn_datecreated, cn.total, cn.currency, cn.clientid, cn.addedfrom AS cn_addedfrom, '
            . 'c.company AS client_company';
        if ($this->db->table_exists($p . 'ipms_approval_requests')) {
            $select .= ', ar.submitted_by AS ar_submitted_by, ar.submitted_at AS ar_submitted_at, ar.request_ref AS approval_request_ref';
        }
        $this->db->select($select);
        $this->db->from($p . 'ipms_credit_note_meta AS m');
        $this->db->join($p . 'creditnotes AS cn', 'cn.id = m.credit_note_id', 'inner');
        $this->db->join($p . 'clients AS c', 'c.userid = cn.clientid', 'left');
        if ($this->db->table_exists($p . 'ipms_approval_requests')) {
            $this->db->join($p . 'ipms_approval_requests AS ar', 'ar.id = m.gm_approval_request_id', 'left');
        }
        $this->db->where('m.gm_approval_status', 'pending');

        return $this->db->get()->result();
    }

    /**
     * @return array<int, object>
     */
    public function get_pending_large_payments()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'ipms_payment_meta')) {
            return [];
        }

        $select = 'pm.*, pr.amount, pr.date AS payment_date, pr.invoiceid, pr.paymentmode, pr.daterecorded, '
            . 'i.currency AS invoice_currency, c.company AS client_company, '
            . 'pmod.name AS perfex_payment_mode_name';
        if ($this->db->table_exists($p . 'ipms_approval_requests')) {
            $select .= ', ar.submitted_by AS ar_submitted_by, ar.submitted_at AS ar_submitted_at';
        }
        $this->db->select($select);
        $this->db->from($p . 'ipms_payment_meta AS pm');
        $this->db->join($p . 'invoicepaymentrecords AS pr', 'pr.id = pm.payment_id', 'inner');
        $this->db->join($p . 'invoices AS i', 'i.id = pr.invoiceid', 'left');
        $this->db->join($p . 'clients AS c', 'c.userid = i.clientid', 'left');
        $this->db->join($p . 'payment_modes AS pmod', 'pmod.id = pr.paymentmode', 'left');
        if ($this->db->table_exists($p . 'ipms_approval_requests')) {
            $this->db->join($p . 'ipms_approval_requests AS ar', 'ar.id = pm.approval_request_id', 'left');
        }
        $this->db->where('pm.gm_approval_required', 1);
        $this->db->where('pm.gm_approved_by IS NULL', null, false);

        return $this->db->get()->result();
    }

    /**
     * @return array<int, object>
     */
    public function get_overdue_invoices()
    {
        $p = db_prefix();

        $this->db->select('i.*, c.company as client_company');
        $this->db->from($p . 'invoices AS i');
        $this->db->join($p . 'clients AS c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.status', Invoices_model::STATUS_OVERDUE);
        $this->db->order_by('i.duedate', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * @return array<string, string>
     */
    public function get_all_settings()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'ipms_billing_settings')) {
            return [];
        }

        $rows = $this->db->get($p . 'ipms_billing_settings')->result();
        $out  = [];
        foreach ($rows as $row) {
            $out[(string) $row->setting_key] = (string) $row->setting_value;
        }

        return $out;
    }

    /**
     * @param array<string, string> $settings
     */
    public function save_settings(array $settings)
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'ipms_billing_settings')) {
            return;
        }

        foreach ($settings as $key => $value) {
            $key = (string) $key;
            $this->db->where('setting_key', $key);
            $this->db->update($p . 'ipms_billing_settings', [
                'setting_value' => (string) $value,
            ]);
            if ($this->db->affected_rows() === 0) {
                $this->db->insert($p . 'ipms_billing_settings', [
                    'setting_key'   => $key,
                    'setting_value' => (string) $value,
                ]);
            }
        }
    }
}

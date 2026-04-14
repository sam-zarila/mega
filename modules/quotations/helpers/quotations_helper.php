<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @return string|false
 */
function qt_generate_ref()
{
    $CI = &get_instance();
    $p  = db_prefix();
    $t  = $p . 'ipms_quotation_settings';

    if (!$CI->db->table_exists($t)) {
        return false;
    }

    $CI->db->trans_begin();

    try {
        $rowNum = $CI->db->query(
            'SELECT `setting_value` FROM `' . $t . '` WHERE `setting_key` = \'qt_next_number\' FOR UPDATE'
        )->row();

        $prefixRow = $CI->db->query('SELECT `setting_value` FROM `' . $t . '` WHERE `setting_key` = \'qt_prefix\'')->row();
        $yearRow   = $CI->db->query('SELECT `setting_value` FROM `' . $t . '` WHERE `setting_key` = \'qt_year_in_ref\'')->row();

        $next = $rowNum && isset($rowNum->setting_value) ? (int) $rowNum->setting_value : 1;
        $prefix = ($prefixRow && isset($prefixRow->setting_value) && (string) $prefixRow->setting_value !== '')
            ? (string) $prefixRow->setting_value
            : 'QT';

        $yearInRef = $yearRow && isset($yearRow->setting_value) ? (string) $yearRow->setting_value : '1';

        $padded = str_pad((string) max(1, $next), 5, '0', STR_PAD_LEFT);
        if ($yearInRef === '1' || strtolower($yearInRef) === 'true') {
            $ref = $prefix . '-' . date('Y') . '-' . $padded;
        } else {
            $ref = $prefix . '-' . $padded;
        }

        $CI->db->where('setting_key', 'qt_next_number');
        $CI->db->update($t, ['setting_value' => (string) ($next + 1)]);

        if ($CI->db->trans_status() === false) {
            $CI->db->trans_rollback();

            return false;
        }

        $CI->db->trans_commit();

        return $ref;
    } catch (Throwable $e) {
        $CI->db->trans_rollback();
        log_message('error', 'qt_generate_ref: ' . $e->getMessage());

        return false;
    }
}

/**
 * @param float|int|string|null $amount
 */
function qt_format_mwk($amount)
{
    return 'MWK ' . number_format((float) $amount, 2, '.', ',');
}

/**
 * @param string $status
 *
 * @return string
 */
function qt_get_status_label($status)
{
    $map = [
        'draft'     => ['class' => 'label label-default', 'label' => 'Draft'],
        'submitted' => ['class' => 'label label-warning', 'label' => 'Submitted for Approval'],
        'approved'  => ['class' => 'label label-success', 'label' => 'Approved'],
        'rejected'  => ['class' => 'label label-danger', 'label' => 'Rejected'],
        'converted' => ['class' => 'label label-primary', 'label' => 'Converted to Job'],
    ];

    $status = (string) $status;
    if (isset($map[$status])) {
        $m = $map[$status];

        return '<span class="' . $m['class'] . '">' . $m['label'] . '</span>';
    }

    return '<span class="label label-default">' . html_escape(ucfirst($status)) . '</span>';
}

function qt_get_vat_rate()
{
    $v = qt_setting('qt_vat_rate');
    if ($v === false || $v === null || $v === '') {
        return defined('QUOTATIONS_VAT_RATE') ? (float) QUOTATIONS_VAT_RATE : 16.5;
    }

    return (float) $v;
}

/**
 * @param string $key
 *
 * @return string|false
 */
function qt_setting($key)
{
    $CI = &get_instance();
    $p  = db_prefix();

    if (!$CI->db->table_exists($p . 'ipms_quotation_settings')) {
        return false;
    }

    $row = $CI->db->where('setting_key', $key)->get($p . 'ipms_quotation_settings')->row();

    if (!$row || !isset($row->setting_value)) {
        return false;
    }

    return $row->setting_value;
}

function qt_current_user_role()
{
    $CI = &get_instance();
    $p  = db_prefix();

    $CI->db->select('r.name');
    $CI->db->from($p . 'staff s');
    $CI->db->join($p . 'roles r', 's.role = r.roleid', 'left');
    $CI->db->where('s.staffid', get_staff_user_id());
    $row = $CI->db->get()->row();

    return $row && isset($row->name) ? (string) $row->name : '';
}

/**
 * @param int $quotation_id
 */
function qt_can_view_quotation($quotation_id)
{
    $quotation_id = (int) $quotation_id;
    if ($quotation_id < 1) {
        return false;
    }

    $CI = &get_instance();
    $p  = db_prefix();
    $t  = $p . 'ipms_quotations';

    if (!$CI->db->table_exists($t)) {
        return false;
    }

    $q = $CI->db->where('id', $quotation_id)->get($t)->row();
    if (!$q) {
        return false;
    }

    if ((int) $q->created_by === (int) get_staff_user_id()) {
        return true;
    }

    $role = qt_current_user_role();

    return in_array($role, [
        'Sales Manager',
        'Finance Manager',
        'General Manager',
        'GM',
        'System Administrator',
    ], true) || is_admin();
}

/**
 * SQL fragment for WHERE; use with main query alias `q` for tblipms_quotations.
 */
function qt_peer_visibility_where()
{
    $role = qt_current_user_role();

    if (in_array($role, [
        'Sales Manager',
        'Finance Manager',
        'General Manager',
        'GM',
        'System Administrator',
    ], true) || is_admin()) {
        return '1=1';
    }

    return 'q.created_by = ' . (int) get_staff_user_id();
}

/**
 * Sensitive cost/margin figures (hidden from Sales Executive / Rep).
 */
function qt_can_view_quotation_margin()
{
    $role = qt_current_user_role();

    return in_array($role, [
        'Sales Manager',
        'Finance Manager',
        'General Manager',
        'GM',
        'System Administrator',
    ], true) || is_admin();
}

/**
 * Convert-to-job and similar sales-leadership actions.
 */
function qt_can_convert_quotation_to_job()
{
    return qt_can_view_quotation_margin();
}

/**
 * @return array<string, string>
 */
function qt_tab_labels()
{
    return [
        'signage'       => 'Signage & Printing',
        'installation'  => 'Installation',
        'construction'  => 'Construction Works',
        'retrofitting'  => 'Shop Retrofitting',
        'promotional'   => 'Promotional Items',
        'additional'    => 'Additional Charges',
    ];
}

/**
 * @param array $line
 *
 * @return array
 */
function qt_calculate_line_totals(&$line)
{
    $cost    = isset($line['cost_price']) ? (float) $line['cost_price'] : 0.0;
    $qty     = isset($line['quantity']) ? (float) $line['quantity'] : 0.0;
    $markup  = isset($line['markup_percent']) ? (float) $line['markup_percent'] : 0.0;
    $manual  = !empty($line['sell_price_manual']);

    if (!$manual && $markup > 0) {
        $line['sell_price'] = $cost * (1 + $markup / 100);
    }

    $sell = isset($line['sell_price']) ? (float) $line['sell_price'] : 0.0;

    $line['line_total_cost'] = $cost * $qty;
    $line['line_total_sell'] = $sell * $qty;

    return $line;
}

/**
 * @param int $quotation_id
 *
 * @return array<string, float|int>
 */
function qt_recalculate_totals($quotation_id)
{
    $quotation_id = (int) $quotation_id;
    $empty        = [
        'subtotal_sell'           => 0.0,
        'total_cost'              => 0.0,
        'taxable_base'            => 0.0,
        'sub_after_contingency'   => 0.0,
        'discount_applied'        => 0.0,
        'sub_final'               => 0.0,
        'vat_amount'              => 0.0,
        'grand_total'             => 0.0,
    ];

    if ($quotation_id < 1) {
        return $empty;
    }

    $CI = &get_instance();
    $p  = db_prefix();
    $qt = $p . 'ipms_quotations';
    $ln = $p . 'ipms_quotation_lines';

    if (!$CI->db->table_exists($qt) || !$CI->db->table_exists($ln)) {
        return $empty;
    }

    $q = $CI->db->where('id', $quotation_id)->get($qt)->row();
    if (!$q) {
        return $empty;
    }

    $lines = $CI->db->where('quotation_id', $quotation_id)->get($ln)->result_array();

    $subtotal_sell = 0.0;
    $total_cost    = 0.0;
    $taxable_base  = 0.0;

    foreach ($lines as $row) {
        $ltSell = isset($row['line_total_sell']) ? (float) $row['line_total_sell'] : 0.0;
        $ltCost = isset($row['line_total_cost']) ? (float) $row['line_total_cost'] : 0.0;
        $subtotal_sell += $ltSell;
        $total_cost += $ltCost;
        $taxable = !isset($row['is_taxable']) || (int) $row['is_taxable'] === 1;
        if ($taxable) {
            $taxable_base += $ltSell;
        }
    }

    $cont_pct = isset($q->contingency_percent) ? (float) $q->contingency_percent : 0.0;
    $sub_cont = $subtotal_sell * (1 + $cont_pct / 100);
    $tax_cont = $taxable_base * (1 + $cont_pct / 100);

    $disc = 0.0;
    if (isset($q->discount_amount) && (float) $q->discount_amount > 0) {
        $disc = (float) $q->discount_amount;
    } elseif (isset($q->discount_percent) && (float) $q->discount_percent > 0) {
        $disc = $sub_cont * ((float) $q->discount_percent / 100);
    }

    $sub_final = max(0.0, $sub_cont - $disc);

    if ($sub_cont <= 0) {
        $tax_final = 0.0;
    } else {
        $tax_final = max(0.0, $tax_cont - $disc * ($tax_cont / $sub_cont));
    }

    $rate      = qt_get_vat_rate();
    $vat_amount = $tax_final * ($rate / 100);
    $grand_total = $sub_final + $vat_amount;

    $CI->db->where('id', $quotation_id);
    $CI->db->update($qt, [
        'total_cost'   => round($total_cost, 2),
        'total_sell'   => round($subtotal_sell, 2),
        'vat_amount'   => round($vat_amount, 2),
        'grand_total'  => round($grand_total, 2),
    ]);

    return [
        'subtotal_sell'         => $subtotal_sell,
        'total_cost'            => $total_cost,
        'taxable_base'          => $taxable_base,
        'sub_after_contingency' => $sub_cont,
        'discount_applied'      => $disc,
        'sub_final'             => $sub_final,
        'vat_amount'            => $vat_amount,
        'grand_total'           => $grand_total,
    ];
}

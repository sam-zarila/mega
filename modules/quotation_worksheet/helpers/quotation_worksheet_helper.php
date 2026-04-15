<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('qt_setting')) {
    function qt_setting($key, $default = '')
    {
        $CI = &get_instance();
        if (!$CI->db->table_exists(db_prefix() . 'ipms_qt_settings')) {
            return $default;
        }
        $row = $CI->db->get_where(db_prefix() . 'ipms_qt_settings', ['setting_key' => $key])->row();

        return $row ? $row->setting_value : $default;
    }
}

if (!function_exists('qt_generate_ref')) {
    function qt_generate_ref()
    {
        $CI     = &get_instance();
        $num    = (int) qt_setting('qt_next_number', 1);
        $prefix = qt_setting('qt_prefix', 'QT');
        $ref    = $prefix . '-' . date('Y') . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);

        $CI->db->where('setting_key', 'qt_next_number');
        $CI->db->update(db_prefix() . 'ipms_qt_settings', ['setting_value' => $num + 1]);

        return $ref;
    }
}

if (!function_exists('qt_get_or_create_worksheet')) {
    function qt_get_or_create_worksheet($proposal_id)
    {
        $CI = &get_instance();
        $ws = $CI->db->get_where(db_prefix() . 'ipms_qt_worksheets', ['proposal_id' => $proposal_id])->row();

        if (!$ws) {
            $CI->db->insert(db_prefix() . 'ipms_qt_worksheets', [
                'proposal_id'    => $proposal_id,
                'qt_ref'         => qt_generate_ref(),
                'validity_days'  => (int) qt_setting('qt_default_validity_days', 30),
                'terms'          => qt_setting('qt_terms_and_conditions', ''),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $ws_id = $CI->db->insert_id();

            $ws = $CI->db->get_where(db_prefix() . 'ipms_qt_worksheets', ['id' => $ws_id])->row();
            $CI->db->where('id', $proposal_id);
            $CI->db->update(db_prefix() . 'proposals', [
                'qt_worksheet_id' => $ws_id,
                'qt_ref'          => $ws->qt_ref,
            ]);
        }

        return $ws;
    }
}

if (!function_exists('qt_get_lines')) {
    function qt_get_lines($proposal_id, $tab = '')
    {
        $CI = &get_instance();

        $CI->db->where('proposal_id', $proposal_id);
        if ($tab) {
            $CI->db->where('tab', $tab);
        }
        $CI->db->order_by('tab, section_name, line_order', 'ASC');

        return $CI->db->get(db_prefix() . 'ipms_qt_lines')->result_array();
    }
}

if (!function_exists('qt_get_lines_by_tab')) {
    function qt_get_lines_by_tab($proposal_id)
    {
        $lines   = qt_get_lines($proposal_id);
        $grouped = [];

        foreach ($lines as $line) {
            $grouped[$line['tab']][] = $line;
        }

        return $grouped;
    }
}

if (!function_exists('qt_calculate_line')) {
    function qt_calculate_line($line)
    {
        $qty  = (float) ($line['quantity'] ?? 1);
        $area = null;
        if (!empty($line['width_m']) && !empty($line['height_m'])) {
            $area                  = (float) $line['width_m'] * (float) $line['height_m'];
            $line['computed_area'] = $area;
        }

        $multiplier = (!empty($line['size_based']) && $area) ? $area * $qty : $qty;
        $cost       = (float) ($line['cost_price'] ?? 0);
        $markup     = (float) ($line['markup_percent'] ?? qt_setting('qt_default_markup', 25));
        $sell       = (float) ($line['sell_price'] ?? 0);

        if ($sell == 0 && $cost > 0) {
            $sell = $cost * (1 + $markup / 100);
        } elseif ($sell > 0 && $cost > 0) {
            $markup = (($sell - $cost) / $cost) * 100;
        }

        $line['sell_price']      = $sell;
        $line['markup_percent']  = round($markup, 2);
        $line['line_total_cost'] = round($cost * $multiplier, 2);
        $line['line_total_sell'] = round($sell * $multiplier, 2);

        return $line;
    }
}

if (!function_exists('qt_recalculate_totals')) {
    function qt_recalculate_totals($proposal_id)
    {
        $CI    = &get_instance();
        $ws    = qt_get_or_create_worksheet($proposal_id);
        $lines = qt_get_lines($proposal_id);

        $total_cost = 0;
        $total_sell = 0;

        foreach ($lines as $line) {
            $total_cost += (float) $line['line_total_cost'];
            $total_sell += (float) $line['line_total_sell'];
        }

        $contingency_pct            = (float) $ws->contingency_percent;
        $contingency_amt            = round($total_sell * ($contingency_pct / 100), 2);
        $subtotal_after_contingency = $total_sell + $contingency_amt;
        $discount_pct               = (float) $ws->discount_percent;
        $discount_amt               = round($subtotal_after_contingency * ($discount_pct / 100), 2);
        $after_discount             = $subtotal_after_contingency - $discount_amt;
        $vat_rate                   = (float) qt_setting('qt_vat_rate', 16.5);
        $vat_amt                    = round($after_discount * ($vat_rate / 100), 2);
        $grand_total                = $after_discount + $vat_amt;
        $margin                     = $total_sell > 0 ? round((($total_sell - $total_cost) / $total_sell) * 100, 1) : 0;

        $CI->db->where('proposal_id', $proposal_id);
        $CI->db->update(db_prefix() . 'ipms_qt_worksheets', [
            'total_cost'         => round($total_cost, 2),
            'total_sell'         => round($total_sell, 2),
            'contingency_amount' => $contingency_amt,
            'discount_amount'    => $discount_amt,
            'vat_amount'         => $vat_amt,
            'grand_total'        => round($grand_total, 2),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $CI->db->where('id', $proposal_id);
        $CI->db->update(db_prefix() . 'proposals', [
            'subtotal'         => round($total_sell, 2),
            'total'            => round($grand_total, 2),
            'discount_percent' => $discount_pct,
            'discount_total'   => $discount_amt,
        ]);

        return [
            'total_cost'         => round($total_cost, 2),
            'total_sell'         => round($total_sell, 2),
            'contingency_amount' => $contingency_amt,
            'discount_amount'    => $discount_amt,
            'vat_amount'         => $vat_amt,
            'grand_total'        => round($grand_total, 2),
            'margin_percent'     => $margin,
        ];
    }
}

if (!function_exists('qt_sync_worksheet_to_proposal')) {
    function qt_sync_worksheet_to_proposal($proposal_id, $post_data)
    {
        $CI = &get_instance();

        $contingency = isset($post_data['qt_contingency']) ? (float) $post_data['qt_contingency'] : 0;
        $discount    = isset($post_data['qt_discount']) ? (float) $post_data['qt_discount'] : 0;

        $CI->db->where('proposal_id', $proposal_id);
        $CI->db->update(db_prefix() . 'ipms_qt_worksheets', [
            'contingency_percent' => $contingency,
            'discount_percent'    => $discount,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        return qt_recalculate_totals($proposal_id);
    }
}

if (!function_exists('qt_can_see_margins')) {
    function qt_can_see_margins()
    {
        $CI      = &get_instance();
        $staffId = get_staff_user_id();
        if (!$staffId) {
            return false;
        }

        $CI->db->select('r.name');
        $CI->db->from(db_prefix() . 'staff s');
        $CI->db->join(db_prefix() . 'roles r', 'r.roleid = s.role', 'left');
        $CI->db->where('s.staffid', $staffId);
        $roleName = $CI->db->get()->row('name');

        $allowed = [
            'General Manager',
            'Finance Manager',
            'Sales Manager',
            'System Administrator',
        ];

        return in_array($roleName, $allowed, true);
    }
}

if (!function_exists('qt_format_mwk')) {
    function qt_format_mwk($amount)
    {
        return 'MWK ' . number_format((float) $amount, 2, '.', ',');
    }
}

if (!function_exists('qt_get_tab_labels')) {
    function qt_get_tab_labels()
    {
        return [
            'signage'      => 'Signage & Printing',
            'installation' => 'Installation',
            'construction' => 'Construction Works',
            'retrofitting' => 'Shop Retrofitting',
            'promotional'  => 'Promotional Items',
            'additional'   => 'Additional Charges',
        ];
    }
}

if (!function_exists('qt_get_boq_sections')) {
    function qt_get_boq_sections($tab)
    {
        if ($tab === 'construction') {
            return ['Materials', 'Labour', 'Plant & Equipment', 'Subcontractors'];
        }

        if ($tab === 'retrofitting') {
            return ['Carpentry & Joinery', 'Electrical Works', 'Signage Works', 'Painting & Finishing'];
        }

        return [];
    }
}

if (!function_exists('qt_get_status_badge')) {
    function qt_get_status_badge($worksheet)
    {
        $status = isset($worksheet->qt_status) && $worksheet->qt_status !== ''
            ? strtolower((string) $worksheet->qt_status)
            : 'draft';

        $labels = [
            'draft'             => 'Draft',
            'pending_approval'  => 'Pending Approval',
            'approved'          => 'Approved',
            'rejected'          => 'Rejected',
            'submitted'         => 'Submitted',
        ];

        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }
}

if (!function_exists('qt_slugify')) {
    function qt_slugify($value)
    {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);

        return trim((string) $slug, '-');
    }
}

if (!function_exists('qt_render_line_row')) {
    function qt_render_line_row($tab, $line = [])
    {
        $id            = isset($line['id']) ? (int) $line['id'] : 0;
        $description   = isset($line['description']) ? $line['description'] : '';
        $unit          = isset($line['unit']) ? $line['unit'] : '';
        $qty           = isset($line['quantity']) ? (float) $line['quantity'] : 1;
        $cost          = isset($line['cost_price']) ? (float) $line['cost_price'] : 0;
        $markup        = isset($line['markup_percent']) ? (float) $line['markup_percent'] : (float) qt_setting('qt_default_markup', 25);
        $sell          = isset($line['sell_price']) ? (float) $line['sell_price'] : 0;
        $lineTotalSell = isset($line['line_total_sell']) ? (float) $line['line_total_sell'] : 0;
        $computedArea  = isset($line['computed_area']) ? (float) $line['computed_area'] : 0;
        $width         = isset($line['width_m']) ? (float) $line['width_m'] : 0;
        $height        = isset($line['height_m']) ? (float) $line['height_m'] : 0;
        $itemCode      = isset($line['item_code']) ? $line['item_code'] : '';
        $sectionName   = isset($line['section_name']) ? $line['section_name'] : '';

        ob_start();
        ?>
<tr class="qt-line-row" data-line-id="<?php echo $id; ?>" data-tab="<?php echo e($tab); ?>" data-section="<?php echo e($sectionName); ?>">
  <td><i class="fa fa-bars qt-drag-handle"></i></td>
  <?php if ($tab === 'signage'): ?>
    <td><input type="text" class="form-control input-sm qt-field qt-description" data-field="description" value="<?php echo e($description); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="substrate" value="<?php echo e($line['substrate'] ?? ''); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="print_type" value="<?php echo e($line['print_type'] ?? ''); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-width-field" data-field="width_m" value="<?php echo e($width); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-height-field" data-field="height_m" value="<?php echo e($height); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field qt-area-field" data-field="computed_area" readonly value="<?php echo e(number_format($computedArea, 2, '.', '')); ?>"></td>
    <td><input type="number" step="1" min="1" class="form-control input-sm qt-field qt-qty-field" data-field="quantity" value="<?php echo e($qty); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-cost-field" data-field="cost_price" value="<?php echo e($cost); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-markup-field" data-field="markup_percent" value="<?php echo e($markup); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-sell-field" data-field="sell_price" value="<?php echo e($sell); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field qt-line-total-field" data-field="line_total_sell" readonly value="<?php echo e(number_format($lineTotalSell, 2, '.', '')); ?>"></td>
  <?php elseif ($tab === 'installation'): ?>
    <td><input type="text" class="form-control input-sm qt-field qt-description" data-field="description" value="<?php echo e($description); ?>"></td>
    <td>
      <select class="form-control input-sm qt-field" data-field="activity_type">
        <?php $types = ['Labour', 'Travel', 'Equipment', 'Lump Sum']; ?>
        <?php foreach ($types as $type): ?>
          <option value="<?php echo e($type); ?>" <?php echo (($line['activity_type'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo e($type); ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-qty-field" data-field="quantity" value="<?php echo e($qty); ?>"></td>
    <td>
      <select class="form-control input-sm qt-field" data-field="rate_type">
        <?php $rateTypes = ['per Hour', 'per Day', 'Lump Sum']; ?>
        <?php foreach ($rateTypes as $type): ?>
          <option value="<?php echo e($type); ?>" <?php echo (($line['rate_type'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo e($type); ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field" data-field="rate_value" value="<?php echo e((float) ($line['rate_value'] ?? 0)); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field" data-field="duration" value="<?php echo e((float) ($line['duration'] ?? $qty)); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-cost-field" data-field="cost_price" value="<?php echo e($cost); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-markup-field" data-field="markup_percent" value="<?php echo e($markup); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-sell-field" data-field="sell_price" value="<?php echo e($sell); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field qt-line-total-field" data-field="line_total_sell" readonly value="<?php echo e(number_format($lineTotalSell, 2, '.', '')); ?>"></td>
  <?php elseif (in_array($tab, ['construction', 'retrofitting'], true)): ?>
    <td><input type="text" class="form-control input-sm qt-field qt-description" data-field="description" value="<?php echo e($description); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="unit" value="<?php echo e($unit); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-qty-field" data-field="quantity" value="<?php echo e($qty); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-sell-field" data-field="sell_price" value="<?php echo e($sell); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="line_total_cost" readonly value="<?php echo e(number_format((float) ($line['line_total_cost'] ?? 0), 2, '.', '')); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-markup-field" data-field="markup_percent" value="<?php echo e($markup); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field qt-line-total-field" data-field="line_total_sell" readonly value="<?php echo e(number_format($lineTotalSell, 2, '.', '')); ?>"></td>
  <?php elseif ($tab === 'promotional'): ?>
    <td>
      <input type="text" class="form-control input-sm qt-field qt-description qt-item-autocomplete" data-field="description" value="<?php echo e($description); ?>">
      <input type="hidden" class="qt-field" data-field="commodity_id" value="<?php echo (int) ($line['commodity_id'] ?? 0); ?>">
    </td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="item_code" value="<?php echo e($itemCode); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="unit" value="<?php echo e($unit); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field" data-field="stock_qty" readonly value="<?php echo e((float) ($line['stock_qty'] ?? 0)); ?>"></td>
    <td><input type="number" step="1" min="1" class="form-control input-sm qt-field qt-qty-field" data-field="quantity" value="<?php echo e($qty); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-cost-field" data-field="cost_price" value="<?php echo e($cost); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-markup-field" data-field="markup_percent" value="<?php echo e($markup); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-sell-field" data-field="sell_price" value="<?php echo e($sell); ?>"></td>
    <td><input type="text" class="form-control input-sm qt-field qt-line-total-field" data-field="line_total_sell" readonly value="<?php echo e(number_format($lineTotalSell, 2, '.', '')); ?>"></td>
  <?php else: ?>
    <td><input type="text" class="form-control input-sm qt-field qt-description" data-field="description" value="<?php echo e($description); ?>"></td>
    <td><input type="number" step="0.01" min="0" class="form-control input-sm qt-field qt-sell-field" data-field="sell_price" value="<?php echo e($sell); ?>"></td>
    <td>
      <select class="form-control input-sm qt-field" data-field="is_taxable">
        <option value="1" <?php echo ((int) ($line['is_taxable'] ?? 1) === 1) ? 'selected' : ''; ?>>Yes</option>
        <option value="0" <?php echo ((int) ($line['is_taxable'] ?? 1) === 0) ? 'selected' : ''; ?>>No</option>
      </select>
    </td>
  <?php endif; ?>
  <td>
    <button type="button" class="btn btn-xs btn-danger qt-delete-line" data-id="<?php echo $id; ?>">
      <i class="fa fa-trash"></i>
    </button>
  </td>
</tr>
        <?php
        return trim(ob_get_clean());
    }
}

if (!function_exists('qt_can_view_proposal')) {
    function qt_can_view_proposal($proposalId)
    {
        if (!is_staff_logged_in()) {
            return false;
        }

        $proposalId = (int) $proposalId;
        if ($proposalId <= 0) {
            return staff_can('create', 'proposals') || staff_can('edit', 'proposals');
        }

        $CI       = &get_instance();
        $proposal = $CI->db->get_where(db_prefix() . 'proposals', ['id' => $proposalId])->row();
        if (!$proposal) {
            return false;
        }

        $staffId = (int) get_staff_user_id();

        if (is_admin()) {
            return true;
        }

        if (staff_can('view', 'proposals')) {
            return true;
        }

        if (staff_can('view_own', 'proposals')
            && ((int) $proposal->addedfrom === $staffId || (int) $proposal->assigned === $staffId)) {
            return true;
        }

        if (get_option('allow_staff_view_proposals_assigned') == 1
            && (int) $proposal->assigned === $staffId) {
            return true;
        }

        $CI->db->select('r.name');
        $CI->db->from(db_prefix() . 'staff s');
        $CI->db->join(db_prefix() . 'roles r', 'r.roleid = s.role', 'left');
        $CI->db->where('s.staffid', $staffId);
        $roleName = $CI->db->get()->row('name');

        $managerRoles = [
            'General Manager',
            'GM',
            'Finance Manager',
            'Sales Manager',
            'System Administrator',
            'Administrator',
        ];

        return in_array($roleName, $managerRoles, true);
    }
}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @return array<int, string>
 */
function inv_mgr_allowed_inventory_roles()
{
    return [
        'Store Manager',
        'Storekeeper',
        'Storekeeper/Stores Clerk',
        'Finance Manager',
        'General Manager',
    ];
}

/**
 * Staff may use IPMS Inventory menus (not permission-capability; role-based like M05).
 */
function inv_mgr_staff_can_access_inventory()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return false;
    }

    if (function_exists('is_admin') && is_admin()) {
        return true;
    }

    $roleName = inv_mgr_get_current_staff_role();

    return $roleName !== '' && in_array($roleName, inv_mgr_allowed_inventory_roles(), true);
}

/**
 * @return string
 */
function inv_mgr_get_current_staff_role()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return '';
    }

    $staffId = (int) get_staff_user_id();
    if ($staffId < 1) {
        return '';
    }

    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'roles.name as role_name');
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');
    $CI->db->where(db_prefix() . 'staff.staffid', $staffId);
    $row = $CI->db->get()->row();

    return $row && isset($row->role_name) ? (string) $row->role_name : '';
}

/**
 * Active staff for a Perfex role name (exact match on tblroles.name).
 *
 * @param string $role_name
 * @return array<int, object>
 */
function inv_mgr_get_staff_by_role($role_name)
{
    $CI        = &get_instance();
    $role_name = (string) $role_name;

    if ($role_name === '') {
        return [];
    }

    $CI->db->select(
        db_prefix() . 'staff.staffid,
        CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) AS full_name,
        ' . db_prefix() . 'staff.email'
    );
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

/**
 * @param string $key
 * @param string $default
 * @return string
 */
function inv_mgr_setting($key, $default = '')
{
    $CI = &get_instance();
    $t  = db_prefix() . 'ipms_inv_settings';
    if (!$CI->db->table_exists($t)) {
        return $default;
    }
    $r = $CI->db->get_where($t, ['setting_key' => $key])->row();

    return $r ? (string) $r->setting_value : $default;
}

/**
 * Next document reference for grn | adj | mov.
 *
 * @param string $type grn|adj|mov
 * @return string
 */
function inv_mgr_generate_ref($type)
{
    $CI   = &get_instance();
    $type = (string) $type;

    if (!in_array($type, ['grn', 'adj', 'mov'], true)) {
        $type = 'mov';
    }

    $prefixKey = $type . '_prefix';
    $nextKey   = $type . '_next_number';

    $prefix = inv_mgr_setting($prefixKey, strtoupper($type));
    $num    = (int) inv_mgr_setting($nextKey, '1');

    $ref = $prefix . '-' . date('Y') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);

    $CI->db->where('setting_key', $nextKey);
    $CI->db->update(db_prefix() . 'ipms_inv_settings', [
        'setting_value' => (string) ($num + 1),
    ]);

    return $ref;
}

/**
 * @param int $item_id
 * @param int $warehouse_id
 * @return float
 */
function inv_mgr_get_stock_qty($item_id, $warehouse_id)
{
    $CI = &get_instance();
    $p  = db_prefix();

    $sql = 'SELECT COALESCE(SUM(CAST(`im`.`inventory_number` AS DECIMAL(10,3))), 0) AS qty
        FROM `' . $p . 'inventory_manage` AS `im`
        WHERE `im`.`commodity_id` = ? AND `im`.`warehouse_id` = ?';

    $row = $CI->db->query($sql, [(int) $item_id, (int) $warehouse_id])->row();

    return $row ? (float) $row->qty : 0.0;
}

/**
 * @param int $item_id
 * @return array<int, array{warehouse_id:int, qty:float}>
 */
function inv_mgr_get_all_stock($item_id)
{
    $CI = &get_instance();
    $p  = db_prefix();

    $sql = 'SELECT `im`.`warehouse_id`,
            COALESCE(SUM(CAST(`im`.`inventory_number` AS DECIMAL(10,3))), 0) AS qty
        FROM `' . $p . 'inventory_manage` AS `im`
        WHERE `im`.`commodity_id` = ?
        GROUP BY `im`.`warehouse_id`';

    $rows = $CI->db->query($sql, [(int) $item_id])->result_array();
    $out   = [];

    foreach ($rows as $r) {
        $out[] = [
            'warehouse_id' => (int) $r['warehouse_id'],
            'qty'          => (float) $r['qty'],
        ];
    }

    return $out;
}

/**
 * @param int $item_id
 * @return float
 */
function inv_mgr_get_wac($item_id)
{
    $CI = &get_instance();
    $CI->db->select('purchase_price');
    $CI->db->where('id', (int) $item_id);
    $row = $CI->db->get(db_prefix() . 'items')->row();

    if (!$row || $row->purchase_price === null || $row->purchase_price === '') {
        return 0.0;
    }

    return (float) $row->purchase_price;
}

/**
 * Recalculate global item WAC after a receipt at one warehouse.
 *
 * @param int   $item_id
 * @param int   $warehouse_id
 * @param float $new_qty
 * @param float $new_unit_price
 * @return array<string, float>
 */
function inv_mgr_recalculate_wac($item_id, $warehouse_id, $new_qty, $new_unit_price)
{
    $CI = &get_instance();

    $current_qty = inv_mgr_get_stock_qty((int) $item_id, (int) $warehouse_id);
    $current_wac = inv_mgr_get_wac((int) $item_id);

    $new_qty          = (float) $new_qty;
    $new_unit_price   = (float) $new_unit_price;
    $current_value    = $current_qty * $current_wac;
    $new_receipt_value = $new_qty * $new_unit_price;
    $total_qty        = $current_qty + $new_qty;

    if ($total_qty <= 0) {
        $new_wac = $new_unit_price;
    } else {
        $new_wac = ($current_value + $new_receipt_value) / $total_qty;
    }

    $new_wac = round($new_wac, 4);

    $CI->db->where('id', (int) $item_id);
    $CI->db->update(db_prefix() . 'items', ['purchase_price' => $new_wac]);

    return [
        'wac_before'   => $current_wac,
        'wac_after'    => $new_wac,
        'stock_before' => $current_qty,
        'stock_after'  => $total_qty,
        'new_wac'      => $new_wac,
    ];
}

/**
 * Update warehouse addon stock row (latest row per item/warehouse).
 *
 * @param int   $item_id
 * @param int   $warehouse_id
 * @param float $qty_change
 * @return bool
 */
function inv_mgr_update_stock_ledger($item_id, $warehouse_id, $qty_change)
{
    $CI = &get_instance();
    $p  = db_prefix();

    $item_id       = (int) $item_id;
    $warehouse_id  = (int) $warehouse_id;
    $qty_change    = (float) $qty_change;

    $CI->db->select('id, inventory_number');
    $CI->db->from($p . 'inventory_manage');
    $CI->db->where('commodity_id', $item_id);
    $CI->db->where('warehouse_id', $warehouse_id);
    $CI->db->order_by('id', 'DESC');
    $CI->db->limit(1);
    $row = $CI->db->get()->row();

    if ($row) {
        $current = (float) $row->inventory_number;
        $newQty  = max(0, $current + $qty_change);
        $CI->db->where('id', (int) $row->id);
        $CI->db->update($p . 'inventory_manage', [
            'inventory_number' => (string) $newQty,
        ]);

        return true;
    }

    if ($qty_change > 0) {
        $CI->db->insert($p . 'inventory_manage', [
            'warehouse_id'     => $warehouse_id,
            'commodity_id'     => $item_id,
            'inventory_number' => (string) $qty_change,
        ]);

        return true;
    }

    log_message(
        'error',
        'inv_mgr: Cannot deduct from non-existent stock: item ' . $item_id . ' wh ' . $warehouse_id
    );

    return false;
}

/**
 * @param array<string, mixed> $data
 * @return int|false
 */
function inv_mgr_log_movement($data)
{
    if (!is_array($data)) {
        return false;
    }

    $required = [
        'movement_type',
        'item_id',
        'item_code',
        'item_name',
        'warehouse_id',
        'qty_change',
        'qty_before',
        'qty_after',
        'wac_at_movement',
        'value_change',
        'rel_type',
        'rel_id',
        'rel_ref',
        'performed_by',
        'performed_at',
    ];

    foreach ($required as $key) {
        if (!array_key_exists($key, $data)) {
            return false;
        }
    }

    $CI = &get_instance();
    $p  = db_prefix();

    $movement_ref = isset($data['movement_ref']) && (string) $data['movement_ref'] !== ''
        ? (string) $data['movement_ref']
        : inv_mgr_generate_ref('mov');

    $insert = [
        'movement_ref'    => $movement_ref,
        'movement_type'   => (string) $data['movement_type'],
        'item_id'         => (int) $data['item_id'],
        'item_code'       => (string) $data['item_code'],
        'item_name'       => (string) $data['item_name'],
        'warehouse_id'    => (int) $data['warehouse_id'],
        'qty_change'      => (float) $data['qty_change'],
        'qty_before'      => (float) $data['qty_before'],
        'qty_after'       => (float) $data['qty_after'],
        'wac_at_movement' => (float) $data['wac_at_movement'],
        'value_change'    => (float) $data['value_change'],
        'rel_type'        => $data['rel_type'] !== null && $data['rel_type'] !== '' ? (string) $data['rel_type'] : null,
        'rel_id'          => $data['rel_id'] !== null && $data['rel_id'] !== '' ? (int) $data['rel_id'] : null,
        'rel_ref'         => $data['rel_ref'] !== null && $data['rel_ref'] !== '' ? (string) $data['rel_ref'] : null,
        'notes'           => isset($data['notes']) ? $data['notes'] : null,
        'performed_by'    => (int) $data['performed_by'],
        'performed_at'    => (string) $data['performed_at'],
    ];

    if ($CI->db->insert($p . 'ipms_stock_movements', $insert)) {
        return (int) $CI->db->insert_id();
    }

    return false;
}

/**
 * @param int $item_id
 * @return object|false
 */
function inv_mgr_get_item($item_id)
{
    $CI     = &get_instance();
    $p      = db_prefix();
    $itemsT = $p . 'items';

    if (!$CI->db->table_exists($itemsT)) {
        return false;
    }

    $ctT = $p . 'ware_commodity_type';
    $hasCt = $CI->db->table_exists($ctT)
        && $CI->db->field_exists('commodity_type', $itemsT)
        && $CI->db->field_exists('commodity_type_id', $ctT)
        && ($CI->db->field_exists('commondity_name', $ctT) || $CI->db->field_exists('commodity_name', $ctT));

    $igT = $p . 'items_groups';
    $hasIg = $CI->db->table_exists($igT)
        && $CI->db->field_exists('group_id', $itemsT)
        && $CI->db->field_exists('id', $igT)
        && ($CI->db->field_exists('name', $igT) || $CI->db->field_exists('group_name', $igT));

    $utT = $p . 'ware_unit_type';
    $hasUt = $CI->db->table_exists($utT)
        && $CI->db->field_exists('unit_id', $itemsT)
        && $CI->db->field_exists('unit_type_id', $utT);

    $CI->db->select('i.*', false);
    if ($hasUt) {
        $CI->db->select('u.unit_symbol, u.unit_name', false);
    } else {
        $CI->db->select("'' AS unit_symbol, '' AS unit_name", false);
    }
    if ($hasCt) {
        $catCol = $CI->db->field_exists('commondity_name', $ctT) ? 'ct.commondity_name' : 'ct.commodity_name';
        $CI->db->select($catCol . ' as category_name', false);
    } else {
        $CI->db->select("'' AS category_name", false);
    }
    if ($hasIg) {
        $grpCol = $CI->db->field_exists('name', $igT) ? 'ig.name' : 'ig.group_name';
        $CI->db->select($grpCol . ' as group_name', false);
    } else {
        $CI->db->select("'' AS group_name", false);
    }
    $CI->db->from($itemsT . ' i');
    if ($hasUt) {
        $CI->db->join($utT . ' u', 'u.unit_type_id = i.unit_id', 'left');
    }
    if ($hasCt) {
        $CI->db->join($ctT . ' ct', 'ct.commodity_type_id = i.commodity_type', 'left');
    }
    if ($hasIg) {
        $CI->db->join($igT . ' ig', 'ig.id = i.group_id', 'left');
    }
    $CI->db->where('i.id', (int) $item_id);

    try {
        $q = $CI->db->get();
    } catch (Throwable $e) {
        log_message('error', 'inv_mgr_get_item exception: ' . $e->getMessage());

        return false;
    }
    if ($q === false) {
        log_message('error', 'inv_mgr_get_item: ' . json_encode($CI->db->error()));

        return false;
    }

    return $q->row();
}

/**
 * When a proposal line has no item_id, try to match a single active inventory row
 * by exact case-insensitive description / commodity_name / long_description / commodity_code.
 *
 * @param string $text Proposal line description or catalogue label
 * @return int tblitems.id or 0 if ambiguous / none
 */
function inv_mgr_resolve_item_id_by_line_text($text)
{
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
    if ($text === '') {
        return 0;
    }

    $norm = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

    $CI     = &get_instance();
    $p      = db_prefix();
    $itemsT = $p . 'items';
    if (!$CI->db->table_exists($itemsT)) {
        return 0;
    }

    $esc       = $CI->db->escape($norm);
    $activeSql = $CI->db->field_exists('active', $itemsT) ? '`active` = 1 AND ' : '';

    $parts = ['(LOWER(TRIM(COALESCE(description, ""))) = ' . $esc . ')'];
    if ($CI->db->field_exists('commodity_name', $itemsT)) {
        $parts[] = '(LOWER(TRIM(COALESCE(commodity_name, ""))) = ' . $esc . ')';
    }
    if ($CI->db->field_exists('long_description', $itemsT)) {
        $parts[] = '(LOWER(TRIM(COALESCE(long_description, ""))) = ' . $esc . ')';
    }

    $sql = 'SELECT `id` FROM `' . $itemsT . '` WHERE ' . $activeSql . '(' . implode(' OR ', $parts) . ') LIMIT 2';
    $rows = $CI->db->query($sql)->result();
    if (count($rows) === 1) {
        return (int) $rows[0]->id;
    }

    if ($CI->db->field_exists('commodity_code', $itemsT)) {
        $sql = 'SELECT `id` FROM `' . $itemsT . '` WHERE ' . $activeSql . 'LOWER(TRIM(COALESCE(commodity_code, ""))) = ' . $esc . ' LIMIT 2';
        $rows = $CI->db->query($sql)->result();
        if (count($rows) === 1) {
            return (int) $rows[0]->id;
        }
    }

    return 0;
}

/**
 * @param string    $term
 * @param int|null  $warehouse_id
 * @param bool      $with_stock
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_search_items($term, $warehouse_id = null, $with_stock = false)
{
    $CI     = &get_instance();
    $p      = db_prefix();
    $term   = (string) $term;
    $itemsT = $p . 'items';

    if (!$CI->db->table_exists($itemsT)) {
        return [];
    }

    $hasIm   = $CI->db->table_exists($p . 'inventory_manage');
    $hasUt   = $CI->db->table_exists($p . 'ware_unit_type') && $CI->db->field_exists('unit_id', $itemsT);
    $hasCode = $CI->db->field_exists('commodity_code', $itemsT);

    $CI->db->from($itemsT . ' i');
    if ($hasUt) {
        $CI->db->join($p . 'ware_unit_type u', 'u.unit_type_id = i.unit_id', 'left');
    }

    $CI->db->group_start();
    $CI->db->like('i.description', $term);
    if ($hasCode) {
        $CI->db->or_like('i.commodity_code', $term);
    }
    if ($CI->db->field_exists('commodity_name', $itemsT)) {
        $CI->db->or_like('i.commodity_name', $term);
    }
    if ($CI->db->field_exists('long_description', $itemsT)) {
        $CI->db->or_like('i.long_description', $term);
    }
    $CI->db->group_end();

    if ($warehouse_id !== null && !$with_stock && $hasIm) {
        $wh = (int) $warehouse_id;
        $CI->db->where(
            'EXISTS (SELECT 1 FROM `' . $p . 'inventory_manage` imx WHERE imx.commodity_id = i.id AND imx.warehouse_id = ' . $wh . ')',
            null,
            false
        );
    }

    $unitSymExpr = $hasUt ? 'u.unit_symbol' : "''";

    if ($with_stock && $hasIm) {
        $joinCond = 'im.commodity_id = i.id';
        if ($warehouse_id !== null) {
            $joinCond .= ' AND im.warehouse_id = ' . (int) $warehouse_id;
        }
        $CI->db->join($p . 'inventory_manage im', $joinCond, 'left');
        $codeSel = $hasCode ? 'i.commodity_code' : "'' AS commodity_code";
        $CI->db->select(
            'i.id, ' . $codeSel . ', i.description, ' . $unitSymExpr . ' AS unit_symbol, i.purchase_price, i.rate, '
            . 'COALESCE(SUM(CAST(im.inventory_number AS DECIMAL(10,3))), 0) AS qty_on_hand',
            false
        );
        $gb = ['i.id', 'i.description'];
        if ($hasCode) {
            array_splice($gb, 1, 0, ['i.commodity_code']);
        }
        if ($hasUt) {
            $gb[] = 'u.unit_symbol';
        }
        $gb[] = 'i.purchase_price';
        $gb[] = 'i.rate';
        $CI->db->group_by(implode(', ', $gb), false);
    } else {
        if ($hasCode) {
            $CI->db->select('i.id, i.commodity_code, i.description, ' . $unitSymExpr . ' AS unit_symbol, i.purchase_price, i.rate', false);
        } else {
            $CI->db->select("i.id, '' AS commodity_code, i.description, " . $unitSymExpr . " AS unit_symbol, i.purchase_price, i.rate", false);
        }
    }

    $CI->db->limit(50);

    $q = $CI->db->get();
    if ($q === false) {
        log_message('error', 'inv_mgr_search_items: ' . json_encode($CI->db->error()));

        return [];
    }
    $rows = $q->result_array();
    $out  = [];

    foreach ($rows as $r) {
        $row = [
            'id'              => (int) $r['id'],
            'commodity_code'  => isset($r['commodity_code']) ? (string) $r['commodity_code'] : '',
            'description'     => isset($r['description']) ? (string) $r['description'] : '',
            'unit_symbol'     => isset($r['unit_symbol']) ? (string) $r['unit_symbol'] : '',
            'purchase_price'  => isset($r['purchase_price']) ? (float) $r['purchase_price'] : 0.0,
            'rate'            => isset($r['rate']) ? (float) $r['rate'] : 0.0,
        ];
        if ($with_stock) {
            $row['qty_on_hand'] = isset($r['qty_on_hand']) ? (float) $r['qty_on_hand'] : 0.0;
        }
        $out[] = $row;
    }

    return $out;
}

/**
 * Stock master list with optional filters and qty aggregates.
 *
 * @param array<string, mixed> $filters
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_get_all_items($filters = [])
{
    $CI = &get_instance();
    $p  = db_prefix();

    if (!is_array($filters)) {
        $filters = [];
    }

    $itemsT = $p . 'items';
    if (!$CI->db->table_exists($itemsT)) {
        return [];
    }

    $imT = $p . 'inventory_manage';
    $hasIm = $CI->db->table_exists($imT)
        && $CI->db->field_exists('commodity_id', $imT)
        && $CI->db->field_exists('inventory_number', $imT)
        && $CI->db->field_exists('warehouse_id', $imT);

    $ctT = $p . 'ware_commodity_type';
    $hasCt = $CI->db->table_exists($ctT)
        && $CI->db->field_exists('commodity_type', $itemsT)
        && $CI->db->field_exists('commodity_type_id', $ctT)
        && ($CI->db->field_exists('commondity_name', $ctT) || $CI->db->field_exists('commodity_name', $ctT));

    $igT = $p . 'items_groups';
    $hasIg = $CI->db->table_exists($igT)
        && $CI->db->field_exists('group_id', $itemsT)
        && $CI->db->field_exists('id', $igT)
        && ($CI->db->field_exists('name', $igT) || $CI->db->field_exists('group_name', $igT));

    $utT = $p . 'ware_unit_type';
    $hasUt = $CI->db->table_exists($utT)
        && $CI->db->field_exists('unit_id', $itemsT)
        && $CI->db->field_exists('unit_type_id', $utT);

    $hasCode = $CI->db->field_exists('commodity_code', $itemsT);

    $CI->db->select('i.*', false);
    if ($hasUt) {
        $CI->db->select('u.unit_symbol, u.unit_name', false);
    } else {
        $CI->db->select("'' AS unit_symbol, '' AS unit_name", false);
    }
    if ($hasCt) {
        $catCol = $CI->db->field_exists('commondity_name', $ctT) ? 'ct.commondity_name' : 'ct.commodity_name';
        $CI->db->select($catCol . ' as category_name', false);
    } else {
        $CI->db->select("'' AS category_name", false);
    }
    if ($hasIg) {
        $grpCol = $CI->db->field_exists('name', $igT) ? 'ig.name' : 'ig.group_name';
        $CI->db->select($grpCol . ' as group_name', false);
    } else {
        $CI->db->select("'' AS group_name", false);
    }

    $CI->db->from($p . 'items i');
    if ($hasUt) {
        $CI->db->join($utT . ' u', 'u.unit_type_id = i.unit_id', 'left');
    }
    if ($hasCt) {
        $CI->db->join($ctT . ' ct', 'ct.commodity_type_id = i.commodity_type', 'left');
    }
    if ($hasIg) {
        $CI->db->join($igT . ' ig', 'ig.id = i.group_id', 'left');
    }

    if (!empty($filters['category']) && $hasCt) {
        $CI->db->where('i.commodity_type', (int) $filters['category']);
    }

    if (!empty($filters['group']) && $hasIg) {
        $CI->db->where('i.group_id', (int) $filters['group']);
    }

    if (!empty($filters['warehouse']) && $hasIm) {
        $wh = (int) $filters['warehouse'];
        $CI->db->where(
            'EXISTS (SELECT 1 FROM `' . $imT . '` imf WHERE imf.commodity_id = i.id AND imf.warehouse_id = ' . $wh . ')',
            null,
            false
        );
    }

    if (!empty($filters['search_term'])) {
        $t = (string) $filters['search_term'];
        $CI->db->group_start();
        $CI->db->like('i.description', $t);
        if ($hasCode) {
            $CI->db->or_like('i.commodity_code', $t);
        }
        $CI->db->group_end();
    }

    if (!empty($filters['low_stock_only'])) {
        $low = inv_mgr_get_low_stock_items();
        $ids = [];
        foreach ($low as $lr) {
            if (isset($lr['id'])) {
                $ids[] = (int) $lr['id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return [];
        }
        $CI->db->where_in('i.id', $ids);
    }

    try {
        $q = $CI->db->get();
    } catch (Throwable $e) {
        log_message('error', 'inv_mgr_get_all_items exception: ' . $e->getMessage());

        return [];
    }
    if ($q === false) {
        log_message('error', 'inv_mgr_get_all_items: ' . json_encode($CI->db->error()));

        return [];
    }
    $rows = $q->result_array();

    $aggMap = [];
    if ($hasIm && $rows !== []) {
        $ids = [];
        foreach ($rows as $r0) {
            if (!empty($r0['id'])) {
                $ids[] = (int) $r0['id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids !== []) {
            $inList = implode(',', $ids);
            $aggSql = 'SELECT subq.commodity_id, SUM(subq.q) AS total_qty, '
                . 'GROUP_CONCAT(CONCAT(subq.warehouse_id, ":", subq.q) ORDER BY subq.warehouse_id SEPARATOR "|") AS warehouse_qty_raw '
                . 'FROM ('
                . 'SELECT im.commodity_id, im.warehouse_id, '
                . 'SUM(CAST(im.inventory_number AS DECIMAL(10,3))) AS q '
                . 'FROM `' . $imT . '` im '
                . 'WHERE im.commodity_id IN (' . $inList . ') '
                . 'GROUP BY im.commodity_id, im.warehouse_id'
                . ') subq GROUP BY subq.commodity_id';
            try {
                $q2 = $CI->db->query($aggSql);
            } catch (Throwable $e) {
                log_message('error', 'inv_mgr_get_all_items agg query: ' . $e->getMessage());
                $q2 = false;
            }
            if ($q2 !== false) {
                foreach ($q2->result_array() as $a) {
                    $cid = (int) ($a['commodity_id'] ?? 0);
                    if ($cid > 0) {
                        $aggMap[$cid] = [
                            'total_qty'           => isset($a['total_qty']) ? (float) $a['total_qty'] : 0.0,
                            'warehouse_qty_raw'   => $a['warehouse_qty_raw'] ?? null,
                        ];
                    }
                }
            }
        }
    }

    $out = [];

    foreach ($rows as $r) {
        $cid = (int) ($r['id'] ?? 0);
        $whRaw = null;
        $tot   = 0.0;
        if ($hasIm && $cid > 0 && isset($aggMap[$cid])) {
            $tot   = $aggMap[$cid]['total_qty'];
            $whRaw = $aggMap[$cid]['warehouse_qty_raw'];
        }

        $whMap = [];
        if ($whRaw !== null && $whRaw !== '') {
            $pairs = explode('|', (string) $whRaw);
            foreach ($pairs as $pair) {
                if ($pair === '' || strpos($pair, ':') === false) {
                    continue;
                }
                [$wid, $qty] = explode(':', $pair, 2);
                $whMap[(int) $wid] = (float) $qty;
            }
        }
        $r['total_qty']       = $tot;
        $r['warehouse_qty']   = $whMap;
        $r['category_name']   = isset($r['category_name']) ? (string) $r['category_name'] : '';
        $r['group_name']      = isset($r['group_name']) ? (string) $r['group_name'] : '';
        $r['unit_symbol']    = isset($r['unit_symbol']) ? (string) $r['unit_symbol'] : '';
        $r['unit_name']      = isset($r['unit_name']) ? (string) $r['unit_name'] : '';
        $out[]               = $r;
    }

    return $out;
}

/**
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_get_low_stock_items()
{
    $CI     = &get_instance();
    $p      = db_prefix();
    $itemsT = $p . 'items';
    $icmT   = $p . 'inventory_commodity_min';

    $imT = $p . 'inventory_manage';
    if (!$CI->db->table_exists($icmT)
        || !$CI->db->table_exists($itemsT)
        || !$CI->db->table_exists($imT)) {
        return [];
    }

    if (!$CI->db->field_exists('commodity_id', $imT)
        || !$CI->db->field_exists('inventory_number', $imT)) {
        return [];
    }

    if (!$CI->db->field_exists('commodity_id', $icmT)
        || !$CI->db->field_exists('inventory_number_min', $icmT)
        || !$CI->db->field_exists('description', $itemsT)) {
        return [];
    }

    $hasCode = $CI->db->field_exists('commodity_code', $itemsT);
    $codeSel = $hasCode ? '`i`.`commodity_code`' : "'' AS `commodity_code`";
    $groupBy = $hasCode
        ? '`i`.`id`, `i`.`description`, `i`.`commodity_code`'
        : '`i`.`id`, `i`.`description`';

    $sql = 'SELECT `i`.`id`, `i`.`description`, ' . $codeSel . ',
            MIN(CAST(`icm`.`inventory_number_min` AS DECIMAL(10,3))) AS `inventory_number_min`,
            COALESCE(SUM(CAST(`im`.`inventory_number` AS DECIMAL(10,3))), 0) AS `total_qty`
        FROM `' . $itemsT . '` `i`
        JOIN `' . $icmT . '` `icm` ON `icm`.`commodity_id` = `i`.`id`
        LEFT JOIN `' . $imT . '` `im` ON `im`.`commodity_id` = `i`.`id`
        GROUP BY ' . $groupBy . '
        HAVING `total_qty` <= MIN(CAST(`icm`.`inventory_number_min` AS DECIMAL(10,3)))
            AND MIN(CAST(`icm`.`inventory_number_min` AS DECIMAL(10,3))) > 0';

    try {
        $q = $CI->db->query($sql);
    } catch (Throwable $e) {
        log_message('error', 'inv_mgr_get_low_stock_items exception: ' . $e->getMessage());

        return [];
    }
    if ($q === false) {
        log_message('error', 'inv_mgr_get_low_stock_items: ' . json_encode($CI->db->error()));

        return [];
    }

    return $q->result_array();
}

/**
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_get_categories()
{
    $CI = &get_instance();
    $t = db_prefix() . 'ware_commodity_type';
    if (!$CI->db->table_exists($t)) {
        return [];
    }
    $CI->db->from($t);
    if ($CI->db->field_exists('display', $t)) {
        $CI->db->where('display', 1);
    }
    if ($CI->db->field_exists('commondity_name', $t)) {
        $CI->db->order_by('commondity_name', 'ASC');
    } elseif ($CI->db->field_exists('commodity_type_id', $t)) {
        $CI->db->order_by('commodity_type_id', 'ASC');
    }
    $q = $CI->db->get();
    if ($q === false) {
        log_message('error', 'inv_mgr_get_categories: ' . json_encode($CI->db->error()));

        return [];
    }

    return $q->result_array();
}

/**
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_get_units()
{
    $CI = &get_instance();
    $t = db_prefix() . 'ware_unit_type';
    if (!$CI->db->table_exists($t)) {
        return [];
    }
    $CI->db->from($t);
    if ($CI->db->field_exists('display', $t)) {
        $CI->db->where('display', 1);
    }
    if ($CI->db->field_exists('unit_name', $t)) {
        $CI->db->order_by('unit_name', 'ASC');
    } elseif ($CI->db->field_exists('unit_type_id', $t)) {
        $CI->db->order_by('unit_type_id', 'ASC');
    }
    $q = $CI->db->get();
    if ($q === false) {
        log_message('error', 'inv_mgr_get_units: ' . json_encode($CI->db->error()));

        return [];
    }

    return $q->result_array();
}

/**
 * Canonical branch warehouses (IDs align with stock master columns / filters).
 *
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_default_warehouses()
{
    return [
        1 => [
            'warehouse_id'   => 1,
            'warehouse_name' => 'Blantyre',
            'warehouse_code' => 'BLT',
        ],
        2 => [
            'warehouse_id'   => 2,
            'warehouse_name' => 'Lilongwe',
            'warehouse_code' => 'LLW',
        ],
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function inv_mgr_get_warehouses()
{
    $CI   = &get_instance();
    $t    = db_prefix() . 'warehouse';
    $byId = [];

    if ($CI->db->table_exists($t)) {
        $CI->db->from($t);
        if ($CI->db->field_exists('display', $t)) {
            $CI->db->where('display', 1);
        }
        if ($CI->db->field_exists('warehouse_id', $t)) {
            $CI->db->order_by('warehouse_id', 'ASC');
        }
        $q = $CI->db->get();
        if ($q === false) {
            log_message('error', 'inv_mgr_get_warehouses: ' . json_encode($CI->db->error()));
        } else {
            foreach ($q->result_array() as $r) {
                $id = (int) ($r['warehouse_id'] ?? 0);
                if ($id > 0) {
                    $byId[$id] = $r;
                }
            }
        }
    }

    foreach (inv_mgr_default_warehouses() as $id => $stub) {
        if (!isset($byId[$id])) {
            $byId[$id] = $stub;
            continue;
        }
        $nm = trim((string) ($byId[$id]['warehouse_name'] ?? ''));
        if ($nm === '') {
            $byId[$id]['warehouse_name'] = $stub['warehouse_name'];
        }
        if (trim((string) ($byId[$id]['warehouse_code'] ?? '')) === '' && !empty($stub['warehouse_code'])) {
            $byId[$id]['warehouse_code'] = $stub['warehouse_code'];
        }
    }

    ksort($byId, SORT_NUMERIC);

    return array_values($byId);
}

/**
 * @param float|string $amount
 * @return string
 */
function inv_mgr_format_mwk($amount)
{
    return 'MWK ' . number_format((float) $amount, 2, '.', ',');
}

/**
 * @param float|string $qty
 * @return string
 */
function inv_mgr_format_qty($qty)
{
    $f = (float) $qty;

    return $f == floor($f) ? number_format($f, 0) : number_format($f, 3);
}

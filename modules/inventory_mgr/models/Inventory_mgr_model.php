<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_mgr_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();

        if (!function_exists('inv_mgr_get_wac')) {
            $this->load->helper('inventory_mgr/inventory_mgr');
        }
    }

    // --------------------------------------------------------------------
    // Item master
    // --------------------------------------------------------------------

    /**
     * @param int|string $id
     * @param array      $filters
     * @return object|array|false
     */
    public function get_item($id = '', $filters = [])
    {
        if (is_numeric($id) && (int) $id > 0) {
            $item = inv_mgr_get_item((int) $id);
            if (!$item) {
                return false;
            }

            $stocks                = inv_mgr_get_all_stock((int) $id);
            $item->stock_by_warehouse = $stocks;
            $item->total_stock     = 0.0;
            foreach ($stocks as $row) {
                $item->total_stock += (float) $row['qty'];
            }

            $p = db_prefix();
            $this->db->select('inventory_number_min');
            $this->db->from($p . 'inventory_commodity_min');
            $this->db->where('commodity_id', (int) $id);
            $this->db->order_by('id', 'ASC');
            $this->db->limit(1);
            $minRow = $this->db->get()->row();
            $item->reorder_level = $minRow && $minRow->inventory_number_min !== null && $minRow->inventory_number_min !== ''
                ? (float) $minRow->inventory_number_min
                : null;

            $this->db->from($p . 'ipms_stock_movements');
            $this->db->where('item_id', (int) $id);
            $this->db->order_by('performed_at', 'DESC');
            $this->db->limit(10);
            $item->recent_movements = $this->db->get()->result();

            $this->db->select('gl.*, g.grn_ref, g.received_at, g.status AS grn_status', false);
            $this->db->from($p . 'ipms_grn_lines gl');
            $this->db->join($p . 'ipms_grn_log g', 'g.id = gl.grn_id', 'left');
            $this->db->where('gl.item_id', (int) $id);
            $this->db->order_by('gl.id', 'DESC');
            $this->db->limit(5);
            $item->recent_grn = $this->db->get()->result();

            return $item;
        }

        return inv_mgr_get_all_items(is_array($filters) ? $filters : []);
    }

    /**
     * @param array<string, mixed> $data
     * @return int|false
     */
    public function add_item($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $p = db_prefix();

        $commodity_code = isset($data['commodity_code']) ? trim((string) $data['commodity_code']) : '';
        $description   = isset($data['description']) ? trim((string) $data['description']) : '';
        $unit_id       = isset($data['unit_id']) ? (int) $data['unit_id'] : 0;
        $purchase_price = isset($data['purchase_price']) ? (float) $data['purchase_price'] : null;

        if ($commodity_code === '' || $description === '' || $unit_id < 1 || $purchase_price === null) {
            return false;
        }

        $this->db->from($p . 'items');
        $this->db->where('commodity_code', $commodity_code);
        if ((int) $this->db->count_all_results() > 0) {
            return false;
        }

        $this->db->from($p . 'ware_unit_type');
        $this->db->where('unit_type_id', $unit_id);
        if ((int) $this->db->count_all_results() < 1) {
            return false;
        }

        $rate = isset($data['rate']) ? (float) $data['rate'] : (float) $purchase_price;
        $group_id = isset($data['group_id']) ? (int) $data['group_id'] : 0;
        $long_description = isset($data['long_description']) ? (string) $data['long_description'] : '';
        $commodity_type = isset($data['commodity_type']) ? ($data['commodity_type'] === '' || $data['commodity_type'] === null ? null : (int) $data['commodity_type']) : null;

        $insert = [
            'description'       => $description,
            'commodity_name'    => isset($data['commodity_name']) ? (string) $data['commodity_name'] : $description,
            'rate'              => $rate,
            'purchase_price'    => $purchase_price,
            'commodity_code'    => $commodity_code,
            'unit_id'           => $unit_id,
            'group_id'          => $group_id,
            'long_description'  => $long_description,
            'commodity_type'    => $commodity_type,
        ];

        if ($this->db->field_exists('commodity_barcode', $p . 'items') && array_key_exists('commodity_barcode', $data)) {
            $insert['commodity_barcode'] = $data['commodity_barcode'] !== null && $data['commodity_barcode'] !== ''
                ? (string) $data['commodity_barcode'] : null;
        }
        if ($this->db->field_exists('warehouse_id', $p . 'items') && array_key_exists('default_warehouse_id', $data)) {
            $dw = (int) $data['default_warehouse_id'];
            $insert['warehouse_id'] = $dw > 0 ? $dw : null;
        }
        if ($this->db->field_exists('active', $p . 'items') && array_key_exists('active', $data)) {
            $insert['active'] = (int) $data['active'] ? 1 : 0;
        }

        if ($this->db->field_exists('tax', $p . 'items')) {
            $insert['tax'] = isset($data['tax']) && $data['tax'] !== '' ? (int) $data['tax'] : null;
        }
        if ($this->db->field_exists('taxrate', $p . 'items') && isset($data['taxrate'])) {
            $insert['taxrate'] = (float) $data['taxrate'];
        }

        $this->db->trans_begin();

        $this->db->insert($p . 'items', $insert);
        $item_id = (int) $this->db->insert_id();
        if ($item_id < 1) {
            $this->db->trans_rollback();

            return false;
        }

        $reorder = isset($data['reorder_level']) ? (float) $data['reorder_level'] : 0.0;
        if ($reorder > 0) {
            $icmIns = [
                'commodity_id'         => $item_id,
                'commodity_code'       => $commodity_code,
                'commodity_name'       => $description,
                'inventory_number_min' => (string) $reorder,
            ];
            if ($this->db->field_exists('warehouse_id', $p . 'inventory_commodity_min')) {
                $rw = isset($data['default_warehouse_id']) ? (int) $data['default_warehouse_id'] : 0;
                $icmIns['warehouse_id'] = $rw > 0 ? $rw : null;
            }
            $this->db->insert($p . 'inventory_commodity_min', $icmIns);
        }

        $openQty = isset($data['opening_qty']) ? (float) $data['opening_qty'] : 0.0;
        $openWh  = isset($data['opening_warehouse_id']) ? (int) $data['opening_warehouse_id'] : 0;
        if ($openQty > 0 && $openWh > 0) {
            $okOpen = $this->post_grn_for_item($item_id, $openWh, $openQty, (float) $purchase_price, [
                'note' => 'Opening balance',
            ]);
            if (!$okOpen) {
                $this->db->trans_rollback();

                return false;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            return false;
        }

        $this->db->trans_commit();

        return $item_id;
    }

    /**
     * Opening receipt: WAC, ledger, movement (opening_balance). No IPMS GRN header.
     *
     * @param int   $item_id
     * @param int   $warehouse_id
     * @param float $qty
     * @param float $unit_price
     * @param array<string, mixed> $extra
     * @return bool
     */
    public function post_grn_for_item($item_id, $warehouse_id, $qty, $unit_price, $extra = [])
    {
        $item_id       = (int) $item_id;
        $warehouse_id  = (int) $warehouse_id;
        $qty           = (float) $qty;
        $unit_price    = (float) $unit_price;

        if ($item_id < 1 || $warehouse_id < 1 || $qty <= 0) {
            return false;
        }

        $item = inv_mgr_get_item($item_id);
        if (!$item) {
            return false;
        }

        $wacResult = inv_mgr_recalculate_wac($item_id, $warehouse_id, $qty, $unit_price);
        if (!inv_mgr_update_stock_ledger($item_id, $warehouse_id, $qty)) {
            return false;
        }

        $qtyAfter = inv_mgr_get_stock_qty($item_id, $warehouse_id);
        $lineTotal = round($qty * $unit_price, 2);

        inv_mgr_log_movement([
            'movement_type'   => 'opening_balance',
            'item_id'         => $item_id,
            'item_code'       => (string) ($item->commodity_code ?? ''),
            'item_name'       => (string) ($item->description ?? ''),
            'warehouse_id'    => $warehouse_id,
            'qty_change'      => $qty,
            'qty_before'      => (float) $wacResult['stock_before'],
            'qty_after'       => $qtyAfter,
            'wac_at_movement' => (float) $wacResult['wac_after'],
            'value_change'    => $lineTotal,
            'rel_type'        => 'opening_balance',
            'rel_id'          => $item_id,
            'rel_ref'         => 'OB-' . $item_id,
            'notes'           => isset($extra['note']) ? (string) $extra['note'] : null,
            'performed_by'    => (int) get_staff_user_id(),
            'performed_at'    => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * @param int                  $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update_item($id, $data)
    {
        $id = (int) $id;
        if ($id < 1 || !is_array($data)) {
            return false;
        }

        $p = db_prefix();

        if (isset($data['commodity_code'])) {
            $code = trim((string) $data['commodity_code']);
            $this->db->from($p . 'items');
            $this->db->where('commodity_code', $code);
            $this->db->where('id !=', $id);
            if ((int) $this->db->count_all_results() > 0) {
                return false;
            }
        }

        $allowed = [
            'description', 'commodity_name', 'long_description', 'rate', 'purchase_price',
            'commodity_code', 'unit_id', 'group_id', 'commodity_type', 'tax', 'tax2',
        ];
        $update = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $update[$col] = $data[$col];
            }
        }
        if ($this->db->field_exists('commodity_barcode', $p . 'items') && array_key_exists('commodity_barcode', $data)) {
            $update['commodity_barcode'] = $data['commodity_barcode'] !== null && $data['commodity_barcode'] !== ''
                ? (string) $data['commodity_barcode'] : null;
        }
        if ($this->db->field_exists('warehouse_id', $p . 'items') && array_key_exists('default_warehouse_id', $data)) {
            $dw = (int) $data['default_warehouse_id'];
            $update['warehouse_id'] = $dw > 0 ? $dw : null;
        }
        if ($this->db->field_exists('active', $p . 'items') && array_key_exists('active', $data)) {
            $update['active'] = (int) $data['active'] ? 1 : 0;
        }
        if ($this->db->field_exists('taxrate', $p . 'items') && array_key_exists('taxrate', $data)) {
            $update['taxrate'] = $data['taxrate'];
        }

        if (!empty($update)) {
            $this->db->where('id', $id);
            $this->db->update($p . 'items', $update);
        }

        if (array_key_exists('reorder_level', $data)) {
            $reorder = (float) $data['reorder_level'];
            $this->db->where('commodity_id', $id);
            $existing = $this->db->get($p . 'inventory_commodity_min')->row();
            if ($reorder > 0) {
                $row = [
                    'commodity_id'          => $id,
                    'commodity_code'        => isset($data['commodity_code']) ? trim((string) $data['commodity_code']) : null,
                    'commodity_name'        => isset($data['description']) ? (string) $data['description'] : null,
                    'inventory_number_min'  => (string) $reorder,
                ];
                if ($existing) {
                    $updIcm = [
                        'inventory_number_min' => (string) $reorder,
                        'commodity_code'       => $row['commodity_code'] ?? $existing->commodity_code,
                        'commodity_name'       => $row['commodity_name'] ?? $existing->commodity_name,
                    ];
                    if ($this->db->field_exists('warehouse_id', $p . 'inventory_commodity_min') && array_key_exists('default_warehouse_id', $data)) {
                        $rw = (int) $data['default_warehouse_id'];
                        $updIcm['warehouse_id'] = $rw > 0 ? $rw : null;
                    }
                    $this->db->where('id', (int) $existing->id);
                    $this->db->update($p . 'inventory_commodity_min', $updIcm);
                } else {
                    $item = $this->db->where('id', $id)->get($p . 'items')->row();
                    $row['commodity_code'] = $item ? (string) $item->commodity_code : '';
                    $row['commodity_name'] = $item ? (string) $item->description : '';
                    if ($this->db->field_exists('warehouse_id', $p . 'inventory_commodity_min') && array_key_exists('default_warehouse_id', $data)) {
                        $rw = (int) $data['default_warehouse_id'];
                        $row['warehouse_id'] = $rw > 0 ? $rw : null;
                    }
                    $this->db->insert($p . 'inventory_commodity_min', $row);
                }
            } elseif ($existing) {
                $this->db->where('id', (int) $existing->id);
                $this->db->update($p . 'inventory_commodity_min', ['inventory_number_min' => '0']);
            }
        }

        return true;
    }

    // --------------------------------------------------------------------
    // GRN
    // --------------------------------------------------------------------

    /**
     * @param int|string $id
     * @param array      $filters
     * @return object|array|false
     */
    public function get_grn($id = '', $filters = [])
    {
        $p = db_prefix();

        if (is_numeric($id) && (int) $id > 0) {
            $this->db->select('g.*, w.warehouse_name', false);
            $this->db->from($p . 'ipms_grn_log g');
            $this->db->join($p . 'warehouse w', 'w.warehouse_id = g.warehouse_id', 'left');
            $this->db->where('g.id', (int) $id);
            $grn = $this->db->get()->row();
            if (!$grn) {
                return false;
            }
            $grn->lines = $this->get_grn_lines((int) $id);

            return $grn;
        }

        $this->db->select('g.*, w.warehouse_name', false);
        $this->db->from($p . 'ipms_grn_log g');
        $this->db->join($p . 'warehouse w', 'w.warehouse_id = g.warehouse_id', 'left');

        if (!empty($filters['warehouse_id'])) {
            $this->db->where('g.warehouse_id', (int) $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('g.status', (string) $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('g.received_at >=', (string) $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('g.received_at <=', (string) $filters['date_to']);
        }
        if (!empty($filters['supplier_name'])) {
            $this->db->like('g.supplier_name', (string) $filters['supplier_name']);
        }

        $this->db->order_by('g.received_at', 'DESC');
        $this->db->order_by('g.id', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * @param int $grn_id
     * @return array<int, array<string, mixed>>
     */
    public function get_grn_lines($grn_id)
    {
        $p = db_prefix();
        $this->db->select('gl.*, u.unit_symbol', false);
        $this->db->from($p . 'ipms_grn_lines gl');
        $this->db->join($p . 'items i', 'i.id = gl.item_id', 'left');
        $this->db->join($p . 'ware_unit_type u', 'u.unit_type_id = i.unit_id', 'left');
        $this->db->where('gl.grn_id', (int) $grn_id);
        $this->db->order_by('gl.id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * @param array<string, mixed>        $data
     * @param array<int, array<string, mixed>> $lines
     * @return int|false
     */
    public function create_grn($data, $lines)
    {
        $p = db_prefix();

        if (!is_array($data) || !is_array($lines) || empty($lines)) {
            return false;
        }

        $warehouse_id = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : 0;
        $received_at  = isset($data['received_at']) ? (string) $data['received_at'] : '';
        if ($warehouse_id < 1 || $received_at === '') {
            return false;
        }

        $receivedDate = date('Y-m-d', strtotime($received_at));
        if ($receivedDate === '1970-01-01') {
            $receivedDate = date('Y-m-d');
        }

        $this->db->trans_begin();

        $grn_ref = inv_mgr_generate_ref('grn');

        $ipmsHeader = [
            'grn_ref'          => $grn_ref,
            'warehouse_grn_id' => null,
            'warehouse_id'     => $warehouse_id,
            'supplier_name'    => isset($data['supplier_name']) ? (string) $data['supplier_name'] : null,
            'supplier_ref'     => isset($data['supplier_ref']) ? (string) $data['supplier_ref'] : null,
            'po_ref'           => isset($data['po_ref']) ? (string) $data['po_ref'] : null,
            'received_by'      => (int) get_staff_user_id(),
            'received_at'      => $receivedDate,
            'status'           => 'draft',
            'notes'            => isset($data['notes']) ? (string) $data['notes'] : null,
        ];

        $this->db->insert($p . 'ipms_grn_log', $ipmsHeader);
        $ipms_grn_id = (int) $this->db->insert_id();
        if ($ipms_grn_id < 1) {
            $this->db->trans_rollback();

            return false;
        }

        $addonHeader = [
            'supplier_name'       => $ipmsHeader['supplier_name'],
            'warehouse_id'        => $warehouse_id,
            'date_c'              => $receivedDate,
            'date_add'            => date('Y-m-d'),
            'goods_receipt_code'  => $grn_ref,
            'total_tax_money'     => '0',
            'total_goods_money'   => '0',
            'value_of_inventory'  => '0',
            'total_money'         => '0',
            'addedfrom'           => (int) get_staff_user_id(),
            'approval'            => 1,
            'pr_order_id'         => isset($data['pr_order_id']) && $data['pr_order_id'] !== '' ? (int) $data['pr_order_id'] : null,
        ];

        $this->db->insert($p . 'goods_receipt', $addonHeader);
        $wh_grn_id = (int) $this->db->insert_id();
        if ($wh_grn_id < 1) {
            $this->db->trans_rollback();

            return false;
        }

        $this->db->where('id', $ipms_grn_id);
        $this->db->update($p . 'ipms_grn_log', ['warehouse_grn_id' => $wh_grn_id]);

        $total_cost = 0.0;
        $lineCount  = 0;

        foreach ($lines as $line) {
            $item_id = isset($line['item_id']) ? (int) $line['item_id'] : 0;
            $qty      = isset($line['qty_received']) ? (float) $line['qty_received'] : 0.0;
            $unit_price = isset($line['unit_price']) ? (float) $line['unit_price'] : -1.0;

            if ($item_id < 1 || $qty <= 0 || $unit_price < 0) {
                $this->db->trans_rollback();

                return false;
            }

            $item = inv_mgr_get_item($item_id);
            if (!$item) {
                $this->db->trans_rollback();

                return false;
            }

            $wacResult = inv_mgr_recalculate_wac($item_id, $warehouse_id, $qty, $unit_price);
            $line_total = round($qty * $unit_price, 2);
            $total_cost += $line_total;
            $lineCount++;

            $item_code = isset($line['item_code']) ? (string) $line['item_code'] : (string) ($item->commodity_code ?? '');
            $item_name = isset($line['item_name']) ? (string) $line['item_name'] : (string) ($item->description ?? '');
            $unit_sym  = isset($line['unit_symbol']) ? (string) $line['unit_symbol'] : (string) ($item->unit_symbol ?? '');
            $qty_ordered = isset($line['qty_ordered']) ? (float) $line['qty_ordered'] : 0.0;

            $this->db->insert($p . 'ipms_grn_lines', [
                'grn_id'        => $ipms_grn_id,
                'item_id'       => $item_id,
                'item_code'     => $item_code,
                'item_name'     => $item_name,
                'unit_symbol'   => $unit_sym,
                'qty_ordered'   => $qty_ordered,
                'qty_received'  => $qty,
                'unit_price'    => $unit_price,
                'line_total'    => $line_total,
                'wac_before'    => (float) $wacResult['wac_before'],
                'wac_after'     => (float) $wacResult['wac_after'],
                'stock_before'  => (float) $wacResult['stock_before'],
                'stock_after'   => (float) $wacResult['stock_after'],
            ]);

            if (!inv_mgr_update_stock_ledger($item_id, $warehouse_id, $qty)) {
                $this->db->trans_rollback();

                return false;
            }

            $qty_after_move = inv_mgr_get_stock_qty($item_id, $warehouse_id);

            inv_mgr_log_movement([
                'movement_type'   => 'grn',
                'item_id'         => $item_id,
                'item_code'       => $item_code,
                'item_name'       => $item_name,
                'warehouse_id'    => $warehouse_id,
                'qty_change'      => $qty,
                'qty_before'      => (float) $wacResult['stock_before'],
                'qty_after'       => $qty_after_move,
                'wac_at_movement' => (float) $wacResult['wac_after'],
                'value_change'    => $line_total,
                'rel_type'        => 'grn',
                'rel_id'          => $ipms_grn_id,
                'rel_ref'         => $grn_ref,
                'performed_by'    => (int) get_staff_user_id(),
                'performed_at'    => date('Y-m-d H:i:s'),
            ]);

            $detail = [
                'goods_receipt_id' => $wh_grn_id,
                'commodity_code'   => (string) ($item->commodity_code ?? ''),
                'commodity_name'   => (string) ($item->description ?? ''),
                'warehouse_id'     => (string) $warehouse_id,
                'unit_id'          => (string) ($item->unit_id ?? ''),
                'quantities'       => (string) $qty,
                'unit_price'       => (string) $unit_price,
                'goods_money'      => (string) $line_total,
                'tax'              => '0',
                'tax_money'        => '0',
            ];
            $this->db->insert($p . 'goods_receipt_detail', $detail);

            $txn = [
                'goods_receipt_id' => $wh_grn_id,
                'goods_id'         => $item_id,
                'commodity_id'     => $item_id,
                'warehouse_id'     => $warehouse_id,
                'quantity'         => (string) $qty,
                'date_add'         => date('Y-m-d H:i:s'),
                'status'           => 1,
                'note'             => $grn_ref,
            ];
            $this->db->insert($p . 'goods_transaction_detail', $txn);
        }

        $totStr = (string) round($total_cost, 2);
        $this->db->where('id', $wh_grn_id);
        $this->db->update($p . 'goods_receipt', [
            'total_goods_money'  => $totStr,
            'value_of_inventory' => $totStr,
            'total_money'        => $totStr,
        ]);

        $this->db->where('id', $ipms_grn_id);
        $this->db->update($p . 'ipms_grn_log', [
            'status'            => 'posted',
            'total_qty_lines'   => $lineCount,
            'total_cost_value'  => round($total_cost, 2),
            'wac_recalculated'  => 1,
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            return false;
        }

        $this->db->trans_commit();

        return $ipms_grn_id;
    }

    // --------------------------------------------------------------------
    // Job card material issue
    // --------------------------------------------------------------------

    /**
     * @param int   $job_card_id
     * @param int   $warehouse_id
     * @param array<int, array<string, mixed>> $lines
     * @param int   $actor_id
     * @return array<string, mixed>
     */
    public function issue_materials_for_job($job_card_id, $warehouse_id, $lines, $actor_id)
    {
        $p            = db_prefix();
        $job_card_id  = (int) $job_card_id;
        $warehouse_id = (int) $warehouse_id;
        $actor_id     = (int) $actor_id;

        $out = ['success' => false, 'errors' => [], 'total_cost' => 0.0, 'lines_count' => 0];

        if ($job_card_id < 1 || $warehouse_id < 1 || empty($lines)) {
            $out['errors'][] = 'Invalid job card, warehouse, or lines.';

            return $out;
        }

        $this->db->where('id', $job_card_id);
        $jobCard = $this->db->get($p . 'ipms_job_cards')->row();
        if (!$jobCard) {
            $out['errors'][] = 'Job card not found.';

            return $out;
        }

        $status = (int) $jobCard->status;
        if ($status < 1 || $status >= 6) {
            $out['errors'][] = 'Job card status does not allow material issue.';

            return $out;
        }

        $job_card_ref = (string) $jobCard->jc_ref;

        $issue_id = 0;
        foreach ((array) $lines as $line) {
            if (empty($line['line_id'])) {
                $out['errors'][] = 'Each line must include line_id.';

                return $out;
            }
            $this->db->where('id', (int) $line['line_id']);
            $dbLine = $this->db->get($p . 'ipms_jc_material_issue_lines')->row();
            if (!$dbLine || (int) $dbLine->job_card_id !== $job_card_id) {
                $out['errors'][] = 'Invalid material issue line.';

                return $out;
            }
            if ($issue_id === 0) {
                $issue_id = (int) $dbLine->issue_id;
            } elseif ((int) $dbLine->issue_id !== $issue_id) {
                $out['errors'][] = 'All lines must belong to the same material issue.';

                return $out;
            }
        }

        if ($issue_id < 1) {
            $out['errors'][] = 'Could not resolve material issue.';

            return $out;
        }

        $shortfalls = [];
        foreach ((array) $lines as $line) {
            $item_id   = isset($line['item_id']) ? (int) $line['item_id'] : 0;
            $qty_issue = isset($line['qty_issued']) ? (float) $line['qty_issued'] : 0.0;
            if ($item_id < 1 || $qty_issue <= 0) {
                $shortfalls[] = 'Invalid item or quantity on line ' . (isset($line['line_id']) ? (int) $line['line_id'] : 0);

                continue;
            }
            $on_hand = inv_mgr_get_stock_qty($item_id, $warehouse_id);
            if ($on_hand + 0.0001 < $qty_issue) {
                $shortfalls[] = 'Insufficient stock for item ID ' . $item_id . ' (need ' . $qty_issue . ', have ' . $on_hand . ').';
            }
        }

        if (!empty($shortfalls)) {
            $out['errors'] = $shortfalls;

            return $out;
        }

        $this->db->where('id', $issue_id);
        $issueRow = $this->db->get($p . 'ipms_jc_material_issues')->row();
        if (!$issueRow || (int) $issueRow->job_card_id !== $job_card_id) {
            $out['errors'][] = 'Material issue header not found.';

            return $out;
        }

        $issue_ref = (string) ($issueRow->issue_ref ?? '');
        if ($issue_ref === '' && function_exists('jc_generate_issue_ref')) {
            $issue_ref = jc_generate_issue_ref();
            $this->db->where('id', $issue_id);
            $this->db->update($p . 'ipms_jc_material_issues', ['issue_ref' => $issue_ref]);
        } elseif ($issue_ref === '') {
            $issue_ref = 'ISS-' . $issue_id;
        }

        $this->db->trans_begin();

        $total_cost = 0.0;
        $count      = 0;

        foreach ((array) $lines as $line) {
            $line_id   = (int) $line['line_id'];
            $item_id   = isset($line['item_id']) ? (int) $line['item_id'] : 0;
            $qty_issue = isset($line['qty_issued']) ? (float) $line['qty_issued'] : 0.0;
            if ($item_id < 1 || $qty_issue <= 0) {
                continue;
            }

            $wac       = inv_mgr_get_wac($item_id);
            $qty_before = inv_mgr_get_stock_qty($item_id, $warehouse_id);

            if (!inv_mgr_update_stock_ledger($item_id, $warehouse_id, -$qty_issue)) {
                $this->db->trans_rollback();
                $out['errors'][] = 'Stock ledger update failed for item ' . $item_id;

                return $out;
            }

            $qty_after = inv_mgr_get_stock_qty($item_id, $warehouse_id);
            $line_cost = round($qty_issue * $wac, 2);
            $total_cost += $line_cost;
            $count++;

            $item = inv_mgr_get_item($item_id);
            $item_code = isset($line['item_code']) ? (string) $line['item_code'] : ($item ? (string) ($item->commodity_code ?? '') : '');
            $item_name = isset($line['item_name']) ? (string) $line['item_name'] : ($item ? (string) ($item->description ?? '') : '');

            $this->db->where('id', $line_id);
            $this->db->update($p . 'ipms_jc_material_issue_lines', [
                'wac_at_issue'    => $wac,
                'line_total_cost' => $line_cost,
            ]);

            inv_mgr_log_movement([
                'movement_type'   => 'issue',
                'item_id'         => $item_id,
                'item_code'       => $item_code,
                'item_name'       => $item_name,
                'warehouse_id'    => $warehouse_id,
                'qty_change'      => -$qty_issue,
                'qty_before'      => $qty_before,
                'qty_after'       => $qty_after,
                'wac_at_movement' => $wac,
                'value_change'    => -$line_cost,
                'rel_type'        => 'job_card',
                'rel_id'          => $job_card_id,
                'rel_ref'         => $job_card_ref,
                'notes'           => 'Issue ' . $issue_ref,
                'performed_by'    => $actor_id > 0 ? $actor_id : (int) get_staff_user_id(),
                'performed_at'    => date('Y-m-d H:i:s'),
            ]);

            $txnQty = (string) (0 - $qty_issue);
            $this->db->insert($p . 'goods_transaction_detail', [
                'goods_receipt_id' => $issue_id,
                'goods_id'         => $item_id,
                'commodity_id'     => $item_id,
                'warehouse_id'     => $warehouse_id,
                'quantity'         => $txnQty,
                'date_add'         => date('Y-m-d H:i:s'),
                'status'           => 2,
                'note'             => 'Job Card Issue: ' . $job_card_ref,
            ]);
        }

        $this->db->where('id', $issue_id);
        $this->db->update($p . 'ipms_jc_material_issues', [
            'total_cost_value' => round($total_cost, 2),
            'status'           => 'issued',
            'warehouse_id'   => $warehouse_id,
        ]);

        $this->db->where('id', $job_card_id);
        $this->db->update($p . 'ipms_job_cards', [
            'materials_issued'    => 1,
            'materials_issued_at' => date('Y-m-d H:i:s'),
            'materials_issued_by' => $actor_id > 0 ? $actor_id : (int) get_staff_user_id(),
            'status'              => 2,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert($p . 'ipms_jc_status_log', [
            'job_card_id'     => $job_card_id,
            'from_status'     => $status,
            'to_status'       => 2,
            'changed_by'      => $actor_id > 0 ? $actor_id : (int) get_staff_user_id(),
            'changed_by_name' => get_staff_full_name($actor_id > 0 ? $actor_id : (int) get_staff_user_id()),
            'changed_by_role' => $this->get_staff_role_name($actor_id > 0 ? $actor_id : (int) get_staff_user_id()),
            'notes'           => 'Materials issued via ' . $issue_ref,
            'changed_at'      => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $out['errors'][] = 'Transaction failed.';

            return $out;
        }

        $this->db->trans_commit();

        $out['success']     = true;
        $out['total_cost']  = round($total_cost, 2);
        $out['lines_count'] = $count;
        $out['issue_ref']   = $issue_ref;

        return $out;
    }

    /**
     * @param int $staff_id
     * @return string
     */
    protected function get_staff_role_name($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id < 1) {
            return '';
        }
        $p = db_prefix();
        $this->db->select('r.name');
        $this->db->from($p . 'staff s');
        $this->db->join($p . 'roles r', 'r.roleid = s.role', 'left');
        $this->db->where('s.staffid', $staff_id);
        $row = $this->db->get()->row();

        return $row && isset($row->name) ? (string) $row->name : '';
    }

    // --------------------------------------------------------------------
    // Adjustments
    // --------------------------------------------------------------------

    /**
     * @param int|string $id
     * @param array      $filters
     * @return object|array|false
     */
    public function get_adjustment($id = '', $filters = [])
    {
        $p = db_prefix();

        if (is_numeric($id) && (int) $id > 0) {
            $this->db->where('id', (int) $id);
            $adj = $this->db->get($p . 'ipms_stock_adjustments')->row();
            if (!$adj) {
                return false;
            }
            $this->db->where('adj_id', (int) $id);
            $this->db->order_by('id', 'ASC');
            $adj->lines = $this->db->get($p . 'ipms_stock_adjustment_lines')->result();

            return $adj;
        }

        $this->db->from($p . 'ipms_stock_adjustments a');
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('a.warehouse_id', (int) $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('a.status', (string) $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('a.created_at >=', (string) $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('a.created_at <=', (string) $filters['date_to'] . ' 23:59:59');
        }
        $this->db->order_by('a.created_at', 'DESC');

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$r) {
            $aid = (int) $r['id'];
            $this->db->where('adj_id', $aid);
            $this->db->order_by('id', 'ASC');
            $r['lines'] = $this->db->get($p . 'ipms_stock_adjustment_lines')->result_array();
        }
        unset($r);

        return $rows;
    }

    /**
     * @param array<string, mixed>        $data
     * @param array<int, array<string, mixed>> $lines
     * @return int|false
     */
    public function create_adjustment($data, $lines)
    {
        $p = db_prefix();
        if (!is_array($data) || !is_array($lines) || empty($lines)) {
            return false;
        }

        $warehouse_id = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : 0;
        $adj_type     = isset($data['adj_type']) ? (string) $data['adj_type'] : '';
        $reason       = isset($data['reason']) ? trim((string) $data['reason']) : '';
        $requested_by = isset($data['requested_by']) ? (int) $data['requested_by'] : (int) get_staff_user_id();

        if ($warehouse_id < 1 || !in_array($adj_type, ['write_off', 'write_up'], true) || $reason === '') {
            return false;
        }

        $total_value = 0.0;
        $lineRows    = [];
        foreach ($lines as $line) {
            $item_id    = isset($line['item_id']) ? (int) $line['item_id'] : 0;
            $qty_adjust = isset($line['qty_adjust']) ? (float) $line['qty_adjust'] : 0.0;
            if ($item_id < 1 || $qty_adjust <= 0) {
                continue;
            }
            $item = inv_mgr_get_item($item_id);
            if (!$item) {
                continue;
            }
            $wac = inv_mgr_get_wac($item_id);
            $lv  = round($qty_adjust * $wac, 2);
            $total_value += $lv;
            $lineRows[] = [
                'item_id'       => $item_id,
                'item_code'     => (string) ($item->commodity_code ?? ''),
                'item_name'     => (string) ($item->description ?? ''),
                'unit_symbol'   => (string) ($item->unit_symbol ?? ''),
                'qty_current'   => inv_mgr_get_stock_qty($item_id, $warehouse_id),
                'qty_adjust'    => $qty_adjust,
                'wac_at_adj'    => $wac,
                'line_value'    => $lv,
                'reason_notes'  => isset($line['reason_notes']) ? (string) $line['reason_notes'] : null,
            ];
        }

        if (empty($lineRows)) {
            return false;
        }

        $threshold = (float) inv_mgr_setting('adj_approval_threshold', '0');
        $status    = abs($total_value) > $threshold ? 'pending_approval' : 'draft';

        $adj_ref = inv_mgr_generate_ref('adj');

        $header = [
            'adj_ref'          => $adj_ref,
            'warehouse_id'   => $warehouse_id,
            'adj_type'         => $adj_type,
            'reason'           => $reason,
            'status'           => $status,
            'requested_by'     => $requested_by,
            'total_qty_lines'  => count($lineRows),
            'total_value'      => round($total_value, 2),
        ];

        $this->db->insert($p . 'ipms_stock_adjustments', $header);
        $adj_id = (int) $this->db->insert_id();
        if ($adj_id < 1) {
            return false;
        }

        foreach ($lineRows as $lr) {
            $lr['adj_id'] = $adj_id;
            $this->db->insert($p . 'ipms_stock_adjustment_lines', $lr);
        }

        if ($status === 'pending_approval') {
            foreach (['Store Manager', 'General Manager'] as $roleName) {
                $staffList = inv_mgr_get_staff_by_role($roleName);
                foreach ($staffList as $st) {
                    if (function_exists('add_notification')) {
                        add_notification([
                            'description' => 'Stock adjustment ' . $adj_ref . ' pending approval',
                            'touserid'    => (int) $st->staffid,
                            'fromuserid'  => 0,
                            'link'        => 'inventory_mgr/adjustments',
                        ]);
                    }
                }
            }
        }

        return $adj_id;
    }

    /**
     * @param int $adj_id
     * @param int $approver_id
     * @return bool
     */
    public function approve_adjustment($adj_id, $approver_id)
    {
        $p = db_prefix();
        $this->db->where('id', (int) $adj_id);
        $adj = $this->db->get($p . 'ipms_stock_adjustments')->row();
        if (!$adj || !in_array((string) $adj->status, ['pending_approval', 'draft'], true)) {
            return false;
        }

        $this->db->where('id', (int) $adj_id);
        $this->db->update($p . 'ipms_stock_adjustments', [
            'status'      => 'approved',
            'approved_by' => (int) $approver_id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * @param int $adj_id
     * @return bool
     */
    public function post_adjustment($adj_id)
    {
        $p      = db_prefix();
        $adj_id = (int) $adj_id;

        $this->db->where('id', $adj_id);
        $adj = $this->db->get($p . 'ipms_stock_adjustments')->row();
        if (!$adj) {
            return false;
        }

        $st = (string) $adj->status;
        if (!in_array($st, ['approved', 'draft'], true)) {
            return false;
        }

        $this->db->where('adj_id', $adj_id);
        $lines = $this->db->get($p . 'ipms_stock_adjustment_lines')->result();
        if (empty($lines)) {
            return false;
        }

        $warehouse_id = (int) $adj->warehouse_id;
        $adj_type      = (string) $adj->adj_type;
        $adj_ref       = (string) $adj->adj_ref;

        $this->db->trans_begin();

        foreach ($lines as $ln) {
            $item_id    = (int) $ln->item_id;
            $qty_adjust = (float) $ln->qty_adjust;
            if ($item_id < 1 || $qty_adjust <= 0) {
                continue;
            }

            $qty_change = $adj_type === 'write_off' ? -$qty_adjust : $qty_adjust;
            $qty_before = inv_mgr_get_stock_qty($item_id, $warehouse_id);

            if (!inv_mgr_update_stock_ledger($item_id, $warehouse_id, $qty_change)) {
                $this->db->trans_rollback();

                return false;
            }

            $qty_after = inv_mgr_get_stock_qty($item_id, $warehouse_id);
            $wac       = inv_mgr_get_wac($item_id);
            $valueChg  = round($qty_change * $wac, 2);

            $movType = $qty_change >= 0 ? 'adjustment_in' : 'adjustment_out';

            inv_mgr_log_movement([
                'movement_type'   => $movType,
                'item_id'         => $item_id,
                'item_code'       => (string) ($ln->item_code ?? ''),
                'item_name'       => (string) ($ln->item_name ?? ''),
                'warehouse_id'    => $warehouse_id,
                'qty_change'      => (float) $qty_change,
                'qty_before'      => $qty_before,
                'qty_after'       => $qty_after,
                'wac_at_movement' => $wac,
                'value_change'    => (float) $valueChg,
                'rel_type'        => 'adjustment',
                'rel_id'          => $adj_id,
                'rel_ref'         => $adj_ref,
                'performed_by'    => (int) get_staff_user_id(),
                'performed_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->where('id', $adj_id);
        $this->db->update($p . 'ipms_stock_adjustments', ['status' => 'posted']);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            return false;
        }

        $this->db->trans_commit();

        return true;
    }

    // --------------------------------------------------------------------
    // Reporting
    // --------------------------------------------------------------------

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function get_movements($filters = [])
    {
        $p = db_prefix();
        if (!is_array($filters)) {
            $filters = [];
        }

        $this->db->select(
            'sm.*, i.description AS item_name, i.commodity_code, u.unit_symbol, w.warehouse_name, '
            . 's.firstname AS staff_firstname, s.lastname AS staff_lastname',
            false
        );
        $this->db->from($p . 'ipms_stock_movements sm');
        $this->db->join($p . 'items i', 'i.id = sm.item_id', 'left');
        $this->db->join($p . 'ware_unit_type u', 'u.unit_type_id = i.unit_id', 'left');
        $this->db->join($p . 'warehouse w', 'w.warehouse_id = sm.warehouse_id', 'left');
        $this->db->join($p . 'staff s', 's.staffid = sm.performed_by', 'left');

        if (!empty($filters['item_id'])) {
            $this->db->where('sm.item_id', (int) $filters['item_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('sm.warehouse_id', (int) $filters['warehouse_id']);
        }
        if (!empty($filters['movement_type'])) {
            $this->db->where('sm.movement_type', (string) $filters['movement_type']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('sm.performed_at >=', (string) $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('sm.performed_at <=', (string) $filters['date_to'] . ' 23:59:59');
        }

        $this->db->order_by('sm.performed_at', 'DESC');
        $this->db->limit(500);

        return $this->db->get()->result_array();
    }

    /**
     * @param int|null $warehouse_id Unused; report uses fixed warehouse IDs 1 and 2 per spec.
     * @return array<int, array<string, mixed>>
     */
    public function get_stock_valuation_report($warehouse_id = null)
    {
        $p = db_prefix();

        $sql = 'SELECT i.id, i.commodity_code, i.description, i.purchase_price AS wac,
            u.unit_symbol, ct.commondity_name AS category,
            COALESCE(SUM(CASE WHEN im.warehouse_id = 1 THEN CAST(im.inventory_number AS DECIMAL(15,4)) ELSE 0 END), 0) AS blantyre_qty,
            COALESCE(SUM(CASE WHEN im.warehouse_id = 2 THEN CAST(im.inventory_number AS DECIMAL(15,4)) ELSE 0 END), 0) AS lilongwe_qty,
            COALESCE(SUM(CAST(im.inventory_number AS DECIMAL(15,4))), 0) AS total_qty
            FROM `' . $p . 'items` i
            LEFT JOIN `' . $p . 'inventory_manage` im ON im.commodity_id = i.id
            LEFT JOIN `' . $p . 'ware_unit_type` u ON u.unit_type_id = i.unit_id
            LEFT JOIN `' . $p . 'ware_commodity_type` ct ON ct.commodity_type_id = i.commodity_type
            GROUP BY i.id
            ORDER BY i.description ASC';

        $rows = $this->db->query($sql)->result_array();

        foreach ($rows as &$r) {
            $wac = isset($r['wac']) ? (float) $r['wac'] : 0.0;
            $bq  = isset($r['blantyre_qty']) ? (float) $r['blantyre_qty'] : 0.0;
            $lq  = isset($r['lilongwe_qty']) ? (float) $r['lilongwe_qty'] : 0.0;
            $tq  = isset($r['total_qty']) ? (float) $r['total_qty'] : 0.0;

            $r['blantyre_value'] = round($bq * $wac, 2);
            $r['lilongwe_value'] = round($lq * $wac, 2);
            $r['total_value']    = round($tq * $wac, 2);
        }

        return $rows;
    }
}

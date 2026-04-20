<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_mgr extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!function_exists('inv_mgr_staff_can_access_inventory')) {
            $this->load->helper('inventory_mgr/inventory_mgr');
        }

        $this->load->model('inventory_mgr/inventory_mgr_model');
        $this->load->helper('inventory_mgr/inventory_mgr');

        $lang = isset($GLOBALS['language']) ? $GLOBALS['language'] : 'english';
        if (file_exists(module_dir_path('inventory_mgr', 'language/' . $lang . '/inventory_mgr_lang.php'))) {
            $this->lang->load('inventory_mgr/inventory_mgr', $lang);
        } else {
            $this->lang->load('inventory_mgr/inventory_mgr', 'english');
        }
    }

    /**
     * @return void
     */
    protected function ensure_inventory_access()
    {
        if (!inv_mgr_staff_can_access_inventory()) {
            set_alert('danger', 'Access denied.');
            redirect(admin_url());
        }
    }

    /**
     * @param array<int, string> $roles
     * @return bool
     */
    protected function staff_role_in(array $roles)
    {
        if (function_exists('is_admin') && is_admin()) {
            return true;
        }

        $r = inv_mgr_get_current_staff_role();

        return $r !== '' && in_array($r, $roles, true);
    }

    /**
     * Add Blantyre/Lilongwe qty columns, reorder hints, and low-stock flag for the stock master table.
     *
     * @param array<int, array<string, mixed>>|mixed $items
     * @return array<int, array<string, mixed>>
     */
    protected function enrich_items_for_list($items)
    {
        if (!is_array($items)) {
            return [];
        }
        if ($items === []) {
            return $items;
        }

        $p   = db_prefix();
        $ids = [];
        foreach ($items as $it) {
            if (!empty($it['id'])) {
                $ids[] = (int) $it['id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return $items;
        }

        $icmMap = [];
        $icmTable = $p . 'inventory_commodity_min';
        if ($this->db->table_exists($icmTable) && $this->db->field_exists('commodity_id', $icmTable)) {
            $this->db->where_in('commodity_id', $ids);
            $icmQ = $this->db->get($icmTable);
            $icmRows = ($icmQ !== false) ? $icmQ->result_array() : [];
            if ($icmQ === false) {
                log_message('error', 'enrich_items_for_list inventory_commodity_min: ' . json_encode($this->db->error()));
            }
            foreach ($icmRows as $r) {
                $cid = (int) ($r['commodity_id'] ?? 0);
                if ($cid < 1) {
                    continue;
                }
                $whRaw = $r['warehouse_id'] ?? null;
                $whKey  = ($whRaw === null || $whRaw === '') ? '_g' : (int) $whRaw;
                $min    = isset($r['inventory_number_min']) ? (float) $r['inventory_number_min'] : 0.0;
                if (!isset($icmMap[$cid][$whKey]) || $min < $icmMap[$cid][$whKey]) {
                    $icmMap[$cid][$whKey] = $min;
                }
            }
        }

        $lowIds = [];
        foreach (inv_mgr_get_low_stock_items() as $ls) {
            if (!empty($ls['id'])) {
                $lowIds[(int) $ls['id']] = true;
            }
        }

        foreach ($items as &$it) {
            $cid = (int) ($it['id'] ?? 0);
            $wq  = isset($it['warehouse_qty']) && is_array($it['warehouse_qty']) ? $it['warehouse_qty'] : [];
            $it['qty_blantyre'] = (float) ($wq[1] ?? 0);
            $it['qty_lilongwe'] = (float) ($wq[2] ?? 0);

            $gMin = isset($icmMap[$cid]['_g']) ? (float) $icmMap[$cid]['_g'] : null;
            $m1   = isset($icmMap[$cid][1]) ? (float) $icmMap[$cid][1] : $gMin;
            $m2   = isset($icmMap[$cid][2]) ? (float) $icmMap[$cid][2] : $gMin;
            $it['reorder_wh1']   = $m1;
            $it['reorder_wh2']   = $m2;
            $mins                = [];
            if (!empty($icmMap[$cid])) {
                foreach ($icmMap[$cid] as $v) {
                    $mins[] = (float) $v;
                }
            }
            $it['reorder_level'] = $mins !== [] ? min($mins) : null;
            $it['is_low_flag']   = isset($lowIds[$cid]);
            $it['qty_total']     = (float) ($it['total_qty'] ?? 0);
        }
        unset($it);

        return $items;
    }

    /**
     * @return string
     */
    protected function generate_next_commodity_code()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'items') || !$this->db->field_exists('commodity_code', $p . 'items')) {
            return 'ITEM-00001';
        }
        $this->db->select('commodity_code');
        $this->db->from($p . 'items');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $q = $this->db->get();
        $row = ($q !== false) ? $q->row() : null;
        $last = $row && isset($row->commodity_code) ? (string) $row->commodity_code : '';

        if ($last !== '' && preg_match('/^(.*?)(\d+)$/', $last, $m)) {
            $prefix = $m[1];
            $num    = (int) $m[2];
            $width  = strlen($m[2]);

            return $prefix . str_pad((string) ($num + 1), $width, '0', STR_PAD_LEFT);
        }

        return 'ITEM-' . str_pad((string) ((int) $this->db->count_all($p . 'items') + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * JSON: { "next_code": "..." } for item code generator on the item form.
     */
    public function ajax_next_item_code()
    {
        $this->ensure_inventory_access();
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode(['next_code' => $this->generate_next_commodity_code()]));
    }

    /**
     * Quick-add category (warehouse addon table).
     */
    public function quick_add_category()
    {
        $this->ensure_inventory_access();

        if (!$this->input->post()) {
            $this->output->set_status_header(405)->set_content_type('application/json')->set_output(json_encode(['success' => false]));

            return;
        }

        $name = trim((string) $this->input->post('commondity_name'));
        $code = trim((string) $this->input->post('commondity_code'));
        if ($name === '') {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Name required']));

            return;
        }

        $p = db_prefix();
        $t = $p . 'ware_commodity_type';
        if (!$this->db->table_exists($t)) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Category table is missing. Install/activate the Warehouse module (or create tblware_commodity_type).',
            ]));

            return;
        }
        $this->db->insert($t, [
            'commondity_code' => $code !== '' ? $code : null,
            'commondity_name' => $name,
            'display'         => 1,
        ]);
        $id = (int) $this->db->insert_id();
        if ($id < 1) {
            $err = $this->db->error();
            $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => !empty($err['message']) ? $err['message'] : 'Insert failed',
            ]));

            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success'    => true,
            'id'         => $id,
            'label'      => $name,
            'csrf_hash'  => $this->security->get_csrf_hash(),
        ]));
    }

    /**
     * Quick-add unit (warehouse addon table).
     */
    public function quick_add_unit()
    {
        $this->ensure_inventory_access();

        if (!$this->input->post()) {
            $this->output->set_status_header(405)->set_content_type('application/json')->set_output(json_encode(['success' => false]));

            return;
        }

        $name   = trim((string) $this->input->post('unit_name'));
        $code   = trim((string) $this->input->post('unit_code'));
        $symbol = trim((string) $this->input->post('unit_symbol'));
        if ($name === '') {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Name required']));

            return;
        }

        $p = db_prefix();
        $t = $p . 'ware_unit_type';
        if (!$this->db->table_exists($t)) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Unit table is missing. Install/activate the Warehouse module (or create tblware_unit_type).',
            ]));

            return;
        }
        $this->db->insert($t, [
            'unit_code'   => $code !== '' ? $code : null,
            'unit_name'   => $name,
            'unit_symbol' => $symbol !== '' ? $symbol : $name,
            'display'     => 1,
        ]);
        $id = (int) $this->db->insert_id();
        if ($id < 1) {
            $err = $this->db->error();
            $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => !empty($err['message']) ? $err['message'] : 'Insert failed',
            ]));

            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success'    => true,
            'id'         => $id,
            'label'      => $name . ($symbol !== '' ? ' (' . $symbol . ')' : ''),
            'csrf_hash'  => $this->security->get_csrf_hash(),
        ]));
    }

    // --------------------------------------------------------------------
    // Item master
    // --------------------------------------------------------------------

    public function items()
    {
        $this->ensure_inventory_access();

        $filters = [
            'category'       => $this->input->get('category') ? (int) $this->input->get('category') : null,
            'group'          => $this->input->get('group') ? (int) $this->input->get('group') : null,
            'warehouse'      => $this->input->get('warehouse') ? (int) $this->input->get('warehouse') : null,
            'search_term'    => $this->input->get('search') ? trim((string) $this->input->get('search')) : null,
            'low_stock_only' => (string) $this->input->get('low_stock') === '1',
        ];

        try {
            $data['title']            = 'Stock Master — All Items';
            $list                     = $this->inventory_mgr_model->get_item('', $filters);
            $data['items']            = $this->enrich_items_for_list(is_array($list) ? $list : []);
            $data['low_stock_items']  = inv_mgr_get_low_stock_items();
            $data['filters']          = $filters;
            $data['categories']       = inv_mgr_get_categories();
            $data['warehouses']       = inv_mgr_get_warehouses();
            $data['item_groups']      = [];
            $igTable = db_prefix() . 'items_groups';
            if ($this->db->table_exists($igTable)) {
                if ($this->db->field_exists('name', $igTable)) {
                    $this->db->order_by('name', 'ASC');
                }
                $igQ = $this->db->get($igTable);
                $data['item_groups'] = ($igQ !== false) ? $igQ->result_array() : [];
                if ($igQ === false) {
                    log_message('error', 'inventory_mgr/items items_groups: ' . json_encode($this->db->error()));
                }
            }

            $this->load->view('inventory_mgr/items_list', $data);
        } catch (Throwable $e) {
            log_message('error', 'inventory_mgr/items: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            show_error('Stock Master could not load. ' . $e->getMessage(), 500, 'Inventory');
        }
    }

    public function add_item()
    {
        $this->ensure_inventory_access();

        if ($this->input->post()) {
            $post = $this->input->post();
            $row  = [
                'commodity_code'        => isset($post['commodity_code']) ? trim((string) $post['commodity_code']) : '',
                'description'           => isset($post['description']) ? $post['description'] : '',
                'unit_id'               => isset($post['unit_id']) ? $post['unit_id'] : '',
                'purchase_price'        => isset($post['purchase_price']) ? $post['purchase_price'] : '',
                'rate'                  => isset($post['rate']) ? $post['rate'] : null,
                'reorder_level'         => isset($post['reorder_level']) ? $post['reorder_level'] : 0,
                'group_id'              => isset($post['group_id']) ? $post['group_id'] : 0,
                'commodity_type'        => isset($post['commodity_type']) ? $post['commodity_type'] : null,
                'long_description'      => isset($post['long_description']) ? $post['long_description'] : '',
                'commodity_barcode'     => isset($post['commodity_barcode']) ? trim((string) $post['commodity_barcode']) : '',
                'default_warehouse_id'  => isset($post['default_warehouse_id']) ? (int) $post['default_warehouse_id'] : 0,
                'active'                => isset($post['active']) && (string) $post['active'] === '1' ? 1 : 0,
                'opening_qty'           => isset($post['opening_qty']) ? $post['opening_qty'] : 0,
                'opening_warehouse_id'  => isset($post['opening_warehouse_id']) ? $post['opening_warehouse_id'] : 0,
                'tax'                   => isset($post['tax']) ? $post['tax'] : null,
                'taxrate'               => isset($post['taxrate']) ? $post['taxrate'] : null,
            ];

            $id = $this->inventory_mgr_model->add_item($row);
            if ($id) {
                set_alert('success', 'Item created successfully.');
                if (!empty($post['save_and_add'])) {
                    redirect(admin_url('inventory_mgr/add_item'));
                } else {
                    redirect(admin_url('inventory_mgr/view_item/' . (int) $id));
                }
            }

            set_alert('danger', 'Could not create item. Check required fields and unique item code.');
            redirect(admin_url('inventory_mgr/add_item'));
        }

        $p = db_prefix();
        $data['title']          = 'Add New Inventory Item';
        $data['edit']           = false;
        $data['item']           = null;
        $data['categories']     = inv_mgr_get_categories();
        $data['units']          = inv_mgr_get_units();
        $data['warehouses']     = inv_mgr_get_warehouses();
        $data['has_barcode']    = $this->db->field_exists('commodity_barcode', $p . 'items');
        $data['has_active']     = $this->db->field_exists('active', $p . 'items');
        $data['item_groups']    = [];
        $igTbl = $p . 'items_groups';
        if ($this->db->table_exists($igTbl)) {
            if ($this->db->field_exists('name', $igTbl)) {
                $this->db->order_by('name', 'ASC');
            }
            $igQ = $this->db->get($igTbl);
            $data['item_groups'] = ($igQ !== false) ? $igQ->result_array() : [];
        }

        $this->load->view('inventory_mgr/item_form', $data);
    }

    public function edit_item($id)
    {
        $this->ensure_inventory_access();

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $item = $this->inventory_mgr_model->get_item($id);
        if (!$item) {
            show_404();
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $row  = [
                'commodity_code'       => isset($post['commodity_code']) ? trim((string) $post['commodity_code']) : '',
                'description'          => isset($post['description']) ? $post['description'] : '',
                'unit_id'              => isset($post['unit_id']) ? $post['unit_id'] : '',
                'purchase_price'       => isset($post['purchase_price']) ? $post['purchase_price'] : '',
                'rate'                 => isset($post['rate']) ? $post['rate'] : null,
                'reorder_level'        => isset($post['reorder_level']) ? $post['reorder_level'] : 0,
                'group_id'             => isset($post['group_id']) ? $post['group_id'] : 0,
                'commodity_type'       => isset($post['commodity_type']) ? $post['commodity_type'] : null,
                'long_description'     => isset($post['long_description']) ? $post['long_description'] : '',
                'commodity_barcode'    => isset($post['commodity_barcode']) ? trim((string) $post['commodity_barcode']) : '',
                'default_warehouse_id' => isset($post['default_warehouse_id']) ? (int) $post['default_warehouse_id'] : 0,
                'active'               => isset($post['active']) && (string) $post['active'] === '1' ? 1 : 0,
                'tax'                  => isset($post['tax']) ? $post['tax'] : null,
                'taxrate'              => isset($post['taxrate']) ? $post['taxrate'] : null,
            ];

            $ok = $this->inventory_mgr_model->update_item($id, $row);
            set_alert($ok ? 'success' : 'danger', $ok ? 'Item updated.' : 'Could not update item (duplicate code?).');
            if ($ok && !empty($post['save_and_add'])) {
                redirect(admin_url('inventory_mgr/add_item'));
            }
            redirect(admin_url('inventory_mgr/edit_item/' . $id));
        }

        $p = db_prefix();
        $data['title']          = 'Edit: ' . ($item->description ?? 'Item');
        $data['edit']           = true;
        $data['item']           = $item;
        $data['categories']     = inv_mgr_get_categories();
        $data['units']          = inv_mgr_get_units();
        $data['warehouses']     = inv_mgr_get_warehouses();
        $data['has_barcode']    = $this->db->field_exists('commodity_barcode', $p . 'items');
        $data['has_active']     = $this->db->field_exists('active', $p . 'items');
        $data['item_groups']    = [];
        $igTbl = $p . 'items_groups';
        if ($this->db->table_exists($igTbl)) {
            if ($this->db->field_exists('name', $igTbl)) {
                $this->db->order_by('name', 'ASC');
            }
            $igQ = $this->db->get($igTbl);
            $data['item_groups'] = ($igQ !== false) ? $igQ->result_array() : [];
        }

        $this->load->view('inventory_mgr/item_form', $data);
    }

    public function view_item($id)
    {
        $this->ensure_inventory_access();

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $item = $this->inventory_mgr_model->get_item($id);
        if (!$item) {
            show_404();
        }

        $data['title'] = 'Item: ' . html_escape($item->description ?? '');
        $data['item']  = $item;

        $this->load->view('inventory_mgr/item_view', $data);
    }

    public function delete_item($id)
    {
        $this->ensure_inventory_access();

        if (!$this->input->post() || $this->input->method(true) !== 'POST') {
            show_error('Invalid request', 405);
        }

        if (!function_exists('is_admin') || !is_admin()) {
            set_alert('danger', 'Only administrators can delete items.');
            redirect(admin_url('inventory_mgr/view_item/' . (int) $id));
        }

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $p = db_prefix();

        $stocks = inv_mgr_get_all_stock($id);
        $total  = 0.0;
        foreach ($stocks as $s) {
            $total += (float) $s['qty'];
        }

        if ($total > 0.0001) {
            set_alert('danger', 'Cannot delete: item still has stock on hand.');
            redirect(admin_url('inventory_mgr/view_item/' . $id));
        }

        $this->db->select('l.id');
        $this->db->from($p . 'ipms_jc_material_issue_lines l');
        $this->db->join($p . 'ipms_jc_material_issues i', 'i.id = l.issue_id', 'inner');
        $this->db->where('l.inventory_item_id', $id);
        $this->db->where_in('i.status', ['draft', 'issued']);
        if ($this->db->get()->num_rows() > 0) {
            set_alert('danger', 'Cannot delete: item is referenced on a pending material issue.');
            redirect(admin_url('inventory_mgr/view_item/' . $id));
        }

        $this->db->trans_begin();
        $this->db->where('commodity_id', $id);
        $this->db->delete($p . 'inventory_manage');
        $this->db->where('commodity_id', $id);
        $this->db->delete($p . 'inventory_commodity_min');
        $this->db->where('id', $id);
        $this->db->delete($p . 'items');

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            set_alert('danger', 'Delete failed.');
            redirect(admin_url('inventory_mgr/view_item/' . $id));
        }

        $this->db->trans_commit();
        set_alert('success', 'Item deleted.');
        redirect(admin_url('inventory_mgr/items'));
    }

    public function search_items_ajax()
    {
        $this->ensure_inventory_access();

        if ((string) $this->input->get('auto_code') === '1') {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['next_code' => $this->generate_next_commodity_code()]));

            return;
        }

        $term         = (string) $this->input->get('term');
        $warehouseRaw = $this->input->get('warehouse_id');
        $warehouse_id = $warehouseRaw !== null && $warehouseRaw !== '' ? (int) $warehouseRaw : null;
        $with_stock   = (string) $this->input->get('with_stock') === '1';

        $rows = inv_mgr_search_items($term, $warehouse_id, $with_stock);

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($rows));
    }

    public function get_item_info()
    {
        $this->ensure_inventory_access();

        $item_id      = (int) $this->input->get('item_id');
        $warehouse_id = (int) $this->input->get('warehouse_id');

        if ($item_id < 1 || $warehouse_id < 1) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => 'item_id and warehouse_id required']));

            return;
        }

        $item = inv_mgr_get_item($item_id);
        if (!$item) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => 'Item not found']));

            return;
        }

        $wac   = inv_mgr_get_wac($item_id);
        $stock = inv_mgr_get_stock_qty($item_id, $warehouse_id);

        $this->db->select('inventory_number_min');
        $this->db->from(db_prefix() . 'inventory_commodity_min');
        $this->db->where('commodity_id', $item_id);
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        $minRow       = $this->db->get()->row();
        $reorder      = $minRow && $minRow->inventory_number_min !== '' && $minRow->inventory_number_min !== null
            ? (float) $minRow->inventory_number_min
            : 0.0;
        $is_low_stock = $reorder > 0 && $stock <= $reorder;

        $out = [
            'id'             => $item_id,
            'commodity_code' => (string) ($item->commodity_code ?? ''),
            'description'    => (string) ($item->description ?? ''),
            'unit_symbol'    => (string) ($item->unit_symbol ?? ''),
            'wac'            => (float) $wac,
            'sell_price'     => isset($item->rate) ? (float) $item->rate : 0.0,
            'stock_qty'      => (float) $stock,
            'reorder_level'  => (float) $reorder,
            'is_low_stock'   => (bool) $is_low_stock,
        ];

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($out));
    }

    // --------------------------------------------------------------------
    // GRN
    // --------------------------------------------------------------------

    public function grn()
    {
        $this->ensure_inventory_access();

        $filters = [
            'warehouse_id' => $this->input->get('warehouse_id') ? (int) $this->input->get('warehouse_id') : null,
            'status'       => $this->input->get('status') ? (string) $this->input->get('status') : null,
            'date_from'    => $this->input->get('date_from') ? (string) $this->input->get('date_from') : null,
            'date_to'      => $this->input->get('date_to') ? (string) $this->input->get('date_to') : null,
            'supplier_name'=> $this->input->get('supplier') ? trim((string) $this->input->get('supplier')) : null,
        ];

        $data['title']       = 'Goods Receipt (GRN)';
        $data['grns']        = $this->inventory_mgr_model->get_grn('', $filters);
        $data['filters']     = $filters;
        $data['warehouses']  = inv_mgr_get_warehouses();

        $this->load->view('inventory_mgr/grn_list', $data);
    }

    public function add_grn()
    {
        $this->ensure_inventory_access();

        if ($this->input->post()) {
            $post  = $this->input->post();
            $lines = $this->parse_grn_lines_from_post($post);

            $data = [
                'warehouse_id'  => isset($post['warehouse_id']) ? (int) $post['warehouse_id'] : 0,
                'received_at'   => isset($post['received_at']) ? (string) $post['received_at'] : '',
                'supplier_name' => isset($post['supplier_name']) ? (string) $post['supplier_name'] : '',
                'supplier_ref'  => isset($post['supplier_ref']) ? (string) $post['supplier_ref'] : '',
                'po_ref'        => isset($post['po_ref']) ? (string) $post['po_ref'] : '',
                'notes'         => isset($post['notes']) ? (string) $post['notes'] : '',
                'pr_order_id'   => isset($post['pr_order_id']) ? $post['pr_order_id'] : null,
            ];

            $grnId = $this->inventory_mgr_model->create_grn($data, $lines);
            if ($grnId) {
                set_alert('success', 'GRN posted and WAC updated.');
                redirect(admin_url('inventory_mgr/view_grn/' . (int) $grnId));
            }

            set_alert('danger', 'Could not post GRN. Check warehouse, date, and line items.');
            redirect(admin_url('inventory_mgr/add_grn'));
        }

        $data['title']            = 'Receive Stock — New Goods Receipt Note (GRN)';
        $data['warehouses']      = inv_mgr_get_warehouses();
        $data['prefill_item_id'] = (int) $this->input->get('item_id');
        $data['supplier_history'] = $this->get_distinct_grn_supplier_names();

        $this->load->view('inventory_mgr/grn_form', $data);
    }

    public function view_grn($id)
    {
        $this->ensure_inventory_access();

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $grn = $this->inventory_mgr_model->get_grn($id);
        if (!$grn) {
            show_404();
        }

        $data['title']         = 'GRN: ' . html_escape($grn->grn_ref ?? '');
        $data['grn']          = $grn;
        $data['received_by_name'] = function_exists('get_staff_full_name')
            ? get_staff_full_name((int) ($grn->received_by ?? 0))
            : '';

        $this->load->view('inventory_mgr/grn_view', $data);
    }

    // --------------------------------------------------------------------
    // Material issue (job cards)
    // --------------------------------------------------------------------

    public function issue_form($job_card_id)
    {
        $this->ensure_inventory_access();

        if (module_dir_path('job_cards', 'helpers/job_cards_helper.php')) {
            if (!function_exists('jc_can_view')
                || !function_exists('jc_get_status_badge')
                || !function_exists('jc_get_client_name')) {
                $this->load->helper('job_cards/job_cards');
            }
        }

        $job_card_id = (int) $job_card_id;
        if ($job_card_id < 1) {
            show_404();
        }

        if (function_exists('jc_can_view') && !jc_can_view($job_card_id)) {
            set_alert('danger', 'You do not have permission to view this job card.');
            redirect(admin_url('job_cards'));
        }

        $p = db_prefix();
        $this->db->where('id', $job_card_id);
        $jc = $this->db->get($p . 'ipms_job_cards')->row();
        if (!$jc) {
            show_404();
        }

        $this->load->model('job_cards/job_cards_model');
        $this->job_cards_model->attach_effective_proposal_context($jc);

        $proposalId = (int) $jc->proposal_id;
        if ($proposalId < 1) {
            set_alert('warning', 'This job card is not linked to a proposal. Link a proposal (or create the job card from an approved proposal) to load materials for issuing.');
        }

        $qt_items = $this->job_cards_model->get_qt_items_for_inventory_issue($proposalId);

        $clientName = '';
        if (function_exists('jc_get_client_name')) {
            $clientName = (string) jc_get_client_name((int) ($jc->client_id ?? 0));
        } elseif ((int) ($jc->client_id ?? 0) > 0) {
            $this->db->select('company');
            $this->db->where('userid', (int) $jc->client_id);
            $cr = $this->db->get($p . 'clients')->row();
            $clientName = $cr ? (string) ($cr->company ?? '') : '';
        }

        $data['title']       = 'Issue Materials — Job Card: ' . html_escape($jc->jc_ref ?? '');
        $data['job_card']    = $jc;
        $data['qt_items']    = $qt_items;
        $data['warehouses']  = inv_mgr_get_warehouses();
        $data['client_name'] = $clientName;

        $this->load->view('inventory_mgr/issue_form', $data);
    }

    public function process_issue($job_card_id)
    {
        $this->ensure_inventory_access();

        $job_card_id = (int) $job_card_id;

        // method(true) returns uppercase verb (e.g. "POST"); comparing to "post" always failed.
        if ($this->input->method(true) !== 'POST') {
            set_alert('warning', 'Use the Issue Materials form to confirm an issue (this page only accepts a form submission).');
            redirect($job_card_id > 0 ? admin_url('inventory_mgr/issue_form/' . $job_card_id) : admin_url('job_cards'));

            return;
        }
        if ($job_card_id < 1) {
            show_404();
        }

        $p           = db_prefix();
        $warehouse_id = (int) $this->input->post('warehouse_id');
        $actor_id    = (int) get_staff_user_id();

        $this->db->where('id', $job_card_id);
        $jc = $this->db->get($p . 'ipms_job_cards')->row();
        if (!$jc) {
            set_alert('danger', 'Job card not found.');
            redirect(admin_url('job_cards'));
        }

        $merged = $this->parse_material_issue_post_lines($this->input->post());

        if ($warehouse_id < 1 || empty($merged)) {
            set_alert('danger', 'Select a warehouse and at least one line with quantity.');
            redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));
        }

        $shortfalls = [];
        foreach ($merged as $m) {
            $avail = inv_mgr_get_stock_qty((int) $m['item_id'], $warehouse_id);
            if ($avail + 0.0001 < (float) $m['qty']) {
                $it = inv_mgr_get_item((int) $m['item_id']);
                $shortfalls[] = ($it ? (string) $it->commodity_code : 'ID ' . $m['item_id'])
                    . ': need ' . $m['qty'] . ', available ' . $avail;
            }
        }

        if (!empty($shortfalls)) {
            set_alert('danger', 'Insufficient stock: ' . implode('; ', $shortfalls));
            redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));
        }

        $this->db->insert($p . 'ipms_jc_material_issues', [
            'job_card_id'      => $job_card_id,
            'issue_ref'        => null,
            'issued_by'        => $actor_id,
            'issued_at'        => date('Y-m-d H:i:s'),
            'warehouse_id'     => $warehouse_id,
            'status'           => 'draft',
            'total_cost_value' => 0.00,
            'notes'            => '',
        ]);
        $issue_id = (int) $this->db->insert_id();
        if ($issue_id < 1) {
            set_alert('danger', 'Could not create material issue.');
            redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));
        }

        $modelLines = [];
        foreach ($merged as $m) {
            $itemId = (int) $m['item_id'];
            $item   = inv_mgr_get_item($itemId);
            $this->db->insert($p . 'ipms_jc_material_issue_lines', [
                'issue_id'          => $issue_id,
                'job_card_id'       => $job_card_id,
                'inventory_item_id' => $itemId,
                'item_code'         => $item ? (string) ($item->commodity_code ?? '') : '',
                'item_description'  => $item ? (string) ($item->description ?? '') : '',
                'unit'              => $item && isset($item->unit_symbol) ? (string) $item->unit_symbol : null,
                'qty_required'      => $m['qty_required'],
                'qty_issued'        => (float) $m['qty'],
                'wac_at_issue'      => null,
                'line_total_cost'   => null,
                'qt_line_id'        => $m['qt_line_id'] ?: null,
            ]);
            $line_id = (int) $this->db->insert_id();
            if ($line_id < 1) {
                continue;
            }
            $modelLines[] = [
                'line_id'    => $line_id,
                'item_id'    => $itemId,
                'qty_issued' => (float) $m['qty'],
                'item_code'  => $item ? (string) ($item->commodity_code ?? '') : '',
                'item_name'  => $item ? (string) ($item->description ?? '') : '',
            ];
        }

        if (empty($modelLines)) {
            set_alert('danger', 'No valid issue lines were created.');
            redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));
        }

        $result = $this->inventory_mgr_model->issue_materials_for_job($job_card_id, $warehouse_id, $modelLines, $actor_id);
        if (!empty($result['success'])) {
            set_alert('success', 'Materials issued. Total cost: ' . inv_mgr_format_mwk($result['total_cost'] ?? 0));
            redirect(admin_url('job_cards/view/' . $job_card_id));

            return;
        }

        $this->db->where('issue_id', $issue_id);
        $this->db->delete($p . 'ipms_jc_material_issue_lines');
        $this->db->where('id', $issue_id);
        $this->db->delete($p . 'ipms_jc_material_issues');

        $msg = !empty($result['errors']) ? implode(' ', $result['errors']) : 'Issue failed.';
        set_alert('danger', $msg);
        redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));
    }

    public function confirm_issue()
    {
        $this->ensure_inventory_access();

        if (!$this->input->post()) {
            $this->output->set_status_header(405)->set_output('Method not allowed');

            return;
        }

        $post         = $this->input->post();
        $warehouse_id = (int) ($post['warehouse_id'] ?? 0);
        $merged       = $this->parse_material_issue_post_lines($post);

        $warnings    = [];
        $total_cost  = 0.0;
        $valid_lines = 0;

        if ($warehouse_id < 1 || $merged === []) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success'     => false,
                    'warnings'    => [],
                    'total_cost'  => inv_mgr_format_mwk(0),
                    'can_proceed' => false,
                ]));

            return;
        }

        foreach ($merged as $m) {
            $iid = (int) ($m['item_id'] ?? 0);
            $qty = (float) ($m['qty'] ?? 0);
            if ($iid < 1 || $qty <= 0) {
                continue;
            }
            $valid_lines++;
            $avail = inv_mgr_get_stock_qty($iid, $warehouse_id);
            $wac   = inv_mgr_get_wac($iid);
            $total_cost += $qty * $wac;

            $it = inv_mgr_get_item($iid);
            $code = $it ? (string) ($it->commodity_code ?? '') : '';
            $name = $it ? (string) ($it->description ?? '') : '';

            if ($avail + 0.0001 < $qty) {
                $warnings[] = [
                    'item_code'  => $code,
                    'item_name'  => $name,
                    'available'  => $avail,
                    'requested'  => $qty,
                    'shortfall'  => max(0, $qty - $avail),
                ];
            }
        }

        $canProceed = $valid_lines > 0 && empty($warnings);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'     => $valid_lines > 0 || !empty($warnings),
                'warnings'    => $warnings,
                'total_cost'  => inv_mgr_format_mwk($total_cost),
                'can_proceed' => $canProceed,
            ]));
    }

    // --------------------------------------------------------------------
    // Adjustments
    // --------------------------------------------------------------------

    public function adjustments()
    {
        $this->ensure_inventory_access();

        $filters = [
            'warehouse_id' => $this->input->get('warehouse_id') ? (int) $this->input->get('warehouse_id') : null,
            'status'       => $this->input->get('status') ? (string) $this->input->get('status') : null,
            'date_from'    => $this->input->get('date_from') ? (string) $this->input->get('date_from') : null,
            'date_to'      => $this->input->get('date_to') ? (string) $this->input->get('date_to') : null,
        ];

        $data['title']       = 'Stock Adjustments';
        $data['adjustments'] = $this->inventory_mgr_model->get_adjustment('', $filters);
        $data['filters']     = $filters;
        $data['warehouses']  = inv_mgr_get_warehouses();

        $this->load->view('inventory_mgr/adj_list', $data);
    }

    public function add_adjustment()
    {
        $this->ensure_inventory_access();

        if ($this->input->post()) {
            $post  = $this->input->post();
            $lines = [];

            $itemsRaw = isset($post['lines']) && is_array($post['lines']) ? $post['lines'] : [];
            foreach ($itemsRaw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $iid = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                $adj = isset($row['qty_adjust']) ? (float) $row['qty_adjust'] : 0.0;
                if ($iid < 1 || $adj <= 0) {
                    continue;
                }
                $lines[] = [
                    'item_id'      => $iid,
                    'qty_adjust'   => $adj,
                    'reason_notes' => isset($row['reason_notes']) ? $row['reason_notes'] : '',
                ];
            }

            $data = [
                'warehouse_id'  => isset($post['warehouse_id']) ? (int) $post['warehouse_id'] : 0,
                'adj_type'      => isset($post['adj_type']) ? (string) $post['adj_type'] : '',
                'reason'        => isset($post['reason']) ? (string) $post['reason'] : '',
                'requested_by'  => (int) get_staff_user_id(),
            ];

            $adjId = $this->inventory_mgr_model->create_adjustment($data, $lines);
            if ($adjId) {
                set_alert('success', 'Adjustment saved.');
                redirect(admin_url('inventory_mgr/view_adjustment/' . (int) $adjId));
            }

            set_alert('danger', 'Could not create adjustment.');
            redirect(admin_url('inventory_mgr/add_adjustment'));
        }

        $data['title']       = 'New Stock Adjustment';
        $data['warehouses'] = inv_mgr_get_warehouses();

        $this->load->view('inventory_mgr/adj_form', $data);
    }

    public function view_adjustment($id)
    {
        $this->ensure_inventory_access();

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $adj = $this->inventory_mgr_model->get_adjustment($id);
        if (!$adj) {
            show_404();
        }

        $data['title']       = 'Adjustment: ' . html_escape($adj->adj_ref ?? '');
        $data['adj']        = $adj;
        $data['can_approve'] = $this->staff_role_in(['Store Manager', 'General Manager']);
        $data['can_post']    = $this->staff_role_in(['Store Manager', 'Finance Manager']);

        $this->load->view('inventory_mgr/adj_view', $data);
    }

    public function approve_adjustment_action($id)
    {
        $this->ensure_inventory_access();

        if (!$this->input->post() || $this->input->method(true) !== 'POST') {
            show_error('Invalid request', 405);
        }

        if (!$this->staff_role_in(['Store Manager', 'General Manager'])) {
            set_alert('danger', 'You do not have permission to approve adjustments.');
            redirect(admin_url('inventory_mgr/view_adjustment/' . (int) $id));
        }

        $ok = $this->inventory_mgr_model->approve_adjustment((int) $id, (int) get_staff_user_id());
        set_alert($ok ? 'success' : 'danger', $ok ? 'Adjustment approved.' : 'Could not approve adjustment.');
        redirect(admin_url('inventory_mgr/view_adjustment/' . (int) $id));
    }

    public function post_adjustment_action($id)
    {
        $this->ensure_inventory_access();

        if (!$this->input->post() || $this->input->method(true) !== 'POST') {
            show_error('Invalid request', 405);
        }

        if (!$this->staff_role_in(['Store Manager', 'Finance Manager'])) {
            set_alert('danger', 'You do not have permission to post adjustments.');
            redirect(admin_url('inventory_mgr/view_adjustment/' . (int) $id));
        }

        $ok = $this->inventory_mgr_model->post_adjustment((int) $id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Adjustment posted to stock.' : 'Could not post adjustment.');
        redirect(admin_url('inventory_mgr/view_adjustment/' . (int) $id));
    }

    // --------------------------------------------------------------------
    // Movements & reports
    // --------------------------------------------------------------------

    public function movements()
    {
        $this->ensure_inventory_access();

        $filters = [
            'item_id'       => $this->input->get('item_id') ? (int) $this->input->get('item_id') : null,
            'warehouse_id'  => $this->input->get('warehouse_id') ? (int) $this->input->get('warehouse_id') : null,
            'movement_type' => $this->input->get('movement_type') ? (string) $this->input->get('movement_type') : null,
            'date_from'     => $this->input->get('date_from') ? (string) $this->input->get('date_from') : null,
            'date_to'       => $this->input->get('date_to') ? (string) $this->input->get('date_to') : null,
        ];

        $data['title']      = 'Stock Movements';
        $data['movements']  = $this->inventory_mgr_model->get_movements($filters);
        $data['filters']    = $filters;
        $data['warehouses'] = inv_mgr_get_warehouses();

        $this->load->view('inventory_mgr/movements_list', $data);
    }

    public function stock_report()
    {
        $this->ensure_inventory_access();

        $rows = $this->inventory_mgr_model->get_stock_valuation_report(null);

        $sumTotalValue = 0.0;
        $byWh          = [
            'blantyre'  => 0.0,
            'lilongwe'  => 0.0,
        ];

        foreach ($rows as $r) {
            $sumTotalValue += isset($r['total_value']) ? (float) $r['total_value'] : 0.0;
            $byWh['blantyre'] += isset($r['blantyre_value']) ? (float) $r['blantyre_value'] : 0.0;
            $byWh['lilongwe'] += isset($r['lilongwe_value']) ? (float) $r['lilongwe_value'] : 0.0;
        }

        $data['title']          = 'Stock Valuation Report';
        $data['rows']           = $rows;
        $data['summary_total']  = $sumTotalValue;
        $data['summary_by_wh']  = $byWh;

        $this->load->view('inventory_mgr/stock_report', $data);
    }

    /**
     * @param array<string, mixed> $post
     * @return array<int, array<string, mixed>>
     */
    protected function parse_grn_lines_from_post(array $post)
    {
        $itemsRaw = [];
        if (!empty($post['lines']) && is_array($post['lines'])) {
            $itemsRaw = $post['lines'];
        } elseif (!empty($post['items']) && is_array($post['items'])) {
            $itemsRaw = $post['items'];
        }

        $lines = [];
        foreach ($itemsRaw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $iid = isset($row['item_id']) ? (int) $row['item_id'] : 0;
            $qty = isset($row['qty_received']) ? (float) $row['qty_received'] : 0.0;
            $up  = isset($row['unit_price']) ? (float) $row['unit_price'] : -1.0;
            if ($iid < 1 || $qty <= 0 || $up < 0) {
                continue;
            }
            $lines[] = [
                'item_id'       => $iid,
                'qty_received'  => $qty,
                'unit_price'    => $up,
                'item_code'     => isset($row['item_code']) ? (string) $row['item_code'] : '',
                'item_name'     => isset($row['item_name']) ? (string) $row['item_name'] : '',
                'unit_symbol'   => isset($row['unit_symbol']) ? (string) $row['unit_symbol'] : '',
                'qty_ordered'   => isset($row['qty_ordered']) ? (float) $row['qty_ordered'] : 0.0,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    protected function get_distinct_grn_supplier_names()
    {
        $p = db_prefix();
        if (!$this->db->table_exists($p . 'goods_receipt')) {
            return [];
        }
        $this->db->distinct();
        $this->db->select('supplier_name');
        $this->db->from($p . 'goods_receipt');
        $this->db->where('supplier_name IS NOT NULL', null, false);
        $this->db->where('supplier_name !=', '');
        $this->db->order_by('supplier_name', 'ASC');
        $this->db->limit(200);
        $out = [];
        foreach ($this->db->get()->result_array() as $r) {
            $n = trim((string) ($r['supplier_name'] ?? ''));
            if ($n !== '' && !in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * Normalized lines for material issue POST (quotation checkboxes + optional legacy lines[] + extra_items).
     *
     * @param array<string, mixed> $post
     * @return array<int, array{item_id:int,qty:float,qty_required:?float,qt_line_id:?int,notes?:string}>
     */
    protected function parse_material_issue_post_lines(array $post)
    {
        if (isset($post['lines']) && is_string($post['lines']) && $post['lines'] !== '') {
            $decoded = json_decode($post['lines'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $post['lines'] = $decoded;
            }
        }

        $merged = [];

        if (!empty($post['qt_issue_items']) && is_array($post['qt_issue_items'])) {
            $qtQty     = isset($post['qt_qty']) && is_array($post['qt_qty']) ? $post['qt_qty'] : [];
            $qtLineIds = isset($post['qt_line_id']) && is_array($post['qt_line_id']) ? $post['qt_line_id'] : [];
            foreach ($post['qt_issue_items'] as $iidRaw) {
                $iid = (int) $iidRaw;
                if ($iid < 1) {
                    continue;
                }
                $qty = 0.0;
                if (isset($qtQty[$iidRaw])) {
                    $qty = (float) $qtQty[$iidRaw];
                } elseif (isset($qtQty[(string) $iid])) {
                    $qty = (float) $qtQty[(string) $iid];
                }
                if ($qty <= 0) {
                    continue;
                }
                $qtl = null;
                if (isset($qtLineIds[$iidRaw])) {
                    $qtl = (int) $qtLineIds[$iidRaw];
                } elseif (isset($qtLineIds[(string) $iid])) {
                    $qtl = (int) $qtLineIds[(string) $iid];
                }
                $merged[] = [
                    'item_id'      => $iid,
                    'qty'          => $qty,
                    'qty_required' => null,
                    'qt_line_id'   => $qtl > 0 ? $qtl : null,
                ];
            }
        } elseif (!empty($post['lines']) && is_array($post['lines'])) {
            foreach ($post['lines'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $iid = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                $qty = isset($row['qty_issued']) ? (float) $row['qty_issued'] : 0.0;
                if ($iid < 1 || $qty <= 0) {
                    continue;
                }
                $merged[] = [
                    'item_id'      => $iid,
                    'qty'          => $qty,
                    'qty_required' => isset($row['qty_required']) ? (float) $row['qty_required'] : null,
                    'qt_line_id'   => isset($row['qt_line_id']) ? (int) $row['qt_line_id'] : null,
                ];
            }
        }

        if (!empty($post['extra_items']) && is_array($post['extra_items'])) {
            foreach ($post['extra_items'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $iid = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                $qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
                if ($iid < 1 || $qty <= 0) {
                    continue;
                }
                $merged[] = [
                    'item_id'      => $iid,
                    'qty'          => $qty,
                    'qty_required' => null,
                    'qt_line_id'   => null,
                    'notes'        => isset($row['notes']) ? (string) $row['notes'] : '',
                ];
            }
        }

        if (!empty($post['extra_item_id']) && is_array($post['extra_item_id'])) {
            $extraQtys = isset($post['extra_qty']) && is_array($post['extra_qty']) ? $post['extra_qty'] : [];
            foreach ($post['extra_item_id'] as $idx => $iidRaw) {
                $iid = (int) $iidRaw;
                $qty = isset($extraQtys[$idx]) ? (float) $extraQtys[$idx] : 0.0;
                if ($iid < 1 || $qty <= 0) {
                    continue;
                }
                $merged[] = [
                    'item_id'      => $iid,
                    'qty'          => $qty,
                    'qty_required' => null,
                    'qt_line_id'   => null,
                    'notes'        => '',
                ];
            }
        }

        $qtMapped    = isset($post['qt_mapped']) && is_array($post['qt_mapped']) ? $post['qt_mapped'] : [];
        $qtMappedQty = isset($post['qt_mapped_qty']) && is_array($post['qt_mapped_qty']) ? $post['qt_mapped_qty'] : [];
        foreach ($qtMapped as $lineKey => $iidRaw) {
            $iid = (int) $iidRaw;
            if ($iid < 1) {
                continue;
            }
            $qty = 0.0;
            if (isset($qtMappedQty[$lineKey])) {
                $qty = (float) $qtMappedQty[$lineKey];
            }
            $qtl = 0;
            if (is_string($lineKey) && ctype_digit((string) $lineKey)) {
                $qtl = (int) $lineKey;
            } elseif (is_int($lineKey)) {
                $qtl = $lineKey;
            }

            if ($qty <= 0) {
                continue;
            }
            $merged[] = [
                'item_id'        => $iid,
                'qty'            => $qty,
                'qty_required'   => null,
                'qt_line_id'     => $qtl > 0 ? $qtl : null,
                'notes'          => '',
            ];
        }

        return $merged;
    }
}

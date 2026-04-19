<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Job_cards_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '', $filters = [])
    {
        $p = db_prefix();

        if (is_numeric($id) && (int) $id > 0) {
            $this->db->select(
                'jc.*,
                c.company AS client_name,
                CONCAT(COALESCE(cb.firstname, \'\'), \' \', COALESCE(cb.lastname, \'\')) AS created_by_name,
                CONCAT(COALESCE(sa.firstname, \'\'), \' \', COALESCE(sa.lastname, \'\')) AS assigned_sales_name,
                pr.subject AS proposal_subject,
                pr.qt_ref AS proposal_qt_ref',
                false
            );
            $this->db->from($p . 'ipms_job_cards jc');
            $this->db->join($p . 'clients c', 'c.userid = jc.client_id', 'left');
            $this->db->join($p . 'staff cb', 'cb.staffid = jc.created_by', 'left');
            $this->db->join($p . 'staff sa', 'sa.staffid = jc.assigned_sales_id', 'left');
            $this->db->join($p . 'proposals pr', 'pr.id = jc.proposal_id', 'left');
            $this->db->where('jc.id', (int) $id);
            $jc = $this->db->get()->row();

            if (!$jc) {
                return false;
            }

            $jc->department_assignments = $this->get_department_assignments((int) $id);
            $jc->status_log             = $this->get_status_log((int) $id);
            $jc->material_issues        = $this->get_material_issues((int) $id);
            $jc->qt_lines               = $this->db
                ->where('proposal_id', (int) $jc->proposal_id)
                ->get($p . 'ipms_qt_lines')
                ->result_array();

            return $jc;
        }

        $this->db->select(
            'jc.*,
            c.company AS client_name,
            CONCAT(COALESCE(cb.firstname, \'\'), \' \', COALESCE(cb.lastname, \'\')) AS created_by_name,
            CONCAT(COALESCE(sa.firstname, \'\'), \' \', COALESCE(sa.lastname, \'\')) AS assigned_sales_name,
            pr.subject AS proposal_subject,
            pr.qt_ref AS proposal_qt_ref',
            false
        );
        $this->db->from($p . 'ipms_job_cards jc');
        $this->db->join($p . 'clients c', 'c.userid = jc.client_id', 'left');
        $this->db->join($p . 'staff cb', 'cb.staffid = jc.created_by', 'left');
        $this->db->join($p . 'staff sa', 'sa.staffid = jc.assigned_sales_id', 'left');
        $this->db->join($p . 'proposals pr', 'pr.id = jc.proposal_id', 'left');

        $this->apply_visibility_filter();
        $this->apply_filters($filters);

        $this->db->order_by('jc.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_department_assignments($job_card_id)
    {
        $p = db_prefix();

        $this->db->select(
            'a.*,
            CONCAT(COALESCE(s.firstname, \'\'), \' \', COALESCE(s.lastname, \'\')) AS acknowledged_by_name',
            false
        );
        $this->db->from($p . 'ipms_jc_department_assignments a');
        $this->db->join($p . 'staff s', 's.staffid = a.acknowledged_by', 'left');
        $this->db->where('a.job_card_id', (int) $job_card_id);
        $this->db->order_by('a.id', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_status_log($job_card_id)
    {
        $this->db->from(db_prefix() . 'ipms_jc_status_log');
        $this->db->where('job_card_id', (int) $job_card_id);
        $this->db->order_by('changed_at', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_material_issues($job_card_id)
    {
        $p = db_prefix();
        $this->db->select('i.*, COUNT(l.id) AS line_count', false);
        $this->db->from($p . 'ipms_jc_material_issues i');
        $this->db->join($p . 'ipms_jc_material_issue_lines l', 'l.issue_id = i.id', 'left');
        $this->db->where('i.job_card_id', (int) $job_card_id);
        $this->db->group_by('i.id');
        $this->db->order_by('i.issued_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_issue($issue_id)
    {
        $this->db->where('id', (int) $issue_id);
        $issue = $this->db->get(db_prefix() . 'ipms_jc_material_issues')->row();
        if (!$issue) {
            return false;
        }

        $issue->lines = $this->get_issue_lines((int) $issue_id);

        return $issue;
    }

    public function get_issue_lines($issue_id)
    {
        $p = db_prefix();
        $commodityTable = $p . 'ware_commodity';
        $itemsTable     = $p . 'items';
        $useCommodity   = $this->db->table_exists($commodityTable);
        $useItems       = !$useCommodity && $this->db->table_exists($itemsTable);

        if ($useCommodity) {
            $this->db->select('l.*, c.commodity_name, c.wac_price AS current_wac, u.unit_symbol', false);
        } elseif ($useItems) {
            $this->db->select('l.*, i.commodity_name, i.purchase_price AS current_wac, i.unit AS unit_symbol', false);
        } else {
            $this->db->select('l.*', false);
        }
        $this->db->from($p . 'ipms_jc_material_issue_lines l');
        if ($useCommodity) {
            $this->db->join($commodityTable . ' c', 'c.commodity_id = l.inventory_item_id', 'left');
            $this->db->join($p . 'ware_unit_type u', 'u.unit_type_id = c.unit_type_id', 'left');
        } elseif ($useItems) {
            $this->db->join($itemsTable . ' i', 'i.id = l.inventory_item_id', 'left');
        }
        $this->db->where('l.issue_id', (int) $issue_id);
        $this->db->order_by('l.id', 'ASC');

        return $this->db->get()->result_array();
    }

    public function update_status($job_card_id, $new_status, $notes = '', $actor_id = null)
    {
        $job_card_id = (int) $job_card_id;
        $new_status  = (int) $new_status;
        $actor_id    = $actor_id === null ? (int) get_staff_user_id() : (int) $actor_id;

        $this->db->where('id', $job_card_id);
        $jobCard = $this->db->get(db_prefix() . 'ipms_job_cards')->row();
        if (!$jobCard) {
            return false;
        }

        $currentStatus = (int) $jobCard->status;
        if ($new_status <= $currentStatus) {
            return false;
        }

        $update = [
            'status'     => $new_status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($new_status === 6) {
            $update['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $job_card_id);
        $ok = (bool) $this->db->update(db_prefix() . 'ipms_job_cards', $update);
        if (!$ok) {
            return false;
        }

        $this->db->insert(db_prefix() . 'ipms_jc_status_log', [
            'job_card_id'     => $job_card_id,
            'from_status'     => $currentStatus,
            'to_status'       => $new_status,
            'changed_by'      => $actor_id > 0 ? $actor_id : 0,
            'changed_by_name' => $actor_id > 0 ? get_staff_full_name($actor_id) : 'System',
            'changed_by_role' => $this->get_staff_role_name($actor_id),
            'notes'           => (string) $notes,
            'changed_at'      => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function create_material_issue($job_card_id, $data, $lines)
    {
        $p          = db_prefix();
        $job_card_id = (int) $job_card_id;
        $actor_id   = isset($data['issued_by']) ? (int) $data['issued_by'] : (int) get_staff_user_id();

        $this->db->where('id', $job_card_id);
        $jobCard = $this->db->get($p . 'ipms_job_cards')->row();
        if (!$jobCard || (int) $jobCard->status < 1) {
            return false;
        }

        $issue = [
            'job_card_id'       => $job_card_id,
            'issue_ref'         => jc_generate_issue_ref(),
            'issued_by'         => $actor_id,
            'issued_at'         => date('Y-m-d H:i:s'),
            'warehouse_id'      => isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            'status'            => 'draft',
            'total_cost_value'  => 0.00,
            'notes'             => isset($data['notes']) ? $data['notes'] : '',
        ];

        $this->db->insert($p . 'ipms_jc_material_issues', $issue);
        $issue_id = (int) $this->db->insert_id();
        if ($issue_id < 1) {
            return false;
        }

        $totalCost = 0.00;
        foreach ((array) $lines as $line) {
            $itemId = isset($line['inventory_item_id']) ? (int) $line['inventory_item_id'] : 0;
            $qty    = isset($line['qty_issued']) ? (float) $line['qty_issued'] : 0.0;
            if ($itemId < 1 || $qty <= 0) {
                continue;
            }

            $commodityTable = $p . 'ware_commodity';
            $itemsTable     = $p . 'items';
            $item           = null;
            $wac            = 0.0;
            $itemCode       = '';
            $itemName       = '';
            $canDeductStock = false;

            if ($this->db->table_exists($commodityTable)) {
                $this->db->select('commodity_id, commodity_code, commodity_name, wac_price');
                $this->db->from($commodityTable);
                $this->db->where('commodity_id', $itemId);
                $item = $this->db->get()->row();
                if ($item) {
                    $wac            = (float) ($item->wac_price ?? 0);
                    $itemCode       = (string) ($item->commodity_code ?? '');
                    $itemName       = (string) ($item->commodity_name ?? '');
                    $canDeductStock = $this->db->field_exists('current_quantity', $commodityTable);
                }
            } elseif ($this->db->table_exists($itemsTable)) {
                $this->db->select('id, commodity_code, commodity_name, purchase_price');
                $this->db->from($itemsTable);
                $this->db->where('id', $itemId);
                $item = $this->db->get()->row();
                if ($item) {
                    $wac      = (float) ($item->purchase_price ?? 0);
                    $itemCode = (string) ($item->commodity_code ?? '');
                    $itemName = (string) ($item->commodity_name ?? '');
                }
            }

            if (!$item) {
                continue;
            }

            $lineTotal = round($qty * $wac, 2);
            $totalCost += $lineTotal;

            $this->db->insert($p . 'ipms_jc_material_issue_lines', [
                'issue_id'          => $issue_id,
                'job_card_id'       => $job_card_id,
                'inventory_item_id' => $itemId,
                'item_code'         => isset($line['item_code']) ? $line['item_code'] : $itemCode,
                'item_description'  => isset($line['item_description']) ? $line['item_description'] : $itemName,
                'unit'              => isset($line['unit']) ? $line['unit'] : null,
                'qty_required'      => isset($line['qty_required']) ? (float) $line['qty_required'] : null,
                'qty_issued'        => $qty,
                'wac_at_issue'      => $wac,
                'line_total_cost'   => $lineTotal,
                'qt_line_id'        => isset($line['qt_line_id']) ? (int) $line['qt_line_id'] : null,
            ]);

            if ($canDeductStock) {
                $this->db->set('current_quantity', 'current_quantity - ' . $this->db->escape_str((string) $qty), false);
                $this->db->where('commodity_id', $itemId);
                $this->db->update($commodityTable);
            }
        }

        $this->db->where('id', $issue_id);
        $this->db->update($p . 'ipms_jc_material_issues', [
            'total_cost_value' => round($totalCost, 2),
            'status'           => 'issued',
        ]);

        $this->db->where('id', $job_card_id);
        $this->db->update($p . 'ipms_job_cards', [
            'materials_issued'    => 1,
            'materials_issued_at' => date('Y-m-d H:i:s'),
            'materials_issued_by' => $actor_id,
            'status'              => 2,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert($p . 'ipms_jc_status_log', [
            'job_card_id'     => $job_card_id,
            'from_status'     => (int) $jobCard->status,
            'to_status'       => 2,
            'changed_by'      => $actor_id,
            'changed_by_name' => get_staff_full_name($actor_id),
            'changed_by_role' => $this->get_staff_role_name($actor_id),
            'notes'           => 'Materials issued via ' . $issue['issue_ref'],
            'changed_at'      => date('Y-m-d H:i:s'),
        ]);

        return $issue_id;
    }

    public function confirm_issue($issue_id)
    {
        $this->db->where('id', (int) $issue_id);

        return (bool) $this->db->update(db_prefix() . 'ipms_jc_material_issues', ['status' => 'confirmed']);
    }

    public function get_qt_lines_for_job($proposal_id)
    {
        $p       = db_prefix();
        $qtTable = $p . 'ipms_qt_lines';
        if (!$this->db->table_exists($qtTable)) {
            $idCol           = 'inventory_item_id';
            $commodityTable  = $p . 'ware_commodity';
            $itemsTable      = $p . 'items';
            $useCommodity    = $this->db->table_exists($commodityTable);
            $useItems        = !$useCommodity && $this->db->table_exists($itemsTable);

            return $this->get_qt_line_rows_from_ipms_quotation((int) $proposal_id, $idCol, $useCommodity, $useItems, $commodityTable, $itemsTable);
        }

        // M03 quotation worksheet uses commodity_id; tolerate legacy column name if present.
        $idCol = 'commodity_id';
        if (!$this->db->field_exists('commodity_id', $qtTable) && $this->db->field_exists('inventory_item_id', $qtTable)) {
            $idCol = 'inventory_item_id';
        }

        $commodityTable = $p . 'ware_commodity';
        $itemsTable     = $p . 'items';
        $useCommodity   = $this->db->table_exists($commodityTable);
        $useItems       = !$useCommodity && $this->db->table_exists($itemsTable);

        if ($useCommodity) {
            $this->db->select('l.*, c.commodity_name, c.wac_price, c.commodity_code', false);
        } elseif ($useItems) {
            $this->db->select('l.*, i.commodity_name, i.purchase_price AS wac_price, i.commodity_code', false);
        } else {
            $this->db->select('l.*', false);
        }
        $this->db->from($qtTable . ' l');
        if ($useCommodity) {
            $this->db->join($commodityTable . ' c', 'c.commodity_id = l.' . $idCol, 'left');
        } elseif ($useItems) {
            $this->db->join($itemsTable . ' i', 'i.id = l.' . $idCol, 'left');
        }
        $this->db->where('l.proposal_id', (int) $proposal_id);
        $this->db->where('l.' . $idCol . ' IS NOT NULL', null, false);
        $this->db->where('l.' . $idCol . ' >', 0);
        $this->db->order_by('l.id', 'ASC');

        $lines = $this->db->get()->result_array();
        if (!empty($lines)) {
            return $lines;
        }

        return $this->get_qt_line_rows_from_ipms_quotation((int) $proposal_id, $idCol, $useCommodity, $useItems, $commodityTable, $itemsTable);
    }

    /**
     * Quotation worksheet lines (ipms_qt_lines) are empty — use IPMS Quotations module lines
     * linked via proposal → estimate → ipms_quotations.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_qt_line_rows_from_ipms_quotation(
        $proposal_id,
        $idCol,
        $useCommodity,
        $useItems,
        $commodityTable,
        $itemsTable
    ) {
        $proposal_id = (int) $proposal_id;
        if ($proposal_id < 1) {
            return [];
        }

        $this->load->helper('job_cards/job_cards');
        if (!function_exists('jc_get_ipms_quotation_for_proposal')) {
            return [];
        }

        $proposal = $this->db->where('id', $proposal_id)->get(db_prefix() . 'proposals')->row();
        if (!$proposal) {
            return [];
        }

        $q = jc_get_ipms_quotation_for_proposal($proposal);
        if (!$q || empty($q->id)) {
            return [];
        }

        $p         = db_prefix();
        $linesTbl  = $p . 'ipms_quotation_lines';
        if (!$this->db->table_exists($linesTbl)) {
            return [];
        }

        $this->db->from($linesTbl . ' l');
        if ($useCommodity) {
            $this->db->select('l.*, c.commodity_name, c.wac_price, c.commodity_code', false);
            $this->db->join($commodityTable . ' c', 'c.commodity_id = l.inventory_item_id', 'left');
        } elseif ($useItems) {
            $this->db->select('l.*, i.commodity_name, i.purchase_price AS wac_price, i.commodity_code', false);
            $this->db->join($itemsTable . ' i', 'i.id = l.inventory_item_id', 'left');
        } else {
            $this->db->select('l.*', false);
        }
        $this->db->where('l.quotation_id', (int) $q->id);
        $this->db->order_by('l.id', 'ASC');
        $rows = $this->db->get()->result_array();

        $out = [];
        foreach ($rows as $row) {
            $iid = isset($row['inventory_item_id']) ? (int) $row['inventory_item_id'] : 0;
            $row['proposal_id']             = $proposal_id;
            $row['qt_quotation_fallback']   = 1;
            $row['commodity_id']            = $iid;
            $row['inventory_item_id']       = $iid > 0 ? $iid : null;
            if ($idCol !== 'commodity_id') {
                $row[$idCol] = $iid;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * Rows for inventory_mgr issue form (stdClass list), from worksheet lines and/or IPMS quotation lines.
     *
     * @return array<int, object>
     */
    public function get_qt_items_for_inventory_issue($proposal_id)
    {
        $proposal_id = (int) $proposal_id;
        $p           = db_prefix();
        $qt_items    = [];

        $qtTable = $p . 'ipms_qt_lines';
        if (!$this->db->table_exists($qtTable)) {
            return $this->build_std_qt_items_from_quotation_lines($proposal_id);
        }

        $idCol = 'commodity_id';
        if (!$this->db->field_exists('commodity_id', $qtTable) && $this->db->field_exists('inventory_item_id', $qtTable)) {
            $idCol = 'inventory_item_id';
        }

        $helperPath = module_dir_path('inventory_mgr', 'helpers/inventory_mgr_helper.php');
        if (is_file($helperPath) && !function_exists('inv_mgr_get_item')) {
            require_once $helperPath;
        }

        $this->db->where('proposal_id', $proposal_id);
        $this->db->order_by('line_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $qtLines = $this->db->get($qtTable)->result();

        foreach ($qtLines as $ql) {
            $itemId = (int) ($ql->{$idCol} ?? 0);
            $item   = ($itemId > 0 && function_exists('inv_mgr_get_item')) ? inv_mgr_get_item($itemId) : null;
            $row    = new stdClass();
            $row->qt_line_id         = (int) ($ql->id ?? 0);
            $row->inventory_item_id  = $itemId > 0 ? $itemId : null;
            $row->commodity_code     = $item ? (string) ($item->commodity_code ?? '') : (string) ($ql->item_code ?? '');
            $row->item_name          = $item ? (string) ($item->description ?? '') : (string) ($ql->description ?? '');
            $row->unit_symbol        = $item && isset($item->unit_symbol) && (string) $item->unit_symbol !== ''
                ? (string) $item->unit_symbol
                : (string) ($ql->unit ?? '');
            $row->quoted_qty = (float) ($ql->quantity ?? 0);
            $row->wac        = $itemId > 0 && function_exists('inv_mgr_get_wac') ? inv_mgr_get_wac($itemId) : 0.0;
            $qt_items[]      = $row;
        }

        if (!empty($qt_items)) {
            return $qt_items;
        }

        return $this->build_std_qt_items_from_quotation_lines($proposal_id);
    }

    /**
     * @return array<int, object>
     */
    protected function build_std_qt_items_from_quotation_lines($proposal_id)
    {
        $rows = $this->get_qt_lines_for_job((int) $proposal_id);
        if (empty($rows)) {
            return [];
        }

        $helperPath = module_dir_path('inventory_mgr', 'helpers/inventory_mgr_helper.php');
        if (is_file($helperPath) && !function_exists('inv_mgr_get_item')) {
            require_once $helperPath;
        }

        $qt_items = [];
        foreach ($rows as $ln) {
            $itemId = (int) ($ln['commodity_id'] ?? ($ln['inventory_item_id'] ?? 0));
            $item   = ($itemId > 0 && function_exists('inv_mgr_get_item')) ? inv_mgr_get_item($itemId) : null;
            $row    = new stdClass();
            $row->qt_line_id         = (int) ($ln['id'] ?? 0);
            $row->inventory_item_id  = $itemId > 0 ? $itemId : null;
            $row->commodity_code     = $item ? (string) ($item->commodity_code ?? '') : (string) ($ln['commodity_code'] ?? ($ln['item_code'] ?? ''));
            $row->item_name          = $item ? (string) ($item->description ?? '') : (string) ($ln['description'] ?? ($ln['commodity_name'] ?? ''));
            $row->unit_symbol        = $item && isset($item->unit_symbol) && (string) $item->unit_symbol !== ''
                ? (string) $item->unit_symbol
                : (string) ($ln['unit'] ?? '');
            $row->quoted_qty = (float) ($ln['quantity'] ?? 0);
            $row->wac        = $itemId > 0 && function_exists('inv_mgr_get_wac') ? inv_mgr_get_wac($itemId) : (float) ($ln['wac_price'] ?? 0);
            $qt_items[]      = $row;
        }

        return $qt_items;
    }

    public function get_summary_counts($filters = [])
    {
        $counts = [
            1         => 0,
            2         => 0,
            3         => 0,
            4         => 0,
            5         => 0,
            6         => 0,
            7         => 0,
            'overdue' => 0,
        ];

        $p = db_prefix();
        $this->db->select('status, COUNT(*) as total', false);
        $this->db->from($p . 'ipms_job_cards jc');
        $this->apply_visibility_filter();
        $this->apply_filters($filters);
        $this->db->group_by('status');
        $rows = $this->db->get()->result_array();

        foreach ($rows as $row) {
            $status = (int) $row['status'];
            if (isset($counts[$status])) {
                $counts[$status] = (int) $row['total'];
            }
        }

        $this->db->from($p . 'ipms_job_cards jc');
        $this->apply_visibility_filter();
        $this->apply_filters($filters);
        $this->db->where('jc.deadline IS NOT NULL', null, false);
        $this->db->where('jc.deadline <', date('Y-m-d'));
        $this->db->where_not_in('jc.status', [6, 7]);
        $counts['overdue'] = (int) $this->db->count_all_results();

        return $counts;
    }

    public function count_pending_for_role($role_name)
    {
        $role = strtolower(trim((string) $role_name));
        $this->db->from(db_prefix() . 'ipms_job_cards');
        $this->db->where_in('status', [1, 2, 3, 4]);

        if (in_array($role, ['general manager', 'finance manager', 'sales manager', 'system administrator'], true)) {
            return (int) $this->db->count_all_results();
        }

        if (in_array($role, ['studio/production'], true)) {
            $this->db->where('FIND_IN_SET("studio", department_routing) >', 0, false);

            return (int) $this->db->count_all_results();
        }

        if (in_array($role, ['store manager', 'storekeeper/stores clerk'], true)) {
            $this->db->group_start();
            $this->db->where('FIND_IN_SET("stores", department_routing) >', 0, false);
            $this->db->or_where('FIND_IN_SET("warehouse", department_routing) >', 0, false);
            $this->db->group_end();

            return (int) $this->db->count_all_results();
        }

        if ($role === 'field team') {
            $this->db->where('FIND_IN_SET("field_team", department_routing) >', 0, false);

            return (int) $this->db->count_all_results();
        }

        if (in_array($role, ['sales executive', 'sales rep', 'sales representative'], true)) {
            $this->db->where('assigned_sales_id', (int) get_staff_user_id());

            return (int) $this->db->count_all_results();
        }

        if ($role === 'receptionist') {
            $this->db->where('status >=', 5);

            return (int) $this->db->count_all_results();
        }

        return 0;
    }

    public function search_clients_for_filter($term)
    {
        $term = trim((string) $term);
        $this->db->select('userid, company');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('active', 1);
        if ($term !== '') {
            $this->db->like('company', $term);
        }
        $this->db->order_by('company', 'ASC');
        $this->db->limit(20);

        return $this->db->get()->result_array();
    }

    private function apply_filters($filters = [])
    {
        $filters = is_array($filters) ? $filters : [];

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            if (is_array($filters['status'])) {
                $statuses = array_map('intval', $filters['status']);
                if (!empty($statuses)) {
                    $this->db->where_in('jc.status', $statuses);
                }
            } else {
                $this->db->where('jc.status', (int) $filters['status']);
            }
        }

        if (!empty($filters['client_id'])) {
            $this->db->where('jc.client_id', (int) $filters['client_id']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(jc.created_at) >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(jc.created_at) <=', $filters['date_to']);
        }

        if (!empty($filters['department'])) {
            $this->db->where('FIND_IN_SET(' . $this->db->escape($filters['department']) . ', jc.department_routing) >', 0, false);
        }
    }

    private function apply_visibility_filter()
    {
        if (is_admin()) {
            return;
        }

        $role = strtolower($this->get_staff_role_name((int) get_staff_user_id()));

        if (in_array($role, ['general manager', 'finance manager', 'sales manager', 'system administrator'], true)) {
            return;
        }

        if ($role === 'studio/production') {
            $this->db->where('FIND_IN_SET("studio", jc.department_routing) >', 0, false);

            return;
        }

        if (in_array($role, ['storekeeper/stores clerk', 'store manager'], true)) {
            $this->db->group_start();
            $this->db->where('FIND_IN_SET("stores", jc.department_routing) >', 0, false);
            $this->db->or_where('FIND_IN_SET("warehouse", jc.department_routing) >', 0, false);
            $this->db->group_end();

            return;
        }

        if ($role === 'receptionist') {
            $this->db->where('jc.status >=', 5);

            return;
        }

        $this->db->where('jc.assigned_sales_id', (int) get_staff_user_id());
    }

    private function get_staff_role_name($staff_id)
    {
        $staff_id = (int) $staff_id;
        if ($staff_id < 1) {
            return '';
        }

        $p = db_prefix();
        $this->db->select($p . 'roles.name AS role_name');
        $this->db->from($p . 'staff');
        $this->db->join($p . 'roles', $p . 'roles.roleid = ' . $p . 'staff.role', 'left');
        $this->db->where($p . 'staff.staffid', $staff_id);
        $row = $this->db->get()->row();

        return $row && isset($row->role_name) ? (string) $row->role_name : '';
    }
}

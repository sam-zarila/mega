<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quotations_model extends App_Model
{
    protected $table;

    protected $lines_table;

    protected $settings_table;

    /** @var array<string> */
    protected $tabs = [
        'signage',
        'installation',
        'construction',
        'retrofitting',
        'promotional',
        'additional',
    ];

    public function __construct()
    {
        parent::__construct();
        $p                    = db_prefix();
        $this->table          = $p . 'ipms_quotations';
        $this->lines_table    = $p . 'ipms_quotation_lines';
        $this->settings_table = $p . 'ipms_quotation_settings';
    }

    /**
     * @param mixed $id
     * @param array $where
     *
     * @return object|array|null
     */
    public function get($id = '', $where = [])
    {
        $this->db->where('(' . qt_peer_visibility_where() . ')', null, false);
        if (is_array($where) && $where !== []) {
            $this->db->where($where);
        }
        if ($id !== '' && $id !== null) {
            $this->db->where('q.id', (int) $id);

            return $this->db->get($this->table . ' q')->row();
        }

        return $this->db->get($this->table . ' q')->result_array();
    }

    /**
     * Quotations list with client name, creator name, and tabs that have line rows (for IPMS list UI).
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_table_list()
    {
        $p = db_prefix();
        $sql = 'SELECT q.*, c.company AS client_company,
            TRIM(CONCAT(COALESCE(st.firstname, \'\'), \' \', COALESCE(st.lastname, \'\'))) AS creator_name,
            (SELECT GROUP_CONCAT(DISTINCT l.tab ORDER BY l.tab SEPARATOR \',\')
               FROM `' . $this->lines_table . '` l WHERE l.quotation_id = q.id) AS tabs_with_content
            FROM `' . $this->table . '` q
            LEFT JOIN `' . $p . 'clients` c ON c.userid = q.client_id
            LEFT JOIN `' . $p . 'staff` st ON st.staffid = q.created_by
            WHERE (' . qt_peer_visibility_where() . ')
            ORDER BY q.created_at DESC';

        return $this->db->query($sql)->result_array();
    }

    /**
     * @return array<string,int>
     */
    public function get_statuses_summary()
    {
        $sql = 'SELECT q.`status`, COUNT(*) AS `c` FROM `' . $this->table . '` q WHERE (' . qt_peer_visibility_where() . ') GROUP BY q.`status`';
        $rows = $this->db->query($sql)->result_array();
        $out  = [
            'draft'     => 0,
            'submitted' => 0,
            'approved'  => 0,
            'rejected'  => 0,
            'converted' => 0,
        ];
        foreach ($rows as $row) {
            $st = $row['status'] ?? '';
            if (isset($out[$st])) {
                $out[$st] = (int) $row['c'];
            }
        }

        return $out;
    }

    /**
     * @param array $data POST-style payload
     *
     * @return false|int ipms quotation id
     */
    public function create(array $data)
    {
        $clientId = isset($data['client_id']) ? (int) $data['client_id'] : 0;
        if ($clientId < 1) {
            return false;
        }

        $this->load->model('clients_model');
        $this->load->model('estimates_model');
        $this->load->model('currencies_model');

        $client = $this->clients_model->get($clientId);
        if (!$client) {
            return false;
        }

        $base = $this->currencies_model->get_base_currency();
        if (!empty($client->default_currency)) {
            $currencyId = (int) $client->default_currency;
        } elseif ($base && isset($base->id)) {
            $currencyId = (int) $base->id;
        } else {
            $fallback = $this->db->select('id')->order_by('id', 'ASC')->get(db_prefix() . 'currencies', 1)->row();
            if (!$fallback || !isset($fallback->id)) {
                log_message('error', 'Quotations_model::create failed: no currency configured.');

                return false;
            }
            $currencyId = (int) $fallback->id;
        }

        $terms = isset($data['terms']) && $data['terms'] !== ''
            ? $data['terms']
            : (string) (qt_setting('qt_terms_and_conditions') ?: '');

        $estimateData = [
            'clientid'                 => $clientId,
            'date'                     => date('Y-m-d'),
            'currency'                 => $currencyId,
            'subtotal'                 => 0,
            'total'                    => 0,
            'total_tax'                => 0,
            'billing_street'           => $client->billing_street ?? '',
            'include_shipping'         => 0,
            'show_shipping_on_estimate'=> 1,
            'sale_agent'               => get_staff_user_id(),
            'status'                   => 1,
            'terms'                    => $terms,
        ];

        $estimateId = $this->estimates_model->add($estimateData);
        if (!$estimateId) {
            return false;
        }

        $ref = qt_generate_ref();
        if ($ref === false) {
            return false;
        }

        $markup       = isset($data['markup_percent']) ? (float) $data['markup_percent'] : (float) (qt_setting('qt_default_markup') ?: 0);
        $contingency  = isset($data['contingency_percent']) ? (float) $data['contingency_percent'] : (float) (qt_setting('qt_default_contingency') ?: 0);
        $validityDays = isset($data['validity_days']) ? (int) $data['validity_days'] : (int) (qt_setting('qt_default_validity_days') ?: 30);

        $insert = [
            'quotation_ref'         => $ref,
            'estimate_id'         => (int) $estimateId,
            'parent_id'             => null,
            'version'               => 1,
            'is_latest'             => 1,
            'client_id'             => $clientId,
            'created_by'            => get_staff_user_id(),
            'service_type'          => $data['service_type'] ?? 'signage',
            'status'                => 'draft',
            'markup_percent'        => $markup,
            'contingency_percent'   => $contingency,
            'validity_days'         => $validityDays,
            'terms'                 => $terms,
            'internal_notes'        => $data['internal_notes'] ?? null,
            'discount_percent'      => isset($data['discount_percent']) ? (float) $data['discount_percent'] : 0,
            'discount_amount'       => isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0,
        ];

        $this->db->insert($this->table, $insert);
        $qid = (int) $this->db->insert_id();
        if ($qid < 1) {
            return false;
        }

        if ($this->db->field_exists('ipms_quotation_id', db_prefix() . 'estimates')) {
            $this->db->where('id', (int) $estimateId);
            $this->db->update(db_prefix() . 'estimates', ['ipms_quotation_id' => $qid]);
        }

        qt_recalculate_totals($qid);

        return $qid;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function submit_for_approval($id)
    {
        $id = (int) $id;
        $q  = $this->get($id);
        if (!$q || $q->status !== 'draft') {
            return false;
        }

        if (!class_exists('ApprovalService', false)) {
            $lib = module_dir_path('approvals', 'libraries/ApprovalService.php');
            if (is_file($lib)) {
                require_once $lib;
            }
        }

        $this->load->library('approvals/ApprovalService', null, 'approvalservice');

        $reqId = $this->approvalservice->submit(
            'quotation',
            $id,
            $q->quotation_ref,
            (float) $q->grand_total,
            (int) get_staff_user_id(),
            'IPMS quotation submitted for approval.'
        );

        if ($reqId === false) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->table, [
            'status'                => 'submitted',
            'approval_request_id'   => (int) $reqId,
        ]);

        return true;
    }

    /**
     * Set quotation status to approved (e.g. after Approvals module final approval).
     *
     * @param int $id ipms_quotations.id
     *
     * @return bool
     */
    public function mark_approved($id)
    {
        $id = (int) $id;
        if ($id < 1 || !$this->db->table_exists($this->table)) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->where_in('status', ['submitted']);

        return (bool) $this->db->update($this->table, ['status' => 'approved']);
    }

    /**
     * Set quotation status to rejected (e.g. after Approvals module rejection).
     *
     * @param int $id ipms_quotations.id
     *
     * @return bool
     */
    public function mark_rejected($id)
    {
        $id = (int) $id;
        if ($id < 1 || !$this->db->table_exists($this->table)) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->where_in('status', ['submitted']);

        return (bool) $this->db->update($this->table, ['status' => 'rejected']);
    }

    /**
     * @param int               $quotation_id
     * @param array             $line_data
     * @param int|string $line_id 0 = insert
     *
     * @return false|int line id
     */
    public function save_line($quotation_id, array $line_data, $line_id = 0)
    {
        $quotation_id = (int) $quotation_id;
        $line_id      = (int) $line_id;

        $tab = $line_data['tab'] ?? '';
        if (!in_array($tab, $this->tabs, true)) {
            return false;
        }

        $row = [
            'quotation_id'      => $quotation_id,
            'tab'               => $tab,
            'line_order'        => isset($line_data['line_order']) ? (int) $line_data['line_order'] : 0,
            'description'       => $line_data['description'] ?? '',
            'item_code'         => $line_data['item_code'] ?? null,
            'inventory_item_id' => isset($line_data['inventory_item_id']) && $line_data['inventory_item_id'] !== '' ? (int) $line_data['inventory_item_id'] : null,
            'unit'              => $line_data['unit'] ?? null,
            'quantity'          => isset($line_data['quantity']) ? (float) $line_data['quantity'] : 1,
            'width_m'           => isset($line_data['width_m']) && $line_data['width_m'] !== '' ? (float) $line_data['width_m'] : null,
            'height_m'          => isset($line_data['height_m']) && $line_data['height_m'] !== '' ? (float) $line_data['height_m'] : null,
            'computed_area'     => isset($line_data['computed_area']) && $line_data['computed_area'] !== '' ? (float) $line_data['computed_area'] : null,
            'cost_price'        => isset($line_data['cost_price']) ? (float) $line_data['cost_price'] : 0,
            'markup_percent'    => isset($line_data['markup_percent']) ? (float) $line_data['markup_percent'] : 0,
            'sell_price'        => isset($line_data['sell_price']) ? (float) $line_data['sell_price'] : 0,
            'is_taxable'        => isset($line_data['is_taxable']) ? (int) (bool) $line_data['is_taxable'] : 1,
            'notes'             => $line_data['notes'] ?? null,
        ];

        if (!empty($line_data['sell_price_manual'])) {
            $row['sell_price'] = (float) $line_data['sell_price'];
        }

        $work = $row;
        if (!empty($line_data['sell_price_manual'])) {
            $work['sell_price_manual'] = true;
        }
        qt_calculate_line_totals($work);
        $row['sell_price']        = $work['sell_price'];
        $row['line_total_cost']   = $work['line_total_cost'];
        $row['line_total_sell']   = $work['line_total_sell'];

        if ($line_id > 0) {
            $this->db->where('id', $line_id);
            $this->db->where('quotation_id', $quotation_id);
            $ok = $this->db->update($this->lines_table, $row);

            return $ok ? $line_id : false;
        }

        $this->db->insert($this->lines_table, $row);

        return (int) $this->db->insert_id();
    }

    /**
     * @param int $line_id
     *
     * @return bool
     */
    public function delete_line($line_id)
    {
        $line_id = (int) $line_id;
        if ($line_id < 1) {
            return false;
        }
        $this->db->where('id', $line_id);

        return $this->db->delete($this->lines_table);
    }

    /**
     * @param int $line_id
     * @param int $quotation_id
     *
     * @return object|false
     */
    public function get_line($line_id, $quotation_id = 0)
    {
        $this->db->where('id', (int) $line_id);
        if ($quotation_id > 0) {
            $this->db->where('quotation_id', (int) $quotation_id);
        }

        return $this->db->get($this->lines_table)->row();
    }

    /**
     * @param int $quotation_id
     *
     * @return array<string, array<int, array>>
     */
    public function get_lines_grouped_by_tab($quotation_id)
    {
        $quotation_id = (int) $quotation_id;
        $out          = array_fill_keys($this->tabs, []);
        $this->db->where('quotation_id', $quotation_id);
        $this->db->order_by('tab', 'ASC');
        $this->db->order_by('line_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $rows = $this->db->get($this->lines_table)->result_array();
        foreach ($rows as $r) {
            $t = $r['tab'] ?? '';
            if (isset($out[$t])) {
                $out[$t][] = $r;
            }
        }

        return $out;
    }

    /**
     * @param int $commodity_id
     *
     * @return object|null
     */
    public function get_inventory_item_row($commodity_id)
    {
        $commodity_id = (int) $commodity_id;
        if ($commodity_id < 1) {
            return null;
        }

        $ware = db_prefix() . 'ware_commodity';
        if ($this->db->table_exists($ware)) {
            $this->db->where('commodity_id', $commodity_id);
            $row = $this->db->get($ware)->row();
            if ($row) {
                if (!isset($row->commodity_id) && isset($row->id)) {
                    $row->commodity_id = $row->id;
                }

                return $row;
            }
        }

        $items = db_prefix() . 'items';
        if ($this->db->table_exists($items)) {
            $this->db->select('id AS commodity_id, commodity_name, commodity_code, purchase_price AS wac_price, unit');
            $this->db->where('id', $commodity_id);

            return $this->db->get($items)->row();
        }

        return null;
    }

    /**
     * Resolve inventory row by exact commodity code (signage / item lookup).
     *
     * @param string $code
     *
     * @return object|null
     */
    public function get_inventory_item_row_by_code($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $ware = db_prefix() . 'ware_commodity';
        if ($this->db->table_exists($ware)) {
            $this->db->where('commodity_code', $code);
            $row = $this->db->get($ware)->row();
            if ($row) {
                if (!isset($row->commodity_id) && isset($row->id)) {
                    $row->commodity_id = $row->id;
                }

                return $row;
            }
        }

        $items = db_prefix() . 'items';
        if ($this->db->table_exists($items)) {
            $this->db->select('id AS commodity_id, commodity_name, commodity_code, purchase_price AS wac_price, unit');
            $this->db->where('commodity_code', $code);

            return $this->db->get($items)->row();
        }

        return null;
    }

    /**
     * @param string   $term
     * @param int|null $warehouse_id
     *
     * @return array<int, array>
     */
    public function search_inventory($term, $warehouse_id = null)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return [];
        }

        $out  = [];
        $limit = 50;

        $ware = db_prefix() . 'ware_commodity';
        if ($this->db->table_exists($ware)) {
            $this->db->select('commodity_id, commodity_code, commodity_name, wac_price, unit_type_id');
            $this->db->group_start();
            $this->db->like('commodity_name', $term);
            $this->db->or_like('commodity_code', $term);
            $this->db->group_end();
            $out = array_merge($out, $this->db->get($ware, $limit)->result_array());
        }

        if (count($out) < $limit && $this->db->table_exists(db_prefix() . 'items')) {
            $this->db->select('id AS commodity_id, commodity_code, commodity_name, purchase_price AS wac_price, unit');
            $this->db->group_start();
            $this->db->like('commodity_name', $term);
            $this->db->or_like('commodity_code', $term);
            $this->db->or_like('description', $term);
            $this->db->group_end();
            if ($warehouse_id) {
                $this->db->where('warehouse_id', (int) $warehouse_id);
            }
            $out = array_merge($out, $this->db->get(db_prefix() . 'items', $limit - count($out))->result_array());
        }

        return $out;
    }

    /**
     * @param int    $id
     * @param string $revision_notes
     *
     * @return false|int new quotation id
     */
    public function create_revision($id, $revision_notes)
    {
        $id = (int) $id;
        $q  = $this->db->where('id', $id)->get($this->table)->row();
        if (!$q || $q->status !== 'rejected') {
            return false;
        }

        $rootId = $q->parent_id !== null && $q->parent_id !== '' ? (int) $q->parent_id : (int) $q->id;

        $this->db->select_max('version', 'version');
        $this->db->where('quotation_ref', $q->quotation_ref);
        $verRow = $this->db->get($this->table)->row();
        $maxVer = max(1, (int) ($verRow->version ?? 1));

        $this->load->model('estimates_model');
        $oldEst = $this->estimates_model->get((int) $q->estimate_id);
        if (!$oldEst) {
            return false;
        }

        $estimateData = [
            'clientid'                 => (int) $oldEst->clientid,
            'date'                     => date('Y-m-d'),
            'currency'                 => (int) $oldEst->currency,
            'subtotal'                 => 0,
            'total'                    => 0,
            'total_tax'                => 0,
            'billing_street'           => $oldEst->billing_street ?? '',
            'include_shipping'         => (int) $oldEst->include_shipping,
            'show_shipping_on_estimate'=> (int) $oldEst->show_shipping_on_estimate,
            'sale_agent'               => get_staff_user_id(),
            'status'                   => 1,
            'terms'                    => $q->terms ?? ($oldEst->terms ?? ''),
        ];

        $estimateId = $this->estimates_model->add($estimateData);
        if (!$estimateId) {
            return false;
        }

        $this->db->where('quotation_ref', $q->quotation_ref);
        $this->db->update($this->table, ['is_latest' => 0]);

        $insert = [
            'quotation_ref'       => $q->quotation_ref,
            'estimate_id'         => (int) $estimateId,
            'parent_id'           => $rootId,
            'version'             => $maxVer + 1,
            'is_latest'           => 1,
            'client_id'           => (int) $q->client_id,
            'created_by'          => get_staff_user_id(),
            'service_type'        => $q->service_type,
            'status'              => 'draft',
            'revision_notes'      => $revision_notes,
            'markup_percent'      => (float) $q->markup_percent,
            'contingency_percent' => (float) $q->contingency_percent,
            'validity_days'       => (int) $q->validity_days,
            'terms'               => $q->terms,
            'internal_notes'      => $q->internal_notes,
            'discount_percent'    => (float) $q->discount_percent,
            'discount_amount'     => (float) $q->discount_amount,
        ];

        $this->db->insert($this->table, $insert);
        $newId = (int) $this->db->insert_id();
        if ($newId < 1) {
            return false;
        }

        if ($this->db->field_exists('ipms_quotation_id', db_prefix() . 'estimates')) {
            $this->db->where('id', (int) $estimateId);
            $this->db->update(db_prefix() . 'estimates', ['ipms_quotation_id' => $newId]);
        }

        $this->db->where('quotation_id', $id);
        $oldLines = $this->db->get($this->lines_table)->result_array();
        foreach ($oldLines as $ln) {
            unset($ln['id']);
            $ln['quotation_id'] = $newId;
            $this->db->insert($this->lines_table, $ln);
        }

        qt_recalculate_totals($newId);

        return $newId;
    }

    /**
     * @param int $id
     *
     * @return object|false
     */
    public function get_for_view($id)
    {
        $id = (int) $id;
        $q  = $this->get($id);
        if (!$q) {
            return false;
        }
        $q->lines_by_tab = $this->get_lines_grouped_by_tab($id);

        return $q;
    }

    /**
     * @param int $approval_request_id
     *
     * @return array
     */
    public function get_approval_history($approval_request_id)
    {
        $approval_request_id = (int) $approval_request_id;
        if ($approval_request_id < 1) {
            return [];
        }
        $this->load->model('approvals/approvals_model');

        return $this->approvals_model->get_actions_for_request($approval_request_id);
    }

    /**
     * @param string $quotation_ref
     *
     * @return array
     */
    public function get_version_history_by_ref($quotation_ref)
    {
        $this->db->where('quotation_ref', $quotation_ref);
        $this->db->order_by('version', 'ASC');

        return $this->db->get($this->table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return array<string, mixed>
     */
    public function get_quotation_summary_for_pdf($id)
    {
        $this->load->model('clients_model');
        $q = $this->get_for_view((int) $id);
        if (!$q) {
            return [];
        }

        return [
            'quotation' => $q,
            'client'    => $this->clients_model->get((int) $q->client_id),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function get_all_settings()
    {
        if (!$this->db->table_exists($this->settings_table)) {
            return [];
        }
        $rows = $this->db->get($this->settings_table)->result_array();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }

        return $out;
    }

    /**
     * @param array $post
     */
    public function update_settings(array $post)
    {
        if (!$this->db->table_exists($this->settings_table)) {
            return;
        }
        unset($post['csrf_token_name']);
        foreach ($post as $key => $val) {
            if (!is_string($key) || preg_match('/^qt_/', $key) !== 1) {
                continue;
            }
            $this->db->where('setting_key', $key);
            $this->db->update($this->settings_table, ['setting_value' => (string) $val]);
        }
    }

    /**
     * @param int $quotation_id
     *
     * @return array<string, mixed>
     */
    public function get_tab_totals_json($quotation_id)
    {
        $quotation_id = (int) $quotation_id;
        $grouped      = $this->get_lines_grouped_by_tab($quotation_id);
        $tabsOut      = [];
        $grand        = 0.0;
        foreach ($this->tabs as $tab) {
            $sum = 0.0;
            foreach ($grouped[$tab] as $ln) {
                $sum += isset($ln['line_total_sell']) ? (float) $ln['line_total_sell'] : 0.0;
            }
            $tabsOut[$tab] = round($sum, 2);
            $grand += $sum;
        }

        $totals = qt_recalculate_totals($quotation_id);

        return [
            'tabs'        => $tabsOut,
            'grand_sell'  => round($grand, 2),
            'subtotal'    => $totals['sub_final'] ?? 0,
            'vat'         => $totals['vat_amount'] ?? 0,
            'grand_total' => $totals['grand_total'] ?? 0,
        ];
    }

    /**
     * Promotional tab inventory list (tab 5).
     *
     * @return array
     */
    public function get_promotional_inventory_items()
    {
        $items = db_prefix() . 'items';
        if (!$this->db->table_exists($items)) {
            return [];
        }
        $this->db->order_by('commodity_name', 'ASC');

        return $this->db->get($items, 200)->result_array();
    }

    /**
     * @param int                $quotation_id
     * @param array<int, int>    $line_id_to_order  line id => line_order
     */
    public function update_line_orders($quotation_id, array $line_id_to_order)
    {
        $quotation_id = (int) $quotation_id;
        foreach ($line_id_to_order as $lid => $ord) {
            $this->db->where('quotation_id', $quotation_id);
            $this->db->where('id', (int) $lid);
            $this->db->update($this->lines_table, ['line_order' => (int) $ord]);
        }

        return true;
    }

    /**
     * @param int   $quotation_id
     * @param array $data keys: internal_notes, contingency_percent, discount_percent, discount_amount, validity_days, client_id, quote_date (Y-m-d), expirydate (Y-m-d)
     */
    public function update_builder_header($quotation_id, array $data)
    {
        $quotation_id = (int) $quotation_id;
        $q            = $this->db->where('id', $quotation_id)->get($this->table)->row();
        if (!$q) {
            return false;
        }

        $up = [];
        if (array_key_exists('internal_notes', $data)) {
            $up['internal_notes'] = $data['internal_notes'];
        }
        if (isset($data['contingency_percent'])) {
            $up['contingency_percent'] = (float) $data['contingency_percent'];
        }
        if (isset($data['discount_percent'])) {
            $up['discount_percent'] = (float) $data['discount_percent'];
        }
        if (isset($data['discount_amount'])) {
            $up['discount_amount'] = (float) $data['discount_amount'];
        }
        if (isset($data['validity_days'])) {
            $up['validity_days'] = (int) $data['validity_days'];
        }

        if ($up !== []) {
            $this->db->where('id', $quotation_id);
            $this->db->update($this->table, $up);
        }

        $estUp = [];
        if (isset($data['client_id'])) {
            $cid = (int) $data['client_id'];
            $this->db->where('id', $quotation_id);
            $this->db->update($this->table, ['client_id' => $cid]);
            $estUp['clientid'] = $cid;
        }
        if (isset($data['quote_date']) && $data['quote_date'] !== '') {
            $estUp['date'] = $data['quote_date'];
        }
        if (isset($data['expirydate']) && $data['expirydate'] !== '') {
            $estUp['expirydate'] = $data['expirydate'];
        }

        if ($estUp !== [] && !empty($q->estimate_id)) {
            $this->db->where('id', (int) $q->estimate_id);
            $this->db->update(db_prefix() . 'estimates', $estUp);
        }

        qt_recalculate_totals($quotation_id);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_full_totals_payload($quotation_id)
    {
        $quotation_id = (int) $quotation_id;
        $recalc       = qt_recalculate_totals($quotation_id);
        $grouped      = $this->get_lines_grouped_by_tab($quotation_id);
        $tabsOut      = [];
        $grandLines   = 0.0;
        foreach ($this->tabs as $tab) {
            $sum = 0.0;
            foreach ($grouped[$tab] as $ln) {
                $sum += isset($ln['line_total_sell']) ? (float) $ln['line_total_sell'] : 0.0;
            }
            $tabsOut[$tab] = round($sum, 2);
            $grandLines += $sum;
        }

        $q = $this->db->where('id', $quotation_id)->get($this->table)->row();

        return [
            'tabs'                => $tabsOut,
            'subtotal_lines'      => round($grandLines, 2),
            'subtotal'            => (float) ($recalc['sub_final'] ?? 0),
            'sub_after_cont'      => (float) ($recalc['sub_after_contingency'] ?? 0),
            'discount_applied'    => (float) ($recalc['discount_applied'] ?? 0),
            'vat'                 => (float) ($recalc['vat_amount'] ?? 0),
            'grand_total'         => (float) ($recalc['grand_total'] ?? 0),
            'total_cost'          => $q ? (float) $q->total_cost : 0.0,
            'total_sell_raw'      => $q ? (float) $q->total_sell : 0.0,
            'contingency_percent' => $q ? (float) $q->contingency_percent : 0.0,
            'discount_percent'    => $q ? (float) $q->discount_percent : 0.0,
            'discount_amount'     => $q ? (float) $q->discount_amount : 0.0,
        ];
    }
}

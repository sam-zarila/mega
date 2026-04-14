<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quotations extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('quotations/quotations_model');
        $this->lang->load('quotations/quotations', 'english');
    }

    public function index()
    {
        if (!$this->quotations_user_has_module_access()) {
            access_denied('estimates');
        }

        $data['title']             = 'Quotations';
        $data['quotations']        = $this->quotations_model->get_table_list();
        $data['statuses_summary']  = $this->quotations_model->get_statuses_summary();
        $data['current_staff_id']  = get_staff_user_id();

        $this->load->view('quotations/list', $data);
    }

    public function create($client_id = '')
    {
        if (!$this->quotations_user_has_module_access()) {
            access_denied('estimates');
        }

        if ($this->input->post()) {
            if (staff_cant('create', 'estimates')) {
                access_denied('estimates');
            }

            $clientId = (int) $this->input->post('client_id');
            if ($clientId < 1) {
                set_alert('danger', 'Client is required.');
                redirect(admin_url('quotations/create' . ($client_id !== '' ? '/' . (int) $client_id : '')));
            }

            $payload = [
                'client_id'            => $clientId,
                'terms'                => $this->input->post('terms'),
                'internal_notes'       => $this->input->post('internal_notes'),
                'markup_percent'       => $this->input->post('markup_percent'),
                'contingency_percent'  => $this->input->post('contingency_percent'),
                'validity_days'        => $this->input->post('validity_days'),
                'discount_percent'     => $this->input->post('discount_percent'),
                'discount_amount'      => $this->input->post('discount_amount'),
                'service_type'         => $this->input->post('service_type') ?: 'signage',
            ];

            $id = $this->quotations_model->create($payload);
            if (!$id) {
                set_alert('danger', 'Unable to create quotation.');
                redirect(admin_url('quotations/create'));
            }

            if ($this->input->post('save_and_submit')) {
                if (!$this->quotations_model->submit_for_approval($id)) {
                    set_alert('warning', 'Quotation was created but could not be submitted for approval.');
                }
            }

            set_alert('success', _l('added_successfully', 'quotation'));
            redirect(admin_url('quotations/edit/' . $id));
        }

        $data['title']                  = 'Create quotation';
        $data['edit']                   = false;
        $data['lines_locked']           = false;
        $data['quotation']              = null;
        $data['estimate']              = null;
        $data['lines_by_tab']           = null;
        $data['client']                 = null;
        $data['promotional_inventory']  = $this->quotations_model->get_promotional_inventory_items();
        $data['default_markup']         = qt_setting('qt_default_markup');
        $data['default_contingency']    = qt_setting('qt_default_contingency');
        $data['default_validity_days']  = qt_setting('qt_default_validity_days');
        $data['default_terms']            = qt_setting('qt_terms_and_conditions');
        $data['version_history']          = [];
        $data['client_primary_email']     = '';
        $data['qt_discount_threshold']    = (float) (qt_setting('qt_discount_requires_approval_above') ?: 10);
        $data['qt_vat_rate']              = qt_get_vat_rate();
        $data['full_totals']              = null;

        if ($client_id !== '' && is_numeric($client_id)) {
            $this->load->model('clients_model');
            $data['client']      = $this->clients_model->get((int) $client_id);
            $data['customer_id'] = (int) $client_id;
            $data['client_primary_email'] = $this->quotations_primary_contact_email((int) $client_id);
        }

        $this->load->view('quotations/builder', $data);
    }

    public function edit($id = '')
    {
        if (!$this->quotations_user_has_module_access()) {
            access_denied('estimates');
        }

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        if (!qt_can_view_quotation($id)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations'));
        }

        $this->load->model('clients_model');
        $quotation = $this->quotations_model->get($id);
        if (!$quotation) {
            show_404();
        }

        $this->load->model('estimates_model');
        $data['title']            = 'Edit quotation';
        $data['edit']             = true;
        $data['lines_locked']     = ($quotation->status !== 'draft');
        $data['quotation']        = $quotation;
        $data['estimate']         = $this->estimates_model->get((int) $quotation->estimate_id);
        $data['lines_by_tab']     = $this->quotations_model->get_lines_grouped_by_tab($id);
        $data['client']           = $this->clients_model->get((int) $quotation->client_id);
        $data['promotional_inventory'] = $this->quotations_model->get_promotional_inventory_items();
        $data['default_markup']   = qt_setting('qt_default_markup');
        $data['default_contingency'] = qt_setting('qt_default_contingency');
        $data['default_validity_days'] = qt_setting('qt_default_validity_days');
        $data['default_terms']    = qt_setting('qt_terms_and_conditions');
        $data['version_history']  = $this->quotations_model->get_version_history_by_ref($quotation->quotation_ref);
        $data['client_primary_email'] = $this->quotations_primary_contact_email((int) $quotation->client_id);
        $data['qt_discount_threshold'] = (float) (qt_setting('qt_discount_requires_approval_above') ?: 10);
        $data['qt_vat_rate']      = qt_get_vat_rate();
        $data['full_totals']      = $this->quotations_model->get_full_totals_payload($id);

        $this->load->view('quotations/builder', $data);
    }

    public function save_line()
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST' || !$this->input->post()) {
            show_404();
        }

        $quotationId = (int) $this->input->post('quotation_id');
        $tab         = (string) $this->input->post('tab');
        $description = trim((string) $this->input->post('description'));
        if ($description === '') {
            $description = '—';
        }

        if ($quotationId < 1) {
            echo json_encode(['success' => false, 'message' => 'quotation_id is required']);

            return;
        }

        if (!qt_can_view_quotation($quotationId)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $q = $this->quotations_model->get($quotationId);
        if (!$q || $q->status !== 'draft') {
            echo json_encode(['success' => false, 'message' => 'Only draft quotations can be edited.']);

            return;
        }

        $lineId = (int) $this->input->post('line_id');
        $lineData = [
            'tab'                 => $tab,
            'description'         => $description,
            'item_code'           => $this->input->post('item_code'),
            'inventory_item_id'   => $this->input->post('inventory_item_id'),
            'unit'                => $this->input->post('unit'),
            'quantity'            => $this->input->post('quantity'),
            'width_m'             => $this->input->post('width_m'),
            'height_m'            => $this->input->post('height_m'),
            'computed_area'       => $this->input->post('computed_area'),
            'cost_price'          => $this->input->post('cost_price'),
            'markup_percent'      => $this->input->post('markup_percent'),
            'sell_price'          => $this->input->post('sell_price'),
            'sell_price_manual'   => $this->input->post('sell_price_manual'),
            'is_taxable'          => $this->input->post('is_taxable'),
            'notes'               => $this->input->post('notes'),
            'line_order'          => $this->input->post('line_order'),
        ];

        $savedId = $this->quotations_model->save_line($quotationId, $lineData, $lineId);
        if ($savedId === false) {
            echo json_encode(['success' => false, 'message' => 'Could not save line']);

            return;
        }

        $payload = $this->quotations_model->get_full_totals_payload($quotationId);

        echo json_encode([
            'success' => true,
            'line_id' => (int) $savedId,
            'totals'  => [
                'subtotal'    => (float) ($payload['subtotal'] ?? 0),
                'vat'         => (float) ($payload['vat'] ?? 0),
                'grand_total' => (float) ($payload['grand_total'] ?? 0),
            ],
            'full_totals' => $payload,
        ]);
    }

    public function delete_line()
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST' || !$this->input->post()) {
            show_404();
        }

        $lineId      = (int) $this->input->post('line_id');
        $quotationId = (int) $this->input->post('quotation_id');
        if ($lineId < 1 || $quotationId < 1) {
            echo json_encode(['success' => false, 'message' => 'line_id and quotation_id are required']);

            return;
        }

        if (!qt_can_view_quotation($quotationId)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $q = $this->quotations_model->get($quotationId);
        if (!$q || $q->status !== 'draft') {
            echo json_encode(['success' => false, 'message' => 'Only draft quotations can be edited.']);

            return;
        }

        $line = $this->quotations_model->get_line($lineId, $quotationId);
        if (!$line) {
            echo json_encode(['success' => false, 'message' => 'Line not found']);

            return;
        }

        $this->quotations_model->delete_line($lineId);
        $payload = $this->quotations_model->get_full_totals_payload($quotationId);

        echo json_encode([
            'success'     => true,
            'totals'      => [
                'subtotal'    => (float) ($payload['subtotal'] ?? 0),
                'vat'         => (float) ($payload['vat'] ?? 0),
                'grand_total' => (float) ($payload['grand_total'] ?? 0),
            ],
            'full_totals' => $payload,
        ]);
    }

    public function ajax_create()
    {
        // Do not require is_ajax_request(): some stacks strip X-Requested-With; jQuery still POSTs JSON.
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            show_404();
        }

        if (staff_cant('create', 'estimates')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        if (!$this->quotations_user_has_module_access()) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $clientId = (int) $this->input->post('client_id');
        if ($clientId < 1) {
            echo json_encode(['success' => false, 'message' => 'Client is required']);

            return;
        }

        $id = $this->quotations_model->create([
            'client_id'       => $clientId,
            'internal_notes'  => $this->input->post('internal_notes'),
            'service_type'    => $this->input->post('service_type') ?: 'signage',
        ]);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Could not create quotation']);

            return;
        }

        $q = $this->quotations_model->get($id);

        echo json_encode([
            'success'        => true,
            'quotation_id'   => (int) $id,
            'quotation_ref'  => $q ? $q->quotation_ref : '',
            'redirect'       => admin_url('quotations/edit/' . $id),
        ]);
    }

    public function save_line_order()
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST' || !$this->input->post()) {
            show_404();
        }

        $quotationId = (int) $this->input->post('quotation_id');
        $ordersRaw   = $this->input->post('orders');
        if ($quotationId < 1) {
            echo json_encode(['success' => false, 'message' => 'quotation_id required']);

            return;
        }

        if (!qt_can_view_quotation($quotationId)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $q = $this->quotations_model->get($quotationId);
        if (!$q || $q->status !== 'draft') {
            echo json_encode(['success' => false, 'message' => 'Only draft quotations can be edited.']);

            return;
        }

        $orders = is_array($ordersRaw) ? $ordersRaw : json_decode((string) $ordersRaw, true);
        if (!is_array($orders)) {
            echo json_encode(['success' => false, 'message' => 'Invalid orders payload']);

            return;
        }

        $map = [];
        if ($orders !== [] && isset($orders[0]) && is_array($orders[0]) && (isset($orders[0]['id']) || isset($orders[0]['line_id']))) {
            foreach ($orders as $row) {
                $lid = (int) ($row['id'] ?? $row['line_id'] ?? 0);
                if ($lid > 0) {
                    $map[$lid] = (int) ($row['order'] ?? $row['line_order'] ?? 0);
                }
            }
        } else {
            foreach ($orders as $lid => $ord) {
                $map[(int) $lid] = (int) $ord;
            }
        }

        $this->quotations_model->update_line_orders($quotationId, $map);
        $payload = $this->quotations_model->get_full_totals_payload($quotationId);

        echo json_encode(['success' => true, 'full_totals' => $payload]);
    }

    public function save_builder()
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST' || !$this->input->post()) {
            show_404();
        }

        $quotationId = (int) $this->input->post('quotation_id');
        if ($quotationId < 1) {
            echo json_encode(['success' => false, 'message' => 'quotation_id required']);

            return;
        }

        if (!qt_can_view_quotation($quotationId)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $q = $this->quotations_model->get($quotationId);
        if (!$q || $q->status !== 'draft') {
            echo json_encode(['success' => false, 'message' => 'Only draft quotations can be edited.']);

            return;
        }

        $quoteDate = (string) $this->input->post('quote_date');
        $validUntil = (string) $this->input->post('valid_until');
        $validDays = null;
        if ($quoteDate !== '' && $validUntil !== '') {
            $ts1 = strtotime($quoteDate);
            $ts2 = strtotime($validUntil);
            if ($ts1 && $ts2 && $ts2 >= $ts1) {
                $validDays = (int) round(($ts2 - $ts1) / 86400);
            }
        }

        $data = [
            'internal_notes'      => $this->input->post('internal_notes'),
            'contingency_percent' => $this->input->post('contingency_percent'),
            'discount_percent'    => $this->input->post('discount_percent'),
            'discount_amount'     => $this->input->post('discount_amount'),
            'quote_date'          => $quoteDate,
            'expirydate'          => $validUntil,
        ];

        if ($validDays !== null) {
            $data['validity_days'] = $validDays;
        }

        $clientId = (int) $this->input->post('client_id');
        if ($clientId > 0) {
            $data['client_id'] = $clientId;
        }

        $this->quotations_model->update_builder_header($quotationId, $data);

        echo json_encode([
            'success'     => true,
            'full_totals' => $this->quotations_model->get_full_totals_payload($quotationId),
        ]);
    }

    public function get_inventory_item()
    {
        $commodityId = (int) $this->input->get('commodity_id');
        $code          = trim((string) $this->input->get('code'));

        $row = null;
        if ($commodityId > 0) {
            $row = $this->quotations_model->get_inventory_item_row($commodityId);
        } elseif ($code !== '') {
            $row = $this->quotations_model->get_inventory_item_row_by_code($code);
            if ($row) {
                $commodityId = (int) ($row->commodity_id ?? $row->id ?? 0);
            }
        }

        if (!$row) {
            echo json_encode(['success' => false, 'message' => $code !== '' || $commodityId > 0 ? 'Not found' : 'code or commodity_id required']);

            return;
        }

        if ($commodityId < 1) {
            $commodityId = (int) ($row->commodity_id ?? $row->id ?? 0);
        }

        $row = $this->quotations_model->get_inventory_item_row($commodityId) ?: $row;
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Not found']);

            return;
        }

        $unit = $row->unit ?? null;
        if ($unit === null && !empty($row->unit_type_id)) {
            $ut = db_prefix() . 'ware_unit_type';
            if ($this->db->table_exists($ut)) {
                $u = $this->db->where('unit_type_id', (int) $row->unit_type_id)->get($ut)->row();
                if ($u && isset($u->unit_name)) {
                    $unit = $u->unit_name;
                }
            }
        }

        echo json_encode([
            'success'        => true,
            'commodity_id'   => (int) ($row->commodity_id ?? $commodityId),
            'commodity_name' => $row->commodity_name ?? $row->description ?? '',
            'commodity_code' => $row->commodity_code ?? '',
            'wac_price'      => (float) ($row->wac_price ?? $row->purchase_price ?? $row->rate ?? 0),
            'unit'           => $unit,
        ]);
    }

    public function search_inventory()
    {
        $term         = (string) $this->input->get('term');
        $warehouseId  = $this->input->get('warehouse_id');
        $warehouseId  = $warehouseId !== null && $warehouseId !== '' ? (int) $warehouseId : null;

        $rows = $this->quotations_model->search_inventory($term, $warehouseId);
        echo json_encode($rows);
    }

    public function submit_for_approval($id = '')
    {
        $id = (int) $id;
        if ($id < 1 || !$this->input->post()) {
            show_404();
        }

        if (!$this->quotations_user_has_module_access()) {
            access_denied('estimates');
        }

        if (!qt_can_view_quotation($id)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations'));
        }

        $q = $this->quotations_model->get($id);
        if (!$q) {
            show_404();
        }

        if (!$this->quotation_can_submit_or_revise($q)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations/view/' . $id));
        }

        if ($q->status !== 'draft') {
            set_alert('warning', 'Only draft quotations can be submitted.');
            redirect(admin_url('quotations/view/' . $id));
        }

        if ($this->quotations_model->submit_for_approval($id)) {
            set_alert('success', 'Quotation submitted for approval');
        } else {
            set_alert('danger', 'Unable to submit for approval.');
        }

        redirect(admin_url('quotations/view/' . $id));
    }

    public function create_revision($id = '')
    {
        $id = (int) $id;
        if ($id < 1 || !$this->input->post()) {
            show_404();
        }

        if (!$this->quotations_user_has_module_access()) {
            access_denied('estimates');
        }

        if (!qt_can_view_quotation($id)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations'));
        }

        $q = $this->db->where('id', $id)->get(db_prefix() . 'ipms_quotations')->row();
        if (!$q) {
            show_404();
        }

        if (!$this->quotation_can_submit_or_revise($q)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations/view/' . $id));
        }

        if ($q->status !== 'rejected') {
            set_alert('warning', 'Only rejected quotations can be revised.');
            redirect(admin_url('quotations/view/' . $id));
        }

        $notes = (string) $this->input->post('revision_notes');
        $newId = $this->quotations_model->create_revision($id, $notes);
        if (!$newId) {
            set_alert('danger', 'Could not create revision.');
            redirect(admin_url('quotations/view/' . $id));
        }

        $ver = (int) $this->db->select('version')->where('id', $newId)->get(db_prefix() . 'ipms_quotations')->row()->version;
        set_alert('success', 'Revision v' . $ver . ' created');
        redirect(admin_url('quotations/edit/' . $newId));
    }

    public function view($id = '')
    {
        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        if (!qt_can_view_quotation($id)) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations'));
        }

        $quotation = $this->quotations_model->get_for_view($id);
        if (!$quotation) {
            show_404();
        }

        $approvalHistory = [];
        if (!empty($quotation->approval_request_id)) {
            $approvalHistory = $this->quotations_model->get_approval_history((int) $quotation->approval_request_id);
        }

        $pendingApproverName = '';
        if ($quotation->status === 'submitted' && !empty($quotation->approval_request_id)) {
            $reqRow = $this->db->where('id', (int) $quotation->approval_request_id)
                ->get(db_prefix() . 'ipms_approval_requests')->row();
            if ($reqRow && !empty($reqRow->current_approver_id)) {
                $pendingApproverName = get_staff_full_name((int) $reqRow->current_approver_id);
            }
        }

        $rejectionReason = '';
        if ($quotation->status === 'rejected' && $approvalHistory !== []) {
            foreach (array_reverse($approvalHistory) as $act) {
                if (($act['action'] ?? '') === 'rejected') {
                    $rejectionReason = trim((string) ($act['comments'] ?? ''));
                    break;
                }
            }
        }

        $this->load->model('clients_model');
        $this->load->model('estimates_model');
        $client   = $this->clients_model->get((int) $quotation->client_id);
        $estimate = $this->estimates_model->get((int) $quotation->estimate_id);

        $versionHistory = $this->quotations_model->get_version_history_by_ref($quotation->quotation_ref);
        $maxVer         = 1;
        foreach ($versionHistory as $vh) {
            $maxVer = max($maxVer, (int) ($vh['version'] ?? 1));
        }
        $nextVersion = $maxVer + 1;

        $data['title']                 = 'Quotation ' . $quotation->quotation_ref;
        $data['quotation']             = $quotation;
        $data['client']                = $client;
        $data['estimate']              = $estimate;
        $data['approval_history']     = $approvalHistory;
        $data['version_history']       = $versionHistory;
        $data['full_totals']           = $this->quotations_model->get_full_totals_payload($id);
        $data['pending_approver_name'] = $pendingApproverName;
        $data['rejection_reason']      = $rejectionReason;
        $data['next_revision_version'] = $nextVersion;
        $data['is_creator']            = (int) $quotation->created_by === (int) get_staff_user_id();
        $data['can_view_margin']        = qt_can_view_quotation_margin();
        $data['can_convert_to_job']     = qt_can_convert_quotation_to_job();
        $data['client_primary_email']   = $this->quotations_primary_contact_email((int) $quotation->client_id);
        $data['can_submit_for_approval'] = $quotation->status === 'draft' && $this->quotation_can_submit_or_revise($quotation);
        $data['can_create_revision']    = $quotation->status === 'rejected' && $this->quotation_can_submit_or_revise($quotation);

        $this->load->view('quotations/view', $data);
    }

    /**
     * Placeholder for job conversion workflow (wire to projects/jobs when available).
     */
    public function convert_to_job($id = '')
    {
        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        if (!qt_can_view_quotation($id) || !qt_can_convert_quotation_to_job()) {
            set_alert('danger', _l('access_denied'));
            redirect(admin_url('quotations/view/' . $id));
        }

        $q = $this->quotations_model->get($id);
        if (!$q || $q->status !== 'approved') {
            set_alert('warning', 'Only approved quotations can be converted to a job.');
            redirect(admin_url('quotations/view/' . $id));
        }

        set_alert('info', 'Job conversion is not configured yet. Use internal process to open a job from this quotation.');
        redirect(admin_url('quotations/view/' . $id));
    }

    public function pdf($id = '')
    {
        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        if (!qt_can_view_quotation($id)) {
            access_denied('estimates');
        }

        $q = $this->quotations_model->get($id);
        if (!$q) {
            show_404();
        }

        $this->load->model('estimates_model');
        $estimate = $this->estimates_model->get((int) $q->estimate_id);
        if (!$estimate) {
            show_404();
        }

        $this->load->library('quotations/Quotation_pdf');
        $this->quotation_pdf->generate($id, 'D');
    }

    public function send_email($id = '')
    {
        $id = (int) $id;
        if ($id < 1 || !$this->input->post()) {
            show_404();
        }

        if (!qt_can_view_quotation($id)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $to = trim((string) $this->input->post('recipient_email'));
        if ($to === '' || !valid_email($to)) {
            echo json_encode(['success' => false, 'message' => 'Valid recipient email is required']);

            return;
        }

        $q = $this->quotations_model->get($id);
        if (!$q) {
            echo json_encode(['success' => false, 'message' => 'Quotation not found']);

            return;
        }

        if ($q->status !== 'approved') {
            echo json_encode(['success' => false, 'message' => 'Only approved quotations can be sent to the client.']);

            return;
        }

        $attachPdf = $this->input->post('attach_pdf');
        $doAttach  = !($attachPdf === '0' || $attachPdf === 0 || $attachPdf === 'false' || $attachPdf === false);

        $attachPath = false;
        $name       = preg_replace('/[^A-Za-z0-9._-]+/', '_', $q->quotation_ref . '_v' . (int) $q->version . '.pdf');
        if ($doAttach) {
            $this->load->library('quotations/Quotation_pdf');
            $attachPath = $this->quotation_pdf->get_pdf_path($id);
            if ($attachPath === false || !is_file($attachPath)) {
                echo json_encode(['success' => false, 'message' => 'Could not generate quotation PDF']);

                return;
            }
        }

        $this->load->model('clients_model');
        $tplData = [
            'quotation' => $q,
            'client'    => $this->clients_model->get((int) $q->client_id),
        ];
        $customBody = trim((string) $this->input->post('message'));
        $body       = $customBody !== ''
            ? nl2br(html_escape($customBody))
            : $this->load->view('quotations/email_template', $tplData, true);

        $subject = trim((string) $this->input->post('subject'));
        if ($subject === '') {
            $subject = 'Quotation ' . $q->quotation_ref . ' from ' . get_option('companyname');
        }

        $this->load->library('email');
        $this->email->clear(true);
        $this->email->from(get_option('smtp_email'), get_option('companyname'));
        $this->email->to($to);
        $cc = trim((string) $this->input->post('cc'));
        if ($cc !== '') {
            $this->email->cc($cc);
        }
        $this->email->subject($subject);
        $this->email->set_mailtype('html');
        $this->email->message($body);
        if ($attachPath) {
            $this->email->attach($attachPath, 'attachment', $name, 'application/pdf');
        }

        $ok = $this->email->send(true);

        if ($ok) {
            echo json_encode(['success' => true, 'message' => _l('sent', 'email')]);
        } else {
            echo json_encode(['success' => false, 'message' => $this->email->print_debugger()]);
        }
    }

    /**
     * @param int $clientId
     */
    protected function quotations_primary_contact_email($clientId)
    {
        if ($clientId < 1) {
            return '';
        }
        $this->load->model('clients_model');
        $contacts = $this->clients_model->get_contacts($clientId, ['active' => 1]);
        foreach ($contacts as $c) {
            if (!empty($c['is_primary']) && (int) $c['is_primary'] === 1 && !empty($c['email'])) {
                return (string) $c['email'];
            }
        }
        foreach ($contacts as $c) {
            if (!empty($c['email'])) {
                return (string) $c['email'];
            }
        }

        return '';
    }

    public function save_settings()
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!is_admin() && qt_current_user_role() !== 'System Administrator') {
            access_denied('estimates');
        }

        $post = $this->input->post();
        if (!isset($post['qt_year_in_ref'])) {
            $post['qt_year_in_ref'] = '0';
        }

        $this->handle_quotation_logo_upload();
        $this->quotations_model->update_settings($post);
        set_alert('success', _l('settings_updated'));
        redirect(admin_url('quotations/settings'));
    }

    /**
     * Save uploads/quotation_logo.png from optional logo file field.
     */
    protected function handle_quotation_logo_upload()
    {
        if (empty($_FILES['quotation_logo']['tmp_name']) || !is_uploaded_file($_FILES['quotation_logo']['tmp_name'])) {
            return;
        }

        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            set_alert('warning', 'PHP GD extension is required to process the company logo upload.');

            return;
        }

        $bin = @file_get_contents($_FILES['quotation_logo']['tmp_name']);
        if ($bin === false || $bin === '') {
            set_alert('warning', 'Could not read the uploaded logo file.');

            return;
        }

        $im = @imagecreatefromstring($bin);
        if ($im === false) {
            set_alert('warning', 'Invalid image file for company logo. Use PNG, JPG, or GIF.');

            return;
        }

        $uploads = FCPATH . 'uploads';
        if (!is_dir($uploads)) {
            @mkdir($uploads, 0755, true);
        }

        $dest = $uploads . DIRECTORY_SEPARATOR . 'quotation_logo.png';
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($im);
        }
        imagealphablending($im, false);
        imagesavealpha($im, true);
        if (!@imagepng($im, $dest)) {
            set_alert('warning', 'Could not save the company logo.');
        }
        imagedestroy($im);
    }

    public function settings()
    {
        if (!is_admin() && qt_current_user_role() !== 'System Administrator') {
            access_denied('estimates');
        }

        $data['title']              = 'Quotation settings';
        $data['settings']           = $this->quotations_model->get_all_settings();
        $data['quotation_threshold'] = false;
        $t                          = db_prefix() . 'ipms_approval_thresholds';
        if ($this->db->table_exists($t)) {
            $this->load->model('approvals/approvals_model');
            $data['quotation_threshold'] = $this->approvals_model->get_thresholds('quotation');
        }

        $this->load->view('quotations/settings', $data);
    }

    public function get_tab_totals()
    {
        $quotationId = (int) $this->input->get('quotation_id');
        if ($quotationId < 1) {
            echo json_encode(['success' => false, 'message' => 'quotation_id required']);

            return;
        }

        if (!qt_can_view_quotation($quotationId)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $out = $this->quotations_model->get_tab_totals_json($quotationId);
        echo json_encode(array_merge(['success' => true], $out));
    }

    /**
     * @return bool
     */
    protected function quotations_user_has_module_access()
    {
        if (staff_can('view', 'estimates') || staff_can('view_own', 'estimates')) {
            return true;
        }

        if (is_admin()) {
            return true;
        }

        $role = function_exists('get_staff_role') ? get_staff_role(get_staff_user_id()) : '';

        return in_array($role, [
            'Sales Executive',
            'Sales Representative',
            'Sales Manager',
            'Finance Manager',
            'General Manager',
            'GM',
            'System Administrator',
        ], true);
    }

    /**
     * @param object $q
     */
    protected function quotation_can_submit_or_revise($q)
    {
        if (is_admin()) {
            return true;
        }
        if ((int) $q->created_by === (int) get_staff_user_id()) {
            return true;
        }

        return qt_current_user_role() === 'Sales Manager';
    }
}

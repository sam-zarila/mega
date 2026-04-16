<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Job_cards extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('job_cards/job_cards_model');
        $this->load->helper('job_cards/job_cards');
        $this->lang->load('job_cards/job_cards', $GLOBALS['language'] ?? 'english');
    }

    public function index()
    {
        $data['title']     = 'Job Cards';
        $data['job_cards'] = $this->job_cards_model->get();
        $data['counts']    = $this->job_cards_model->get_summary_counts();

        $this->load->view('job_cards/list', $data);
    }

    public function view($id)
    {
        $id = (int) $id;
        if ($id < 1 || !jc_can_view($id)) {
            set_alert('danger', 'You do not have permission to view this job card.');
            redirect(admin_url('job_cards'));
        }

        $jobCard = $this->job_cards_model->get($id);
        if (!$jobCard) {
            show_404();
        }

        $data['title']    = 'Job Card: ' . $jobCard->jc_ref;
        $data['job_card'] = $jobCard;
        $data['worksheet'] = $this->db
            ->where('proposal_id', (int) $jobCard->proposal_id)
            ->get(db_prefix() . 'ipms_qt_worksheets')
            ->row();
        $data['client'] = $this->db
            ->where('userid', (int) $jobCard->client_id)
            ->get(db_prefix() . 'clients')
            ->row();

        $this->load->view('job_cards/view', $data);
    }

    public function create($proposal_id = '')
    {
        if (!$this->is_manager_or_admin()) {
            access_denied('job_cards');
        }

        $proposal_id = (int) $proposal_id;

        if ($this->input->post()) {
            $clientId       = (int) $this->input->post('client_id');
            $description    = trim((string) $this->input->post('job_description'));
            $selectedTypes  = $this->input->post('job_type');
            $jobTypes       = is_array($selectedTypes) ? array_values(array_unique(array_map('trim', $selectedTypes))) : [];
            $jobTypeStr     = implode(',', $jobTypes);
            $routing        = jc_determine_routing($jobTypeStr);

            if ($clientId < 1 || $description === '') {
                set_alert('danger', 'Client and job description are required.');
                redirect(admin_url('job_cards/create' . ($proposal_id > 0 ? '/' . $proposal_id : '')));
            }

            $data = [
                'jc_ref'             => jc_generate_ref(),
                'proposal_id'        => (int) $this->input->post('proposal_id'),
                'qt_ref'             => (string) $this->input->post('qt_ref'),
                'client_id'          => $clientId,
                'created_by'         => (int) get_staff_user_id(),
                'assigned_sales_id'  => (int) $this->input->post('assigned_sales_id'),
                'job_description'    => $description,
                'job_type'           => $jobTypeStr,
                'department_routing' => implode(',', $routing),
                'status'             => 1,
                'start_date'         => $this->input->post('start_date') ?: date('Y-m-d'),
                'deadline'           => $this->input->post('deadline') ?: null,
                'special_instructions' => (string) $this->input->post('special_instructions'),
                'approved_cost'      => (float) $this->input->post('approved_cost'),
                'approved_sell'      => (float) $this->input->post('approved_sell'),
                'approved_total'     => (float) $this->input->post('approved_total'),
            ];

            $this->db->insert(db_prefix() . 'ipms_job_cards', $data);
            $newId = (int) $this->db->insert_id();
            if ($newId < 1) {
                set_alert('danger', 'Could not create job card.');
                redirect(admin_url('job_cards/create' . ($proposal_id > 0 ? '/' . $proposal_id : '')));
            }

            foreach ($routing as $dept) {
                $this->db->insert(db_prefix() . 'ipms_jc_department_assignments', [
                    'job_card_id' => $newId,
                    'department'  => $dept,
                    'notified_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->insert(db_prefix() . 'ipms_jc_status_log', [
                'job_card_id'     => $newId,
                'from_status'     => 0,
                'to_status'       => 1,
                'changed_by'      => (int) get_staff_user_id(),
                'changed_by_name' => get_staff_full_name(get_staff_user_id()),
                'changed_by_role' => $this->current_role_name(),
                'notes'           => 'Manual job card creation',
                'changed_at'      => date('Y-m-d H:i:s'),
            ]);

            set_alert('success', 'Job card created successfully.');
            redirect(admin_url('job_cards/view/' . $newId));
        }

        $data['title']    = 'Create Job Card';
        $data['proposal'] = null;
        $data['client']   = null;
        if ($proposal_id > 0) {
            $data['proposal'] = $this->db->where('id', $proposal_id)->get(db_prefix() . 'proposals')->row();
            if ($data['proposal'] && $data['proposal']->rel_type === 'customer') {
                $data['client'] = $this->db->where('userid', (int) $data['proposal']->rel_id)->get(db_prefix() . 'clients')->row();
            }
        }
        $data['clients'] = $this->db->where('active', 1)->order_by('company', 'ASC')->get(db_prefix() . 'clients')->result_array();
        $data['staff']   = $this->db->where('active', 1)->order_by('firstname', 'ASC')->get(db_prefix() . 'staff')->result_array();

        $this->load->view('job_cards/create', $data);
    }

    public function update_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $jobCardId = (int) $this->input->post('job_card_id');
        $newStatus = (int) $this->input->post('new_status');
        $notes     = (string) $this->input->post('notes');

        if ($jobCardId < 1 || $newStatus < 1) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        if (!jc_can_update_status($jobCardId, $newStatus)) {
            echo json_encode(['success' => false, 'message' => 'Not allowed to update status']);
            return;
        }

        $role = $this->current_role_name();
        if (!$this->can_set_status_by_role($newStatus, $role)) {
            echo json_encode(['success' => false, 'message' => 'Role is not allowed to set this status']);
            return;
        }

        $ok = $this->job_cards_model->update_status($jobCardId, $newStatus, $notes, get_staff_user_id());
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Status update failed']);
            return;
        }

        $jobCard = $this->job_cards_model->get($jobCardId);
        if ($jobCard && (int) $jobCard->assigned_sales_id > 0 && in_array($newStatus, [2, 5, 6], true)) {
            add_notification([
                'description' => 'Job Card ' . $jobCard->jc_ref . ' moved to ' . jc_get_status_label($newStatus)['label'],
                'touserid'    => (int) $jobCard->assigned_sales_id,
                'fromuserid'  => (int) get_staff_user_id(),
                'link'        => 'job_cards/view/' . $jobCardId,
            ]);
        }

        if (in_array($newStatus, [5, 6], true)) {
            foreach (jc_get_staff_by_role('General Manager') as $gm) {
                add_notification([
                    'description' => 'Job Card ' . $jobCard->jc_ref . ' reached ' . jc_get_status_label($newStatus)['label'],
                    'touserid'    => (int) $gm->staffid,
                    'fromuserid'  => (int) get_staff_user_id(),
                    'link'        => 'job_cards/view/' . $jobCardId,
                ]);
            }
        }

        echo json_encode([
            'success'    => true,
            'new_status' => $newStatus,
            'badge_html' => jc_get_status_badge($newStatus),
            'message'    => 'Job card status updated',
        ]);
    }

    public function create_material_issue($job_card_id)
    {
        $job_card_id = (int) $job_card_id;
        $jobCard     = $this->job_cards_model->get($job_card_id);
        if (!$jobCard || !jc_can_view($job_card_id)) {
            set_alert('danger', 'Job card not found or access denied.');
            redirect(admin_url('job_cards'));
        }

        if ($this->input->post()) {
            $warehouseId = (int) $this->input->post('warehouse_id');
            $qtyIssued   = (array) $this->input->post('qty_issued');
            $qtLineIds   = (array) $this->input->post('qt_line_id');
            $wacAtIssue  = (array) $this->input->post('wac_at_issue');
            $issueItems  = (array) $this->input->post('issue_items');

            $lines      = [];
            $shortfalls = [];

            foreach ($qtyIssued as $itemIdRaw => $qtyRaw) {
                $itemId = (int) $itemIdRaw;
                $qty    = (float) $qtyRaw;
                if ($itemId < 1 || $qty <= 0) {
                    continue;
                }
                if (!in_array((string) $itemIdRaw, array_map('strval', $issueItems), true) && !in_array($itemId, array_map('intval', $issueItems), true)) {
                    continue;
                }

                $commodity = $this->db->where('commodity_id', $itemId)->get(db_prefix() . 'ware_commodity')->row();
                if (!$commodity) {
                    continue;
                }

                $available = isset($commodity->current_quantity) ? (float) $commodity->current_quantity : 0.0;
                if ($available < $qty) {
                    $shortfalls[] = ($commodity->commodity_name ?: ('Item #' . $itemId)) . ' (need ' . $qty . ', available ' . $available . ')';
                }

                $lines[] = [
                    'inventory_item_id' => $itemId,
                    'qty_issued'        => $qty,
                    'qty_required'      => $qty,
                    'wac_at_issue'      => isset($wacAtIssue[$itemIdRaw]) ? (float) $wacAtIssue[$itemIdRaw] : (float) $commodity->wac_price,
                    'qt_line_id'        => isset($qtLineIds[$itemIdRaw]) ? (int) $qtLineIds[$itemIdRaw] : null,
                    'item_code'         => (string) $commodity->commodity_code,
                    'item_description'  => (string) $commodity->commodity_name,
                ];
            }

            if (empty($lines)) {
                set_alert('danger', 'Please provide at least one issued item with quantity greater than zero.');
                redirect(admin_url('job_cards/create_material_issue/' . $job_card_id));
            }

            if (!empty($shortfalls)) {
                set_alert('danger', 'Insufficient stock: ' . implode('; ', $shortfalls));
                redirect(admin_url('job_cards/create_material_issue/' . $job_card_id));
            }

            $issueId = $this->job_cards_model->create_material_issue($job_card_id, [
                'warehouse_id' => $warehouseId,
                'issued_by'    => (int) get_staff_user_id(),
                'notes'        => (string) $this->input->post('notes'),
            ], $lines);

            if (!$issueId) {
                set_alert('danger', 'Could not create material issue.');
                redirect(admin_url('job_cards/create_material_issue/' . $job_card_id));
            }

            foreach (jc_get_staff_by_role(jc_setting('jc_store_manager_role', 'Store Manager')) as $manager) {
                add_notification([
                    'description' => 'Materials issued for ' . $jobCard->jc_ref,
                    'touserid'    => (int) $manager->staffid,
                    'fromuserid'  => (int) get_staff_user_id(),
                    'link'        => 'job_cards/view/' . $job_card_id,
                ]);
            }

            if ((int) $jobCard->assigned_sales_id > 0) {
                add_notification([
                    'description' => 'Materials issued for ' . $jobCard->jc_ref,
                    'touserid'    => (int) $jobCard->assigned_sales_id,
                    'fromuserid'  => (int) get_staff_user_id(),
                    'link'        => 'job_cards/view/' . $job_card_id,
                ]);
            }

            set_alert('success', 'Materials issued successfully.');
            redirect(admin_url('job_cards/view/' . $job_card_id));
        }

        $data['title']     = 'Issue Materials';
        $data['job_card']  = $jobCard;
        $data['qt_lines']  = $this->job_cards_model->get_qt_lines_for_job((int) $jobCard->proposal_id);
        $data['client']    = $this->db->where('userid', (int) $jobCard->client_id)->get(db_prefix() . 'clients')->row();
        $data['warehouses'] = $this->get_warehouses();

        $this->load->view('job_cards/material_issue_form', $data);
    }

    public function get_material_issue($issue_id)
    {
        $issue_id = (int) $issue_id;
        $issue    = $this->job_cards_model->get_issue($issue_id);
        if (!$issue) {
            show_404();
        }

        if (!jc_can_view((int) $issue->job_card_id)) {
            access_denied('job_cards');
        }

        $data['title'] = 'Material Issue: ' . $issue->issue_ref;
        $data['issue'] = $issue;

        $this->load->view('job_cards/material_issue_view', $data);
    }

    public function update_notes()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $jobCardId = (int) $this->input->post('job_card_id');
        $noteType  = (string) $this->input->post('note_type');
        $value     = (string) $this->input->post('value');

        if ($jobCardId < 1 || !in_array($noteType, ['production_notes', 'quality_notes', 'special_instructions'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $role = $this->current_role_name();
        $isManager = $this->is_manager_or_admin();
        $isStudio  = $role === 'Studio/Production';

        if (in_array($noteType, ['production_notes', 'quality_notes'], true) && !($isStudio || $isManager)) {
            echo json_encode(['success' => false, 'message' => 'Not allowed to update this note']);
            return;
        }
        if ($noteType === 'special_instructions' && !$isManager) {
            echo json_encode(['success' => false, 'message' => 'Only managers can update special instructions']);
            return;
        }

        $this->db->where('id', $jobCardId);
        $ok = $this->db->update(db_prefix() . 'ipms_job_cards', [$noteType => $value]);

        echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'Notes updated' : 'Failed to update notes']);
    }

    public function acknowledge_department()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $jobCardId   = (int) $this->input->post('job_card_id');
        $department  = (string) $this->input->post('department');

        if ($jobCardId < 1 || $department === '') {
            echo json_encode(['success' => false]);
            return;
        }

        $this->db->where('job_card_id', $jobCardId);
        $this->db->where('department', $department);
        $ok = $this->db->update(db_prefix() . 'ipms_jc_department_assignments', [
            'acknowledged_by' => (int) get_staff_user_id(),
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => (bool) $ok]);
    }

    public function get_timeline($job_card_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $job_card_id = (int) $job_card_id;
        if ($job_card_id < 1 || !jc_can_view($job_card_id)) {
            echo json_encode([]);
            return;
        }

        $rows = $this->job_cards_model->get_status_log($job_card_id);
        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'from_status' => (int) $row['from_status'],
                'to_status'   => (int) $row['to_status'],
                'label'       => jc_get_status_label((int) $row['to_status'])['label'],
                'actor'       => $row['changed_by_name'] ?: ('User #' . (int) $row['changed_by']),
                'role'        => $row['changed_by_role'],
                'notes'       => $row['notes'],
                'changed_at'  => _dt($row['changed_at']),
            ];
        }

        echo json_encode($events);
    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('job_cards');
        }

        if ($this->input->post()) {
            $keys = [
                'jc_prefix',
                'jc_next_number',
                'iss_prefix',
                'iss_next_number',
                'jc_default_deadline_days',
                'jc_auto_create_on_approval',
                'jc_studio_role',
                'jc_stores_role',
                'jc_store_manager_role',
                'jc_field_team_role',
                'jc_warehouse_role',
            ];

            foreach ($keys as $key) {
                if ($this->input->post($key) !== null) {
                    $this->db->where('setting_key', $key);
                    $this->db->update(db_prefix() . 'ipms_jc_settings', [
                        'setting_value' => (string) $this->input->post($key),
                    ]);
                }
            }

            set_alert('success', 'Settings updated');
            redirect(admin_url('job_cards/settings'));
        }

        $settingsRows = $this->db->get(db_prefix() . 'ipms_jc_settings')->result_array();
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $data['title']    = 'Job Card Settings';
        $data['settings'] = $settings;
        $this->load->view('job_cards/settings', $data);
    }

    public function pdf($id)
    {
        $id = (int) $id;
        if ($id < 1 || !jc_can_view($id)) {
            access_denied('job_cards');
        }

        $jobCard = $this->job_cards_model->get($id);
        if (!$jobCard) {
            show_404();
        }

        $p = db_prefix();
        $client = $this->db->where('userid', (int) $jobCard->client_id)->get($p . 'clients')->row();
        $proposal = $this->db->where('id', (int) $jobCard->proposal_id)->get($p . 'proposals')->row();
        $worksheet = $this->db->where('proposal_id', (int) $jobCard->proposal_id)->get($p . 'ipms_qt_worksheets')->row();
        $lines = $this->db->where('proposal_id', (int) $jobCard->proposal_id)->order_by('tab', 'ASC')->order_by('id', 'ASC')->get($p . 'ipms_qt_lines')->result_array();
        $settingsRows = $this->db->get($p . 'ipms_jc_settings')->result_array();

        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $companyName = isset($settings['jc_company_name']) && $settings['jc_company_name'] !== ''
            ? $settings['jc_company_name']
            : (get_option('companyname') ?: 'MW');

        $statusMeta = jc_get_status_label((int) $jobCard->status);
        $statusLabel = $statusMeta['label'];
        $statusColor = $statusMeta['color'];
        $deadline = (string) $jobCard->deadline;
        $isOverdue = $deadline !== '' && $deadline < date('Y-m-d') && (int) $jobCard->status < 6;
        $version = $worksheet && isset($worksheet->version) ? (int) $worksheet->version : 1;
        $assignedTo = $jobCard->assigned_sales_name ?: ((int) $jobCard->assigned_sales_id > 0 ? get_staff_full_name((int) $jobCard->assigned_sales_id) : '—');
        $clientName = $client && isset($client->company) ? $client->company : 'Unknown Client';

        $grouped = [];
        foreach ($lines as $line) {
            $tab = isset($line['tab']) && $line['tab'] !== '' ? (string) $line['tab'] : 'other';
            if (!isset($grouped[$tab])) {
                $grouped[$tab] = [];
            }
            $grouped[$tab][] = $line;
        }

        require_once APPPATH . 'third_party/mPDF/vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 30,
            'margin_bottom' => 20,
        ]);

        $headerHtml = '
        <table width="100%" style="border-bottom:1px solid #d9d9d9;padding-bottom:6px;">
            <tr>
                <td width="60%">
                    <div style="font-size:14pt;font-weight:bold;">' . html_escape($companyName) . '</div>
                    <div style="font-size:18pt;font-weight:bold;">Job Card</div>
                </td>
                <td width="40%" style="text-align:right;">
                    <div style="font-size:20pt;font-weight:bold;">' . html_escape($jobCard->jc_ref) . '</div>
                    <span style="display:inline-block;background:' . html_escape($statusColor) . ';color:#fff;padding:3px 10px;border-radius:3px;font-size:10pt;">' . html_escape($statusLabel) . '</span>
                </td>
            </tr>
        </table>';
        $mpdf->SetHTMLHeader($headerHtml);

        $footerHtml = '
        <table width="100%" style="font-size:9pt;color:#666;">
            <tr>
                <td width="40%">MW — Internal Production Document — Confidential</td>
                <td width="20%" style="text-align:center;">Page {PAGENO} of {nbpg}</td>
                <td width="40%" style="text-align:right;">' . html_escape($jobCard->jc_ref) . ' | Printed ' . date('Y-m-d H:i') . '</td>
            </tr>
        </table>';
        $mpdf->SetHTMLFooter($footerHtml);

        $html = '';
        $html .= '<style>
            body{font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#222;}
            .block{margin-bottom:12px;}
            .title{font-weight:bold;margin-bottom:6px;}
            .info-table td{border:1px solid #d9d9d9;padding:6px;background:#f8f8f8;vertical-align:top;}
            .chip{display:inline-block;padding:3px 8px;border-radius:10px;font-size:9pt;margin-right:4px;margin-bottom:4px;}
            .chip.studio{background:#e8f4fd;color:#0c5460;}
            .chip.stores{background:#fff3cd;color:#856404;}
            .chip.field_team{background:#d1e7dd;color:#0f5132;}
            .chip.warehouse{background:#f8d7da;color:#842029;}
            .tbl{width:100%;border-collapse:collapse;}
            .tbl th,.tbl td{border:1px solid #d9d9d9;padding:5px;}
            .tbl th{background:#f3f3f3;}
            .sec-head{background:#1f2d4d;color:#fff;font-weight:bold;padding:6px 8px;margin-top:8px;}
            .note-box{background:#f7f7f7;border:1px solid #d9d9d9;padding:8px;}
            .sig td{border:1px solid #d9d9d9;padding:8px;vertical-align:top;}
        </style>';

        $html .= '<div class="block">';
        $html .= '<table class="info-table" width="100%"><tr><td width="50%">';
        $html .= '<strong>Client:</strong> ' . html_escape($clientName) . '<br>';
        $html .= '<strong>Proposal Ref:</strong> ' . html_escape($jobCard->qt_ref) . '<br>';
        $html .= '<strong>Job Type:</strong> ' . html_escape(str_replace(',', ', ', (string) $jobCard->job_type)) . '<br>';
        $html .= '<strong>Deadline:</strong> <span style="color:' . ($isOverdue ? '#d9534f' : '#222') . ';">' . html_escape($deadline !== '' ? $deadline : '—') . '</span><br>';
        $html .= '<strong>Assigned To:</strong> ' . html_escape($assignedTo) . '</td><td width="50%">';
        $html .= '<strong>JC Reference:</strong> ' . html_escape($jobCard->jc_ref) . '<br>';
        $html .= '<strong>Version:</strong> v' . $version . '<br>';
        $html .= '<strong>Date Created:</strong> ' . html_escape(date('Y-m-d', strtotime((string) $jobCard->created_at))) . '<br>';
        $html .= '<strong>Status:</strong> ' . html_escape($statusLabel) . '<br>';
        $html .= '<strong>Approved Value:</strong> ' . html_escape(jc_format_mwk($jobCard->approved_total)) . '</td></tr></table>';
        $html .= '</div>';

        $html .= '<div class="block"><div class="title">Assigned Departments:</div>';
        foreach ((array) $jobCard->department_assignments as $assignment) {
            $dept = (string) ($assignment['department'] ?? '');
            $ack = !empty($assignment['acknowledged_at'])
                ? 'Acknowledged by ' . (($assignment['acknowledged_by_name'] ?? '') !== '' ? $assignment['acknowledged_by_name'] : ('Staff #' . (int) ($assignment['acknowledged_by'] ?? 0)))
                : 'Pending acknowledgement';
            $html .= '<span class="chip ' . html_escape($dept) . '">' . html_escape(jc_get_department_label($dept)) . '</span> <small>' . html_escape($ack) . '</small><br>';
        }
        $html .= '</div>';

        $sectionNo = 1;
        foreach ($grouped as $tab => $tabLines) {
            $tabTitle = strtoupper(str_replace('_', ' ', (string) $tab));
            $html .= '<div class="sec-head">' . $sectionNo . '. ' . html_escape($tabTitle) . '</div>';
            $html .= '<table class="tbl"><thead><tr><th width="6%">No</th><th width="44%">Description</th><th width="10%">Unit</th><th width="10%">Qty</th><th width="15%">Unit Price</th><th width="15%">Amount</th></tr></thead><tbody>';
            $i = 0;
            $subtotal = 0;
            foreach ($tabLines as $line) {
                $i++;
                $qty = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) ($line['sell_price'] ?? 0);
                $amount = (float) ($line['line_total_sell'] ?? 0);
                $subtotal += $amount;
                $html .= '<tr>'
                    . '<td>' . $i . '</td>'
                    . '<td>' . html_escape((string) ($line['description'] ?? '')) . '</td>'
                    . '<td>' . html_escape((string) ($line['unit'] ?? '')) . '</td>'
                    . '<td>' . number_format($qty, 3, '.', ',') . '</td>'
                    . '<td>' . number_format($unitPrice, 2, '.', ',') . '</td>'
                    . '<td>' . number_format($amount, 2, '.', ',') . '</td>'
                    . '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '<div style="text-align:right;margin:4px 0 8px 0;"><strong>Section subtotal: ' . html_escape(jc_format_mwk($subtotal)) . '</strong></div>';
            $sectionNo++;
        }

        $materials = array_values(array_filter($lines, static function ($line) {
            return isset($line['inventory_item_id']) && (int) $line['inventory_item_id'] > 0;
        }));
        $html .= '<div class="sec-head">MATERIALS TO BE ISSUED FROM STORES</div>';
        $html .= '<table class="tbl"><thead><tr><th>Item Code</th><th>Description</th><th>Unit</th><th>Qty Required</th></tr></thead><tbody>';
        if (empty($materials)) {
            $html .= '<tr><td colspan="4">No inventory-linked materials in this quotation.</td></tr>';
        } else {
            foreach ($materials as $line) {
                $html .= '<tr>'
                    . '<td>' . html_escape((string) ($line['item_code'] ?? '')) . '</td>'
                    . '<td>' . html_escape((string) ($line['description'] ?? '')) . '</td>'
                    . '<td>' . html_escape((string) ($line['unit'] ?? '')) . '</td>'
                    . '<td>' . number_format((float) ($line['quantity'] ?? 0), 3, '.', ',') . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';

        if (!empty($jobCard->special_instructions)) {
            $html .= '<div class="block"><div class="title">Special Instructions:</div><div class="note-box">' . nl2br(html_escape((string) $jobCard->special_instructions)) . '</div></div>';
        }

        if ((int) $jobCard->status > 2 && !empty($jobCard->production_notes)) {
            $html .= '<div class="block"><div class="title">Production Notes:</div><div class="note-box">' . nl2br(html_escape((string) $jobCard->production_notes)) . '</div></div>';
        }

        $html .= '<div class="block"><div class="title">Signatures</div>';
        $html .= '<table class="sig" width="100%"><tr>'
            . '<td width="25%"><strong>Issued By (Stores)</strong><br><br>Name: __________<br><br>Sig: __________<br><br>Date: __________</td>'
            . '<td width="25%"><strong>Production Supervisor</strong><br><br>Name: __________<br><br>Sig: __________<br><br>Date: __________</td>'
            . '<td width="25%"><strong>Quality Check</strong><br><br>Name: __________<br><br>Sig: __________<br><br>Date: __________</td>'
            . '<td width="25%"><strong>Salesperson</strong><br><br>Name: __________<br><br>Sig: __________<br><br>Date: __________</td>'
            . '</tr></table></div>';

        $mpdf->WriteHTML($html);
        header('Content-Type: application/pdf');
        $mpdf->Output($jobCard->jc_ref . '.pdf', 'I');
    }

    public function delete($id)
    {
        if (!is_admin()) {
            access_denied('job_cards');
        }

        $id = (int) $id;
        $jc = $this->db->where('id', $id)->get(db_prefix() . 'ipms_job_cards')->row();
        if (!$jc) {
            show_404();
        }

        if ((int) $jc->status !== 1) {
            set_alert('danger', 'Only job cards in Created status can be deleted.');
            redirect(admin_url('job_cards/view/' . $id));
        }

        $softDelete = (string) jc_setting('jc_soft_delete', '0') === '1';
        if ($softDelete && $this->db->field_exists('deleted', db_prefix() . 'ipms_job_cards')) {
            $this->db->where('id', $id)->update(db_prefix() . 'ipms_job_cards', ['deleted' => 1]);
        } else {
            $this->db->where('id', $id)->delete(db_prefix() . 'ipms_job_cards');
        }

        set_alert('success', 'Job card deleted.');
        redirect(admin_url('job_cards'));
    }

    public function table()
    {
        // DataTables POST: require draw param (do not rely on X-Requested-With — some stacks strip it).
        $draw = (int) $this->input->post('draw');
        if ($draw < 1) {
            show_404();
        }

        $start  = (int) $this->input->post('start');
        $length = (int) $this->input->post('length');

        $filters = [
            'status'     => $this->input->post('status'),
            'client_id'  => $this->input->post('client_id'),
            'date_from'  => $this->input->post('date_from'),
            'date_to'    => $this->input->post('date_to'),
            'department' => $this->input->post('department'),
        ];

        try {
            $rows  = $this->job_cards_model->get('', $filters);
            $total  = is_array($rows) ? count($rows) : 0;
            $slice  = is_array($rows) ? $rows : [];
            if ($length > 0) {
                $slice = array_slice($slice, max(0, $start), $length);
            }
            $payload = [
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $total,
                'data'            => array_values($slice),
            ];
        } catch (Throwable $e) {
            log_message('error', 'job_cards table: ' . $e->getMessage());
            $payload = [
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Server error loading job cards.',
            ];
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function search_inventory_for_issue()
    {
        if (!$this->input->is_ajax_request() && !$this->input->get()) {
            show_404();
        }

        $term = trim((string) $this->input->get('term'));
        if (strlen($term) < 2) {
            echo json_encode([]);
            return;
        }

        $p = db_prefix();
        $this->db->select('c.commodity_id, c.commodity_code, c.commodity_name, c.wac_price, c.current_quantity, u.unit_symbol');
        $this->db->from($p . 'ware_commodity c');
        if ($this->db->table_exists($p . 'ware_unit_type')) {
            $this->db->join($p . 'ware_unit_type u', 'u.unit_type_id = c.unit_type_id', 'left');
        }
        $this->db->group_start();
        $this->db->like('c.commodity_code', $term);
        $this->db->or_like('c.commodity_name', $term);
        $this->db->group_end();
        $this->db->limit(30);
        $rows = $this->db->get()->result_array();

        $warehouseMap = $this->warehouse_name_map();
        $out = [];
        foreach ($rows as $row) {
            $warehouseId = isset($row['warehouse_id']) ? (int) $row['warehouse_id'] : 0;
            $out[] = [
                'commodity_id'     => (int) $row['commodity_id'],
                'commodity_code'   => $row['commodity_code'],
                'commodity_name'   => $row['commodity_name'],
                'wac_price'        => (float) $row['wac_price'],
                'unit'             => isset($row['unit_symbol']) ? $row['unit_symbol'] : '',
                'current_quantity' => isset($row['current_quantity']) ? (float) $row['current_quantity'] : 0,
                'warehouse_name'   => $warehouseMap[$warehouseId] ?? '',
            ];
        }

        echo json_encode($out);
    }

    /**
     * AJAX: current stock for a commodity, optionally scoped to warehouse.
     */
    public function get_item_stock()
    {
        $commodityId = (int) $this->input->get('commodity_id');
        $warehouseId = (int) $this->input->get('warehouse_id');
        if ($commodityId < 1) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(['current_quantity' => 0]));

            return;
        }

        $p   = db_prefix();
        $tbl = $p . 'ware_commodity';

        $this->db->select_sum('current_quantity', 'qty');
        $this->db->where('commodity_id', $commodityId);
        if ($warehouseId > 0 && $this->db->field_exists('warehouse_id', $tbl)) {
            $this->db->where('warehouse_id', $warehouseId);
        }
        $row = $this->db->get($tbl)->row();
        $qty = $row && isset($row->qty) && $row->qty !== null ? (float) $row->qty : 0.0;

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(['current_quantity' => $qty]));
    }

    private function current_role_name()
    {
        if (function_exists('get_staff_role')) {
            return (string) get_staff_role(get_staff_user_id());
        }

        $p = db_prefix();
        $this->db->select($p . 'roles.name AS role_name');
        $this->db->from($p . 'staff');
        $this->db->join($p . 'roles', $p . 'roles.roleid = ' . $p . 'staff.role', 'left');
        $this->db->where($p . 'staff.staffid', (int) get_staff_user_id());
        $row = $this->db->get()->row();

        return $row && isset($row->role_name) ? (string) $row->role_name : '';
    }

    private function is_manager_or_admin()
    {
        if (is_admin()) {
            return true;
        }

        return in_array($this->current_role_name(), ['General Manager', 'Finance Manager', 'Sales Manager'], true);
    }

    private function can_set_status_by_role($newStatus, $role)
    {
        $newStatus = (int) $newStatus;
        $role      = (string) $role;
        $isManager = $this->is_manager_or_admin();

        if ($newStatus === 7) {
            return false;
        }
        if ($newStatus === 2) {
            return in_array($role, ['Storekeeper/Stores Clerk', 'Store Manager'], true) || $isManager;
        }
        if (in_array($newStatus, [3, 4, 5], true)) {
            return $role === 'Studio/Production' || $isManager;
        }
        if ($newStatus === 6) {
            return in_array($role, ['Studio/Production', 'Storekeeper/Stores Clerk', 'Store Manager', 'Field Team'], true) || $isManager;
        }

        return true;
    }

    private function get_warehouses()
    {
        $p = db_prefix();
        foreach (['warehouses', 'warehouse', 'ware_warehouse'] as $tbl) {
            if ($this->db->table_exists($p . $tbl)) {
                return $this->db->get($p . $tbl)->result_array();
            }
        }

        return [];
    }

    private function warehouse_name_map()
    {
        $rows = $this->get_warehouses();
        $map = [];
        foreach ($rows as $row) {
            $id = isset($row['warehouse_id']) ? (int) $row['warehouse_id'] : (isset($row['id']) ? (int) $row['id'] : 0);
            $name = isset($row['warehouse_name']) ? $row['warehouse_name'] : (isset($row['name']) ? $row['name'] : '');
            if ($id > 0) {
                $map[$id] = $name;
            }
        }

        return $map;
    }
}

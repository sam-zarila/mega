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

        if (!$this->input->post() && module_dir_path('inventory_mgr', 'controllers/Inventory_mgr.php')) {
            redirect(admin_url('inventory_mgr/issue_form/' . $job_card_id));

            return;
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

                $p = db_prefix();
                $commodity = null;
                if ($this->db->table_exists($p . 'ware_commodity')) {
                    $commodity = $this->db->where('commodity_id', $itemId)->get($p . 'ware_commodity')->row();
                    if ($commodity) {
                        $commodity->wac_price = isset($commodity->wac_price) ? (float) $commodity->wac_price : 0.0;
                    }
                } elseif ($this->db->table_exists($p . 'items')) {
                    $commodity = $this->db->where('id', $itemId)->get($p . 'items')->row();
                    if ($commodity) {
                        $commodity->commodity_code = isset($commodity->commodity_code) ? $commodity->commodity_code : '';
                        $commodity->commodity_name = isset($commodity->commodity_name) ? $commodity->commodity_name : '';
                        $commodity->wac_price      = isset($commodity->purchase_price) ? (float) $commodity->purchase_price : 0.0;
                    }
                }
                if (!$commodity) {
                    continue;
                }

                $available = isset($commodity->current_quantity) ? (float) $commodity->current_quantity : PHP_FLOAT_MAX;
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
        $proposal = (int) $jobCard->proposal_id > 0
            ? $this->db->where('id', (int) $jobCard->proposal_id)->get($p . 'proposals')->row()
            : null;
        $worksheet = (int) $jobCard->proposal_id > 0
            ? $this->db->where('proposal_id', (int) $jobCard->proposal_id)->get($p . 'ipms_qt_worksheets')->row()
            : null;
        $lines = isset($jobCard->qt_lines) && is_array($jobCard->qt_lines) ? $jobCard->qt_lines : [];
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
        $deadline = (string) $jobCard->deadline;
        $isOverdue = $deadline !== '' && $deadline < date('Y-m-d') && (int) $jobCard->status < 6;
        $version = $worksheet && isset($worksheet->version) ? (int) $worksheet->version : 1;
        $assignedTo = $jobCard->assigned_sales_name ?: ((int) $jobCard->assigned_sales_id > 0 ? get_staff_full_name((int) $jobCard->assigned_sales_id) : '—');
        $clientName = $client && isset($client->company) ? $client->company : 'Unknown Client';
        $billEmail   = $client && !empty($client->email) ? (string) $client->email : '';
        $billPhone   = $client && !empty($client->phonenumber) ? (string) $client->phonenumber : '';
        $billAddr    = '';
        if ($client) {
            $billAddr = trim(implode("\n", array_filter([
                (string) ($client->address ?? ''),
                trim(implode(', ', array_filter([(string) ($client->city ?? ''), (string) ($client->state ?? '')]))),
                (string) ($client->zip ?? ''),
            ])));
        }
        $jcPdfFmtDate = static function ($d) {
            if ($d === null || $d === '') {
                return '—';
            }
            $t = strtotime((string) $d);

            return $t ? date('m.d.Y', $t) : '—';
        };
        $displayCo = get_option('invoice_company_name') ?: get_option('companyname');
        $logoFile  = (string) get_option('company_logo');
        $logoFull  = FCPATH . 'uploads/company/' . $logoFile;
        $pdfLogo   = ($logoFile !== '' && is_file($logoFull))
            ? '<img src="' . html_escape($logoFull) . '" style="max-height:28px;max-width:72px;width:auto;height:auto;" />'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="26" viewBox="0 0 56 52"><path fill="#6BB3E8" d="M6 46 L6 10 L22 10 L14 46 Z"/><path fill="#4B2869" d="M18 10 L34 10 L46 46 L28 46 Z"/></svg>';

        $grouped = [];
        foreach ($lines as $line) {
            $tab = isset($line['tab']) && $line['tab'] !== '' ? (string) $line['tab'] : 'other';
            if (!isset($grouped[$tab])) {
                $grouped[$tab] = [];
            }
            $grouped[$tab][] = $line;
        }

        $composerAutoload = APPPATH . 'vendor/autoload.php';
        if (!is_file($composerAutoload)) {
            show_error('Composer autoload is missing. Run composer install in the application folder to enable PDF export.', 500);
        }
        require_once $composerAutoload;
        if (!class_exists(\Mpdf\Mpdf::class)) {
            show_error('mPDF is not installed (expected package mpdf/mpdf). Run: composer require mpdf/mpdf', 500);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 30,
            'margin_bottom' => 20,
        ]);

        $headerHtml = '<table width="100%" style="border-bottom:1px solid #ddd;padding-bottom:4px;font-size:8pt;color:#555;"><tr>'
            . '<td>' . html_escape($jobCard->jc_ref) . '</td>'
            . '<td style="text-align:right;">' . html_escape($statusLabel) . '</td></tr></table>';
        $mpdf->SetHTMLHeader($headerHtml);

        $footerHtml = '<table width="100%" style="font-size:8pt;color:#444;border-top:1px solid #ccc;padding-top:5px;margin-top:4px;">'
            . '<tr><td style="text-align:center;"><strong>Terms and conditions apply</strong></td></tr>'
            . '<tr><td style="text-align:center;padding-top:3px;">Page {PAGENO} of {nbpg} · ' . html_escape($jobCard->jc_ref) . ' · Printed ' . date('Y-m-d H:i') . '</td></tr></table>';
        $mpdf->SetHTMLFooter($footerHtml);

        $html = '';
        $html .= '<style>
            body{font-family:DejaVu Sans,sans-serif;font-size:9pt;color:#1a1a1a;}
            .block{margin-bottom:11px;}
            .title{font-weight:bold;margin-bottom:5px;}
            .hdr{border-bottom:1px solid #d9d9d9;padding-bottom:10px;margin-bottom:12px;}
            .co-name{font-size:11pt;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;}
            .co-meta{font-size:8pt;line-height:1.45;color:#333;}
            .doc-title{font-size:22pt;font-weight:bold;text-align:right;}
            .meta2{width:100%;border-collapse:collapse;margin-bottom:10px;}
            .meta2 td{vertical-align:top;padding:4px 0;font-size:9pt;}
            .lbl{color:#666;}
            .tbl{width:100%;border-collapse:collapse;}
            .tbl th,.tbl td{border:1px solid #d9d9d9;padding:5px 4px;font-size:8pt;}
            .tbl th.hdb{background:#1a4a8c;color:#fff;font-weight:bold;border-color:#153d75;}
            .tbl th.hg{background:#f0f2f5;font-weight:bold;}
            .tbl tr:nth-child(even) td{background:#f4f6f8;}
            .sec-head{background:#1a4a8c;color:#fff;font-weight:bold;padding:6px 8px;margin-top:10px;font-size:9pt;}
            .note-box{background:#f7f7f7;border:1px solid #d9d9d9;padding:8px;}
            .sig td{border:1px solid #d9d9d9;padding:8px;vertical-align:top;}
        </style>';

        $html .= '<div class="hdr"><table width="100%"><tr><td width="72%" style="vertical-align:top;">'
            . '<table><tr><td style="padding-right:10px;vertical-align:top;">' . $pdfLogo . '</td><td style="vertical-align:top;">'
            . '<div class="co-name">' . html_escape(strtoupper((string) $displayCo)) . '</div>'
            . '<div class="co-meta"><strong>' . html_escape((string) $displayCo) . '</strong></div></td></tr></table></td>'
            . '<td width="28%" style="vertical-align:top;text-align:right;"><div class="doc-title">Job Card</div></td></tr></table></div>';

        $html .= '<table class="meta2"><tr><td width="48%">'
            . '<span class="lbl">Bill to</span><br/><strong>' . html_escape($clientName) . '</strong><br/>'
            . ($billEmail !== '' ? html_escape($billEmail) : '<span style="color:#999;">[E-MAIL]</span>') . '<br/>'
            . ($billPhone !== '' ? html_escape($billPhone) : '<span style="color:#999;">[PHONE]</span>') . '<br/>'
            . ($billAddr !== '' ? nl2br(html_escape($billAddr)) : '<span style="color:#999;">[ADDRESS]</span>')
            . '</td><td width="52%" style="text-align:right;">'
            . '<span class="lbl">Job no.</span> <strong>' . html_escape($jobCard->jc_ref) . '</strong><br/>'
            . '<span class="lbl">Order date</span> <strong>' . html_escape($jcPdfFmtDate($jobCard->start_date ?: $jobCard->created_at)) . '</strong><br/>'
            . '<span class="lbl">Due</span> <strong style="color:' . ($isOverdue ? '#c00' : '#111') . ';">' . html_escape($jcPdfFmtDate($deadline)) . '</strong><br/>'
            . '<span class="lbl">Status</span> ' . html_escape($statusLabel) . '<br/>'
            . '<span class="lbl">Proposal ref</span> <strong>' . html_escape((string) $jobCard->qt_ref) . '</strong><br/>'
            . '<span class="lbl">Approved value</span> <strong>' . html_escape(jc_format_mwk($jobCard->approved_total)) . '</strong><br/>'
            . '<span class="lbl">Version</span> v' . (int) $version . ' &nbsp;|&nbsp; <span class="lbl">Assigned to</span> ' . html_escape($assignedTo)
            . '</td></tr></table>';

        $html .= '<div class="block"><div class="sec-head">Department routing &amp; acknowledgements</div>';
        $html .= '<table class="tbl"><thead><tr><th class="hg">Department</th><th class="hg">Notified</th><th class="hg">Status</th></tr></thead><tbody>';
        foreach ((array) $jobCard->department_assignments as $assignment) {
            $dept = (string) ($assignment['department'] ?? '');
            $deptLabel = jc_get_department_label($dept);
            $notified = !empty($assignment['notified_at']) ? html_escape(_dt($assignment['notified_at'])) : '—';
            if ((int) ($assignment['completed'] ?? 0) === 1) {
                $st = 'Completed';
            } elseif (!empty($assignment['acknowledged_at'])) {
                $st = 'Acknowledged by ' . html_escape((string) ($assignment['acknowledged_by_name'] ?? ('Staff #' . (int) ($assignment['acknowledged_by'] ?? 0)))) . ' at ' . html_escape(_dt($assignment['acknowledged_at']));
            } else {
                $st = 'Pending';
            }
            $html .= '<tr><td>' . html_escape($deptLabel) . '</td><td>' . $notified . '</td><td>' . $st . '</td></tr>';
        }
        $html .= '</tbody></table></div>';

        $sectionNo = 1;
        foreach ($grouped as $tab => $tabLines) {
            $tabTitle = strtoupper(str_replace('_', ' ', (string) $tab));
            $html .= '<div class="sec-head">' . $sectionNo . '. ' . html_escape($tabTitle) . '</div>';
            $html .= '<table class="tbl"><thead><tr>'
                . '<th class="hdb">Product Code</th><th class="hdb">Category</th><th class="hdb">Production Description</th>'
                . '<th class="hdb">Qty</th><th class="hdb">UoM</th><th class="hdb">Notes</th><th class="hdb">Unit Price</th><th class="hdb">Amount</th>'
                . '</tr></thead><tbody>';
            $i = 0;
            $subtotal = 0;
            foreach ($tabLines as $line) {
                $i++;
                $qty = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) ($line['sell_price'] ?? 0);
                $amount = (float) ($line['line_total_sell'] ?? 0);
                $subtotal += $amount;
                $pcode = (string) ($line['item_code'] ?? $line['commodity_code'] ?? '');
                $cat = (string) ($line['category'] ?? $line['commodity_group'] ?? '');
                if ($cat === '') {
                    $cat = ucwords(str_replace('_', ' ', (string) ($line['tab'] ?? $tab)));
                }
                $notes = (string) ($line['notes'] ?? $line['note'] ?? $line['remarks'] ?? $line['long_description'] ?? '');
                $html .= '<tr>'
                    . '<td>' . ($pcode !== '' ? html_escape($pcode) : '—') . '</td>'
                    . '<td>' . html_escape($cat) . '</td>'
                    . '<td>' . html_escape((string) ($line['description'] ?? '')) . '</td>'
                    . '<td>' . number_format($qty, 3, '.', ',') . '</td>'
                    . '<td>' . html_escape((string) ($line['unit'] ?? '')) . '</td>'
                    . '<td>' . html_escape($notes) . '</td>'
                    . '<td style="text-align:right;">' . number_format($unitPrice, 2, '.', ',') . '</td>'
                    . '<td style="text-align:right;">' . number_format($amount, 2, '.', ',') . '</td>'
                    . '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '<div style="text-align:right;margin:4px 0 8px 0;"><strong>Section subtotal: ' . html_escape(jc_format_mwk($subtotal)) . '</strong></div>';
            $sectionNo++;
        }

        $materials = array_values(array_filter($lines, static function ($line) {
            $cid = (int) ($line['commodity_id'] ?? 0);
            $iid = (int) ($line['inventory_item_id'] ?? 0);

            return $cid > 0 || $iid > 0;
        }));
        $html .= '<div class="sec-head">Materials to be issued from stores</div>';
        $html .= '<table class="tbl"><thead><tr><th class="hdb">Item Code</th><th class="hdb">Description</th><th class="hdb">Unit</th><th class="hdb">Qty Required</th></tr></thead><tbody>';
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
        if ($this->db->table_exists($p . 'ware_commodity')) {
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
        } elseif ($this->db->table_exists($p . 'items')) {
            $this->db->select('i.id as commodity_id, i.commodity_code, i.commodity_name, i.purchase_price as wac_price, i.unit');
            $this->db->from($p . 'items i');
            $this->db->group_start();
            $this->db->like('i.commodity_code', $term);
            $this->db->or_like('i.commodity_name', $term);
            $this->db->group_end();
            $this->db->limit(30);
            $rows = $this->db->get()->result_array();
            foreach ($rows as &$r) {
                $r['current_quantity'] = 0;
                $r['unit_symbol']      = isset($r['unit']) ? $r['unit'] : '';
            }
            unset($r);
        } else {
            $rows = [];
        }

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
        if (!$this->db->table_exists($tbl)) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(['current_quantity' => 0]));

            return;
        }

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

<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Approvals extends AdminController
{
    /** @var ApprovalService */
    public $approvalservice;

    protected $core_approval_roles = [
        'General Manager',
        'Finance Manager',
        'Sales Manager',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('approvals/approvals_model');
        $this->load->library('approvals/ApprovalService', null, 'approvalservice');
        $this->lang->load('approvals/approvals', 'english');
    }

    public function index()
    {
        $this->require_core_approvals_roles();

        $staffId = get_staff_user_id();
        $role    = get_staff_role($staffId);

        $data['title']                 = _l('approval_dashboard');
        $data['my_pending']            = $this->approvalservice->get_pending_for_approver($staffId);
        $data['stat_pending_mine']     = count($data['my_pending']);
        $data['stat_approved_today']   = $this->approvals_model->count_approved_today_by_actor($staffId);
        $data['stat_awaiting_others']  = $role === 'General Manager'
            ? $this->approvals_model->count_all_pending_system()
            : $this->approvals_model->count_pending_submitted_by($staffId);
        $data['stat_overdue_sla']      = $this->approvals_model->count_overdue_for_approver($staffId);
        $data['recent_decisions']      = $this->approvals_model->get_recent_decisions_by_actor($staffId, 10);
        $data['is_general_manager']    = ($role === 'General Manager');

        $this->load->view('approvals/dashboard', $data);
    }

    public function view($id = '')
    {
        $this->require_core_approvals_roles();

        $id = (int) $id;
        if ($id < 1) {
            show_404();
        }

        $request = $this->approvals_model->get_request($id);
        if (!$request) {
            show_404();
        }

        if (!$this->can_access_request($request)) {
            access_denied('approvals');
        }

        $document = $this->load_document_for_request($request);

        $data['title']            = _l('approval_request') . ' ' . e($request->request_ref);
        $data['request']          = $request;
        $data['action_history']   = $this->approvalservice->get_approval_history($request->document_type, (int) $request->document_id);
        $data['document']         = $document;
        $data['document_items']   = $this->load_document_items_for_request($request);
        $data['threshold']        = $this->approvals_model->get_thresholds($request->document_type);
        $data['threshold_note']   = $this->build_approval_threshold_note($request, $data['threshold']);
        $data['linked_invoice']   = $this->load_linked_invoice_for_document($request->document_type, $document);
        $data['journal_lines']    = $request->document_type === 'journal_entry'
            ? $this->load_journal_lines((int) $request->document_id)
            : [];
        $data['pr_lines']         = $request->document_type === 'purchase_requisition'
            ? $this->load_pr_lines((int) $request->document_id)
            : [];
        $data['pr_attachment_url'] = $request->document_type === 'purchase_requisition'
            ? $this->detect_pr_attachment_url($document)
            : '';

        $this->load->view('approvals/view_request', $data);
    }

    public function approve()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->require_core_approvals_roles();

        $approval_request_id = (int) $this->input->post('approval_request_id');
        if ($approval_request_id < 1) {
            return $this->json_response(false, _l('approval_error_missing_request_id'));
        }

        $request = $this->approvals_model->get_request($approval_request_id);
        if (!$request || !$this->can_access_request($request)) {
            return $this->json_response(false, _l('approval_error_not_allowed'));
        }

        $comments = $this->input->post('comments');
        $comments = $comments !== null ? (string) $comments : '';
        $fromDash = (bool) $this->input->post('from_dashboard');

        $result = $this->approvalservice->approve($approval_request_id, get_staff_user_id(), $comments);
        if ($result === false) {
            return $this->json_response(false, _l('approval_error_approve_failed'));
        }

        $badge = $this->get_pending_nav_badge_count(get_staff_user_id());

        if (is_array($result) && isset($result['status']) && $result['status'] === 'next_stage') {
            $this->session->unset_userdata('approvals_pending_count_' . get_staff_user_id());
            return $this->json_response(true, _l('approval_forwarded_gm'), [
                'next_stage'            => true,
                'pending_badge_count'   => $badge,
                'next_url'              => $fromDash ? '' : admin_url('approvals/view/' . $approval_request_id),
            ]);
        }

        $this->session->unset_userdata('approvals_pending_count_' . get_staff_user_id());

        return $this->json_response(true, _l('approval_document_approved'), [
            'pending_badge_count' => $badge,
            'next_url'            => $fromDash ? '' : admin_url('approvals'),
        ]);
    }

    public function reject()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->require_core_approvals_roles();

        $approval_request_id = (int) $this->input->post('approval_request_id');
        if ($approval_request_id < 1) {
            return $this->json_response(false, _l('approval_error_missing_request_id'));
        }

        $comments = trim((string) $this->input->post('comments'));
        if ($comments === '') {
            return $this->json_response(false, _l('approval_error_comments_required'));
        }
        if (strlen($comments) < 10) {
            return $this->json_response(false, _l('approval_error_comments_min_length'));
        }

        $request = $this->approvals_model->get_request($approval_request_id);
        if (!$request || !$this->can_access_request($request)) {
            return $this->json_response(false, _l('approval_error_not_allowed'));
        }

        $ok = $this->approvalservice->reject($approval_request_id, get_staff_user_id(), $comments);
        if (!$ok) {
            return $this->json_response(false, _l('approval_error_reject_failed'));
        }

        $fromDash = (bool) $this->input->post('from_dashboard');
        $this->session->unset_userdata('approvals_pending_count_' . get_staff_user_id());

        return $this->json_response(true, _l('approval_document_rejected'), [
            'pending_badge_count' => $this->get_pending_nav_badge_count(get_staff_user_id()),
            'next_url'            => $fromDash ? '' : admin_url('approvals'),
        ]);
    }

    public function request_revision()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->require_core_approvals_roles();

        $approval_request_id = (int) $this->input->post('approval_request_id');
        if ($approval_request_id < 1) {
            return $this->json_response(false, _l('approval_error_missing_request_id'));
        }

        $comments = trim((string) $this->input->post('comments'));
        if ($comments === '') {
            return $this->json_response(false, _l('approval_error_comments_required'));
        }
        if (strlen($comments) < 10) {
            return $this->json_response(false, _l('approval_error_comments_min_length'));
        }

        $request = $this->approvals_model->get_request($approval_request_id);
        if (!$request || !$this->can_access_request($request)) {
            return $this->json_response(false, _l('approval_error_not_allowed'));
        }

        $ok = $this->approvalservice->request_revision($approval_request_id, get_staff_user_id(), $comments);
        if (!$ok) {
            return $this->json_response(false, _l('approval_error_revision_failed'));
        }

        $fromDash = (bool) $this->input->post('from_dashboard');
        $this->session->unset_userdata('approvals_pending_count_' . get_staff_user_id());

        return $this->json_response(true, _l('approval_revision_requested'), [
            'pending_badge_count' => $this->get_pending_nav_badge_count(get_staff_user_id()),
            'next_url'            => $fromDash ? '' : admin_url('approvals'),
        ]);
    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('approvals');
        }

        if ($this->input->post()) {
            $thresholds = $this->input->post('thresholds');
            if (!is_array($thresholds)) {
                set_alert('warning', _l('approval_settings_invalid'));
                redirect(admin_url('approvals/settings'));
            }

            $allowed = [
                'tier1_role', 'tier1_max', 'tier2_role', 'tier2_max', 'tier3_role',
                'always_gm', 'sla_hours', 'total_stages',
            ];

            foreach ($thresholds as $document_type => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $clean = [];
                foreach ($allowed as $key) {
                    if (!array_key_exists($key, $row)) {
                        continue;
                    }
                    $val = $row[$key];
                    if ($key === 'tier1_role' || $key === 'tier2_role' || $key === 'tier3_role') {
                        $clean[$key] = $val === '' || $val === null ? null : (string) $val;
                    } elseif ($key === 'always_gm') {
                        $clean[$key] = (int) (bool) $val;
                    } elseif ($key === 'sla_hours' || $key === 'total_stages') {
                        $clean[$key] = (int) $val;
                    } elseif ($key === 'tier1_max' || $key === 'tier2_max') {
                        $clean[$key] = $val === '' || $val === null ? null : (float) $val;
                    }
                }
                if ($document_type === 'purchase_requisition' && array_key_exists('enable_two_stage', $row)) {
                    $clean['total_stages'] = ((int) $row['enable_two_stage'] === 1) ? 2 : 1;
                }
                if ($clean !== []) {
                    $this->approvals_model->update_thresholds((string) $document_type, $clean);
                }
            }

            set_alert('success', _l('approval_settings_updated'));
            redirect(admin_url('approvals/settings'));
        }

        $data['title']      = _l('approval_settings');
        $data['thresholds'] = $this->approvals_model->get_thresholds('');

        $this->load->view('approvals/settings', $data);
    }

    public function history($document_type = '', $document_id = '')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!$this->staff_can_view_document($document_type)) {
            ajax_access_denied();
        }

        $document_id = (int) $document_id;
        if ($document_type === '' || $document_id < 1) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode([]));
            return;
        }

        $rows = $this->approvalservice->get_approval_history($document_type, $document_id);
        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($rows));
    }

    public function pending_count()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!$this->has_core_approvals_role()) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(['count' => 0]));
            return;
        }

        $staffId = get_staff_user_id();
        $cacheKey = 'approvals_pending_count_' . $staffId;
        $cached   = $this->session->userdata($cacheKey);
        $now      = time();

        if (!$this->input->get('nocache')
            && is_array($cached) && isset($cached['expires'], $cached['count']) && (int) $cached['expires'] > $now) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(['count' => (int) $cached['count']]));
            return;
        }

        $pending = $this->approvals_model->get_pending_count_for_approver($staffId);
        $this->db->where('current_approver_id', $staffId);
        $this->db->where('status', 'escalated');
        $escalated = (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');
        $count     = $pending + $escalated;

        $this->session->set_userdata($cacheKey, [
            'count'   => $count,
            'expires' => $now + 60,
        ]);

        $payload = ['count' => $count];
        if ($count > 0) {
            $latest = $this->approvals_model->get_latest_pending_for_approver($staffId);
            if ($latest) {
                $docRef = $latest->document_ref !== null && $latest->document_ref !== ''
                    ? (string) $latest->document_ref
                    : ('#' . (int) $latest->document_id);
                $payload['latest'] = [
                    'id'            => (int) $latest->id,
                    'request_ref'   => (string) $latest->request_ref,
                    'document_ref'  => $docRef,
                    'document_type' => (string) $latest->document_type,
                ];
            }
        }

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($payload));
    }

    public function my_requests()
    {
        if (!is_staff_logged_in()) {
            redirect(admin_url('authentication'));
        }

        $staffId = get_staff_user_id();

        $data['title']    = _l('approval_my_requests');
        $data['requests'] = $this->approvals_model->get_all_pending([
            'status'       => null,
            'submitted_by' => $staffId,
            'order'        => 'DESC',
        ]);

        $this->load->view('approvals/my_requests', $data);
    }

    // -------------------------------------------------------------------------
    protected function require_core_approvals_roles()
    {
        if (!$this->has_core_approvals_role()) {
            access_denied('approvals');
        }
    }

    protected function has_core_approvals_role()
    {
        if (is_admin()) {
            return true;
        }

        return in_array(get_staff_role(get_staff_user_id()), $this->core_approval_roles, true);
    }

    /**
     * @param object $request
     */
    protected function can_access_request($request)
    {
        $staffId = get_staff_user_id();
        if (is_admin()) {
            return true;
        }
        if (get_staff_role($staffId) === 'General Manager') {
            return true;
        }
        if ((int) $request->current_approver_id === (int) $staffId) {
            return true;
        }

        return false;
    }

    /**
     * @param object $request
     * @return mixed|null
     */
    protected function load_document_for_request($request)
    {
        $p = db_prefix();
        $id = (int) $request->document_id;

        switch ($request->document_type) {
            case 'quotation':
                $this->load->model('estimates_model');
                return $this->estimates_model->get($id);

            case 'credit_note':
                $this->load->model('credit_notes_model');
                return $this->credit_notes_model->get($id);

            case 'journal_entry':
                if ($this->db->table_exists($p . 'ipms_journals')) {
                    $this->db->where('id', $id);
                    return $this->db->get($p . 'ipms_journals')->row();
                }
                break;

            case 'payment':
                if ($this->db->table_exists($p . 'payments')) {
                    $this->db->where('id', $id);
                    return $this->db->get($p . 'payments')->row();
                }
                $this->db->where('id', $id);
                return $this->db->get($p . 'invoicepaymentrecords')->row();

            case 'purchase_requisition':
                if ($this->db->table_exists($p . 'ipms_purchase_requisitions')) {
                    $this->db->where('id', $id);
                    return $this->db->get($p . 'ipms_purchase_requisitions')->row();
                }
                break;
        }

        return null;
    }

    /**
     * @param object $request
     * @return array
     */
    protected function load_document_items_for_request($request)
    {
        if ($request->document_type === 'quotation' && function_exists('get_items_by_type')) {
            return get_items_by_type('estimate', (int) $request->document_id);
        }
        if ($request->document_type === 'credit_note' && function_exists('get_items_by_type')) {
            return get_items_by_type('credit_note', (int) $request->document_id);
        }

        return [];
    }

    /**
     * @param object|false $threshold
     */
    protected function build_approval_threshold_note($request, $threshold)
    {
        if (!$threshold || !is_object($threshold)) {
            return _l('approval_threshold_note_default');
        }

        $val = (float) $request->document_value;
        $fmt = function ($n) {
            return 'MWK ' . number_format((float) $n, 2, '.', ',');
        };

        if ((int) $threshold->always_gm === 1) {
            return _l('approval_threshold_routes_gm_all');
        }

        if ($request->document_type === 'purchase_requisition' && (int) $threshold->total_stages > 1) {
            return _l('approval_threshold_pr_two_stage');
        }

        if ($request->document_type === 'quotation') {
            if (!empty($threshold->tier1_role) && $threshold->tier1_max !== null && $val <= (float) $threshold->tier1_max) {
                return sprintf(_l('approval_threshold_routes_role_upto'), $threshold->tier1_role, $fmt((float) $threshold->tier1_max));
            }
            if (!empty($threshold->tier2_role) && $threshold->tier2_max !== null && $val <= (float) $threshold->tier2_max) {
                return sprintf(_l('approval_threshold_routes_role_upto'), $threshold->tier2_role, $fmt((float) $threshold->tier2_max));
            }
            if (!empty($threshold->tier3_role)) {
                return sprintf(_l('approval_threshold_routes_role_above'), $fmt($val), $threshold->tier3_role);
            }
        }

        if (!empty($threshold->tier1_role)) {
            return sprintf(_l('approval_threshold_default_role'), $threshold->tier1_role);
        }

        return _l('approval_threshold_note_default');
    }

    /**
     * @param object|null $document
     * @return object|null
     */
    protected function load_linked_invoice_for_document($document_type, $document)
    {
        if (!$document || !is_object($document)) {
            return null;
        }

        $invoiceId = null;
        if ($document_type === 'credit_note') {
            if (!empty($document->invoice_id)) {
                $invoiceId = (int) $document->invoice_id;
            } elseif (!empty($document->applied_credits) && is_array($document->applied_credits)) {
                $first = reset($document->applied_credits);
                if (is_array($first) && !empty($first['invoice_id'])) {
                    $invoiceId = (int) $first['invoice_id'];
                } elseif (is_object($first) && !empty($first->invoice_id)) {
                    $invoiceId = (int) $first->invoice_id;
                }
            }
        } elseif ($document_type === 'payment' && !empty($document->invoiceid)) {
            $invoiceId = (int) $document->invoiceid;
        }

        if ($invoiceId < 1) {
            return null;
        }

        $this->load->model('invoices_model');

        return $this->invoices_model->get($invoiceId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function load_journal_lines($journal_id)
    {
        $p = db_prefix();
        $candidates = [
            ['ipms_journal_lines', 'journal_id'],
            ['ipms_journal_line', 'journal_id'],
            ['ipms_journals_lines', 'journal_id'],
        ];
        foreach ($candidates as $pair) {
            [$table, $col] = $pair;
            if (!$this->db->table_exists($p . $table)) {
                continue;
            }
            $this->db->where($col, (int) $journal_id);
            $rows = $this->db->get($p . $table)->result_array();
            if ($rows !== []) {
                return $rows;
            }
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function load_pr_lines($pr_id)
    {
        $p = db_prefix();
        $candidates = [
            ['ipms_purchase_requisition_details', 'requisition_id'],
            ['ipms_purchase_requisition_details', 'pur_request_id'],
            ['ipms_pr_lines', 'requisition_id'],
            ['ipms_purchase_requisition_items', 'requisition_id'],
        ];
        foreach ($candidates as $pair) {
            [$table, $col] = $pair;
            if (!$this->db->table_exists($p . $table)) {
                continue;
            }
            if (!$this->db->field_exists($col, $p . $table)) {
                continue;
            }
            $this->db->where($col, (int) $pr_id);
            $rows = $this->db->get($p . $table)->result_array();
            if ($rows !== []) {
                return $rows;
            }
        }

        return [];
    }

    /**
     * @param object|null $document
     */
    protected function detect_pr_attachment_url($document)
    {
        if (!$document || !is_object($document)) {
            return '';
        }
        foreach (['attachment', 'attachment_url', 'file_path', 'document_path'] as $prop) {
            if (!empty($document->{$prop}) && is_string($document->{$prop})) {
                $path = $document->{$prop};
                if (preg_match('#^https?://#', $path)) {
                    return $path;
                }
                if (file_exists(FCPATH . ltrim($path, '/'))) {
                    return base_url($path);
                }
            }
        }

        return '';
    }

    protected function staff_can_view_document($document_type)
    {
        switch ($document_type) {
            case 'quotation':
                return staff_can('view', 'estimates') || staff_can('view_own', 'estimates');
            case 'credit_note':
                return staff_can('view', 'credit_notes') || staff_can('view_own', 'credit_notes');
            case 'journal_entry':
            case 'payment':
            case 'purchase_requisition':
                return is_staff_logged_in();
            default:
                return false;
        }
    }

    protected function json_response($success, $message, $extra = [])
    {
        $payload = array_merge([
            'success' => (bool) $success,
            'message' => (string) $message,
        ], $extra);

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
    }

    protected function get_pending_nav_badge_count($staffId)
    {
        $pending = $this->approvals_model->get_pending_count_for_approver($staffId);
        $this->db->where('current_approver_id', (int) $staffId);
        $this->db->where('status', 'escalated');
        $escalated = (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');

        return $pending + $escalated;
    }
}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

class ApprovalService
{
    /** @var CI_Controller */
    protected $CI;

    /** @var Approvals_model */
    protected $model;

    protected $allowed_document_types = [
        'quotation',
        'credit_note',
        'journal_entry',
        'payment',
        'purchase_requisition',
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('approvals/approvals_model');
        $this->model = $this->CI->approvals_model;
    }

    /**
     * @return false|int
     */
    public function submit($document_type, $document_id, $document_ref, $document_value, $submitted_by, $notes = '')
    {
        $document_type = (string) $document_type;
        if (!in_array($document_type, $this->allowed_document_types, true)) {
            log_message('error', 'ApprovalService::submit invalid document_type: ' . $document_type);

            return false;
        }

        if (!is_numeric($document_id) || (int) $document_id <= 0 || !is_numeric($submitted_by) || (int) $submitted_by <= 0) {
            log_message('error', 'ApprovalService::submit invalid document_id or submitted_by');

            return false;
        }

        $document_id   = (int) $document_id;
        $submitted_by  = (int) $submitted_by;
        $document_value = (float) $document_value;

        $threshold = $this->model->get_thresholds($document_type);
        if (!$threshold) {
            log_message('error', 'ApprovalService::submit missing threshold for ' . $document_type);

            return false;
        }

        $total_stages = max(1, (int) $threshold->total_stages);

        $resolved = $this->resolve_approver($document_type, $document_value, 1, $threshold);
        if ($resolved === false) {
            log_message('error', 'ApprovalService::submit could not resolve approver for ' . $document_type);

            return false;
        }

        $request_ref = $this->model->generate_request_ref();

        $sla_hours = (int) $threshold->sla_hours;
        if ($sla_hours < 1) {
            $sla_hours = 24;
        }
        $sla_deadline = date('Y-m-d H:i:s', strtotime('+' . $sla_hours . ' hours'));

        $now = date('Y-m-d H:i:s');

        $insert = [
            'request_ref'           => $request_ref,
            'document_type'         => $document_type,
            'document_id'           => $document_id,
            'document_ref'          => $document_ref !== '' ? (string) $document_ref : null,
            'document_value'        => $document_value,
            'submitted_by'          => $submitted_by,
            'submitted_at'          => $now,
            'current_approver_id'   => $resolved['staff_id'],
            'current_approver_role' => $resolved['role_name'],
            'status'                => 'pending',
            'approval_stage'        => 1,
            'total_stages'          => $total_stages,
            'sla_deadline'          => $sla_deadline,
            'notes'                 => $notes !== '' ? $notes : null,
        ];

        $approval_request_id = $this->model->create_request($insert);
        if ($approval_request_id === false || (int) $approval_request_id < 1) {
            log_message('error', 'ApprovalService::submit insert failed');

            return false;
        }

        $this->log_action($approval_request_id, $submitted_by, 'submitted', '', 1);

        $this->notify_approver($approval_request_id);

        return (int) $approval_request_id;
    }

    /**
     * @param object|null $threshold Pre-loaded row (optional)
     * @return array{staff_id:int,role_name:string}|false
     */
    public function resolve_approver($document_type, $document_value, $approval_stage = 1, $threshold = null)
    {
        $document_type = (string) $document_type;
        if (!in_array($document_type, $this->allowed_document_types, true)) {
            return false;
        }

        if ($threshold === null) {
            $threshold = $this->model->get_thresholds($document_type);
        }
        if (!$threshold) {
            return false;
        }

        $approval_stage = (int) $approval_stage;
        if ($approval_stage < 1) {
            $approval_stage = 1;
        }

        if ((int) $threshold->always_gm === 1) {
            return $this->find_staff_by_role_name('General Manager');
        }

        $total_stages = max(1, (int) $threshold->total_stages);
        if ($document_type === 'purchase_requisition' && $total_stages > 1) {
            if ($approval_stage === 1 && !empty($threshold->tier1_role)) {
                return $this->find_staff_by_role_name($threshold->tier1_role);
            }
            if ($approval_stage >= 2 && !empty($threshold->tier2_role)) {
                return $this->find_staff_by_role_name($threshold->tier2_role);
            }
        }

        $value = (float) $document_value;

        if (!empty($threshold->tier1_role) && $threshold->tier1_max !== null && $value <= (float) $threshold->tier1_max) {
            return $this->find_staff_by_role_name($threshold->tier1_role);
        }

        if (!empty($threshold->tier2_role) && $threshold->tier2_max !== null && $value <= (float) $threshold->tier2_max) {
            return $this->find_staff_by_role_name($threshold->tier2_role);
        }

        if (!empty($threshold->tier3_role)) {
            return $this->find_staff_by_role_name($threshold->tier3_role);
        }

        if (!empty($threshold->tier2_role)) {
            return $this->find_staff_by_role_name($threshold->tier2_role);
        }

        if (!empty($threshold->tier1_role)) {
            return $this->find_staff_by_role_name($threshold->tier1_role);
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function approve($approval_request_id, $actor_id, $comments = '')
    {
        $approval_request_id = (int) $approval_request_id;
        $actor_id            = (int) $actor_id;

        $req = $this->model->get_request($approval_request_id);
        if (!$req || (int) $req->current_approver_id !== $actor_id) {
            return false;
        }

        if ($req->status !== 'pending' && $req->status !== 'escalated') {
            return false;
        }

        $stage = (int) $req->approval_stage;
        $total = max(1, (int) $req->total_stages);

        if ($stage < $total) {
            $next_stage = $stage + 1;
            $threshold  = $this->model->get_thresholds($req->document_type);
            $next       = $this->resolve_approver($req->document_type, (float) $req->document_value, $next_stage, $threshold);
            if ($next === false) {
                log_message('error', 'ApprovalService::approve could not resolve next approver');

                return false;
            }

            $this->model->update_request($approval_request_id, [
                'approval_stage'        => $next_stage,
                'current_approver_id'   => $next['staff_id'],
                'current_approver_role' => $next['role_name'],
                'status'                => 'pending',
                'sla_deadline'          => $this->compute_sla_deadline($req->document_type),
            ]);

            $this->log_action($approval_request_id, $actor_id, 'approved', (string) $comments, $stage);

            $this->notify_approver($approval_request_id);

            return [
                'status'         => 'next_stage',
                'next_approver'  => $next,
                'approval_stage' => $next_stage,
            ];
        }

        $this->model->update_request($approval_request_id, [
            'status' => 'approved',
        ]);

        $this->log_action($approval_request_id, $actor_id, 'approved', (string) $comments, $stage);

        $this->notify_submitter($approval_request_id, 'approved', (string) $comments, $actor_id);
        $this->notify_customer($req, 'approved', (string) $comments, $actor_id);

        $this->on_document_approved($req->document_type, (int) $req->document_id);

        return ['status' => 'approved'];
    }

    public function reject($approval_request_id, $actor_id, $comments)
    {
        $comments = trim((string) $comments);
        if ($comments === '') {
            return false;
        }

        $approval_request_id = (int) $approval_request_id;
        $actor_id            = (int) $actor_id;

        $req = $this->model->get_request($approval_request_id);
        if (!$req || (int) $req->current_approver_id !== $actor_id) {
            return false;
        }

        if (!in_array($req->status, ['pending', 'escalated'], true)) {
            return false;
        }

        $this->model->update_request($approval_request_id, [
            'status' => 'rejected',
        ]);

        $this->log_action($approval_request_id, $actor_id, 'rejected', $comments, (int) $req->approval_stage);

        $this->notify_submitter($approval_request_id, 'rejected', $comments, $actor_id);
        $this->notify_customer($req, 'rejected', $comments, $actor_id);

        $this->on_document_rejected($req->document_type, (int) $req->document_id, $comments);

        return true;
    }

    public function request_revision($approval_request_id, $actor_id, $comments)
    {
        $comments = trim((string) $comments);
        if ($comments === '') {
            return false;
        }

        $approval_request_id = (int) $approval_request_id;
        $actor_id            = (int) $actor_id;

        $req = $this->model->get_request($approval_request_id);
        if (!$req || (int) $req->current_approver_id !== $actor_id) {
            return false;
        }

        if (!in_array($req->status, ['pending', 'escalated'], true)) {
            return false;
        }

        $this->model->update_request($approval_request_id, [
            'status' => 'revision_requested',
        ]);

        $this->log_action($approval_request_id, $actor_id, 'revision_requested', $comments, (int) $req->approval_stage);

        $this->notify_submitter($approval_request_id, 'revision_requested', $comments, $actor_id);
        $this->notify_customer($req, 'revision_requested', $comments, $actor_id);

        return true;
    }

    public function notify_approver($approval_request_id)
    {
        $req = $this->model->get_request((int) $approval_request_id);
        if (!$req || !(int) $req->current_approver_id) {
            return;
        }

        $approver_id = (int) $req->current_approver_id;
        $currency    = $this->currency_symbol();
        $value       = number_format((float) $req->document_value, 2);
        $dtype       = $this->format_document_type($req->document_type);
        $ref         = $req->document_ref ? $req->document_ref : '#' . $req->document_id;

        $description = 'Approval required: ' . $dtype . ' ' . $ref . ' (' . $currency . $value . ')';

        try {
            $inAppOk = false;
            if (function_exists('add_notification')) {
                $inAppOk = (bool) add_notification([
                    'description'   => $description,
                    'touserid'      => $approver_id,
                    'link'          => 'approvals/view/' . (int) $req->id,
                    'fromcompany'   => 1,
                    'isread'        => 0,
                    'isread_inline' => 0,
                ]);
            }
            $this->log_notification_row((int) $req->id, 'in_app', $approver_id, $inAppOk ? 'sent' : 'failed', $inAppOk ? null : 'add_notification skipped or inactive user');
        } catch (Throwable $e) {
            log_message('error', 'ApprovalService::notify_approver in_app: ' . $e->getMessage());
            $this->log_notification_row((int) $req->id, 'in_app', $approver_id, 'failed', $e->getMessage());
        }

        $this->CI->load->model('emails_model');
        $staff = $this->get_staff_row($approver_id);
        if ($staff && !empty($staff->email)) {
            $link   = admin_url('approvals/view/' . (int) $req->id);
            $submitter = get_staff_full_name((int) $req->submitted_by);
            $subject = '[Action Required] Approval needed: ' . $ref;
            $body    = '<p>An approval is required for ' . e($dtype) . ' <strong>' . e($ref) . '</strong>.</p>';
            $body .= '<p><strong>Value:</strong> ' . e($currency . $value) . '<br />';
            $body .= '<strong>Submitted by:</strong> ' . e($submitter) . '</p>';
            $body .= '<p><a href="' . $link . '">Open approval</a></p>';

            try {
                $ok = $this->CI->emails_model->send_simple_email($staff->email, $subject, $body);
                $this->log_notification_row((int) $req->id, 'email', $approver_id, $ok ? 'sent' : 'failed', $ok ? null : 'send_simple_email returned false');
            } catch (Throwable $e) {
                log_message('error', 'ApprovalService::notify_approver email: ' . $e->getMessage());
                $this->log_notification_row((int) $req->id, 'email', $approver_id, 'failed', $e->getMessage());
            }
        } else {
            $this->log_notification_row((int) $req->id, 'email', $approver_id, 'failed', 'No approver email');
        }

        $smsHandled = hooks()->apply_filters('approvals_sms_notify_approver', false, $req, $approver_id);
        $this->log_notification_row(
            (int) $req->id,
            'sms',
            $approver_id,
            $smsHandled ? 'sent' : 'failed',
            $smsHandled ? null : 'SMS not dispatched (filter approvals_sms_notify_approver)'
        );
    }

    public function notify_submitter($approval_request_id, $decision, $comments = '', $approver_staff_id = null)
    {
        $req = $this->model->get_request((int) $approval_request_id);
        if (!$req) {
            return;
        }

        $submitter_id = (int) $req->submitted_by;
        $dtype        = $this->format_document_type($req->document_type);
        $ref          = $req->document_ref ? $req->document_ref : '#' . $req->document_id;
        $approver_id  = $approver_staff_id !== null ? (int) $approver_staff_id : (int) get_staff_user_id();
        $approver     = $approver_id > 0 ? get_staff_full_name($approver_id) : '';

        $decision_label = str_replace('_', ' ', (string) $decision);
        $description    = 'Your ' . $dtype . ' ' . $ref . ' has been ' . $decision_label;
        if ($comments !== '') {
            $description .= ': ' . $comments;
        }

        try {
            if (function_exists('add_notification')) {
                add_notification([
                    'description'   => $description,
                    'touserid'      => $submitter_id,
                    'link'          => 'approvals/view/' . (int) $req->id,
                    'fromcompany'   => 1,
                    'isread'        => 0,
                    'isread_inline' => 0,
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'ApprovalService::notify_submitter in_app: ' . $e->getMessage());
        }

        $this->CI->load->model('emails_model');
        $staff = $this->get_staff_row($submitter_id);
        if ($staff && !empty($staff->email)) {
            $subject = 'Approval update: ' . $ref;
            $body    = '<p>Your ' . e($dtype) . ' <strong>' . e($ref) . '</strong> has been <strong>' . e($decision_label) . '</strong>.</p>';
            $body .= '<p><strong>Approver:</strong> ' . e($approver !== '' ? $approver : 'Staff') . '</p>';
            if ($comments !== '') {
                $body .= '<p><strong>Comments:</strong><br />' . nl2br(e($comments)) . '</p>';
            }

            try {
                $this->CI->emails_model->send_simple_email($staff->email, $subject, $body);
            } catch (Throwable $e) {
                log_message('error', 'ApprovalService::notify_submitter email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Notify customer/contact for final decision events.
     *
     * @param object $req Approval request row
     * @param string $decision approved|rejected|revision_requested
     * @param string $comments
     * @param int|null $approver_staff_id
     */
    public function notify_customer($req, $decision, $comments = '', $approver_staff_id = null)
    {
        if (!$req || !is_object($req)) {
            return;
        }

        $targets = $this->get_customer_targets_for_request($req);
        if (empty($targets['emails'])) {
            return;
        }

        $ref      = $req->document_ref ? $req->document_ref : ('#' . $req->document_id);
        $dtype    = $this->format_document_type($req->document_type);
        $approver = $approver_staff_id ? get_staff_full_name((int) $approver_staff_id) : get_staff_full_name((int) get_staff_user_id());
        $decisionLabel = ucwords(str_replace('_', ' ', (string) $decision));
        $subject = $this->build_customer_decision_subject($decision, $ref);

        $body  = '<p>Dear ' . e($targets['name'] !== '' ? $targets['name'] : 'Customer') . ',</p>';
        $body .= '<p>Your ' . e($dtype) . ' <strong>' . e($ref) . '</strong> has been <strong>' . e($decisionLabel) . '</strong>.</p>';
        if ($approver !== '') {
            $body .= '<p><strong>Reviewed by:</strong> ' . e($approver) . '</p>';
        }
        if ($comments !== '') {
            $body .= '<p><strong>Comments:</strong><br />' . nl2br(e($comments)) . '</p>';
        }
        $body .= '<p>Regards,<br />' . e(get_option('companyname')) . '</p>';

        $this->CI->load->model('emails_model');
        foreach ($targets['emails'] as $email) {
            try {
                $this->CI->emails_model->send_simple_email($email, $subject, $body);
            } catch (Throwable $e) {
                log_message('error', 'ApprovalService::notify_customer email: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param object $req
     * @return array{emails:array<int,string>,name:string}
     */
    protected function get_customer_targets_for_request($req)
    {
        $emails = [];
        $name   = '';

        // Quotations can originate from either proposals or estimates.
        if ($req->document_type === 'quotation') {
            if ($this->CI->db->table_exists(db_prefix() . 'proposals')) {
                $this->CI->load->model('proposals_model');
                $proposal = $this->CI->proposals_model->get((int) $req->document_id);
                if ($proposal) {
                    $relType = isset($proposal->rel_type) ? (string) $proposal->rel_type : '';
                    $relId   = isset($proposal->rel_id) ? (int) $proposal->rel_id : 0;
                    if ($relType !== '' && $relId > 0) {
                        $rel = $this->CI->proposals_model->get_relation_data_values($relId, $relType);
                        if ($rel) {
                            if (!empty($rel->email)) {
                                $emails[] = (string) $rel->email;
                            }
                            if (!empty($rel->name)) {
                                $name = (string) $rel->name;
                            }
                        }
                    }
                }
            }

            if (empty($emails)) {
                $this->CI->load->model('estimates_model');
                $estimate = $this->CI->estimates_model->get((int) $req->document_id);
                if ($estimate && !empty($estimate->clientid)) {
                    $primaryId = function_exists('get_primary_contact_user_id')
                        ? (int) get_primary_contact_user_id((int) $estimate->clientid)
                        : 0;
                    if ($primaryId > 0) {
                        $this->CI->db->select('email, firstname, lastname');
                        $this->CI->db->where('id', $primaryId);
                        $contact = $this->CI->db->get(db_prefix() . 'contacts')->row();
                        if ($contact && !empty($contact->email)) {
                            $emails[] = (string) $contact->email;
                            $name = trim(((string) ($contact->firstname ?? '')) . ' ' . ((string) ($contact->lastname ?? '')));
                        }
                    }
                }
            }
        }

        $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));

        return ['emails' => $emails, 'name' => $name];
    }

    protected function build_customer_decision_subject($decision, $documentRef)
    {
        $ref = (string) $documentRef;
        $keyMap = [
            'approved'           => 'approvals_email_subject_approved',
            'rejected'           => 'approvals_email_subject_rejected',
            'revision_requested' => 'approvals_email_subject_revision',
        ];
        $key = isset($keyMap[$decision]) ? $keyMap[$decision] : '';
        if ($key !== '') {
            $tpl = _l($key);
            if (is_string($tpl) && $tpl !== '' && $tpl !== $key) {
                return str_replace('{document_ref}', $ref, $tpl);
            }
        }

        return 'Approval update: ' . $ref;
    }

    public function on_document_approved($document_type, $document_id)
    {
        $document_id = (int) $document_id;
        $p           = db_prefix();

        switch ($document_type) {
            case 'quotation':
                $this->CI->db->where('id', $document_id);
                $this->CI->db->update($p . 'estimates', ['status' => 4]);
                hooks()->do_action('estimate_status_changed', [
                    'estimate_id' => $document_id,
                    'status'      => 4,
                ]);
                break;

            case 'credit_note':
                $this->CI->db->where('id', $document_id);
                $this->CI->db->update($p . 'creditnotes', ['status' => 1]);
                break;

            case 'journal_entry':
                if ($this->CI->db->table_exists($p . 'ipms_journals')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'ipms_journals', ['status' => 'approved']);
                }
                break;

            case 'payment':
                if ($this->CI->db->table_exists($p . 'payments') && $this->CI->db->field_exists('status', $p . 'payments')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'payments', ['status' => 'approved']);
                }
                break;

            case 'purchase_requisition':
                if ($this->CI->db->table_exists($p . 'ipms_purchase_requisitions')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'ipms_purchase_requisitions', ['status' => 'approved']);
                }
                break;
        }

        hooks()->do_action('ipms_document_approved', [
            'type' => $document_type,
            'id'   => $document_id,
        ]);
    }

    public function on_document_rejected($document_type, $document_id, $reason)
    {
        $document_id = (int) $document_id;
        $p           = db_prefix();

        switch ($document_type) {
            case 'quotation':
                $this->CI->db->where('id', $document_id);
                $this->CI->db->update($p . 'estimates', ['status' => 1]);
                break;

            case 'credit_note':
                $this->CI->db->where('id', $document_id);
                $this->CI->db->update($p . 'creditnotes', ['status' => 1]);
                break;

            case 'journal_entry':
                if ($this->CI->db->table_exists($p . 'ipms_journals') && $this->CI->db->field_exists('status', $p . 'ipms_journals')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'ipms_journals', ['status' => 'draft']);
                }
                break;

            case 'payment':
                if ($this->CI->db->table_exists($p . 'payments') && $this->CI->db->field_exists('status', $p . 'payments')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'payments', ['status' => 'rejected']);
                }
                break;

            case 'purchase_requisition':
                if ($this->CI->db->table_exists($p . 'ipms_purchase_requisitions') && $this->CI->db->field_exists('status', $p . 'ipms_purchase_requisitions')) {
                    $this->CI->db->where('id', $document_id);
                    $this->CI->db->update($p . 'ipms_purchase_requisitions', ['status' => 'rejected']);
                }
                break;
        }

        hooks()->do_action('ipms_document_rejected', [
            'type'   => $document_type,
            'id'     => $document_id,
            'reason' => $reason,
        ]);
    }

    /**
     * @return array
     */
    public function get_pending_for_approver($staff_id)
    {
        return $this->model->get_all_pending([
            'current_approver_id' => (int) $staff_id,
            'status'              => ['pending', 'escalated'],
        ]);
    }

    /**
     * @return array
     */
    public function get_approval_history($document_type, $document_id)
    {
        $document_id = (int) $document_id;
        $p           = db_prefix();

        $this->CI->db->select('a.*, TRIM(CONCAT(COALESCE(s.firstname, ""), " ", COALESCE(s.lastname, ""))) AS actor_full_name', false);
        $this->CI->db->from($p . 'ipms_approval_actions a');
        $this->CI->db->join($p . 'ipms_approval_requests r', 'r.id = a.approval_request_id', 'inner');
        $this->CI->db->join($p . 'staff s', 's.staffid = a.actor_id', 'left');
        $this->CI->db->where('r.document_type', $document_type);
        $this->CI->db->where('r.document_id', $document_id);
        $this->CI->db->order_by('a.acted_at', 'ASC');

        return $this->CI->db->get()->result_array();
    }

    public function process_sla_escalations()
    {
        $now = date('Y-m-d H:i:s');
        $this->CI->db->where('status', 'pending');
        $this->CI->db->where('sla_deadline IS NOT NULL', null, false);
        $this->CI->db->where('sla_deadline <', $now);
        $rows = $this->CI->db->get(db_prefix() . 'ipms_approval_requests')->result();

        foreach ($rows as $row) {
            $this->escalate_overdue_request((int) $row->id);
        }
    }

    // --- internal ---

    protected function escalate_overdue_request($approval_request_id)
    {
        $req = $this->model->get_request($approval_request_id);
        if (!$req || $req->status !== 'pending') {
            return;
        }

        $this->model->update_request($approval_request_id, [
            'status' => 'escalated',
        ]);

        log_message('info', 'Approval SLA escalated: request id ' . $approval_request_id . ' ref ' . $req->request_ref);

        $systemActor = $this->system_actor_staff_id();
        $this->model->log_action([
            'approval_request_id' => (int) $approval_request_id,
            'actor_id'            => $systemActor,
            'actor_name'          => 'System (SLA)',
            'actor_role'          => null,
            'action'              => 'escalated',
            'comments'            => 'SLA deadline passed',
            'acted_at'            => date('Y-m-d H:i:s'),
            'stage_number'        => (int) $req->approval_stage,
            'ip_address'          => $this->CI->input->ip_address(),
        ]);

        $this->notify_approver($approval_request_id);
    }

    protected function compute_sla_deadline($document_type)
    {
        $threshold = $this->model->get_thresholds($document_type);
        $hours     = $threshold ? (int) $threshold->sla_hours : 24;
        if ($hours < 1) {
            $hours = 24;
        }

        return date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours'));
    }

    /**
     * @return array{staff_id:int,role_name:string}|false
     */
    protected function find_staff_by_role_name($role_name)
    {
        $role_name = (string) $role_name;
        if ($role_name === '') {
            return false;
        }

        $row = $this->model->find_staff_by_role($role_name);
        if (!$row) {
            return false;
        }

        return [
            'staff_id'  => (int) $row->staffid,
            'role_name' => (string) $row->role_name,
        ];
    }

    protected function log_action($approval_request_id, $actor_id, $action, $comments, $stage_number)
    {
        $actor_id = (int) $actor_id;
        $actor_name = $actor_id > 0 ? get_staff_full_name($actor_id) : 'System';
        $actor_role = $actor_id > 0 ? get_staff_role($actor_id) : '';

        $insertActor = $actor_id > 0 ? $actor_id : (int) get_staff_user_id();
        if ($insertActor < 1) {
            $insertActor = $this->system_actor_staff_id();
        }

        $this->model->log_action([
            'approval_request_id' => (int) $approval_request_id,
            'actor_id'            => $insertActor,
            'actor_name'          => $actor_name,
            'actor_role'          => $actor_role !== '' ? $actor_role : null,
            'action'              => $action,
            'comments'            => $comments !== '' ? $comments : null,
            'acted_at'            => date('Y-m-d H:i:s'),
            'stage_number'        => (int) $stage_number,
            'ip_address'          => $this->CI->input->ip_address(),
        ]);
    }

    protected function log_notification_row($approval_request_id, $type, $staff_id, $status, $error_message)
    {
        $this->model->log_notification([
            'approval_request_id' => (int) $approval_request_id,
            'notification_type'   => $type,
            'sent_to_staff_id'    => (int) $staff_id,
            'sent_at'             => date('Y-m-d H:i:s'),
            'status'              => $status,
            'error_message'       => $error_message,
        ]);
    }

    protected function get_staff_row($staff_id)
    {
        $this->CI->db->where('staffid', (int) $staff_id);

        return $this->CI->db->get(db_prefix() . 'staff')->row();
    }

    protected function currency_symbol()
    {
        if (function_exists('get_base_currency')) {
            $c = get_base_currency();
            if ($c && isset($c->symbol)) {
                return (string) $c->symbol;
            }
        }

        return '';
    }

    protected function format_document_type($document_type)
    {
        return ucwords(str_replace('_', ' ', (string) $document_type));
    }

    /**
     * Valid staff id for cron/system rows when no admin session exists.
     */
    protected function system_actor_staff_id()
    {
        $sid = (int) get_staff_user_id();
        if ($sid > 0) {
            return $sid;
        }

        $this->CI->db->select_min('staffid');
        $this->CI->db->where('active', 1);
        $row = $this->CI->db->get(db_prefix() . 'staff')->row();

        return $row && isset($row->staffid) ? (int) $row->staffid : 1;
    }
}

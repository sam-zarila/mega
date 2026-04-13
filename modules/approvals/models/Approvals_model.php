<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data access for the approvals module only.
 * No business rules; do not add update/delete for ipms_approval_actions.
 */
class Approvals_model extends App_Model
{
    public function get_request($id)
    {
        $this->db->where('id', (int) $id);
        $row = $this->db->get(db_prefix() . 'ipms_approval_requests')->row();

        return $row ?: false;
    }

    /**
     * Latest non-cancelled request for a document (multiple rows possible over time).
     */
    public function get_request_by_document($document_type, $document_id)
    {
        $this->db->where('document_type', (string) $document_type);
        $this->db->where('document_id', (int) $document_id);
        $this->db->where('status !=', 'cancelled');
        $this->db->order_by('id', 'DESC');
        $row = $this->db->get(db_prefix() . 'ipms_approval_requests', 1)->row();

        return $row ?: false;
    }

    /**
     * @param array $data column => value
     * @return false|int insert_id
     */
    public function create_request(array $data)
    {
        $this->db->insert(db_prefix() . 'ipms_approval_requests', $data);
        $id = (int) $this->db->insert_id();

        return $id > 0 ? $id : false;
    }

    /**
     * @param array $data column => value
     */
    public function update_request($id, array $data)
    {
        $this->db->where('id', (int) $id);

        return (bool) $this->db->update(db_prefix() . 'ipms_approval_requests', $data);
    }

    /**
     * Append-only audit row for ipms_approval_actions.
     *
     * @param array $data keys: approval_request_id, actor_id, actor_name, actor_role, action, comments, acted_at, stage_number, ip_address
     * @return int insert_id (0 if insert failed)
     */
    public function log_action(array $data)
    {
        $this->db->insert(db_prefix() . 'ipms_approval_actions', $data);

        return (int) $this->db->insert_id();
    }

    /**
     * @param array $data keys: approval_request_id, notification_type, sent_to_staff_id, sent_at, status, error_message
     * @return int insert_id
     */
    public function log_notification(array $data)
    {
        $this->db->insert(db_prefix() . 'ipms_approval_notifications', $data);

        return (int) $this->db->insert_id();
    }

    public function get_actions_for_request($approval_request_id)
    {
        $p = db_prefix();
        $this->db->select('a.*, TRIM(CONCAT(COALESCE(s.firstname, \'\'), \' \', COALESCE(s.lastname, \'\'))) AS actor_name', false);
        $this->db->from($p . 'ipms_approval_actions a');
        $this->db->join($p . 'staff s', 's.staffid = a.actor_id', 'left');
        $this->db->where('a.approval_request_id', (int) $approval_request_id);
        $this->db->order_by('a.acted_at', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_pending_count_for_approver($staff_id)
    {
        $this->db->where('current_approver_id', (int) $staff_id);
        $this->db->where('status', 'pending');

        return (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');
    }

    /**
     * Most recently submitted request still awaiting this approver (pending or escalated).
     *
     * @return object|false row from ipms_approval_requests
     */
    public function get_latest_pending_for_approver($staff_id)
    {
        $table = db_prefix() . 'ipms_approval_requests';
        $this->db->where('current_approver_id', (int) $staff_id);
        $this->db->where_in('status', ['pending', 'escalated']);
        $this->db->order_by('submitted_at', 'DESC');
        $this->db->limit(1);

        $row = $this->db->get($table)->row();

        return $row ?: false;
    }

    /**
     * @param string $document_type empty string = all rows
     * @return object|false|array false if single type missing; array of objects if all
     */
    public function get_thresholds($document_type = '')
    {
        $table = db_prefix() . 'ipms_approval_thresholds';

        if ($document_type !== '' && $document_type !== null) {
            $this->db->where('document_type', (string) $document_type);
            $row = $this->db->get($table)->row();

            return $row ?: false;
        }

        return $this->db->get($table)->result();
    }

    /**
     * @param array $data columns to update (document_type key removed if present)
     */
    public function update_thresholds($document_type, array $data)
    {
        unset($data['document_type']);
        if ($data === []) {
            return false;
        }
        $this->db->where('document_type', (string) $document_type);

        return (bool) $this->db->update(db_prefix() . 'ipms_approval_thresholds', $data);
    }

    /**
     * @return object|false staffid, email, full_name, role_name
     */
    public function find_staff_by_role($role_name)
    {
        $p = db_prefix();
        $this->db->select("s.staffid, s.email, CONCAT(s.firstname, ' ', s.lastname) AS full_name, r.name AS role_name", false);
        $this->db->from($p . 'staff s');
        $this->db->join($p . 'roles r', 's.role = r.roleid', 'inner');
        $this->db->where('r.name', (string) $role_name);
        $this->db->where('s.active', 1);
        $this->db->order_by('s.staffid', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        return $row ?: false;
    }

    /**
     * Pending-style listing for admin (default: status pending only).
     *
     * $filters keys: status (string|array), document_type, current_approver_id, date_from, date_to (submitted_at)
     */
    public function get_all_pending(array $filters = [])
    {
        $table = db_prefix() . 'ipms_approval_requests';

        if (!array_key_exists('status', $filters)) {
            $this->db->where('status', 'pending');
        } else {
            $st = $filters['status'];
            if ($st === null || $st === '') {
                // explicit: no status filter
            } elseif (is_array($st)) {
                if (count($st) > 0) {
                    $this->db->where_in('status', $st);
                }
            } else {
                $this->db->where('status', $st);
            }
        }

        if (!empty($filters['document_type'])) {
            $this->db->where('document_type', $filters['document_type']);
        }

        if (isset($filters['current_approver_id']) && $filters['current_approver_id'] !== '' && $filters['current_approver_id'] !== null) {
            $this->db->where('current_approver_id', (int) $filters['current_approver_id']);
        }

        if (isset($filters['submitted_by']) && $filters['submitted_by'] !== '' && $filters['submitted_by'] !== null) {
            $this->db->where('submitted_by', (int) $filters['submitted_by']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where('submitted_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where('submitted_at <=', $filters['date_to']);
        }

        $orderDir = 'ASC';
        if (!empty($filters['order']) && strtoupper($filters['order']) === 'DESC') {
            $orderDir = 'DESC';
        }
        $this->db->order_by('submitted_at', $orderDir);

        return $this->db->get($table)->result_array();
    }

    /**
     * Count actionable requests grouped by document_type (pending + escalated).
     *
     * @return array<int, array{document_type:string, total:int}>
     */
    public function count_pending_grouped_by_document_type()
    {
        $this->db->select('document_type, COUNT(*) AS total', false);
        $this->db->where_in('status', ['pending', 'escalated']);
        $this->db->group_by('document_type');

        return $this->db->get(db_prefix() . 'ipms_approval_requests')->result_array();
    }

    public function generate_request_ref()
    {
        $this->db->select_max('id');
        $row = $this->db->get(db_prefix() . 'ipms_approval_requests')->row();
        $next = 1;
        if ($row && isset($row->id) && $row->id !== null && $row->id !== '') {
            $next = (int) $row->id + 1;
        }

        return 'APR-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function count_approved_today_by_actor($staff_id)
    {
        $this->db->where('actor_id', (int) $staff_id);
        $this->db->where('action', 'approved');
        $this->db->where('DATE(acted_at) = CURDATE()', null, false);

        return (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_actions');
    }

    /**
     * All pending + escalated in the system (GM dashboard stat).
     */
    public function count_all_pending_system()
    {
        $this->db->where_in('status', ['pending', 'escalated']);

        return (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');
    }

    /**
     * Pending/escalated requests this user submitted (still awaiting someone else).
     */
    public function count_pending_submitted_by($staff_id)
    {
        $this->db->where('submitted_by', (int) $staff_id);
        $this->db->where_in('status', ['pending', 'escalated']);

        return (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');
    }

    /**
     * My queue items where SLA deadline has passed.
     */
    public function count_overdue_for_approver($staff_id)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('current_approver_id', (int) $staff_id);
        $this->db->where_in('status', ['pending', 'escalated']);
        $this->db->where('sla_deadline IS NOT NULL', null, false);
        $this->db->where('sla_deadline <', $now);

        return (int) $this->db->count_all_results(db_prefix() . 'ipms_approval_requests');
    }

    /**
     * Last actions by this staff member (dashboard "Recent decisions").
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_recent_decisions_by_actor($staff_id, $limit = 10)
    {
        $p = db_prefix();
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 10;
        }

        $this->db->select('a.id, a.action, a.comments, a.acted_at, r.document_type, r.document_ref, r.document_id, r.request_ref', false);
        $this->db->from($p . 'ipms_approval_actions a');
        $this->db->join($p . 'ipms_approval_requests r', 'r.id = a.approval_request_id', 'inner');
        $this->db->where('a.actor_id', (int) $staff_id);
        $this->db->where_in('a.action', ['approved', 'rejected', 'revision_requested']);
        $this->db->order_by('a.acted_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
}

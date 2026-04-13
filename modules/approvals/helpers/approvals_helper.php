<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @param int $staff_id
 * @return string Role display name from tblroles or empty string
 */
if (!function_exists('get_staff_role')) {
    function get_staff_role($staff_id)
    {
        if (!is_numeric($staff_id) || (int) $staff_id <= 0) {
            return '';
        }

        $CI = &get_instance();
        $CI->db->select(db_prefix() . 'roles.name as name');
        $CI->db->from(db_prefix() . 'staff');
        $CI->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');
        $CI->db->where(db_prefix() . 'staff.staffid', (int) $staff_id);
        $row = $CI->db->get()->row();

        return $row && isset($row->name) ? (string) $row->name : '';
    }
}

/**
 * @param string $slug
 * @param array  $item sidebar menu item (name, href, icon, position, badge, …)
 */
if (!function_exists('add_module_menu_item')) {
    function add_module_menu_item($slug, array $item)
    {
        get_instance()->app_menu->add_sidebar_menu_item($slug, $item);
    }
}

if (!function_exists('ipms_format_mwk')) {
    /**
     * @param float|int|string|null $amount
     */
    function ipms_format_mwk($amount)
    {
        return 'MWK ' . number_format((float) $amount, 2, '.', ',');
    }
}

if (!function_exists('ipms_get_approval_status_badge')) {
    /**
     * @param string $status
     * @return string HTML
     */
    function ipms_get_approval_status_badge($status)
    {
        $status = (string) $status;
        $map   = [
            'pending'            => ['class' => 'badge label-warning', 'label' => 'Pending'],
            'approved'           => ['class' => 'badge label-success', 'label' => 'Approved'],
            'rejected'           => ['class' => 'badge label-danger', 'label' => 'Rejected'],
            'revision_requested' => ['class' => 'badge label-info', 'label' => 'Revision Requested'],
            'escalated'          => ['class' => 'badge label-default', 'label' => 'Escalated'],
            'cancelled'          => ['class' => 'badge label-default', 'label' => 'Cancelled'],
        ];
        if (isset($map[$status])) {
            $m = $map[$status];

            return '<span class="' . $m['class'] . '">' . html_escape($m['label']) . '</span>';
        }

        return '<span class="badge label-default">' . html_escape(ucfirst(str_replace('_', ' ', $status))) . '</span>';
    }
}

if (!function_exists('ipms_get_document_type_label')) {
    /**
     * @param string $type
     */
    function ipms_get_document_type_label($type)
    {
        $labels = [
            'quotation'             => 'Quotation',
            'credit_note'           => 'Credit Note',
            'journal_entry'         => 'Journal Entry',
            'payment'               => 'Payment',
            'purchase_requisition'  => 'Purchase Requisition',
        ];
        $type = (string) $type;

        return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
    }
}

if (!function_exists('ipms_get_approver_for_document')) {
    /**
     * @param string $document_type
     * @param float|int|string $value
     * @return array{staff_id:int,role_name:string,full_name:string}|false
     */
    function ipms_get_approver_for_document($document_type, $value)
    {
        $CI = &get_instance();
        $CI->load->library('approvals/ApprovalService', null, 'approvalservice');

        $resolved = $CI->approvalservice->resolve_approver((string) $document_type, (float) $value, 1);
        if ($resolved === false || empty($resolved['staff_id'])) {
            return false;
        }

        $sid = (int) $resolved['staff_id'];

        return [
            'staff_id'   => $sid,
            'role_name'  => (string) ($resolved['role_name'] ?? ''),
            'full_name'  => get_staff_full_name($sid),
        ];
    }
}

if (!function_exists('ipms_has_pending_approval')) {
    /**
     * @param string $document_type
     * @param int $document_id
     * @return object|false request row
     */
    function ipms_has_pending_approval($document_type, $document_id)
    {
        if (!is_numeric($document_id) || (int) $document_id < 1) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->model('approvals/approvals_model');

        $row = $CI->approvals_model->get_request_by_document((string) $document_type, (int) $document_id);
        if (!$row) {
            return false;
        }
        if (!in_array($row->status, ['pending', 'escalated'], true)) {
            return false;
        }

        return $row;
    }
}

if (!function_exists('ipms_can_approve')) {
    /**
     * @param int $approval_request_id
     */
    function ipms_can_approve($approval_request_id)
    {
        if (!is_staff_logged_in()) {
            return false;
        }

        $id = (int) $approval_request_id;
        if ($id < 1) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->model('approvals/approvals_model');

        $req = $CI->approvals_model->get_request($id);
        if (!$req) {
            return false;
        }
        if (!in_array($req->status, ['pending', 'escalated'], true)) {
            return false;
        }

        return (int) $req->current_approver_id === (int) get_staff_user_id();
    }
}

if (!function_exists('ipms_time_until_sla')) {
    /**
     * @param string|null $sla_deadline datetime
     * @return array{text:string,class:string}
     */
    function ipms_time_until_sla($sla_deadline)
    {
        if ($sla_deadline === null || $sla_deadline === '') {
            return ['text' => '', 'class' => 'default'];
        }

        $ts = strtotime((string) $sla_deadline);
        if ($ts === false) {
            return ['text' => '', 'class' => 'default'];
        }

        $now  = time();
        $diff = $ts - $now;

        $formatDur = static function ($seconds) {
            $seconds = (int) $seconds;
            if ($seconds < 60) {
                return $seconds . ' second' . ($seconds !== 1 ? 's' : '');
            }
            $mins = (int) floor($seconds / 60);
            if ($mins < 60) {
                return $mins . ' minute' . ($mins !== 1 ? 's' : '');
            }
            $hours = (int) floor($mins / 60);
            $remM  = $mins % 60;
            $parts = [$hours . ' hour' . ($hours !== 1 ? 's' : '')];
            if ($remM > 0) {
                $parts[] = $remM . ' min';
            }

            return implode(' ', $parts);
        };

        if ($diff < 0) {
            return [
                'text'  => 'OVERDUE by ' . $formatDur(abs($diff)),
                'class' => 'danger',
            ];
        }

        return [
            'text'  => $formatDur($diff) . ' remaining',
            'class' => $diff <= 3600 ? 'warning' : 'success',
        ];
    }
}

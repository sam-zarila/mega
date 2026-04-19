<?php

defined('BASEPATH') or exit('No direct script access allowed');

function jc_setting($key, $default = '')
{
    $CI = &get_instance();
    $row = $CI->db
        ->get_where(db_prefix() . 'ipms_jc_settings', ['setting_key' => $key])
        ->row();

    return $row ? $row->setting_value : $default;
}

function jc_generate_ref()
{
    $CI        = &get_instance();
    $year      = date('Y');
    $prefix    = (string) jc_setting('jc_prefix', 'JC');
    $nextRaw   = (string) jc_setting('jc_next_number', '1');
    $next      = (int) $nextRaw > 0 ? (int) $nextRaw : 1;
    $ref       = $prefix . '-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    $newVal    = (string) ($next + 1);

    $CI->db->where('setting_key', 'jc_next_number');
    $CI->db->update(db_prefix() . 'ipms_jc_settings', ['setting_value' => $newVal]);

    return $ref;
}

function jc_generate_issue_ref()
{
    $CI        = &get_instance();
    $year      = date('Y');
    $prefix    = (string) jc_setting('iss_prefix', 'ISS');
    $nextRaw   = (string) jc_setting('iss_next_number', '1');
    $next      = (int) $nextRaw > 0 ? (int) $nextRaw : 1;
    $ref       = $prefix . '-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    $newVal    = (string) ($next + 1);

    $CI->db->where('setting_key', 'iss_next_number');
    $CI->db->update(db_prefix() . 'ipms_jc_settings', ['setting_value' => $newVal]);

    return $ref;
}

function jc_get_status_label($status)
{
    $status = (int) $status;

    $map = [
        1 => ['label' => 'Created',            'color' => '#6c757d', 'class' => 'label-default'],
        2 => ['label' => 'Materials Issued',   'color' => '#fd7e14', 'class' => 'label-warning'],
        3 => ['label' => 'In Production',      'color' => '#007bff', 'class' => 'label-info'],
        4 => ['label' => 'Quality Check',      'color' => '#6f42c1', 'class' => 'label-primary'],
        5 => ['label' => 'Ready for Delivery', 'color' => '#17a2b8', 'class' => 'label-info'],
        6 => ['label' => 'Completed',          'color' => '#28a745', 'class' => 'label-success'],
        7 => ['label' => 'Invoiced',           'color' => '#6c757d', 'class' => 'label-default'],
    ];

    return $map[$status] ?? ['label' => 'Unknown', 'color' => '#6c757d', 'class' => 'label-default'];
}

function jc_get_status_badge($status)
{
    $info = jc_get_status_label($status);
    $cls  = 'badge ' . $info['class'] . ' jc-status-' . (int) $status;

    return '<span class="' . $cls . '" style="background:' . $info['color'] . ';">'
        . html_escape($info['label']) . '</span>';
}

function jc_get_all_statuses()
{
    $all = [];
    for ($i = 1; $i <= 7; $i++) {
        $info        = jc_get_status_label($i);
        $all[$i] = $info['label'];
    }

    return $all;
}

function jc_determine_routing($service_tabs_string)
{
    $service_tabs_string = (string) $service_tabs_string;
    if ($service_tabs_string === '') {
        return [];
    }

    $parts = array_filter(array_map('trim', explode(',', $service_tabs_string)));
    $tabs  = array_map('strtolower', $parts);

    $departments = [];

    if (array_intersect($tabs, ['signage', 'construction', 'retrofitting'])) {
        $departments[] = 'studio';
        $departments[] = 'stores';
    }

    if (in_array('installation', $tabs, true)) {
        $departments[] = 'field_team';
        $departments[] = 'stores';
    }

    if (in_array('promotional', $tabs, true)) {
        $departments[] = 'stores';
        $departments[] = 'warehouse';
    }

    if (in_array('additional', $tabs, true)) {
        $departments[] = 'stores';
    }

    return array_values(array_unique($departments));
}

function jc_get_department_label($dept)
{
    $dept = (string) $dept;
    $map  = [
        'studio'     => 'Studio & Production',
        'stores'     => 'Stores',
        'field_team' => 'Field Team',
        'warehouse'  => 'Warehouse',
    ];

    return $map[$dept] ?? $dept;
}

function jc_get_role_for_department($dept)
{
    $dept = (string) $dept;
    switch ($dept) {
        case 'studio':
            return jc_setting('jc_studio_role', 'Studio/Production');
        case 'stores':
            return jc_setting('jc_stores_role', 'Storekeeper/Stores Clerk');
        case 'field_team':
            return jc_setting('jc_field_team_role', 'Field Team');
        case 'warehouse':
            return jc_setting('jc_warehouse_role', 'Storekeeper/Stores Clerk');
        default:
            return '';
    }
}

function jc_get_staff_by_role($role_name)
{
    $CI        = &get_instance();
    $role_name = (string) $role_name;

    if ($role_name === '') {
        return [];
    }

    $CI->db->select(
        db_prefix() . 'staff.staffid,
        CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) AS full_name,
        ' . db_prefix() . 'staff.email'
    );
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->join(
        db_prefix() . 'roles',
        db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role',
        'left'
    );
    $CI->db->where(db_prefix() . 'staff.active', 1);
    $CI->db->where(db_prefix() . 'roles.name', $role_name);

    return $CI->db->get()->result();
}

function jc_notify_department($job_card_id, $dept, $jc_ref, $client_name)
{
    $job_card_id = (int) $job_card_id;
    $dept        = (string) $dept;
    $jc_ref      = (string) $jc_ref;
    $client_name = (string) $client_name;

    if ($job_card_id < 1 || $dept === '' || $jc_ref === '') {
        return;
    }

    $CI        = &get_instance();
    $deptLabel = jc_get_department_label($dept);
    $roleName  = jc_get_role_for_department($dept);
    $staffList = $roleName !== '' ? jc_get_staff_by_role($roleName) : [];

    foreach ($staffList as $staff) {
        add_notification([
            'description'     => 'New Job Card ' . $jc_ref . ' assigned to ' . $deptLabel . ': ' . $client_name,
            'touserid'        => (int) $staff->staffid,
            'fromuserid'      => 0,
            'link'            => 'job_cards/view/' . $job_card_id,
            'additional_data' => serialize([$jc_ref, $client_name]),
        ]);
    }

    if (!empty($staffList) && class_exists('App_mailer')) {
        foreach ($staffList as $staff) {
            try {
                $template = 'job_card_new_department'; // optional custom template
                if (method_exists('App_mailer', 'send')) {
                    app_mail_template(
                        $template,
                        (object) [
                            'email' => $staff->email,
                            'name'  => $staff->full_name,
                        ],
                        [
                            'job_card_id'  => $job_card_id,
                            'jc_ref'       => $jc_ref,
                            'client_name'  => $client_name,
                            'department'   => $deptLabel,
                            'job_card_url' => admin_url('job_cards/view/' . $job_card_id),
                        ]
                    );
                }
            } catch (Throwable $e) {
                log_message('error', 'jc_notify_department mail failed: ' . $e->getMessage());
            }
        }
    }
}

function jc_get_client_name($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 'Unknown Client';
    }

    $CI = &get_instance();
    $CI->db->select('company');
    $CI->db->from(db_prefix() . 'clients');
    $CI->db->where('userid', $client_id);
    $row = $CI->db->get()->row();

    if ($row && isset($row->company) && $row->company !== '') {
        return $row->company;
    }

    return 'Client #' . $client_id;
}

function jc_can_view($job_card_id)
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return false;
    }

    $job_card_id = (int) $job_card_id;
    if ($job_card_id < 1) {
        return false;
    }

    $CI = &get_instance();
    $CI->db->from(db_prefix() . 'ipms_job_cards');
    $CI->db->where('id', $job_card_id);
    $jc = $CI->db->get()->row();

    if (!$jc) {
        return false;
    }

    $currentId = (int) get_staff_user_id();

    $roleName = '';
    if (function_exists('get_staff_role')) {
        $roleName = (string) get_staff_role($currentId);
    } else {
        $CI->db->select(db_prefix() . 'roles.name as role_name');
        $CI->db->from(db_prefix() . 'staff');
        $CI->db->join(
            db_prefix() . 'roles',
            db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role',
            'left'
        );
        $CI->db->where(db_prefix() . 'staff.staffid', $currentId);
        $row = $CI->db->get()->row();
        $roleName = $row && isset($row->role_name) ? (string) $row->role_name : '';
    }

    $roleName = trim((string) $roleName);

    $globalRoles = ['General Manager', 'Finance Manager', 'Sales Manager', 'System Administrator'];
    if (in_array($roleName, $globalRoles, true)) {
        return true;
    }

    $deptRoles = [
        'Studio/Production',
        'Storekeeper/Stores Clerk',
        'Store Manager',
        'Field Team',
    ];
    if (in_array($roleName, $deptRoles, true)) {
        return true;
    }

    if ($roleName === 'Receptionist' && (int) $jc->status >= 5) {
        return true;
    }

    if ((int) $jc->created_by === $currentId || (int) $jc->assigned_sales_id === $currentId) {
        return true;
    }

    return false;
}

/**
 * @param string $type 'production' | 'quality'
 */
function jc_can_edit_notes($type)
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return false;
    }

    $type = (string) $type;
    $role  = function_exists('get_staff_role') ? (string) get_staff_role(get_staff_user_id()) : '';

    if (is_admin() || jc_is_manager_role($role)) {
        return true;
    }

    if ($type === 'production' || $type === 'quality') {
        return $role === 'Studio/Production';
    }

    return false;
}

function jc_is_manager_role($roleName)
{
    $roleName = (string) $roleName;

    return in_array($roleName, ['General Manager', 'Finance Manager', 'Sales Manager'], true);
}

function jc_can_update_status($job_card_id, $new_status)
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return false;
    }

    $job_card_id = (int) $job_card_id;
    $new_status  = (int) $new_status;
    if ($job_card_id < 1 || $new_status < 1 || $new_status > 7) {
        return false;
    }

    $CI = &get_instance();
    $CI->db->from(db_prefix() . 'ipms_job_cards');
    $CI->db->where('id', $job_card_id);
    $jc = $CI->db->get()->row();

    if (!$jc) {
        return false;
    }

    $current = (int) $jc->status;
    if ($new_status <= $current) {
        return false;
    }

    $currentId = (int) get_staff_user_id();
    $roleName  = function_exists('get_staff_role') ? (string) get_staff_role($currentId) : '';

    if (jc_is_manager_role($roleName)) {
        return true;
    }

    if ($new_status > $current + 1) {
        return false;
    }

    return true;
}

function jc_format_mwk($amount)
{
    return 'MWK ' . number_format((float) $amount, 2, '.', ',');
}

/**
 * Map an Approvals "quotation" document_id to a Perfex proposal id.
 * document_id is either tblproposals.id (proposal-sent / legacy path) or
 * tblipms_quotations.id (Quotations module submit_for_approval path).
 *
 * @param int $document_id
 * @return int proposal id or 0 if not resolvable
 */
function jc_resolve_proposal_id_from_quotation_approval($document_id)
{
    $document_id = (int) $document_id;
    if ($document_id < 1) {
        return 0;
    }

    $CI = &get_instance();
    $p  = db_prefix();

    // Proposal-sent / legacy approvals: document_id is tblproposals.id
    $CI->db->where('id', $document_id);
    if ($CI->db->count_all_results($p . 'proposals') > 0) {
        return $document_id;
    }

    $qtTable = $p . 'ipms_quotations';
    if ($CI->db->table_exists($qtTable)) {
        $CI->db->where('id', $document_id);
        $quotation = $CI->db->get($qtTable)->row();
        if ($quotation) {
            $estimateId = (int) ($quotation->estimate_id ?? 0);
            if ($estimateId > 0) {
                $CI->db->select('id');
                $CI->db->from($p . 'proposals');
                $CI->db->where('estimate_id', $estimateId);
                $CI->db->order_by('id', 'DESC');
                $prop = $CI->db->get()->row();
                if ($prop) {
                    return (int) $prop->id;
                }
            }

            if ($CI->db->field_exists('ipms_quotation_id', $p . 'estimates')) {
                $CI->db->select('id');
                $CI->db->from($p . 'estimates');
                $CI->db->where('ipms_quotation_id', $document_id);
                $CI->db->order_by('id', 'DESC');
                $est = $CI->db->get()->row();
                if ($est) {
                    $eid = (int) $est->id;
                    $CI->db->select('id');
                    $CI->db->from($p . 'proposals');
                    $CI->db->where('estimate_id', $eid);
                    $CI->db->order_by('id', 'DESC');
                    $prop = $CI->db->get()->row();
                    if ($prop) {
                        return (int) $prop->id;
                    }
                }
            }
        }
    }

    return 0;
}

/**
 * IPMS quotation linked to a proposal via tbl_estimates (estimate_id on proposal).
 *
 * @param object $proposal
 * @return object|null ipms_quotations row
 */
function jc_get_ipms_quotation_for_proposal($proposal)
{
    if (!$proposal || empty($proposal->estimate_id)) {
        return null;
    }

    $estimateId = (int) $proposal->estimate_id;
    if ($estimateId < 1) {
        return null;
    }

    $CI      = &get_instance();
    $p       = db_prefix();
    $qtTable = $p . 'ipms_quotations';
    if (!$CI->db->table_exists($qtTable)) {
        return null;
    }

    $qid = 0;
    if ($CI->db->field_exists('ipms_quotation_id', $p . 'estimates')) {
        $CI->db->select('ipms_quotation_id');
        $CI->db->where('id', $estimateId);
        $est = $CI->db->get($p . 'estimates')->row();
        if ($est && (int) ($est->ipms_quotation_id ?? 0) > 0) {
            $qid = (int) $est->ipms_quotation_id;
        }
    }

    if ($qid > 0) {
        $CI->db->where('id', $qid);

        return $CI->db->get($qtTable)->row() ?: null;
    }

    $CI->db->where('estimate_id', $estimateId);
    if ($CI->db->field_exists('is_latest', $qtTable)) {
        $CI->db->where('is_latest', 1);
    }
    $CI->db->order_by('id', 'DESC');

    return $CI->db->get($qtTable, 1)->row() ?: null;
}

/**
 * Comma-separated tab names for jc_determine_routing from IPMS quotation lines / service_type.
 *
 * @param int         $quotation_id
 * @param object|null $quotation_row
 */
function jc_quotation_tabs_string_for_job_card($quotation_id, $quotation_row = null)
{
    $quotation_id = (int) $quotation_id;
    $CI           = &get_instance();
    $p            = db_prefix();
    $lines        = $p . 'ipms_quotation_lines';

    if ($CI->db->table_exists($lines) && $quotation_id > 0) {
        $row = $CI->db->query(
            'SELECT GROUP_CONCAT(DISTINCT `tab` ORDER BY `tab` SEPARATOR ",") AS tabs FROM `' . $lines . '` WHERE `quotation_id` = ?',
            [$quotation_id]
        )->row();
        if ($row && !empty($row->tabs)) {
            return (string) $row->tabs;
        }
    }

    if ($quotation_row && isset($quotation_row->service_type) && (string) $quotation_row->service_type !== '') {
        return (string) $quotation_row->service_type;
    }

    return '';
}

/**
 * @param object $proposal
 * @return object|null worksheet-like object (service_tabs, total_cost, total_sell, grand_total)
 */
function jc_resolve_worksheet_for_auto_job_card($proposal_id, $proposal)
{
    $CI = &get_instance();
    $p  = db_prefix();

    $CI->db->from($p . 'ipms_qt_worksheets');
    $CI->db->where('proposal_id', (int) $proposal_id);
    $ws = $CI->db->get()->row();

    if (!$ws) {
        $helper = module_dir_path('quotation_worksheet', 'helpers/quotation_worksheet_helper.php');
        if (is_file($helper)) {
            require_once $helper;
        }
        if (function_exists('qt_get_or_create_worksheet')) {
            qt_get_or_create_worksheet((int) $proposal_id);
            $CI->db->from($p . 'ipms_qt_worksheets');
            $CI->db->where('proposal_id', (int) $proposal_id);
            $ws = $CI->db->get()->row();
        }
    }

    if ($ws) {
        return $ws;
    }

    $q = jc_get_ipms_quotation_for_proposal($proposal);
    if (!$q) {
        return null;
    }

    $tabs = jc_quotation_tabs_string_for_job_card((int) $q->id, $q);

    return (object) [
        'service_tabs' => $tabs,
        'total_cost'   => isset($q->total_cost) ? (float) $q->total_cost : 0.0,
        'total_sell'   => isset($q->total_sell) ? (float) $q->total_sell : 0.0,
        'grand_total'  => isset($q->grand_total) ? (float) $q->grand_total : 0.0,
    ];
}

/**
 * Fill empty worksheet totals/tabs from linked ipms_quotations when present.
 *
 * @param object      $worksheet (modified in place)
 * @param object      $proposal
 * @param object|null $quotation optional pre-fetched row
 */
function jc_enrich_worksheet_from_ipms_quotation($worksheet, $proposal, $quotation = null)
{
    if (!$worksheet || !$proposal) {
        return;
    }

    $q = $quotation ?: jc_get_ipms_quotation_for_proposal($proposal);
    if (!$q) {
        return;
    }

    if (trim((string) ($worksheet->service_tabs ?? '')) === '') {
        $worksheet->service_tabs = jc_quotation_tabs_string_for_job_card((int) $q->id, $q);
    }

    $gtWs = (float) ($worksheet->grand_total ?? 0);
    $gtQ  = (float) ($q->grand_total ?? 0);
    if ($gtWs <= 0 && $gtQ > 0) {
        $worksheet->total_cost  = (float) ($q->total_cost ?? 0);
        $worksheet->total_sell  = (float) ($q->total_sell ?? 0);
        $worksheet->grand_total = $gtQ;
    }
}

function jc_auto_create_from_proposal($proposal_id)
{
    $CI          = &get_instance();
    $proposal_id = (int) $proposal_id;
    if ($proposal_id < 1) {
        return false;
    }

    $CI->db->from(db_prefix() . 'proposals');
    $CI->db->where('id', $proposal_id);
    $proposal = $CI->db->get()->row();
    if (!$proposal) {
        log_message('error', 'jc_auto_create_from_proposal: proposal not found ' . $proposal_id);

        return false;
    }

    $worksheet = jc_resolve_worksheet_for_auto_job_card($proposal_id, $proposal);
    if (!$worksheet) {
        log_message('error', 'jc_auto_create_from_proposal: no worksheet or IPMS quotation for proposal ' . $proposal_id);

        return false;
    }

    $qLinked = jc_get_ipms_quotation_for_proposal($proposal);
    jc_enrich_worksheet_from_ipms_quotation($worksheet, $proposal, $qLinked);

    $CI->db->from(db_prefix() . 'ipms_job_cards');
    $CI->db->where('proposal_id', $proposal_id);
    if ($CI->db->count_all_results() > 0) {
        return false;
    }

    $jc_ref    = jc_generate_ref();
    $client_id = 0;
    if (isset($proposal->rel_type, $proposal->rel_id) && $proposal->rel_type === 'customer') {
        $client_id = (int) $proposal->rel_id;
    }

    $serviceTabs = (string) $worksheet->service_tabs;
    $routing     = jc_determine_routing($serviceTabs);

    $qtRef = isset($proposal->qt_ref) && (string) $proposal->qt_ref !== ''
        ? (string) $proposal->qt_ref
        : ($qLinked && !empty($qLinked->quotation_ref) ? (string) $qLinked->quotation_ref : '');

    $data = [
        'jc_ref'             => $jc_ref,
        'proposal_id'        => $proposal_id,
        'qt_ref'             => $qtRef,
        'client_id'          => $client_id,
        'created_by'         => 0,
        'assigned_sales_id'  => isset($proposal->assigned) ? (int) $proposal->assigned : 0,
        'job_description'    => isset($proposal->subject) ? $proposal->subject : '',
        'job_type'           => $serviceTabs,
        'department_routing' => implode(',', $routing),
        'status'             => 1,
        'start_date'         => date('Y-m-d'),
        'deadline'           => date(
            'Y-m-d',
            strtotime('+' . (int) jc_setting('jc_default_deadline_days', '7') . ' days')
        ),
        'approved_cost'      => isset($worksheet->total_cost) ? (float) $worksheet->total_cost : 0.00,
        'approved_sell'      => isset($worksheet->total_sell) ? (float) $worksheet->total_sell : 0.00,
        'approved_total'     => isset($worksheet->grand_total) ? (float) $worksheet->grand_total : 0.00,
    ];

    $CI->db->insert(db_prefix() . 'ipms_job_cards', $data);
    $jc_id = (int) $CI->db->insert_id();
    if ($jc_id <= 0) {
        return false;
    }

    foreach ($routing as $dept) {
        $CI->db->insert(db_prefix() . 'ipms_jc_department_assignments', [
            'job_card_id' => $jc_id,
            'department'  => $dept,
            'notified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $CI->db->insert(db_prefix() . 'ipms_jc_status_log', [
        'job_card_id'     => $jc_id,
        'from_status'     => 0,
        'to_status'       => 1,
        'changed_by'      => 0,
        'changed_by_name' => 'System (Auto-created from approval)',
        'changed_by_role' => '',
        'notes'           => 'Auto-created when quotation ' . $qtRef . ' was approved',
        'changed_at'      => date('Y-m-d H:i:s'),
    ]);

    $CI->db->where('id', $proposal_id);
    $CI->db->update(db_prefix() . 'proposals', ['jc_id' => $jc_id]);

    $client_name = jc_get_client_name($client_id);
    $depts       = $routing;
    foreach ($depts as $dept) {
        jc_notify_department($jc_id, $dept, $jc_ref, $client_name);
    }

    if (isset($proposal->assigned) && (int) $proposal->assigned > 0) {
        add_notification([
            'description' => 'Job Card ' . $jc_ref . ' created for your proposal ' . ($qtRef !== '' ? $qtRef : (string) $proposal_id),
            'touserid'    => (int) $proposal->assigned,
            'fromuserid'  => 0,
            'link'        => 'job_cards/view/' . $jc_id,
        ]);
    }

    return $jc_id;
}


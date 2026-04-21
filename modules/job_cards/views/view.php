<?php defined('BASEPATH') or exit('No direct script access allowed');

$roleName      = function_exists('get_staff_role') ? (string) get_staff_role(get_staff_user_id()) : '';
$isManager     = is_admin() || in_array($roleName, ['General Manager', 'Finance Manager', 'Sales Manager'], true);
$isStudio      = $roleName === 'Studio/Production';
$isStores      = in_array($roleName, ['Storekeeper/Stores Clerk', 'Store Manager'], true);
$canIssue      = $isStores || $isManager;
$canEditProd   = $isStudio || $isManager || (function_exists('jc_can_edit_notes') && jc_can_edit_notes('production'));
$canEditQc     = $isStudio || $isManager || (function_exists('jc_can_edit_notes') && jc_can_edit_notes('quality'));
$nextStatus    = ((int) $job_card->status < 7) ? ((int) $job_card->status + 1) : 7;
$nextLabel     = jc_get_status_label($nextStatus)['label'];
$today         = date('Y-m-d');
$isOverdue     = !empty($job_card->deadline) && $job_card->deadline < $today && (int) $job_card->status < 6;

$tabs = [];
foreach ((array) $job_card->qt_lines as $line) {
    $tab = isset($line['tab']) ? (string) $line['tab'] : 'other';
    if (!isset($tabs[$tab])) {
        $tabs[$tab] = ['subtotal' => 0, 'lines' => []];
    }
    $tabs[$tab]['lines'][] = $line;
    $tabs[$tab]['subtotal'] += (float) ($line['line_total_sell'] ?? 0);
}

$deptIcon = [
    'studio'     => 'fa fa-paint-brush text-info',
    'stores'     => 'fa fa-cubes text-warning',
    'field_team' => 'fa fa-truck text-success',
    'warehouse'  => 'fa fa-archive text-danger',
];

$jcDocDate = static function ($d) {
    if ($d === null || $d === '') {
        return '—';
    }
    $t = strtotime((string) $d);

    return $t ? date('m.d.Y', $t) : '—';
};
$jcDisplayCompany = get_option('invoice_company_name') ?: get_option('companyname');
$jcModuleLogoRel  = 'modules/job_cards/assets/images/mega-logo.png';
$jcModuleLogoAbs  = FCPATH . $jcModuleLogoRel;
$jcHasModuleLogo  = is_file($jcModuleLogoAbs);
$jcLogoFile      = (string) get_option('company_logo');
$jcLogoPath      = FCPATH . 'uploads/company/' . $jcLogoFile;
$jcHasCompanyLogo = $jcLogoFile !== '' && is_file($jcLogoPath);
$jcCompanyLines = [];
$jcVat          = (string) get_option('company_vat');
if ($jcVat !== '') {
    $jcCompanyLines[] = 'TPin: ' . $jcVat;
}
$jcAddrRaw = trim((string) get_option('invoice_company_address'));
if ($jcAddrRaw !== '') {
    $jcCompanyLines[] = preg_replace('/\s+/', ' ', str_replace(["\r\n", "\n", "\r"], ', ', $jcAddrRaw));
}
$jcPhone = trim((string) get_option('invoice_company_phonenumber'));
if ($jcPhone !== '') {
    $jcCompanyLines[] = $jcPhone;
}
$jcSmtp = trim((string) get_option('smtp_email'));
if ($jcSmtp !== '' && stripos($jcSmtp, 'noreply') === false) {
    $jcCompanyLines[] = 'Email: ' . $jcSmtp;
}
$jcWeb = trim((string) get_option('company_website'));
if ($jcWeb === '') {
    $jcWeb = site_url();
}
$jcCompanyLines[] = 'Web: ' . preg_replace('#^https?://#i', '', $jcWeb);
$jcCityLine = trim(implode(', ', array_filter([
    (string) get_option('invoice_company_city'),
    (string) get_option('company_state'),
])));
if ($jcCityLine !== '') {
    $jcCompanyLines[] = $jcCityLine;
}
$cl = $client ?? null;
$billName  = $cl && isset($cl->company) ? (string) $cl->company : 'Unknown Client';
$billEmail = $cl && !empty($cl->email) ? (string) $cl->email : '';
$billPhone = $cl && !empty($cl->phonenumber) ? (string) $cl->phonenumber : '';
$billAddr  = '';
if ($cl) {
    $billAddr = trim(implode("\n", array_filter([
        (string) ($cl->address ?? ''),
        trim(implode(', ', array_filter([(string) ($cl->city ?? ''), (string) ($cl->state ?? '')]))),
        trim((string) ($cl->zip ?? '')),
    ])));
}

init_head();
?>
<div id="wrapper" class="jc-job-card-view-wrap">
    <div class="content">
        <div class="row jc-view-toolbar">
            <div class="col-md-8 col-sm-7">
                <h4 class="page-title jc-view-toolbar-title">
                    <i class="fa fa-clipboard"></i>
                    <?php echo e($job_card->jc_ref); ?>
                    <?php echo jc_get_status_badge($job_card->status); ?>
                    <small class="text-muted">
                        <?php if ((int) $job_card->proposal_id > 0) { ?>
                            <a href="<?php echo admin_url('proposals/list_proposals/' . (int) $job_card->proposal_id); ?>"><?php echo e($job_card->qt_ref ?: 'Proposal'); ?></a>
                        <?php } else { ?>
                            <span class="text-warning">Proposal not linked</span>
                        <?php } ?>
                    </small>
                </h4>
            </div>
            <div class="col-md-4 col-sm-5 text-right jc-view-toolbar-actions">
                <a href="<?php echo admin_url('job_cards/pdf/' . $job_card->id); ?>" class="btn btn-default btn-sm" target="_blank">
                    <i class="fa fa-file-pdf-o"></i> Print Job Card
                </a>
                <?php if (staff_can('admin', 'job_cards')) { ?>
                    <a href="<?php echo admin_url('job_cards/create_material_issue/' . $job_card->id); ?>"
                       class="btn btn-warning btn-sm <?php echo (int) $job_card->materials_issued === 1 ? 'disabled' : ''; ?>">
                        <i class="fa fa-cubes"></i>
                        <?php echo (int) $job_card->materials_issued === 1 ? 'Materials Issued' : 'Issue Materials'; ?>
                    </a>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="jc-doc-sheet">
                    <header class="jc-doc-header">
                        <div class="jc-doc-header-brand">
                            <?php if ($jcHasModuleLogo) { ?>
                                <img src="<?php echo base_url($jcModuleLogoRel); ?>" alt="MEGA Signs &amp; Media Ltd" class="jc-doc-logo-img" />
                            <?php } elseif ($jcHasCompanyLogo) { ?>
                                <img src="<?php echo base_url('uploads/company/' . $jcLogoFile); ?>" alt="" class="jc-doc-logo-img" />
                            <?php } else { ?>
                                <div class="jc-doc-logo-mark" aria-hidden="true">
                                    <svg viewBox="0 0 56 52" xmlns="http://www.w3.org/2000/svg" class="jc-doc-logo-svg">
                                        <path fill="#6BB3E8" d="M6 46 L6 10 L22 10 L14 46 Z"/>
                                        <path fill="#4B2869" d="M18 10 L34 10 L46 46 L28 46 Z"/>
                                    </svg>
                                </div>
                            <?php } ?>
                            <div class="jc-doc-company-block">
                                <div class="jc-doc-company-name"><?php echo e(strtoupper($jcDisplayCompany)); ?></div>
                                <div class="jc-doc-company-meta">
                                    <strong><?php echo e($jcDisplayCompany); ?></strong>
                                    <?php foreach ($jcCompanyLines as $ln) { ?>
                                        <span class="jc-doc-company-line"><?php echo e($ln); ?></span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="jc-doc-header-title">
                            <span class="jc-doc-title-text">Job Card</span>
                        </div>
                    </header>

                    <div class="jc-doc-meta row">
                        <div class="col-sm-6 jc-bill-to">
                            <div class="jc-bill-to-label">Bill to</div>
                            <div class="jc-bill-to-body">
                                <div class="jc-bill-to-line jc-bill-to-name"><a href="<?php echo admin_url('clients/client/' . (int) $job_card->client_id); ?>"><?php echo e($billName); ?></a></div>
                                <div class="jc-bill-to-line"><?php echo $billEmail !== '' ? e($billEmail) : '<span class="jc-bill-placeholder">[E-MAIL]</span>'; ?></div>
                                <div class="jc-bill-to-line"><?php echo $billPhone !== '' ? e($billPhone) : '<span class="jc-bill-placeholder">[PHONE]</span>'; ?></div>
                                <div class="jc-bill-to-line jc-bill-to-addr"><?php echo $billAddr !== '' ? nl2br(e($billAddr)) : '<span class="jc-bill-placeholder">[ADDRESS]</span>'; ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 jc-order-block">
                            <div class="jc-order-line"><span class="jc-order-label">Job no.</span> <strong><?php echo e($job_card->jc_ref); ?></strong></div>
                            <div class="jc-order-line"><span class="jc-order-label">Order date</span> <strong><?php echo e($jcDocDate($job_card->start_date ?: $job_card->created_at)); ?></strong></div>
                            <div class="jc-order-line"><span class="jc-order-label">Due</span> <strong class="<?php echo $isOverdue ? 'text-danger' : ''; ?>"><?php echo e($jcDocDate($job_card->deadline)); ?><?php if ($isOverdue) { ?> <span class="label label-danger">Overdue</span><?php } ?></strong></div>
                            <div class="jc-order-line"><span class="jc-order-label">Status</span> <?php echo jc_get_status_badge($job_card->status); ?></div>
                            <div class="jc-order-line"><span class="jc-order-label">Proposal ref</span>
                                <?php if ((int) $job_card->proposal_id > 0) { ?>
                                    <strong><a href="<?php echo admin_url('proposals/list_proposals/' . (int) $job_card->proposal_id); ?>"><?php echo e($job_card->qt_ref ?: '—'); ?></a></strong>
                                <?php } else { ?>
                                    <span class="text-muted">Not linked — set <code>jc_id</code> on the proposal or recreate from a proposal.</span>
                                <?php } ?>
                            </div>
                            <div class="jc-order-line"><span class="jc-order-label">Approved value</span> <strong><?php echo e(jc_format_mwk($job_card->approved_total)); ?></strong></div>
                        </div>
                    </div>

                    <div class="jc-doc-extra-meta row">
                        <div class="col-sm-4"><span class="jc-order-label">Created by</span> <?php echo e($job_card->created_by_name ?: 'System'); ?><?php if ((int) $job_card->created_by === 0) { ?> <span class="label label-default">Auto</span><?php } ?></div>
                        <div class="col-sm-4"><span class="jc-order-label">Salesperson</span> <?php echo e($job_card->assigned_sales_name ?: '—'); ?></div>
                        <div class="col-sm-4"><span class="jc-order-label">Job types</span>
                            <?php foreach (array_filter(array_map('trim', explode(',', (string) $job_card->job_type))) as $jt) { ?>
                                <span class="label label-info"><?php echo e(ucwords(str_replace('_', ' ', $jt))); ?></span>
                            <?php } ?>
                            <?php if (trim((string) $job_card->job_type) === '') { ?><span class="text-muted">—</span><?php } ?>
                        </div>
                    </div>

                <div class="jc-doc-section">
                    <div class="jc-doc-section-hd"><strong>Department Routing &amp; Acknowledgements</strong></div>
                    <div class="jc-doc-section-bd">
                        <table class="table jc-doc-table">
                            <thead><tr><th>Department</th><th>Notified</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ((array) $job_card->department_assignments as $da) {
                                $dept = (string) $da['department'];
                                $isMyDept = ($dept === 'studio' && $isStudio)
                                    || ($dept === 'stores' && $isStores)
                                    || ($dept === 'field_team' && $roleName === 'Field Team')
                                    || ($dept === 'warehouse' && $roleName === jc_setting('jc_warehouse_role', 'Storekeeper/Stores Clerk'));
                                ?>
                                <tr>
                                    <td><i class="<?php echo e($deptIcon[$dept] ?? 'fa fa-circle'); ?>"></i> <?php echo e(jc_get_department_label($dept)); ?></td>
                                    <td><?php echo !empty($da['notified_at']) ? e(_dt($da['notified_at'])) : '—'; ?></td>
                                    <td>
                                        <?php if ((int) $da['completed'] === 1) { ?>
                                            <span class="label label-success">Completed</span>
                                        <?php } elseif (!empty($da['acknowledged_at'])) { ?>
                                            <span class="label label-info">Acknowledged</span>
                                            <small class="text-muted">by <?php echo e($da['acknowledged_by_name'] ?? ('Staff #' . (int) $da['acknowledged_by'])); ?> at <?php echo e(_dt($da['acknowledged_at'])); ?></small>
                                        <?php } else { ?>
                                            <span class="label label-default">Pending</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($isMyDept && empty($da['acknowledged_at'])) { ?>
                                            <button class="btn btn-xs btn-primary jc-acknowledge" data-jc="<?php echo (int) $job_card->id; ?>" data-dept="<?php echo e($dept); ?>">
                                                Acknowledge
                                            </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="jc-doc-section">
                    <div class="jc-doc-section-hd"><strong>Approved Scope of Work</strong></div>
                    <div class="jc-doc-section-bd">
                        <div class="panel-group jc-scope-accordion" id="jc-scope-accordion">
                            <?php $tabIndex = 0; foreach ($tabs as $tabKey => $tabData) { $tabIndex++; ?>
                                <div class="panel panel-default jc-scope-panel">
                                    <div class="panel-heading jc-scope-panel-hd">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#jc-scope-accordion" href="#scope-<?php echo (int) $tabIndex; ?>">
                                                <?php echo e(ucwords(str_replace('_', ' ', $tabKey))); ?> — <?php echo e(jc_format_mwk($tabData['subtotal'])); ?>
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="scope-<?php echo (int) $tabIndex; ?>" class="panel-collapse collapse <?php echo $tabIndex === 1 ? 'in' : ''; ?>">
                                        <div class="panel-body jc-scope-panel-bd">
                                            <div class="table-responsive">
                                            <table class="table jc-prod-table">
                                                <thead>
                                                <tr>
                                                    <th class="jc-col-code">Product Code</th>
                                                    <th class="jc-col-cat">Category</th>
                                                    <th class="jc-col-desc">Production Description</th>
                                                    <th class="jc-col-qty">Quantity</th>
                                                    <th class="jc-col-uom">UoM</th>
                                                    <th class="jc-col-notes">Notes</th>
                                                    <th class="jc-col-price">Unit Price</th>
                                                    <th class="jc-col-total">Line Total</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $rowNum = 0;
                                                foreach ($tabData['lines'] as $line) {
                                                    $rowNum++;
                                                    $pcode = (string) ($line['item_code'] ?? $line['commodity_code'] ?? '');
                                                    $cat = (string) ($line['category'] ?? $line['commodity_group'] ?? '');
                                                    if ($cat === '') {
                                                        $cat = ucwords(str_replace('_', ' ', (string) ($line['tab'] ?? $tabKey)));
                                                    }
                                                    $notes = (string) ($line['notes'] ?? $line['note'] ?? $line['remarks'] ?? $line['long_description'] ?? '');
                                                    ?>
                                                    <tr class="<?php echo $rowNum % 2 === 0 ? 'jc-row-alt' : ''; ?>">
                                                        <td><?php echo $pcode !== '' ? e($pcode) : '—'; ?></td>
                                                        <td><?php echo e($cat); ?></td>
                                                        <td><?php echo e($line['description'] ?? ''); ?></td>
                                                        <td><?php echo e($line['quantity'] ?? ''); ?></td>
                                                        <td><?php echo e($line['unit'] ?? ''); ?></td>
                                                        <td><?php echo e($notes); ?></td>
                                                        <td><?php echo e(jc_format_mwk($line['sell_price'] ?? 0)); ?></td>
                                                        <td><?php echo e(jc_format_mwk($line['line_total_sell'] ?? 0)); ?></td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php if (empty($tabs) && (int) $job_card->proposal_id > 0) { ?>
                            <p class="text-muted mtop10">No line items were returned for this proposal. Add lines to the proposal (or IPMS quotation) and ensure materials use inventory item / commodity IDs where stock should be issued.</p>
                        <?php } ?>
                        <?php if ((int) $job_card->proposal_id > 0) { ?>
                            <p class="text-muted">Full quotation: <a href="<?php echo admin_url('proposals/list_proposals/' . (int) $job_card->proposal_id); ?>" target="_blank"><?php echo e($job_card->qt_ref ?: 'Open proposal'); ?></a></p>
                        <?php } ?>
                    </div>
                </div>

                <div class="jc-doc-section">
                    <div class="jc-doc-section-hd"><strong>Material Issues</strong> <span class="badge"><?php echo count((array) $job_card->material_issues); ?></span></div>
                    <div class="jc-doc-section-bd">
                        <?php if (empty($job_card->material_issues)) { ?>
                            <p class="text-muted">No materials issued yet.</p>
                        <?php } else { ?>
                            <table class="table jc-doc-table jc-doc-table-striped">
                                <thead><tr><th>Issue Ref</th><th>Date</th><th>Issued By</th><th>Warehouse</th><th>Items</th><th>Total Cost</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php $mi = 0; foreach ((array) $job_card->material_issues as $issue) { $mi++; ?>
                                        <tr class="<?php echo $mi % 2 === 0 ? 'jc-row-alt' : ''; ?>">
                                            <td><a href="<?php echo admin_url('job_cards/get_material_issue/' . (int) $issue['id']); ?>" target="_blank"><?php echo e($issue['issue_ref']); ?></a></td>
                                            <td><?php echo !empty($issue['issued_at']) ? e(_dt($issue['issued_at'])) : '—'; ?></td>
                                            <td><?php echo (int) $issue['issued_by'] > 0 ? e(get_staff_full_name((int) $issue['issued_by'])) : '—'; ?></td>
                                            <td><?php echo e($issue['warehouse_id'] ?? '—'); ?></td>
                                            <td><?php echo (int) ($issue['line_count'] ?? 0); ?></td>
                                            <td><?php echo e(jc_format_mwk($issue['total_cost_value'] ?? 0)); ?></td>
                                            <td><span class="label label-default"><?php echo e(ucfirst($issue['status'] ?? '')); ?></span></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                        <?php if ((int) $job_card->materials_issued === 0 && $canIssue) { ?>
                            <a href="<?php echo admin_url('job_cards/create_material_issue/' . $job_card->id); ?>" class="btn btn-warning mtop10">
                                <i class="fa fa-cubes"></i> Issue Materials
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="jc-doc-section">
                    <div class="jc-doc-section-hd"><strong>Production Notes</strong></div>
                    <div class="jc-doc-section-bd">
                        <div class="qt-notes-section">
                            <textarea id="production_notes" name="production_notes" class="form-control" rows="4" placeholder="Add production progress notes..."><?php echo e($job_card->production_notes); ?></textarea>
                            <?php if ($canEditProd) { ?>
                                <button class="btn btn-xs btn-primary mtop10 jc-save-notes" data-type="production_notes" data-jc="<?php echo (int) $job_card->id; ?>">Save Notes</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="jc-doc-section">
                    <div class="jc-doc-section-hd"><strong>Quality Check Notes</strong></div>
                    <div class="jc-doc-section-bd">
                        <div class="qt-notes-section">
                            <textarea id="quality_notes" name="quality_notes" class="form-control" rows="4" placeholder="Add quality check notes..."><?php echo e($job_card->quality_notes); ?></textarea>
                            <?php if ($canEditQc) { ?>
                                <button class="btn btn-xs btn-primary mtop10 jc-save-notes" data-type="quality_notes" data-jc="<?php echo (int) $job_card->id; ?>">Save Notes</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                    <p class="jc-doc-terms">Terms and conditions apply</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="jc-doc-sidebar">
                <div class="jc-doc-section jc-doc-section-sidebar">
                    <div class="jc-doc-section-hd"><strong>Status Pipeline</strong></div>
                    <div class="jc-doc-section-bd">
                        <ul class="jc-pipeline">
                            <?php for ($s = 1; $s <= 7; $s++) {
                                $label = jc_get_status_label($s);
                                $dotClass = $s < (int) $job_card->status ? 'completed' : ((int) $job_card->status === $s ? 'current' : '');
                                $statusTime = '';
                                foreach ((array) $job_card->status_log as $ev) {
                                    if ((int) $ev['to_status'] === $s && !empty($ev['changed_at'])) {
                                        $statusTime = _dt($ev['changed_at']);
                                    }
                                } ?>
                                <li class="jc-pipeline-step">
                                    <span class="jc-step-dot <?php echo e($dotClass); ?>"><?php echo $s < (int) $job_card->status ? '<i class="fa fa-check"></i>' : (int) $s; ?></span>
                                    <span class="jc-step-content">
                                        <div class="jc-step-label <?php echo $s > (int) $job_card->status ? 'dimmed' : ''; ?>"><?php echo e($label['label']); ?></div>
                                        <?php if ($statusTime !== '') { ?><div class="jc-step-time"><?php echo e($statusTime); ?></div><?php } ?>
                                    </span>
                                </li>
                            <?php } ?>
                        </ul>

                        <?php if ((int) $job_card->status < 7) { ?>
                            <div id="jc-advance-panel" class="mtop15 p-10">
                                <h5>Advance Status</h5>
                                <p class="text-muted small">Next: <?php echo e($nextLabel); ?></p>
                                <textarea id="jc-status-notes" class="form-control" rows="3" placeholder="Add notes for this transition..."></textarea>
                                <button id="jc-advance-status" class="btn btn-info btn-block mtop10" data-jc="<?php echo (int) $job_card->id; ?>" data-next-status="<?php echo (int) $nextStatus; ?>">
                                    <i class="fa fa-arrow-right"></i> Mark <?php echo e($nextLabel); ?>
                                </button>
                                <p class="text-muted small mtop5">Role restrictions apply based on next status.</p>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="jc-doc-section jc-doc-section-sidebar">
                    <div class="jc-doc-section-hd"><strong>Activity Timeline</strong></div>
                    <div class="jc-doc-section-bd">
                        <div class="jc-timeline">
                            <?php $events = array_reverse((array) $job_card->status_log); foreach ($events as $event) {
                                $to = (int) $event['to_status'];
                                $statusLabel = jc_get_status_label($to); ?>
                                <div class="jc-timeline-event">
                                    <span class="jc-timeline-dot status-<?php echo $to; ?>"></span>
                                    <div class="jc-timeline-body">
                                        <div><strong><?php echo e($statusLabel['label']); ?></strong></div>
                                        <div class="jc-timeline-actor"><?php echo e($event['changed_by_name'] ?: ('Staff #' . (int) $event['changed_by'])); ?> <?php echo !empty($event['changed_by_role']) ? '(' . e($event['changed_by_role']) . ')' : ''; ?></div>
                                        <div class="jc-timeline-time"><?php echo !empty($event['changed_at']) ? e(_dt($event['changed_at'])) : ''; ?></div>
                                        <?php if (!empty($event['notes'])) { ?><div class="jc-timeline-notes"><?php echo e($event['notes']); ?></div><?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="jc-doc-section jc-doc-section-sidebar">
                    <div class="jc-doc-section-hd"><strong>Quick Info</strong></div>
                    <div class="jc-doc-section-bd">
                        <p><strong>Materials Status:</strong><br />
                            <?php if ((int) $job_card->materials_issued === 1) { ?>
                                <span class="text-success"><i class="fa fa-check-circle"></i> Issued by <?php echo (int) $job_card->materials_issued_by > 0 ? e(get_staff_full_name((int) $job_card->materials_issued_by)) : '—'; ?> on <?php echo !empty($job_card->materials_issued_at) ? e(_dt($job_card->materials_issued_at)) : '—'; ?></span>
                            <?php } else { ?>
                                <span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Pending issue</span>
                            <?php } ?>
                        </p>
                        <p><strong>Delivery (goods out):</strong><br />
                            <?php if ((int) $job_card->delivery_note_id > 0) { ?>
                                <a href="<?php echo admin_url('warehouse/view_delivery/' . (int) $job_card->delivery_note_id); ?>" target="_blank">Open goods delivery #<?php echo (int) $job_card->delivery_note_id; ?></a>
                            <?php } else { ?>
                                Not linked yet — create under <a href="<?php echo admin_url('warehouse/goods_delivery'); ?>" target="_blank">Warehouse → Goods delivery</a> or open <a href="<?php echo admin_url('warehouse/manage_delivery'); ?>" target="_blank">Manage delivery</a>.
                            <?php } ?>
                        </p>
                        <p><strong>Invoice:</strong><br />
                            <?php if ((int) $job_card->invoice_id > 0) { ?>
                                <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $job_card->invoice_id); ?>" target="_blank">Invoice #<?php echo (int) $job_card->invoice_id; ?></a>
                            <?php } else { ?>Not invoiced yet<?php } ?>
                        </p>
                        <p><strong>Completion:</strong><br />
                            <?php echo !empty($job_card->completed_at) ? e(_dt($job_card->completed_at)) : 'In progress'; ?>
                        </p>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
  $('#jc-advance-status').on('click', function() {
    var jc_id = $(this).data('jc');
    var new_status = $(this).data('next-status');
    var notes = $('#jc-status-notes').val();
    $.post(admin_url + 'job_cards/update_status', {
      job_card_id: jc_id, new_status: new_status, notes: notes
    }, function(res) {
      if (res.success) {
        location.reload();
      } else {
        alert_float('danger', res.message);
      }
    }, 'json');
  });

  $(document).on('click', '.jc-save-notes', function() {
    var type = $(this).data('type');
    var jc_id = $(this).data('jc');
    var value = $('#' + type).val();
    $.post(admin_url + 'job_cards/update_notes', {
      job_card_id: jc_id, note_type: type, value: value
    }, function(res) {
      if (res.success) alert_float('success', 'Notes saved');
      else alert_float('danger', res.message);
    }, 'json');
  });

  $(document).on('click', '.jc-acknowledge', function() {
    var jc_id = $(this).data('jc');
    var dept = $(this).data('dept');
    $.post(admin_url + 'job_cards/acknowledge_department', {
      job_card_id: jc_id, department: dept
    }, function(res) {
      if (res.success) location.reload();
    }, 'json');
  });
});
</script>
</body>
</html>

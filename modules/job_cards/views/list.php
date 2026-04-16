<?php defined('BASEPATH') or exit('No direct script access allowed');

$statuses = jc_get_all_statuses();
$counts   = isset($counts) && is_array($counts) ? $counts : [];
$roleName = function_exists('get_staff_role') ? (string) get_staff_role(get_staff_user_id()) : '';
$isManager = is_admin() || in_array($roleName, ['General Manager', 'Finance Manager', 'Sales Manager'], true);
$canIssueMaterials = in_array($roleName, ['Storekeeper/Stores Clerk', 'Store Manager'], true) || $isManager;
init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="page-title">
                            <i class="fa fa-clipboard"></i> Job Cards
                        </h4>
                        <?php if (staff_can('create', 'job_cards')) { ?>
                            <a href="<?php echo admin_url('job_cards/create'); ?>" class="btn btn-info pull-right">
                                <i class="fa fa-plus"></i> New Job Card
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="jc-status-scroll">
                            <div class="row">
                                <?php foreach ($statuses as $sid => $slabel) {
                                    $meta = jc_get_status_label($sid); ?>
                                    <div class="col-md-1 col-sm-2 col-xs-6">
                                        <div class="jc-stat-card panel panel-default mbot10" data-status="<?php echo (int) $sid; ?>">
                                            <div class="panel-body text-center">
                                                <div class="jc-stat-label" style="color:<?php echo html_escape($meta['color']); ?>">
                                                    <?php echo html_escape($slabel); ?>
                                                </div>
                                                <div class="jc-stat-num"><?php echo (int) ($counts[$sid] ?? 0); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="col-md-1 col-sm-2 col-xs-6">
                                    <div class="jc-stat-card panel panel-danger mbot10 jc-overdue-pulse" data-status="overdue">
                                        <div class="panel-body text-center">
                                            <div class="jc-stat-label text-danger">Overdue</div>
                                            <div class="jc-stat-num text-danger"><?php echo (int) ($counts['overdue'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <form id="jc-filter-form" class="form-inline">
                            <div class="form-group mright10">
                                <label for="jc-filter-status">Status</label><br />
                                <select id="jc-filter-status" class="selectpicker" data-width="210px" data-live-search="false" multiple>
                                    <?php foreach ($statuses as $sid => $slabel) { ?>
                                        <option value="<?php echo (int) $sid; ?>" selected><?php echo html_escape($slabel); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group mright10">
                                <label for="jc-filter-department">Department</label><br />
                                <select id="jc-filter-department" class="selectpicker" data-width="170px">
                                    <option value="">All</option>
                                    <option value="studio">Studio</option>
                                    <option value="stores">Stores</option>
                                    <option value="field_team">Field Team</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                            <div class="form-group mright10">
                                <label for="jc-filter-client">Client</label><br />
                                <input type="text" id="jc-filter-client" class="form-control" placeholder="Search client">
                                <input type="hidden" id="jc-filter-client-id" value="">
                            </div>
                            <div class="form-group mright10">
                                <label for="jc-filter-date-from">From</label><br />
                                <input type="date" id="jc-filter-date-from" class="form-control">
                            </div>
                            <div class="form-group mright10">
                                <label for="jc-filter-date-to">To</label><br />
                                <input type="date" id="jc-filter-date-to" class="form-control">
                            </div>
                            <div class="form-group mright10">
                                <label>&nbsp;</label><br />
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="jc-filter-mine" value="1"> My Job Cards
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label><br />
                                <button type="button" id="jc-apply-filters" class="btn btn-info">Apply</button>
                                <a href="#" id="jc-clear-filters" class="btn btn-default">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="jc-table">
                                <thead>
                                    <tr>
                                        <th>JC Ref</th>
                                        <th>Proposal</th>
                                        <th>Client</th>
                                        <th>Job Type</th>
                                        <th>Routing</th>
                                        <th>Approved Value</th>
                                        <th>Start Date</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Materials</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($job_cards) && is_array($job_cards)) {
                                    $todayIso = date('Y-m-d');
                                    foreach ($job_cards as $row) {
                                        $id        = (int) ($row['id'] ?? 0);
                                        $jcRef     = $row['jc_ref'] ?? '';
                                        $proposalId = (int) ($row['proposal_id'] ?? 0);
                                        $qtRef     = $row['qt_ref'] ?? ($row['proposal_qt_ref'] ?? '');
                                        $subject   = $row['job_description'] ?? ($row['proposal_subject'] ?? '');
                                        if (strlen($subject) > 40) {
                                            $subject = mb_substr($subject, 0, 40) . '...';
                                        }
                                        $clientId  = (int) ($row['client_id'] ?? 0);
                                        $clientName = $row['client_name'] ?? '';
                                        $jobType   = $row['job_type'] ?? '';
                                        $routing   = $row['department_routing'] ?? '';
                                        $approved  = isset($row['approved_total']) ? (float) $row['approved_total'] : 0.0;
                                        $startDate = $row['start_date'] ?? '';
                                        $deadline  = $row['deadline'] ?? '';
                                        $status    = (int) ($row['status'] ?? 0);
                                        $materialsIssued = (int) ($row['materials_issued'] ?? 0);
                                        $createdBy = (int) ($row['created_by'] ?? 0);
                                        $createdByName = $row['created_by_name'] ?? '';
                                        $isOver   = $deadline !== '' && $deadline < $todayIso && $status < 6;
                                        ?>
                                        <tr class="<?php echo $isOver ? 'warning' : ''; ?>">
                                            <td>
                                                <a href="<?php echo admin_url('job_cards/view/' . $id); ?>"><?php echo html_escape($jcRef); ?></a>
                                                <?php if ($createdBy === 0) { ?>
                                                    <span class="label label-default mleft5">Auto</span>
                                                <?php } elseif ($createdByName !== '') {
                                                    $parts = explode(' ', $createdByName);
                                                    $init  = '';
                                                    foreach ($parts as $i => $p) {
                                                        if ($p === '' || $i > 1) { continue; }
                                                        $init .= strtoupper(mb_substr($p, 0, 1));
                                                    } ?>
                                                    <span class="label label-info mleft5"><?php echo html_escape($init); ?></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php
                                                $label = trim($qtRef) !== '' ? $qtRef : '';
                                                if ($subject !== '') {
                                                    $label = ($label !== '' ? $label . ' - ' : '') . $subject;
                                                }
                                                if ($proposalId > 0) { ?>
                                                    <a href="<?php echo admin_url('proposals/list_proposals/' . $proposalId); ?>" target="_blank">
                                                        <?php echo html_escape($label); ?>
                                                    </a>
                                                <?php } else {
                                                    echo html_escape($label !== '' ? $label : '—');
                                                } ?>
                                            </td>
                                            <td>
                                                <?php if ($clientId > 0) { ?>
                                                    <a href="<?php echo admin_url('clients/client/' . $clientId); ?>" target="_blank">
                                                        <?php echo html_escape($clientName !== '' ? $clientName : '—'); ?>
                                                    </a>
                                                <?php } else {
                                                    echo html_escape($clientName !== '' ? $clientName : '—');
                                                } ?>
                                            </td>
                                            <td>
                                                <?php
                                                foreach (array_filter(array_map('trim', explode(',', (string) $jobType))) as $jt) {
                                                    $labelType = ucwords(str_replace('_', ' ', $jt)); ?>
                                                    <span class="jc-type-badge"><?php echo html_escape($labelType); ?></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php
                                                $parts = array_filter(array_map('trim', explode(',', (string) $routing)));
                                                foreach ($parts as $dept) {
                                                    switch ($dept) {
                                                        case 'studio':
                                                            echo '<i class="fa fa-paint-brush text-info" title="Studio"></i> ';
                                                            break;
                                                        case 'stores':
                                                            echo '<i class="fa fa-cubes text-warning" title="Stores"></i> ';
                                                            break;
                                                        case 'field_team':
                                                            echo '<i class="fa fa-truck text-success" title="Field Team"></i> ';
                                                            break;
                                                        case 'warehouse':
                                                            echo '<i class="fa fa-archive text-danger" title="Warehouse"></i> ';
                                                            break;
                                                        default:
                                                            echo html_escape($dept) . ' ';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo html_escape(jc_format_mwk($approved)); ?></strong>
                                            </td>
                                            <td><?php echo $startDate ? html_escape($startDate) : '—'; ?></td>
                                            <td class="<?php echo $isOver ? 'text-danger bold' : ''; ?>">
                                                <?php echo $deadline ? html_escape($deadline) : '—'; ?>
                                                <?php if ($isOver) { ?><span class="label label-danger mleft5">Overdue</span><?php } ?>
                                            </td>
                                            <td>
                                                <?php
                                                $meta = jc_get_status_label($status);
                                                $cls  = 'jc-status-badge jc-status-' . $status;
                                                ?>
                                                <span class="<?php echo $cls; ?>" style="background:<?php echo html_escape($meta['color']); ?>;">
                                                    <?php echo html_escape($meta['label']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($materialsIssued === 1) { ?>
                                                    <span class="text-success"><i class="fa fa-check-circle"></i> Issued</span>
                                                <?php } else { ?>
                                                    <span class="text-warning"><i class="fa fa-clock-o"></i> Pending</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-right">
                                                <a class="btn btn-default btn-icon" href="<?php echo admin_url('job_cards/view/' . $id); ?>" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if ($canIssueMaterials && $status === 1) { ?>
                                                    <a class="btn btn-default btn-icon" href="<?php echo admin_url('job_cards/create_material_issue/' . $id); ?>" title="Issue Materials">
                                                        <i class="fa fa-clipboard"></i>
                                                    </a>
                                                <?php } ?>
                                                <a class="btn btn-default btn-icon" href="<?php echo admin_url('job_cards/pdf/' . $id); ?>" title="PDF" target="_blank">
                                                    <i class="fa fa-file-pdf-o"></i>
                                                </a>
                                                <?php if ($isManager) { ?>
                                                    <a class="btn btn-default btn-icon" href="<?php echo admin_url('job_cards/create/' . $proposalId); ?>" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php }
                                } ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="jc-empty-state" class="text-center text-muted hide mtop20 mbot20">
                            <p class="bold">No job cards found</p>
                            <p>Try adjusting your filters or create a new job card.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.jc-status-scroll{overflow-x:auto;overflow-y:hidden}
.jc-stat-card{cursor:pointer;min-height:92px}
.jc-stat-label{font-size:11px;font-weight:600;min-height:30px}
.jc-stat-num{font-size:24px;font-weight:700;line-height:1.1}
.jc-stat-card.active{border:2px solid #337ab7}
.jc-overdue-pulse{animation:jcPulse 1.4s ease-in-out infinite}
@keyframes jcPulse{0%{box-shadow:0 0 0 0 rgba(217,83,79,.45)}70%{box-shadow:0 0 0 10px rgba(217,83,79,0)}100%{box-shadow:0 0 0 0 rgba(217,83,79,0)}}
.jc-type-badge{display:inline-block;padding:2px 6px;margin:1px;border-radius:2px;font-size:11px;background:#f1f1f1}
.jc-routing i{margin-right:6px}
</style>

<?php init_tail(); ?>
</body>
</html>

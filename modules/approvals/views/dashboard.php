<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$doc_types = [
    'quotation'             => _l('estimates'),
    'credit_note'           => _l('credit_note'),
    'journal_entry'         => 'Journal entry',
    'payment'               => 'Payment',
    'purchase_requisition'  => 'Purchase requisition',
];
?>
<style>
@keyframes approval-sla-pulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(217, 83, 79, 0.35); }
    50% { opacity: 0.92; box-shadow: 0 0 0 6px rgba(217, 83, 79, 0); }
}
.approval-stat-card {
    background: #fff;
    border: 1px solid #e4e8f0;
    border-radius: 4px;
    padding: 18px 16px;
    margin-bottom: 20px;
    min-height: 110px;
    position: relative;
}
.approval-stat-card .approval-stat-icon {
    position: absolute;
    right: 16px;
    top: 18px;
    font-size: 28px;
    opacity: 0.35;
}
.approval-stat-card .approval-stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1.1;
    margin: 4px 0 6px;
}
.approval-stat-card .approval-stat-label {
    font-size: 13px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.approval-stat-pending .approval-stat-value { color: #3498db; }
.approval-stat-approved .approval-stat-value { color: #84c529; }
.approval-stat-await .approval-stat-value { color: #f0ad4e; }
.approval-stat-overdue .approval-stat-value { color: #d9534f; }
.approval-stat-overdue.approval-stat-overdue-breach .approval-stat-value {
    animation: approval-sla-pulse 1.6s ease-in-out infinite;
}
.approval-section-title {
    border-left: 4px solid #3498db;
    padding: 8px 0 8px 14px;
    margin: 24px 0 16px;
    font-size: 18px;
    font-weight: 600;
    background: #fafbfc;
}
.approval-value-strong { font-weight: 700; }
.approval-filter-bar {
    background: #fff;
    border: 1px solid #e4e8f0;
    border-radius: 4px;
    padding: 12px 14px;
    margin-bottom: 16px;
}
.modal-header.approval-modal-header-approve { border-top: 4px solid #84c529; }
.modal-header.approval-modal-header-reject { border-top: 4px solid #d9534f; }
.modal-header.approval-modal-header-revision { border-top: 4px solid #3498db; }
</style>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-bold tw-text-xl tw-mb-3"><?= e($title); ?></h4>
                <p class="tw-mb-4 text-muted">
                    <a href="<?= admin_url('approvals/my_requests'); ?>"><?= _l('approval_my_requests'); ?></a>
                    <?php if (is_admin()) { ?>
                    &nbsp;|&nbsp;<a href="<?= admin_url('approvals/settings'); ?>"><?= _l('approval_settings'); ?></a>
                    <?php } ?>
                </p>

                <!-- SECTION 1: Summary stats -->
                <div class="row" id="approval-dashboard-stats">
                    <div class="col-md-3 col-sm-6">
                        <div class="approval-stat-card approval-stat-pending">
                            <span class="approval-stat-icon text-info"><i class="fa fa-clock-o"></i></span>
                            <div class="approval-stat-label"><?= _l('approval_stat_pending_mine'); ?></div>
                            <div class="approval-stat-value js-stat-pending-mine"><?= (int) $stat_pending_mine; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="approval-stat-card approval-stat-approved">
                            <span class="approval-stat-icon text-success"><i class="fa fa-check-circle"></i></span>
                            <div class="approval-stat-label"><?= _l('approval_stat_approved_today'); ?></div>
                            <div class="approval-stat-value js-stat-approved-today"><?= (int) $stat_approved_today; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="approval-stat-card approval-stat-await">
                            <span class="approval-stat-icon text-warning"><i class="fa fa-hourglass-half"></i></span>
                            <div class="approval-stat-label"><?= _l('approval_stat_awaiting_others'); ?></div>
                            <div class="approval-stat-value js-stat-awaiting-others"><?= (int) $stat_awaiting_others; ?></div>
                            <small class="text-muted"><?= $is_general_manager ? _l('approval_stat_awaiting_others_gm_hint') : _l('approval_stat_awaiting_others_staff_hint'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="approval-stat-card approval-stat-overdue<?= $stat_overdue_sla > 0 ? ' approval-stat-overdue-breach' : ''; ?>">
                            <span class="approval-stat-icon text-danger"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="approval-stat-label"><?= _l('approval_stat_overdue_sla'); ?></div>
                            <div class="approval-stat-value js-stat-overdue-sla"><?= (int) $stat_overdue_sla; ?></div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Pending table -->
                <div class="approval-section-title"><?= _l('approval_section_requires_action'); ?></div>

                <div class="approval-filter-bar">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 tw-mb-2">
                            <label class="control-label"><?= _l('approval_filter_document_type'); ?></label>
                            <select id="approval-filter-doc-type" class="form-control">
                                <option value=""><?= _l('approval_filter_all_types'); ?></option>
                                <?php foreach ($doc_types as $dt => $label) { ?>
                                <option value="<?= e($dt); ?>"><?= e($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6 tw-mb-2">
                            <label class="control-label"><?= _l('approval_filter_date_from'); ?></label>
                            <input type="date" id="approval-filter-date-from" class="form-control" />
                        </div>
                        <div class="col-md-2 col-sm-6 tw-mb-2">
                            <label class="control-label"><?= _l('approval_filter_date_to'); ?></label>
                            <input type="date" id="approval-filter-date-to" class="form-control" />
                        </div>
                        <div class="col-md-3 col-sm-6 tw-mb-2">
                            <label class="control-label"><?= _l('approval_filter_submitter'); ?></label>
                            <input type="text" id="approval-filter-submitter" class="form-control" placeholder="<?= _l('search'); ?>..." autocomplete="off" />
                        </div>
                        <div class="col-md-2 col-sm-12 tw-mb-2">
                            <label class="control-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-default btn-block" id="approval-filter-reset"><?= _l('approval_filter_reset'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (empty($my_pending)) { ?>
                        <p class="text-muted text-center tw-py-8"><?= _l('approval_no_requests'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="approval-queue-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th><?= _l('approval_col_reference'); ?></th>
                                        <th><?= _l('approval_col_document'); ?></th>
                                        <th><?= _l('approval_col_submitted_by'); ?></th>
                                        <th><?= _l('approval_col_value'); ?></th>
                                        <th><?= _l('approval_col_submitted'); ?></th>
                                        <th><?= _l('approval_col_sla'); ?></th>
                                        <th><?= _l('approval_col_stage'); ?></th>
                                        <th class="text-right"><?= _l('approval_col_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rowNum = 0;
                                    foreach ($my_pending as $r) {
                                        $rowNum++;
                                        $submitter = get_staff_full_name($r['submitted_by']);
                                        $submitterKey = strtolower($submitter);
                                        $docLabel = isset($doc_types[$r['document_type']]) ? $doc_types[$r['document_type']] : ucwords(str_replace('_', ' ', $r['document_type']));
                                        $docLine = $docLabel . ' ' . ($r['document_ref'] ? $r['document_ref'] : '#' . (int) $r['document_id']);
                                        $val = (float) $r['document_value'];
                                        $valFmt = 'MWK ' . number_format($val, 2, '.', ',');
                                        $valClass = ($val > 1000000) ? ' approval-value-strong' : '';
                                        $submittedTs = strtotime($r['submitted_at']);
                                        $submittedDay = date('Y-m-d', $submittedTs);
                                        $slaClass = 'text-muted';
                                        $slaText = '—';
                                        $isOverdue = false;
                                        if (!empty($r['sla_deadline'])) {
                                            $slaTs = strtotime($r['sla_deadline']);
                                            $slaText = _dt($r['sla_deadline']);
                                            if ($slaTs < time()) {
                                                $slaClass = 'text-danger';
                                                $isOverdue = true;
                                            } elseif (($slaTs - time()) / 3600 <= 4) {
                                                $slaClass = 'text-warning';
                                            } else {
                                                $slaClass = 'text-success';
                                            }
                                        }
                                        ?>
                                    <tr class="approval-queue-row" id="approval-row-<?= (int) $r['id']; ?>"
                                        data-id="<?= (int) $r['id']; ?>"
                                        data-doc-type="<?= e($r['document_type']); ?>"
                                        data-submitted-day="<?= e($submittedDay); ?>"
                                        data-submitter="<?= e($submitterKey); ?>"
                                        data-overdue="<?= $isOverdue ? '1' : '0'; ?>"
                                        data-ref="<?= e($r['request_ref']); ?>"
                                        data-doc-ref="<?= e($r['document_ref'] ?: '#' . $r['document_id']); ?>"
                                        data-doc-label="<?= e($docLabel); ?>"
                                        data-value="<?= e($valFmt); ?>"
                                        data-submitter-display="<?= e($submitter); ?>">
                                        <td><?= $rowNum; ?></td>
                                        <td>
                                            <a href="<?= admin_url('approvals/view/' . (int) $r['id']); ?>"><strong><?= e($r['request_ref']); ?></strong></a>
                                        </td>
                                        <td><?= e($docLine); ?></td>
                                        <td><?= e($submitter); ?></td>
                                        <td><span class="<?= trim($valClass); ?>"><?= e($valFmt); ?></span></td>
                                        <td>
                                            <span title="<?= e(_dt($r['submitted_at'])); ?>"><?= e(time_ago($r['submitted_at'])); ?></span>
                                        </td>
                                        <td><span class="<?= e($slaClass); ?>" title="<?= e($r['sla_deadline'] ? _dt($r['sla_deadline']) : ''); ?>"><?= e($slaText); ?></span></td>
                                        <td><?= (int) $r['approval_stage']; ?> / <?= (int) $r['total_stages']; ?></td>
                                        <td class="text-right nowrap">
                                            <button type="button" class="btn btn-success btn-sm approval-open-modal" data-action="approve" data-id="<?= (int) $r['id']; ?>"><i class="fa fa-check"></i> <?= _l('approval_btn_approve'); ?></button>
                                            <button type="button" class="btn btn-danger btn-sm approval-open-modal" data-action="reject" data-id="<?= (int) $r['id']; ?>"><i class="fa fa-times"></i> <?= _l('approval_btn_reject'); ?></button>
                                            <button type="button" class="btn btn-primary btn-sm approval-open-modal" data-action="revision" data-id="<?= (int) $r['id']; ?>"><i class="fa fa-undo"></i> <?= _l('approval_btn_revision'); ?></button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- SECTION 3: Recent decisions -->
                <div class="approval-section-title"><?= _l('approval_section_recent'); ?></div>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (empty($recent_decisions)) { ?>
                        <p class="text-muted"><?= _l('approval_no_actions'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th><?= _l('approval_col_submitted'); ?></th>
                                        <th><?= _l('approval_col_document'); ?></th>
                                        <th><?= _l('approval_decision'); ?></th>
                                        <th><?= _l('approval_comments_snippet'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_decisions as $d) {
                                        $dlabel = isset($doc_types[$d['document_type']]) ? $doc_types[$d['document_type']] : ucwords(str_replace('_', ' ', $d['document_type']));
                                        $docShow = $dlabel . ' ' . ($d['document_ref'] ? $d['document_ref'] : '#' . (int) $d['document_id']);
                                        $badge = 'default';
                                        if ($d['action'] === 'approved') {
                                            $badge = 'success';
                                        } elseif ($d['action'] === 'rejected') {
                                            $badge = 'danger';
                                        } elseif ($d['action'] === 'revision_requested') {
                                            $badge = 'warning';
                                        }
                                        $snippet = $d['comments'] ? character_limiter(strip_tags($d['comments']), 80) : '—';
                                        ?>
                                    <tr>
                                        <td class="nowrap"><?= e(_dt($d['acted_at'])); ?></td>
                                        <td><?= e($docShow); ?></td>
                                        <td><span class="label label-<?= e($badge); ?>"><?= e(ucwords(str_replace('_', ' ', $d['action']))); ?></span></td>
                                        <td><?= e($snippet); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="approvalActionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header approval-modal-header-approve" id="approval-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="approval-modal-title"><?= _l('approval_modal_title_approve'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approval-modal-request-id" value="" />
                <input type="hidden" id="approval-modal-action-type" value="" />
                <div class="form-group">
                    <label><?= _l('approval_modal_document_summary'); ?></label>
                    <div class="well well-sm" id="approval-modal-summary" style="margin-bottom:0;"></div>
                </div>
                <div class="form-group">
                    <label for="approval-modal-comments"><?= _l('approval_modal_comments'); ?> <span class="text-danger" id="approval-modal-comments-required" style="display:none;">*</span></label>
                    <textarea id="approval-modal-comments" class="form-control" rows="4" placeholder="<?= _l('approval_modal_comments_placeholder'); ?>"></textarea>
                    <p class="text-muted small" id="approval-modal-min-hint" style="display:none;"><?= _l('approval_error_comments_min_length'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('cancel'); ?></button>
                <button type="button" class="btn btn-lg" id="approval-modal-submit"><?= _l('approval_modal_confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';

    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';

    function updateCsrf(hash) {
        if (hash) { csrfHash = hash; }
    }

    function setNavBadgeCount(n) {
        var $li = $('.menu-item-ipms-approvals');
        var $badge = $li.find('.badge');
        n = parseInt(n, 10) || 0;
        if (n > 0) {
            if ($badge.length) {
                $badge.text(n).show();
            } else {
                $li.find('a').first().append(' <span class="badge pull-right bg-warning">' + n + '</span>');
            }
        } else {
            $badge.hide().text('0');
        }
    }

    function approvalApplyRowFilter() {
        var dt = ($('#approval-filter-doc-type').val() || '').toLowerCase();
        var df = $('#approval-filter-date-from').val();
        var dtTo = $('#approval-filter-date-to').val();
        var sub = ($('#approval-filter-submitter').val() || '').toLowerCase().trim();

        $('.approval-queue-row').each(function() {
            var $r = $(this);
            var ok = true;
            if (dt && $r.data('doc-type') !== dt) ok = false;
            if (ok && df) {
                var day = $r.data('submitted-day');
                if (day < df) ok = false;
            }
            if (ok && dtTo) {
                var day2 = $r.data('submitted-day');
                if (day2 > dtTo) ok = false;
            }
            if (ok && sub) {
                var sk = ($r.data('submitter') || '') + '';
                if (sk.indexOf(sub) === -1) ok = false;
            }
            $r.toggle(ok);
        });
    }

    $('#approval-filter-doc-type, #approval-filter-date-from, #approval-filter-date-to').on('change', approvalApplyRowFilter);
    $('#approval-filter-submitter').on('keyup', function() {
        clearTimeout(window._approvalSubT);
        window._approvalSubT = setTimeout(approvalApplyRowFilter, 200);
    });
    $('#approval-filter-reset').on('click', function() {
        $('#approval-filter-doc-type').val('');
        $('#approval-filter-date-from').val('');
        $('#approval-filter-date-to').val('');
        $('#approval-filter-submitter').val('');
        $('.approval-queue-row').show();
    });

    var $modal = $('#approvalActionModal');
    var $comments = $('#approval-modal-comments');
    var $submit = $('#approval-modal-submit');
    var $header = $('#approval-modal-header');
    var $title = $('#approval-modal-title');

    $('.approval-open-modal').on('click', function() {
        var action = $(this).data('action');
        var id = $(this).data('id');
        var $row = $('#approval-row-' + id);
        var summary = '<strong>' + $row.data('doc-label') + '</strong> ' + $('<div/>').text($row.data('doc-ref')).html();
        summary += '<br/>' + '<?= _l('approval_col_value'); ?>: <strong>' + $('<div/>').text($row.data('value')).html() + '</strong>';
        summary += '<br/>' + '<?= _l('approval_col_submitted_by'); ?>: ' + $('<div/>').text($row.data('submitter-display')).html();

        $('#approval-modal-request-id').val(id);
        $('#approval-modal-action-type').val(action);
        $('#approval-modal-summary').html(summary);
        $comments.val('');

        $header.removeClass('approval-modal-header-approve approval-modal-header-reject approval-modal-header-revision');
        $submit.removeClass('btn-success btn-danger btn-primary');

        if (action === 'approve') {
            $header.addClass('approval-modal-header-approve');
            $title.text('<?= e(_l('approval_modal_title_approve')); ?>');
            $submit.addClass('btn-success').text('<?= e(_l('approval_btn_approve')); ?>');
            $('#approval-modal-comments-required').hide();
            $('#approval-modal-min-hint').hide();
        } else if (action === 'reject') {
            $header.addClass('approval-modal-header-reject');
            $title.text('<?= e(_l('approval_modal_title_reject')); ?>');
            $submit.addClass('btn-danger').text('<?= e(_l('approval_btn_reject')); ?>');
            $('#approval-modal-comments-required').show();
            $('#approval-modal-min-hint').show();
        } else {
            $header.addClass('approval-modal-header-revision');
            $title.text('<?= e(_l('approval_modal_title_revision')); ?>');
            $submit.addClass('btn-primary').text('<?= e(_l('approval_btn_revision')); ?>');
            $('#approval-modal-comments-required').show();
            $('#approval-modal-min-hint').show();
        }
        $modal.modal('show');
    });

    $submit.on('click', function() {
        var id = parseInt($('#approval-modal-request-id').val(), 10);
        var action = $('#approval-modal-action-type').val();
        var text = ($comments.val() || '').trim();
        if (action === 'reject' || action === 'revision') {
            if (text.length < 10) {
                if (typeof alert_float === 'function') {
                    alert_float('warning', '<?= e(_l('approval_error_comments_min_length')); ?>');
                }
                return;
            }
        }
        var url = admin_url + 'approvals/';
        if (action === 'approve') url += 'approve';
        else if (action === 'reject') url += 'reject';
        else url += 'request_revision';

        var payload = {
            approval_request_id: id,
            comments: $comments.val(),
            from_dashboard: 1
        };
        payload[csrfName] = csrfHash;

        $submit.prop('disabled', true);
        $.post(url, payload, null, 'json').done(function(res) {
            if (res.csrf_token && res.csrf_token_name) {
                updateCsrf(res[res.csrf_token_name] || res.csrf_token);
            }
            if (res.success) {
                if (typeof alert_float === 'function') {
                    alert_float('success', res.message);
                }
                if (typeof res.pending_badge_count !== 'undefined') {
                    setNavBadgeCount(res.pending_badge_count);
                }
                var $row = $('#approval-row-' + id);
                var wasOverdue = $row.data('overdue') === 1 || $row.data('overdue') === '1';
                $row.fadeOut(300, function() {
                    $(this).remove();
                    var pm = parseInt($('.js-stat-pending-mine').text(), 10) || 0;
                    $('.js-stat-pending-mine').text(Math.max(0, pm - 1));
                    if (wasOverdue) {
                        var o = parseInt($('.js-stat-overdue-sla').text(), 10) || 0;
                        var no = Math.max(0, o - 1);
                        $('.js-stat-overdue-sla').text(no);
                        if (no === 0) {
                            $('.approval-stat-overdue').removeClass('approval-stat-overdue-breach');
                        }
                    }
                    if (action === 'approve') {
                        var a = parseInt($('.js-stat-approved-today').text(), 10) || 0;
                        $('.js-stat-approved-today').text(a + 1);
                    }
                    if ($('.approval-queue-row').length === 0) {
                        $('#approval-queue-table').closest('.table-responsive').replaceWith('<p class="text-muted text-center tw-py-8" id="approval-empty-msg"><?= e(_l('approval_no_requests')); ?></p>');
                    }
                });
                $modal.modal('hide');
                if (res.next_url) {
                    window.location.href = res.next_url;
                }
            } else {
                if (typeof alert_float === 'function') {
                    alert_float('danger', res.message);
                }
            }
        }).fail(function() {
            if (typeof alert_float === 'function') {
                alert_float('danger', 'Request failed');
            }
        }).always(function() {
            $submit.prop('disabled', false);
        });
    });
})(jQuery);
</script>
<?php init_tail(); ?>

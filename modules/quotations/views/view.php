<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$quotation = isset($quotation) ? $quotation : null;
if (!$quotation) {
    show_404();
}

$client         = isset($client) ? $client : null;
$estimate       = isset($estimate) ? $estimate : null;
$linesByTab     = isset($quotation->lines_by_tab) && is_array($quotation->lines_by_tab) ? $quotation->lines_by_tab : [];
$fullTotals     = isset($full_totals) && is_array($full_totals) ? $full_totals : [];
$approvalHist   = isset($approval_history) && is_array($approval_history) ? $approval_history : [];
$versionHistory = isset($version_history) && is_array($version_history) ? $version_history : [];
$tabLabels      = qt_tab_labels();

$clientName = $client && isset($client->company) ? $client->company : ($client && isset($client->firstname) ? trim($client->firstname . ' ' . ($client->lastname ?? '')) : '—');
$preparedBy = get_staff_full_name((int) $quotation->created_by);
$quoteDate  = $estimate && !empty($estimate->date) ? $estimate->date : date('Y-m-d', strtotime($quotation->created_at));
$validUntil = $estimate && !empty($estimate->expirydate) ? $estimate->expirydate : date('Y-m-d', strtotime($quotation->created_at . ' +' . (int) $quotation->validity_days . ' days'));

$subtotalLines = (float) ($fullTotals['subtotal_lines'] ?? 0);
$subAfterCont  = (float) ($fullTotals['sub_after_cont'] ?? 0);
$contingencyAmt = max(0, $subAfterCont - $subtotalLines);
$discApplied   = (float) ($fullTotals['discount_applied'] ?? 0);
$vatAmt        = (float) ($fullTotals['vat'] ?? 0);
$grandTotal    = (float) ($fullTotals['grand_total'] ?? 0);
$sellExclVat   = (float) ($fullTotals['subtotal'] ?? 0);
$totalCost     = (float) ($quotation->total_cost ?? 0);
$marginAmt     = $sellExclVat - $totalCost;
$marginPct     = $sellExclVat > 0 ? ($marginAmt / $sellExclVat) * 100 : 0;

$serviceTypes = [];
if (!empty($quotation->service_type)) {
    $serviceTypes = is_array($quotation->service_type) ? $quotation->service_type : explode(',', (string) $quotation->service_type);
}
$serviceTypes = array_filter(array_map('trim', $serviceTypes));

$status        = (string) $quotation->status;
$canViewMargin = !empty($can_view_margin);
$canConvert    = !empty($can_convert_to_job);
$isCreator     = !empty($is_creator);
$canSubmitApp  = !empty($can_submit_for_approval);
$canCreateRev  = !empty($can_create_revision);
$pendingName     = isset($pending_approver_name) ? $pending_approver_name : '';
$rejectionReason = isset($rejection_reason) ? $rejection_reason : '';
$nextRevVer      = isset($next_revision_version) ? (int) $next_revision_version : ((int) $quotation->version + 1);
$qid             = (int) $quotation->id;

$actionLabels = [
    'submitted'          => 'Submitted',
    'approved'           => 'Approved',
    'rejected'           => 'Rejected',
    'revision_requested' => 'Revision requested',
    'escalated'          => 'Escalated',
    'cancelled'          => 'Cancelled',
    'reminder_sent'      => 'Reminder sent',
];

$viewConfig = [
    'quotationId'   => $qid,
    'quotationRef'  => $quotation->quotation_ref,
    'clientEmail'   => isset($client_primary_email) ? $client_primary_email : '',
    'companyName'   => get_option('companyname'),
    'urlSendEmail'  => $status === 'approved' ? admin_url('quotations/send_email/' . $qid) : '',
];

init_head();
?>
<style>
.qt-view-timeline { list-style: none; padding-left: 0; margin: 0; position: relative; }
.qt-view-timeline:before { content: ''; position: absolute; left: 8px; top: 4px; bottom: 4px; width: 2px; background: #e0e0e0; }
.qt-view-timeline li { position: relative; padding-left: 28px; margin-bottom: 14px; }
.qt-view-timeline li:before { content: ''; position: absolute; left: 4px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #337ab7; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd; }
.qt-service-tag { display: inline-block; margin: 0 4px 4px 0; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel panel-primary mbot0">
                            <div class="panel-heading">
                                <h4 class="no-margin">
                                    Quotation <?php echo e($quotation->quotation_ref); ?> v<?php echo (int) $quotation->version; ?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p class="text-muted mbot15">
                                    <?php echo e($clientName); ?> &nbsp;|&nbsp;
                                    Created by <?php echo e($preparedBy); ?> &nbsp;|&nbsp;
                                    Date <?php echo e(_d($quoteDate)); ?> &nbsp;|&nbsp;
                                    Valid until <?php echo e(_d($validUntil)); ?>
                                </p>

                                <?php
                                $collapseIdx = 0;
                                foreach ($tabLabels as $tabKey => $tabTitle) {
                                    $rows = $linesByTab[$tabKey] ?? [];
                                    if (count($rows) < 1) {
                                        continue;
                                    }
                                    ++$collapseIdx;
                                    $cid = 'qt-view-tab-' . $tabKey;
                                    ?>
                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab">
                                            <h5 class="no-margin">
                                                <a data-toggle="collapse" href="#<?php echo e($cid); ?>" class="block">
                                                    <?php echo e($tabTitle); ?> (<?php echo count($rows); ?> items)
                                                </a>
                                            </h5>
                                        </div>
                                        <div id="<?php echo e($cid); ?>" class="panel-collapse collapse <?php echo $collapseIdx === 1 ? 'in' : ''; ?>">
                                            <div class="table-responsive">
                                                <table class="table table-condensed table-striped mbot0">
                                                    <thead>
                                                        <tr>
                                                            <th>Description</th>
                                                            <th class="text-right">Qty</th>
                                                            <th class="text-right">Sell Price</th>
                                                            <th class="text-right">Line Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $secSub = 0.0;
                                                        foreach ($rows as $ln) {
                                                            $desc = $ln['description'] ?? '—';
                                                            $qty  = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                                                            $sp   = isset($ln['sell_price']) ? (float) $ln['sell_price'] : 0;
                                                            $lt   = isset($ln['line_total_sell']) ? (float) $ln['line_total_sell'] : ($qty * $sp);
                                                            $secSub += $lt;
                                                            ?>
                                                            <tr>
                                                                <td><?php echo e($desc); ?></td>
                                                                <td class="text-right"><?php echo e(number_format($qty, 3, '.', ',')); ?></td>
                                                                <td class="text-right"><?php echo e(qt_format_mwk($sp)); ?></td>
                                                                <td class="text-right bold"><?php echo e(qt_format_mwk($lt)); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="panel-footer text-right bold">
                                                Tab subtotal: <?php echo e(qt_format_mwk($secSub)); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                                <hr>
                                <h5 class="bold mtop20">Totals</h5>
                                <table class="table table-condensed mbot0" style="max-width:420px;">
                                    <tbody>
                                        <tr><td>Subtotal</td><td class="text-right"><?php echo e(qt_format_mwk($subtotalLines)); ?></td></tr>
                                        <tr>
                                            <td>Contingency (<?php echo e(number_format((float) ($quotation->contingency_percent ?? 0), 2)); ?>%)</td>
                                            <td class="text-right"><?php echo e(qt_format_mwk($contingencyAmt)); ?></td>
                                        </tr>
                                        <tr><td>Discount</td><td class="text-right">-<?php echo e(qt_format_mwk($discApplied)); ?></td></tr>
                                        <tr><td>VAT (<?php echo e((string) qt_get_vat_rate()); ?>%)</td><td class="text-right"><?php echo e(qt_format_mwk($vatAmt)); ?></td></tr>
                                        <tr class="active bold"><td>Grand Total</td><td class="text-right"><?php echo e(qt_format_mwk($grandTotal)); ?></td></tr>
                                    </tbody>
                                </table>

                                <?php if ($canViewMargin) { ?>
                                    <div class="well well-sm mtop20">
                                        <h5 class="bold mtop0 text-muted">Gross margin <small>(management)</small></h5>
                                        <table class="table table-condensed mbot0" style="max-width:420px;">
                                            <tbody>
                                                <tr><td>Total Cost</td><td class="text-right"><?php echo e(qt_format_mwk($totalCost)); ?></td></tr>
                                                <tr><td>Total Sell <small>(excl. VAT)</small></td><td class="text-right"><?php echo e(qt_format_mwk($sellExclVat)); ?></td></tr>
                                                <tr><td>Gross Margin</td><td class="text-right bold"><?php echo e(qt_format_mwk($marginAmt)); ?></td></tr>
                                                <tr><td>Margin %</td><td class="text-right"><?php echo e(number_format($marginPct, 1)); ?>%</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="bold mtop0">Quotation info</h5>
                        <p class="mbot5"><span class="text-muted">Reference</span><br><strong><?php echo e($quotation->quotation_ref); ?></strong></p>
                        <p class="mbot5"><span class="text-muted">Version</span><br>v<?php echo (int) $quotation->version; ?></p>
                        <p class="mbot5"><span class="text-muted">Status</span><br><?php echo qt_get_status_label($status); ?></p>
                        <p class="mbot5">
                            <span class="text-muted">Grand total</span><br>
                            <span class="text-success" style="font-size:22px;font-weight:700;"><?php echo e(qt_format_mwk($grandTotal)); ?></span>
                        </p>
                        <hr class="hr-10">
                        <p class="mbot5 small"><span class="text-muted">Client</span><br><?php echo e($clientName); ?></p>
                        <p class="mbot5 small"><span class="text-muted">Prepared by</span><br><?php echo e($preparedBy); ?></p>
                        <p class="mbot5 small"><span class="text-muted">Date</span><br><?php echo e(_d($quoteDate)); ?></p>
                        <p class="mbot5 small"><span class="text-muted">Valid until</span><br><?php echo e(_d($validUntil)); ?></p>
                        <?php if ($serviceTypes !== []) { ?>
                            <p class="mbot0 small">
                                <span class="text-muted">Service types</span><br>
                                <?php foreach ($serviceTypes as $st) {
                                    $lbl = $tabLabels[$st] ?? ucfirst($st);
                                    echo '<span class="label label-info qt-service-tag">' . e($lbl) . '</span>';
                                } ?>
                            </p>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="bold mtop0">Approval timeline</h5>
                        <?php if ($approvalHist === []) { ?>
                            <p class="text-muted small mbot0">No approval activity recorded.</p>
                        <?php } else { ?>
                            <ul class="qt-view-timeline">
                                <?php foreach ($approvalHist as $act) {
                                    $al = $actionLabels[$act['action'] ?? ''] ?? ucfirst((string) ($act['action'] ?? ''));
                                    $an = $act['actor_name'] ?? get_staff_full_name((int) ($act['actor_id'] ?? 0));
                                    $at = $act['acted_at'] ?? '';
                                    $cm = trim((string) ($act['comments'] ?? ''));
                                    ?>
                                    <li>
                                        <strong><?php echo e($al); ?></strong>
                                        <span class="text-muted small"> — <?php echo e($an); ?></span><br>
                                        <span class="text-muted small"><?php echo e(_dt($at)); ?></span>
                                        <?php if ($cm !== '') { ?>
                                            <div class="small mtop5"><?php echo nl2br(e($cm)); ?></div>
                                        <?php } ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="bold mtop0">Actions</h5>
                        <?php if ($status === 'draft') { ?>
                            <a href="<?php echo admin_url('quotations/edit/' . $qid); ?>" class="btn btn-default btn-block mbot5"><i class="fa fa-pencil"></i> Edit quotation</a>
                            <?php if ($canSubmitApp) { ?>
                                <?php echo form_open(admin_url('quotations/submit_for_approval/' . $qid)); ?>
                                <?php echo form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash()); ?>
                                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check"></i> Submit for approval</button>
                                <?php echo form_close(); ?>
                            <?php } ?>
                        <?php } elseif ($status === 'submitted') { ?>
                            <div class="alert alert-info small">
                                Awaiting approval<?php echo $pendingName !== '' ? ' from <strong>' . e($pendingName) . '</strong>' : ''; ?>.
                            </div>
                            <?php if ($isCreator) { ?>
                                <p class="text-muted small">To change line items while pending, cancel the approval request in the Approvals module or contact an administrator.</p>
                            <?php } ?>
                            <?php if (!empty($quotation->approval_request_id)) { ?>
                                <a href="<?php echo admin_url('approvals/view/' . (int) $quotation->approval_request_id); ?>" class="btn btn-default btn-block mbot5"><i class="fa fa-external-link"></i> Open approval request</a>
                            <?php } ?>
                        <?php } elseif ($status === 'approved') { ?>
                            <a href="<?php echo admin_url('quotations/pdf/' . $qid); ?>" class="btn btn-default btn-block mbot5" target="_blank"><i class="fa fa-file-pdf"></i> Download PDF</a>
                            <button type="button" class="btn btn-info btn-block mbot5" id="qt-view-btn-send-client"><i class="fa fa-envelope"></i> Send to client</button>
                            <?php if ($canConvert) { ?>
                                <a href="<?php echo admin_url('quotations/convert_to_job/' . $qid); ?>" class="btn btn-primary btn-block"><i class="fa fa-briefcase"></i> Convert to job</a>
                            <?php } ?>
                        <?php } elseif ($status === 'rejected') { ?>
                            <?php if ($rejectionReason !== '') { ?>
                                <div class="alert alert-danger small"><?php echo nl2br(e($rejectionReason)); ?></div>
                            <?php } ?>
                            <?php if ($canCreateRev) { ?>
                                <button type="button" class="btn btn-warning btn-block mbot5" data-toggle="modal" data-target="#qt-modal-revision">
                                    <i class="fa fa-code-fork"></i> Create revision v<?php echo (int) $nextRevVer; ?>
                                </button>
                            <?php } ?>
                        <?php } elseif ($status === 'converted') { ?>
                            <p class="text-muted small mbot0">This quotation has been converted.</p>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($versionHistory !== []) { ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="bold mtop0">Version history</h5>
                            <ul class="list-unstyled mbot0 small">
                                <?php foreach ($versionHistory as $vh) {
                                    $vid = (int) ($vh['id'] ?? 0);
                                    $vst = (string) ($vh['status'] ?? '');
                                    $isCurrent = $vid === $qid;
                                    ?>
                                    <li class="mbot8">
                                        <a href="<?php echo admin_url('quotations/view/' . $vid); ?>">v<?php echo (int) ($vh['version'] ?? 1); ?></a>
                                        <?php echo qt_get_status_label($vst); ?>
                                        <span class="text-muted"><?php echo e(_dt($vh['created_at'] ?? '')); ?></span>
                                        <?php if ($isCurrent) { ?><span class="label label-success">current</span><?php } ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php if ($status === 'rejected' && $canCreateRev) { ?>
<div class="modal fade" id="qt-modal-revision" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open(admin_url('quotations/create_revision/' . $qid)); ?>
            <?php echo form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash()); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create revision v<?php echo (int) $nextRevVer; ?></h4>
            </div>
            <div class="modal-body">
                <p class="text-muted small">A new draft copy will be created from this quotation.</p>
                <div class="form-group">
                    <label>Revision notes</label>
                    <textarea name="revision_notes" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="submit" class="btn btn-warning">Create revision</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

<?php if ($status === 'approved') { ?>
<div class="modal fade" id="qt-modal-send-client" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Send quotation to client</h4>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>To</label><input type="email" class="form-control" id="qt-view-email-to"></div>
                <div class="form-group"><label>CC</label><input type="text" class="form-control" id="qt-view-email-cc" placeholder="optional"></div>
                <div class="form-group"><label>Subject</label><input type="text" class="form-control" id="qt-view-email-subject"></div>
                <div class="form-group"><label>Message</label><textarea class="form-control" rows="6" id="qt-view-email-body"></textarea></div>
                <div class="checkbox"><label><input type="checkbox" id="qt-view-email-attach" checked> Attach PDF</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-info" id="qt-view-email-send-btn"><span id="qt-view-email-spin" class="hide"><i class="fa fa-spinner fa-spin"></i></span> Send</button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php init_tail(); ?>
<?php if ($status === 'approved') { ?>
<script>
(function () {
  window.qtViewConfig = <?php echo json_encode($viewConfig); ?>;
  function csrfPayload() {
    var o = {};
    if (typeof csrfData !== 'undefined') {
      o[csrfData.token_name] = csrfData.hash;
    }
    return o;
  }
  $(function () {
    var cfg = window.qtViewConfig || {};
    $('#qt-view-btn-send-client').on('click', function () {
      $('#qt-view-email-to').val(cfg.clientEmail || '');
      $('#qt-view-email-subject').val('Quotation ' + cfg.quotationRef + ' from MW');
      $('#qt-view-email-body').val(
        'Dear Customer,\n\nPlease find our quotation ' +
          cfg.quotationRef +
          ' attached.\n\nKind regards,\n' +
          (cfg.companyName || '')
      );
      $('#qt-modal-send-client').modal('show');
    });
    $('#qt-view-email-send-btn').on('click', function () {
      if (!cfg.urlSendEmail) return;
      var $btn = $(this);
      $btn.prop('disabled', true);
      $('#qt-view-email-spin').removeClass('hide');
      var data = {
        recipient_email: $('#qt-view-email-to').val(),
        cc: $('#qt-view-email-cc').val(),
        subject: $('#qt-view-email-subject').val(),
        message: $('#qt-view-email-body').val(),
        attach_pdf: $('#qt-view-email-attach').is(':checked') ? 1 : 0
      };
      $.extend(data, csrfPayload());
      $.ajax({
        url: cfg.urlSendEmail,
        type: 'POST',
        data: data,
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .done(function (r) {
          if (r && r.success) {
            if (typeof alert_float === 'function') alert_float('success', r.message || 'Sent');
            $('#qt-modal-send-client').modal('hide');
          } else {
            if (typeof alert_float === 'function') alert_float('danger', (r && r.message) || 'Failed');
          }
        })
        .fail(function () {
          if (typeof alert_float === 'function') alert_float('danger', 'Request failed');
        })
        .always(function () {
          $btn.prop('disabled', false);
          $('#qt-view-email-spin').addClass('hide');
        });
    });
  });
})();
</script>
<?php } ?>

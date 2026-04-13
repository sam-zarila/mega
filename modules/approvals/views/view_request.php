<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$doc_type_labels = [
    'quotation'             => _l('estimates'),
    'credit_note'           => _l('credit_note'),
    'journal_entry'         => _l('approval_doc_journal_entry'),
    'payment'               => _l('approval_doc_payment'),
    'purchase_requisition'  => _l('approval_doc_purchase_requisition'),
];

$header_classes = [
    'quotation'             => 'approval-doc-head--quotation',
    'credit_note'           => 'approval-doc-head--credit',
    'journal_entry'         => 'approval-doc-head--journal',
    'payment'               => 'approval-doc-head--payment',
    'purchase_requisition'  => 'approval-doc-head--pr',
];

$fmt_mwk = static function ($n) {
    return 'MWK ' . number_format((float) $n, 2, '.', ',');
};

$timeline_dot = static function ($action) {
    switch ($action) {
        case 'approved':
            return 'approval-tl-dot--approved';
        case 'rejected':
            return 'approval-tl-dot--rejected';
        case 'revision_requested':
            return 'approval-tl-dot--revision';
        case 'escalated':
        case 'reminder_sent':
            return 'approval-tl-dot--forwarded';
        case 'submitted':
        default:
            return 'approval-tl-dot--submitted';
    }
};

$timeline_action_label = static function ($action) {
    static $map = [
        'submitted'          => 'approval_timeline_action_submitted',
        'approved'           => 'approval_timeline_action_approved',
        'rejected'           => 'approval_timeline_action_rejected',
        'revision_requested' => 'approval_timeline_action_revision',
        'escalated'          => 'approval_timeline_action_escalated',
        'cancelled'          => 'approval_timeline_action_cancelled',
        'reminder_sent'      => 'approval_timeline_action_reminder',
    ];
    if (isset($map[$action])) {
        return _l($map[$action]);
    }

    return ucwords(str_replace('_', ' ', (string) $action));
};

$item_line_total = static function (array $it) {
    if (isset($it['amount']) && $it['amount'] !== '') {
        return (float) $it['amount'];
    }
    $q = isset($it['qty']) ? (float) $it['qty'] : 0.0;
    $r = isset($it['rate']) ? (float) $it['rate'] : 0.0;

    return $q * $r;
};

$pick = static function (array $row, array $keys, $default = '') {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }

    return $default;
};

$staff_id    = (int) get_staff_user_id();
$is_pending  = in_array($request->status, ['pending', 'escalated'], true);
$can_act     = $is_pending && (int) $request->current_approver_id === $staff_id;
$doc_label   = $doc_type_labels[$request->document_type] ?? ucwords(str_replace('_', ' ', $request->document_type));
$doc_ref     = $request->document_ref ?: ('#' . (int) $request->document_id);
$submitter   = get_staff_full_name($request->submitted_by);
$sub_role    = get_staff_role($request->submitted_by);
$approver_nm = (int) $request->current_approver_id > 0 ? get_staff_full_name($request->current_approver_id) : '—';
$approver_role = $request->current_approver_role ?: get_staff_role($request->current_approver_id);

$doc_value_fmt = $fmt_mwk($request->document_value);

$quotation_items = [];
if ($request->document_type === 'quotation' && $document && !empty($document->items)) {
    $quotation_items = $document->items;
} elseif ($request->document_type === 'quotation' && !empty($document_items)) {
    $quotation_items = $document_items;
}

$credit_items = [];
if ($request->document_type === 'credit_note' && $document && !empty($document->items)) {
    $credit_items = $document->items;
} elseif ($request->document_type === 'credit_note' && !empty($document_items)) {
    $credit_items = $document_items;
}

$journal_debit_sum  = 0.0;
$journal_credit_sum = 0.0;
foreach ($journal_lines as $jl) {
    $d = (float) $pick($jl, ['debit', 'dr', 'amount_debit', 'debit_amount'], 0);
    $c = (float) $pick($jl, ['credit', 'cr', 'amount_credit', 'credit_amount'], 0);
    $journal_debit_sum += $d;
    $journal_credit_sum += $c;
}
$journal_balanced = count($journal_lines) > 0 && abs($journal_debit_sum - $journal_credit_sum) < 0.005;
?>
<style>
.approval-doc-panel { border: 1px solid #e4e8f0; border-radius: 4px; overflow: hidden; background: #fff; margin-bottom: 20px; }
.approval-doc-head { padding: 14px 18px; color: #fff; font-size: 16px; font-weight: 600; }
.approval-doc-head--quotation { background: #337ab7; }
.approval-doc-head--credit { background: #6f42c1; }
.approval-doc-head--journal { background: #e67e22; }
.approval-doc-head--payment { background: #17a2b8; }
.approval-doc-head--pr { background: #795548; }
.approval-doc-body { padding: 18px; }
.approval-view-h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
.approval-view-sub { color: #6b7280; margin-bottom: 16px; }
.approval-card { border: 1px solid #e4e8f0; border-radius: 4px; background: #fff; margin-bottom: 20px; }
.approval-card-h { padding: 12px 16px; border-bottom: 1px solid #eef1f6; font-weight: 600; font-size: 14px; background: #fafbfc; }
.approval-card-b { padding: 16px; }
.approval-val-lg { font-size: 26px; font-weight: 700; line-height: 1.2; }
@keyframes approval-tl-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.55); }
    50% { box-shadow: 0 0 0 8px rgba(52, 152, 219, 0); }
}
.approval-tl { list-style: none; margin: 0; padding: 0; position: relative; }
.approval-tl > li { position: relative; padding-left: 28px; padding-bottom: 18px; }
.approval-tl > li:last-child { padding-bottom: 0; }
.approval-tl-line { position: absolute; left: 7px; top: 14px; bottom: -4px; width: 2px; background: #e4e8f0; }
.approval-tl > li:last-child .approval-tl-line { display: none; }
.approval-tl-dot {
    position: absolute; left: 0; top: 4px; width: 16px; height: 16px; border-radius: 50%;
    border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;
}
.approval-tl-dot--submitted { background: #95a5a6; }
.approval-tl-dot--approved { background: #84c529; }
.approval-tl-dot--rejected { background: #d9534f; }
.approval-tl-dot--revision { background: #f0ad4e; }
.approval-tl-dot--forwarded { background: #3498db; }
.approval-tl-dot--pulse {
    animation: approval-tl-pulse 1.8s ease-out infinite;
    border-color: #3498db;
}
.approval-tl-meta { font-size: 12px; color: #6b7280; }
.approval-tl-quote { margin-top: 8px; padding: 8px 10px; background: #f8f9fa; border-left: 3px solid #cbd5e1; font-size: 13px; }
.approval-await-box { padding: 14px; background: #fcf8e3; border: 1px solid #faebcc; border-radius: 4px; color: #8a6d3b; }
.modal-header.approval-modal-header-approve { border-top: 4px solid #84c529; }
.modal-header.approval-modal-header-reject { border-top: 4px solid #d9534f; }
.modal-header.approval-modal-header-revision { border-top: 4px solid #f0ad4e; }
</style>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <p class="tw-mb-3">
                    <a href="<?= admin_url('approvals'); ?>"><i class="fa fa-arrow-left"></i> <?= e(_l('approval_dashboard')); ?></a>
                </p>

                <?php if ($request->status === 'approved') { ?>
                <div class="alert alert-success"><?= e(_l('approval_status_final_approved')); ?></div>
                <?php } elseif ($request->status === 'rejected') { ?>
                <div class="alert alert-danger"><?= e(_l('approval_status_final_rejected')); ?></div>
                <?php } elseif ($request->status === 'revision_requested') { ?>
                <div class="alert alert-warning"><?= e(_l('approval_status_final_revision')); ?></div>
                <?php } elseif ($request->status === 'cancelled') { ?>
                <div class="alert alert-default" style="background:#f5f5f5;border-color:#ddd;"><?= e(_l('approval_status_final_cancelled')); ?></div>
                <?php } ?>

                <h4 class="tw-font-bold tw-text-xl tw-mb-1"><?= e($title); ?></h4>
                <p class="text-muted tw-mb-4">
                    <?= e(_l('approval_status')); ?>: <strong><?= e($request->status); ?></strong>
                    &mdash; <?= e(_l('approval_stage')); ?> <?= (int) $request->approval_stage; ?> / <?= (int) $request->total_stages; ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="approval-doc-panel">
                    <div class="approval-doc-head <?= e($header_classes[$request->document_type] ?? 'approval-doc-head--quotation'); ?>">
                        <?= e($doc_label); ?>
                        <?php if ($request->document_ref) { ?>
                        <span class="pull-right" style="opacity:.9;font-weight:400;"><?= e($request->document_ref); ?></span>
                        <?php } ?>
                    </div>
                    <div class="approval-doc-body">

                        <?php if ($request->document_type === 'quotation' && $document) {
                            $c = $document->client ?? null;
                            $company = $c && !empty($c->company) ? $c->company : ($document->deleted_customer_name ?? '');
                            $contact_name = '';
                            if (!empty($document->contact_id) && function_exists('get_contact_full_name')) {
                                $contact_name = trim(get_contact_full_name((int) $document->contact_id));
                            }
                            ?>
                        <div class="approval-view-h1"><?= e($contact_name ?: $company); ?></div>
                        <div class="approval-view-sub"><?= e($contact_name ? $company : ''); ?></div>
                        <dl class="dl-horizontal tw-mb-3">
                            <dt><?= e(_l('estimate')); ?></dt>
                            <dd><?= e(function_exists('format_estimate_number') ? format_estimate_number($document->id) : ('#' . (int) $document->id)); ?></dd>
                            <dt><?= e(_l('estimate_data_date')); ?></dt>
                            <dd><?= e(!empty($document->date) ? _d($document->date) : '—'); ?></dd>
                            <dt><?= e(_l('approval_view_valid_until')); ?></dt>
                            <dd><?= e(!empty($document->expirydate) ? _d($document->expirydate) : '—'); ?></dd>
                            <dt><?= e(_l('approval_view_salesperson')); ?></dt>
                            <dd><?= e(!empty($document->sale_agent) ? get_staff_full_name($document->sale_agent) : '—'); ?></dd>
                        </dl>
                            <?php
                            $all_q = count($quotation_items);
                            $show_q = array_slice($quotation_items, 0, 10);
                            ?>
                        <?php if ($all_q > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('item_description')); ?></th>
                                        <th class="text-right"><?= e(_l('quantity')); ?></th>
                                        <th class="text-right"><?= e(_l('rate')); ?></th>
                                        <th class="text-right"><?= e(_l('invoice_total')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($show_q as $it) {
                                        $lt = $item_line_total($it); ?>
                                    <tr>
                                        <td><?= e($it['description'] ?? ''); ?></td>
                                        <td class="text-right"><?= e($it['qty'] ?? ''); ?></td>
                                        <td class="text-right"><?= e($fmt_mwk($it['rate'] ?? 0)); ?></td>
                                        <td class="text-right"><?= e($fmt_mwk($lt)); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                            <?php if ($all_q > 10) { ?>
                        <p><a href="#quotation-items-all" data-toggle="collapse"><?= e(sprintf(_l('approval_view_show_all_items'), $all_q)); ?></a></p>
                        <div id="quotation-items-all" class="collapse">
                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th><?= e(_l('item_description')); ?></th>
                                            <th class="text-right"><?= e(_l('quantity')); ?></th>
                                            <th class="text-right"><?= e(_l('rate')); ?></th>
                                            <th class="text-right"><?= e(_l('invoice_total')); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quotation_items as $it) {
                                            $lt = $item_line_total($it); ?>
                                        <tr>
                                            <td><?= e($it['description'] ?? ''); ?></td>
                                            <td class="text-right"><?= e($it['qty'] ?? ''); ?></td>
                                            <td class="text-right"><?= e($fmt_mwk($it['rate'] ?? 0)); ?></td>
                                            <td class="text-right"><?= e($fmt_mwk($lt)); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                            <?php } ?>
                        <?php } ?>
                            <?php
                            $sub = (float) ($document->subtotal ?? 0);
                            $tax = (float) ($document->total_tax ?? 0);
                            $tot = (float) ($document->total ?? 0);
                            if ($sub <= 0 && $tot > 0) {
                                $sub = $tot / 1.165;
                                $tax = $tot - $sub;
                            }
                            ?>
                        <table class="table table-condensed" style="max-width:360px;margin-left:auto;">
                            <tr><td><?= e(_l('approval_view_subtotal')); ?></td><td class="text-right"><?= e($fmt_mwk($sub)); ?></td></tr>
                            <tr><td><?= e(_l('approval_view_vat')); ?></td><td class="text-right"><?= e($fmt_mwk($tax)); ?></td></tr>
                            <tr><th><?= e(_l('approval_view_grand_total')); ?></th><th class="text-right"><?= e($fmt_mwk($tot)); ?></th></tr>
                        </table>
                            <?php if (!empty($document->clientnote) || !empty($document->adminnote)) { ?>
                        <div class="tw-mt-3">
                            <strong><?= e(_l('estimate_note')); ?> / <?= e(_l('note')); ?></strong>
                            <div class="well well-sm tw-mt-1" style="margin-bottom:0;">
                                <?php if (!empty($document->clientnote)) { ?><div><?= nl2br(e($document->clientnote)); ?></div><?php } ?>
                                <?php if (!empty($document->adminnote)) { ?><div class="tw-mt-2 text-muted"><?= nl2br(e($document->adminnote)); ?></div><?php } ?>
                            </div>
                        </div>
                            <?php } ?>
                        <?php } elseif ($request->document_type === 'credit_note' && $document) {
                            $c = $document->client ?? null;
                            $company = $c && !empty($c->company) ? $c->company : ($document->deleted_customer_name ?? '');
                            $inv_ref = '—';
                            $inv_date = '—';
                            if ($linked_invoice) {
                                $inv_ref = function_exists('format_invoice_number') ? format_invoice_number($linked_invoice->id) : ('#' . (int) $linked_invoice->id);
                                $inv_date = !empty($linked_invoice->date) ? _d($linked_invoice->date) : '—';
                            }
                            $reason = $pick((array) $document, ['reference', 'adminnote', 'clientnote'], '');
                            ?>
                        <dl class="dl-horizontal tw-mb-3">
                            <dt><?= e(_l('approval_view_original_invoice')); ?></dt>
                            <dd><?= e($inv_ref); ?> <span class="text-muted">(<?= e($inv_date); ?>)</span></dd>
                            <dt><?= e(_l('approval_view_client')); ?></dt>
                            <dd><strong><?= e($company); ?></strong></dd>
                            <dt><?= e(_l('approval_view_credit_reason')); ?></dt>
                            <dd><span class="label label-default"><?= e($reason !== '' ? $reason : _l('no')); ?></span></dd>
                        </dl>
                        <?php if (count($credit_items) > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('item_description')); ?></th>
                                        <th class="text-right"><?= e(_l('quantity')); ?></th>
                                        <th class="text-right"><?= e(_l('rate')); ?></th>
                                        <th class="text-right"><?= e(_l('invoice_total')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($credit_items as $it) {
                                        $lt = $item_line_total($it); ?>
                                    <tr>
                                        <td><?= e($it['description'] ?? ''); ?></td>
                                        <td class="text-right"><?= e($it['qty'] ?? ''); ?></td>
                                        <td class="text-right"><?= e($fmt_mwk($it['rate'] ?? 0)); ?></td>
                                        <td class="text-right text-danger">-<?= e($fmt_mwk(abs($lt))); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                        <p class="approval-val-lg text-danger"><?= e('-' . $fmt_mwk(abs((float) ($document->total ?? 0)))); ?> <small class="text-muted"><?= e(_l('approval_view_total_credit')); ?></small></p>
                            <?php if (!empty($document->clientnote) || !empty($document->adminnote)) { ?>
                        <div class="well well-sm"><?= nl2br(e(trim(($document->adminnote ?? '') . "\n" . ($document->clientnote ?? '')))); ?></div>
                            <?php } ?>
                        <?php } elseif ($request->document_type === 'journal_entry' && $document) {
                            $jref = $pick((array) $document, ['reference', 'journal_no', 'number', 'id'], (string) $document->id);
                            $jdate = $pick((array) $document, ['journal_date', 'date', 'transaction_date'], '');
                            $jdesc = $pick((array) $document, ['description', 'memo', 'narration'], '');
                            ?>
                        <dl class="dl-horizontal tw-mb-3">
                            <dt><?= e(_l('approval_view_journal_ref')); ?></dt>
                            <dd><?= e((string) $jref); ?></dd>
                            <dt><?= e(_l('estimate_data_date')); ?></dt>
                            <dd><?= $jdate !== '' ? e(_d($jdate)) : '—'; ?></dd>
                            <dt><?= e(_l('approval_view_journal_description')); ?></dt>
                            <dd><?= e($jdesc !== '' ? $jdesc : '—'); ?></dd>
                        </dl>
                        <?php if (count($journal_lines) > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('approval_view_col_account_code')); ?></th>
                                        <th><?= e(_l('approval_view_col_account_name')); ?></th>
                                        <th class="text-right"><?= e(_l('approval_view_col_debit')); ?></th>
                                        <th class="text-right"><?= e(_l('approval_view_col_credit')); ?></th>
                                        <th><?= e(_l('approval_view_col_cost_centre')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($journal_lines as $jl) {
                                        $code = $pick($jl, ['account_code', 'accountcode', 'gl_code', 'code'], '');
                                        $name = $pick($jl, ['account_name', 'accountname', 'account', 'description'], '');
                                        $d    = (float) $pick($jl, ['debit', 'dr', 'amount_debit', 'debit_amount'], 0);
                                        $c    = (float) $pick($jl, ['credit', 'cr', 'amount_credit', 'credit_amount'], 0);
                                        $cc   = $pick($jl, ['cost_centre', 'cost_center', 'costcentre', 'cc_code'], '');
                                        ?>
                                    <tr>
                                        <td><?= e((string) $code); ?></td>
                                        <td><?= e((string) $name); ?></td>
                                        <td class="text-right"><?= $d > 0 ? e($fmt_mwk($d)) : '—'; ?></td>
                                        <td class="text-right"><?= $c > 0 ? e($fmt_mwk($c)) : '—'; ?></td>
                                        <td><?= e((string) $cc); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <p>
                            <?php if ($journal_balanced) { ?>
                            <span class="text-success"><i class="fa fa-check-circle"></i> <?= e(_l('approval_view_balanced')); ?>
                                (<?= e($fmt_mwk($journal_debit_sum)); ?> = <?= e($fmt_mwk($journal_credit_sum)); ?>)</span>
                            <?php } else { ?>
                            <span class="text-danger"><i class="fa fa-times-circle"></i> <?= e(_l('approval_view_not_balanced')); ?>
                                (<?= e($fmt_mwk($journal_debit_sum)); ?> vs <?= e($fmt_mwk($journal_credit_sum)); ?>)</span>
                            <?php } ?>
                        </p>
                        <?php } else { ?>
                        <p class="text-muted"><?= e(_l('approval_view_no_journal_lines')); ?></p>
                        <?php } ?>
                        <?php } elseif ($request->document_type === 'payment' && $document) {
                            $inv_disp = '—';
                            if ($linked_invoice) {
                                $inv_disp = function_exists('format_invoice_number') ? format_invoice_number($linked_invoice->id) : ('#' . (int) $linked_invoice->id);
                            }
                            $pay_name = $pick((array) $document, ['name'], '');
                            if ($pay_name === '' && isset($document->paymentmode)) {
                                $pay_name = '#' . (int) $document->paymentmode;
                            }
                            $client_nm = '—';
                            if ($linked_invoice && !empty($linked_invoice->client) && !empty($linked_invoice->client->company)) {
                                $client_nm = $linked_invoice->client->company;
                            }
                            $amt = (float) ($document->amount ?? 0);
                            ?>
                        <dl class="dl-horizontal tw-mb-3">
                            <dt><?= e(_l('invoice')); ?></dt>
                            <dd><?= e($inv_disp); ?></dd>
                            <dt><?= e(_l('approval_view_client')); ?></dt>
                            <dd><strong><?= e($client_nm); ?></strong></dd>
                            <dt><?= e(_l('approval_view_payment_method')); ?></dt>
                            <dd><?= e($pay_name); ?></dd>
                        </dl>
                        <p class="approval-val-lg"><?= e($fmt_mwk($amt)); ?></p>
                            <?php
                            $pnote = $pick((array) $document, ['note', 'transactionid', 'paymentid'], '');
                            if ($pnote !== '') { ?>
                        <div class="well well-sm"><?= nl2br(e((string) $pnote)); ?></div>
                            <?php } ?>
                        <?php } elseif ($request->document_type === 'purchase_requisition' && $document) {
                            $darr = (array) $document;
                            $pr_ref = $pick($darr, ['reference', 'pr_number', 'requisition_no', 'number', 'id'], (string) $document->id);
                            $pr_date = $pick($darr, ['date', 'request_date', 'created_at'], '');
                            $req_by_id = $pick($darr, ['requested_by', 'staff_id', 'created_by'], 0);
                            $req_by = is_numeric($req_by_id) && (int) $req_by_id > 0 ? get_staff_full_name((int) $req_by_id) : '—';
                            $dept = $pick($darr, ['department', 'department_name', 'dept'], '—');
                            $urg  = $pick($darr, ['urgency', 'priority', 'urgency_level'], '—');
                            $gl   = $pick($darr, ['gl_account', 'gl_code', 'account_code'], '—');
                            ?>
                        <dl class="dl-horizontal tw-mb-3">
                            <dt><?= e(_l('approval_ref')); ?></dt>
                            <dd><?= e((string) $pr_ref); ?></dd>
                            <dt><?= e(_l('estimate_data_date')); ?></dt>
                            <dd><?= e($pr_date !== '' ? _d($pr_date) : '—'); ?></dd>
                            <dt><?= e(_l('approval_view_pr_requested_by')); ?></dt>
                            <dd><?= e($req_by); ?></dd>
                            <dt><?= e(_l('approval_view_department')); ?></dt>
                            <dd><?= e((string) $dept); ?></dd>
                            <dt><?= e(_l('approval_view_urgency')); ?></dt>
                            <dd><?= e((string) $urg); ?></dd>
                            <dt><?= e(_l('approval_view_gl_account')); ?></dt>
                            <dd><code><?= e((string) $gl); ?></code></dd>
                        </dl>
                        <?php if (count($pr_lines) > 0) {
                            $pr_total = 0.0;
                            ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('item_description')); ?></th>
                                        <th class="text-right"><?= e(_l('quantity')); ?></th>
                                        <th class="text-right"><?= e(_l('approval_view_estimated_cost')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pr_lines as $pl) {
                                        $desc = $pick($pl, ['description', 'item_description', 'name', 'item'], '');
                                        $qty  = $pick($pl, ['qty', 'quantity', 'units'], 0);
                                        $qnum = is_numeric($qty) ? (float) $qty : 1.0;
                                        $line_amt = (float) $pick($pl, ['line_total', 'total', 'amount', 'estimated_line_total'], 0);
                                        $unit = (float) $pick($pl, ['estimated_cost', 'unit_cost', 'rate'], 0);
                                        if ($line_amt <= 0 && $unit > 0) {
                                            $line_amt = $unit * $qnum;
                                        }
                                        $pr_total += $line_amt;
                                        ?>
                                    <tr>
                                        <td><?= e((string) $desc); ?></td>
                                        <td class="text-right"><?= e((string) $qty); ?></td>
                                        <td class="text-right"><?= e($fmt_mwk($line_amt)); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <p><strong><?= e(_l('approval_view_pr_total_est')); ?></strong> <?= e($fmt_mwk($pr_total)); ?></p>
                        <?php } ?>
                        <p>
                            <strong><?= e(_l('approval_view_attachment')); ?>:</strong>
                            <?php if ($pr_attachment_url !== '') { ?>
                            <a href="<?= e($pr_attachment_url); ?>" target="_blank" rel="noopener"><?= e(_l('approval_view_view_file')); ?> <i class="fa fa-external-link"></i></a>
                            <?php } else { ?>
                            <span class="text-muted"><?= e(_l('approval_view_no_attachment')); ?></span>
                            <?php } ?>
                        </p>
                        <?php } else { ?>
                        <p class="text-muted"><?= e(_l('approval_document')); ?>: <?= e($doc_label); ?></p>
                        <?php if ($document) { ?>
                        <pre class="tw-text-xs" style="max-height:240px;overflow:auto;"><?= e(print_r($document, true)); ?></pre>
                        <?php } else { ?>
                        <p class="text-danger"><?= e(_l('approval_view_document_missing')); ?></p>
                        <?php } ?>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="approval-card">
                    <div class="approval-card-h"><?= e(_l('approval_card_submission')); ?></div>
                    <div class="approval-card-b">
                        <p class="tw-mb-2">
                            <strong><?= e(_l('approval_col_submitted_by')); ?></strong><br />
                            <?= e($submitter); ?>
                            <?php if ($sub_role !== '') { ?>
                            <span class="label label-default"><?= e($sub_role); ?></span>
                            <?php } ?>
                        </p>
                        <p class="tw-mb-2"><strong><?= e(_l('approval_submitted')); ?></strong><br /><?= e(_dt($request->submitted_at)); ?></p>
                        <p class="tw-mb-2"><strong><?= e(_l('approval_col_value')); ?></strong></p>
                        <div class="approval-val-lg tw-mb-2"><?= e($doc_value_fmt); ?></div>
                        <p class="text-muted small tw-mb-0"><?= e($threshold_note); ?></p>
                    </div>
                </div>

                <div class="approval-card">
                    <div class="approval-card-h"><?= e(_l('approval_card_timeline')); ?></div>
                    <div class="approval-card-b">
                        <?php if (empty($action_history) && !$is_pending) { ?>
                        <p class="text-muted tw-mb-0"><?= e(_l('approval_no_actions')); ?></p>
                        <?php } else { ?>
                        <ul class="approval-tl">
                            <?php foreach ($action_history as $a) {
                                $actor = !empty($a['actor_full_name']) ? trim($a['actor_full_name']) : ($a['actor_name'] ?? '');
                                $role  = $a['actor_role'] ?? '';
                                $dot   = $timeline_dot($a['action'] ?? '');
                                ?>
                            <li>
                                <span class="approval-tl-line"></span>
                                <span class="approval-tl-dot <?= e($dot); ?>"></span>
                                <div>
                                    <strong><?= e($timeline_action_label($a['action'] ?? '')); ?></strong>
                                    <div class="approval-tl-meta">
                                        <?= e($actor); ?>
                                        <?php if ($role !== '') { ?> <span class="label label-default" style="font-size:10px;"><?= e($role); ?></span><?php } ?>
                                        &middot; <?= e(_dt($a['acted_at'] ?? '')); ?>
                                    </div>
                                    <?php if (!empty($a['comments'])) { ?>
                                    <div class="approval-tl-quote"><?= nl2br(e($a['comments'])); ?></div>
                                    <?php } ?>
                                </div>
                            </li>
                            <?php } ?>

                            <?php if ($is_pending) { ?>
                            <li>
                                <span class="approval-tl-line"></span>
                                <span class="approval-tl-dot approval-tl-dot--forwarded approval-tl-dot--pulse"></span>
                                <div>
                                    <strong><?= e(_l('approval_timeline_current')); ?></strong>
                                    <div class="approval-tl-meta">
                                        <?php if ($can_act) { ?>
                                        <?= e(_l('approval_timeline_pending_your_action')); ?>
                                        <?php } else { ?>
                                        <?= e(sprintf(_l('approval_awaiting_from'), $approver_nm, $approver_role !== '' ? $approver_role : '—')); ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($can_act) { ?>
                <div class="approval-card">
                    <div class="approval-card-h"><?= e(_l('approval_card_decision')); ?></div>
                    <div class="approval-card-b">
                        <button type="button" class="btn btn-success btn-lg btn-block approval-view-open-modal tw-mb-2" data-action="approve" style="font-size:16px;">
                            ✓ <?= e(_l('approve')); ?>
                        </button>
                        <button type="button" class="btn btn-warning btn-lg btn-block approval-view-open-modal tw-mb-2" data-action="revision" style="font-size:16px;">
                            ↩ <?= e(_l('approval_request_revision')); ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-lg btn-block approval-view-open-modal" data-action="reject" style="font-size:16px;">
                            ✗ <?= e(_l('reject')); ?>
                        </button>
                    </div>
                </div>
                <?php } elseif ($is_pending) { ?>
                <div class="approval-await-box">
                    <?= e(sprintf(_l('approval_awaiting_from'), $approver_nm, $approver_role !== '' ? $approver_role : '—')); ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approvalActionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header approval-modal-header-approve" id="approval-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="approval-modal-title"><?= e(_l('approval_modal_title_approve')); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approval-modal-request-id" value="<?= (int) $request->id; ?>" />
                <input type="hidden" id="approval-modal-action-type" value="" />
                <div class="form-group">
                    <label><?= e(_l('approval_modal_document_summary')); ?></label>
                    <div class="well well-sm" id="approval-modal-summary" style="margin-bottom:0;"></div>
                </div>
                <div class="form-group">
                    <label for="approval-modal-comments"><?= e(_l('approval_modal_comments')); ?> <span class="text-danger" id="approval-modal-comments-required" style="display:none;">*</span></label>
                    <textarea id="approval-modal-comments" class="form-control" rows="4" placeholder="<?= e(_l('approval_modal_comments_placeholder')); ?>"></textarea>
                    <p class="text-muted small" id="approval-modal-min-hint" style="display:none;"><?= e(_l('approval_error_comments_min_length')); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= e(_l('cancel')); ?></button>
                <button type="button" class="btn btn-lg" id="approval-modal-submit"><?= e(_l('approval_modal_confirm')); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';
    var summaryHtml = <?= json_encode(
        '<strong>' . $doc_label . '</strong> ' . $doc_ref
        . '<br/>' . _l('approval_col_value') . ': <strong>' . $doc_value_fmt . '</strong>'
        . '<br/>' . _l('approval_col_submitted_by') . ': ' . $submitter
    ); ?>;

    function updateCsrf(hash) { if (hash) { csrfHash = hash; } }

    var $modal = $('#approvalActionModal');
    var $comments = $('#approval-modal-comments');
    var $submit = $('#approval-modal-submit');
    var $header = $('#approval-modal-header');
    var $title = $('#approval-modal-title');

    $('.approval-view-open-modal').on('click', function() {
        var action = $(this).data('action');
        $('#approval-modal-request-id').val('<?= (int) $request->id; ?>');
        $('#approval-modal-action-type').val(action);
        $('#approval-modal-summary').html(summaryHtml);
        $comments.val('');

        $header.removeClass('approval-modal-header-approve approval-modal-header-reject approval-modal-header-revision');
        $submit.removeClass('btn-success btn-danger btn-warning');

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
            $submit.addClass('btn-warning').text('<?= e(_l('approval_btn_revision')); ?>');
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
            comments: $comments.val()
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
                    var $li = $('.menu-item-ipms-approvals');
                    var $badge = $li.find('.badge');
                    var n = parseInt(res.pending_badge_count, 10) || 0;
                    if (n > 0) {
                        if ($badge.length) { $badge.text(n).show(); }
                        else { $li.find('a').first().append(' <span class="badge pull-right bg-warning">' + n + '</span>'); }
                    } else {
                        $badge.hide().text('0');
                    }
                }
                $modal.modal('hide');
                if (res.next_url) {
                    window.location.href = res.next_url;
                } else {
                    window.location.reload();
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

<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$this->load->helper('credit_notes');

$stats = isset($inbox_stats) && is_array($inbox_stats) ? $inbox_stats : [
    'dn_count' => 0, 'dn_total_mwk' => 0, 'cn_pending' => 0, 'pay_pending' => 0,
    'pay_total_mwk' => 0, 'overdue_count' => 0, 'overdue_total' => 0,
];
$isGm = !empty($is_gm);

$cn_reason_labels = [
    'return_of_goods'    => 'Return of Goods',
    'billing_error'      => 'Billing Error',
    'pricing_adjustment' => 'Pricing Adjustment',
    'goodwill'           => 'Goodwill',
];

$ipms_pay_labels = [
    'cash'           => 'Cash',
    'bank_transfer'  => 'Bank Transfer (EFT)',
    'cheque'         => 'Cheque',
    'airtel_money'   => 'Airtel Money',
    'tnm_mpamba'     => 'TNM Mpamba',
    'other'          => 'Other',
];

if (!function_exists('billing_inbox_dn_delivered')) {
    function billing_inbox_dn_delivered($dn)
    {
        foreach (['delivered_date', 'delivery_date', 'date_delivered', 'completed_at', 'updated_at', 'created_at'] as $f) {
            if (!empty($dn->$f)) {
                return (string) $dn->$f;
            }
        }

        return '';
    }
}

if (!function_exists('billing_inbox_dn_confirmed')) {
    function billing_inbox_dn_confirmed($dn)
    {
        foreach (['signed_confirmed_at', 'confirmed_at', 'status_changed_at', 'updated_at', 'created_at'] as $f) {
            if (!empty($dn->$f)) {
                return (string) $dn->$f;
            }
        }

        return '';
    }
}

if (!function_exists('billing_inbox_days_since')) {
    function billing_inbox_days_since($dateStr)
    {
        $dateStr = trim((string) $dateStr);
        if ($dateStr === '') {
            return null;
        }
        $ts = strtotime($dateStr);
        if ($ts === false) {
            return null;
        }
        $diff = (int) floor((time() - $ts) / 86400);

        return max(0, $diff);
    }
}

if (!function_exists('billing_inbox_dn_value')) {
    function billing_inbox_dn_value($dn)
    {
        foreach (['approved_value', 'grand_total', 'total_sell', 'total_value', 'dn_total', 'total_amount'] as $f) {
            if (isset($dn->$f) && is_numeric($dn->$f)) {
                return (float) $dn->$f;
            }
        }

        return 0.0;
    }
}
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title">
                    <i class="fa fa-inbox"></i> Finance Inbox
                    <small class="text-muted">Billing actions requiring attention</small>
                </h4>
                <div class="clearfix mbot20"></div>

                <div class="row mbot25">
                    <div class="col-md-3 col-sm-6 mbot15">
                        <div class="panel panel-default ipms-fi-metric">
                            <div class="panel-body text-center">
                                <div class="text-muted small text-uppercase">DNs ready to invoice</div>
                                <div class="h3 mtop5 mbot0"><?php echo (int) $stats['dn_count']; ?></div>
                                <div class="text-success"><?php echo html_escape(billing_format_mwk($stats['dn_total_mwk'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mbot15">
                        <div class="panel panel-default ipms-fi-metric">
                            <div class="panel-body text-center">
                                <div class="text-muted small text-uppercase">Pending CN approvals</div>
                                <div class="h3 mtop5 mbot0"><?php echo (int) $stats['cn_pending']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mbot15">
                        <div class="panel panel-default ipms-fi-metric">
                            <div class="panel-body text-center">
                                <div class="text-muted small text-uppercase">Pending payment approvals</div>
                                <div class="h3 mtop5 mbot0"><?php echo (int) $stats['pay_pending']; ?></div>
                                <div class="text-warning"><?php echo html_escape(billing_format_mwk($stats['pay_total_mwk'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mbot15">
                        <div class="panel panel-default ipms-fi-metric">
                            <div class="panel-body text-center">
                                <div class="text-muted small text-uppercase">Overdue invoices</div>
                                <div class="h3 mtop5 mbot0"><?php echo (int) $stats['overdue_count']; ?></div>
                                <div class="text-danger"><?php echo html_escape(billing_format_mwk($stats['overdue_total'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel ipms-fi-section ipms-fi-section-success mbot20">
                    <div class="panel-heading">
                        <strong>Delivery Notes Ready for Invoicing</strong>
                        <span class="badge pull-right"><?php echo count($dns_awaiting ?? []); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($dns_awaiting)) { ?>
                            <p class="text-success mbot0"><i class="fa fa-check-circle"></i> No delivery notes awaiting invoicing. Well done!</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mbot0">
                                    <thead>
                                        <tr>
                                            <th>DN Ref</th>
                                            <th>Job Card</th>
                                            <th>Client</th>
                                            <th>Delivered Date</th>
                                            <th>Approved Value</th>
                                            <th>Days Since Confirmation</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dns_awaiting as $dn) {
                                            $dnid        = (int) $dn->id;
                                            $del         = billing_inbox_dn_delivered($dn);
                                            $conf        = billing_inbox_dn_confirmed($dn);
                                            $days        = billing_inbox_days_since($conf);
                                            $apprVal     = billing_inbox_dn_value($dn);
                                            ?>
                                            <tr>
                                                <td><?php echo html_escape($dn->dn_ref ?? ('#' . $dnid)); ?></td>
                                                <td><?php echo html_escape($dn->jc_ref ?? '—'); ?></td>
                                                <td><?php echo html_escape($dn->client_company ?? '—'); ?></td>
                                                <td><?php echo $del !== '' ? html_escape(_d($del)) : '—'; ?></td>
                                                <td><?php echo html_escape(app_format_money($apprVal, '')); ?></td>
                                                <td><?php echo $days !== null ? (int) $days : '—'; ?></td>
                                                <td class="text-right">
                                                    <a class="btn btn-success btn-lg mbot5" href="<?php echo admin_url('billing/create_from_dn/' . $dnid); ?>">Create Invoice</a>
                                                    <a class="btn btn-default mbot5" href="<?php echo admin_url('billing/create_proforma/' . $dnid); ?>">Create Proforma</a>
                                                    <a class="btn btn-link mbot5" href="<?php echo admin_url('inventory_ops/view_dn/' . $dnid); ?>" title="View DN"><i class="fa fa-external-link fa-lg"></i></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel ipms-fi-section ipms-fi-section-warning mbot20">
                    <div class="panel-heading">
                        <strong>Credit Notes Awaiting Your Approval</strong>
                        <span class="badge pull-right"><?php echo count($pending_cn ?? []); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php if (!$isGm) { ?>
                            <p class="text-muted mbot0">
                                <i class="fa fa-info-circle"></i>
                                <?php echo (int) count($pending_cn ?? []); ?> credit note(s) pending GM approval.
                            </p>
                        <?php } elseif (empty($pending_cn)) { ?>
                            <p class="text-muted mbot0">No credit notes awaiting approval.</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mbot0">
                                    <thead>
                                        <tr>
                                            <th>CN #</th>
                                            <th>Client</th>
                                            <th>Original Invoice</th>
                                            <th>Amount</th>
                                            <th>Reason Category</th>
                                            <th>Submitted By</th>
                                            <th>Submitted Date</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_cn as $row) {
                                            $cnid   = (int) $row->credit_note_id;
                                            $rcat   = (string) ($row->reason_category ?? '');
                                            $rcLabel = $cn_reason_labels[$rcat] ?? ucfirst(str_replace('_', ' ', $rcat));
                                            $subUid = (int) ($row->ar_submitted_by ?? $row->cn_addedfrom ?? 0);
                                            $subAt  = !empty($row->ar_submitted_at) ? $row->ar_submitted_at : ($row->cn_datecreated ?? '');
                                            $cur    = $row->currency ?? '';
                                            ?>
                                            <tr>
                                                <td><?php echo html_escape(format_credit_note_number($cnid)); ?></td>
                                                <td><?php echo html_escape($row->client_company ?? '—'); ?></td>
                                                <td>
                                                    <?php if (!empty($row->original_invoice_id)) { ?>
                                                        <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $row->original_invoice_id); ?>">
                                                            <?php echo html_escape(format_invoice_number((int) $row->original_invoice_id)); ?>
                                                        </a>
                                                    <?php } else { ?>—<?php } ?>
                                                </td>
                                                <td><?php echo html_escape(app_format_money($row->total ?? 0, $cur)); ?></td>
                                                <td><?php echo html_escape($rcLabel); ?></td>
                                                <td><?php echo $subUid > 0 ? html_escape(get_staff_full_name($subUid)) : '—'; ?></td>
                                                <td><?php echo $subAt !== '' ? html_escape(_dt($subAt)) : '—'; ?></td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-success btn-sm billing-approve-cn" data-id="<?php echo $cnid; ?>">Approve</button>
                                                    <button type="button" class="btn btn-danger btn-sm billing-reject-cn-open" data-id="<?php echo $cnid; ?>">Reject</button>
                                                    <a class="btn btn-link btn-sm" href="<?php echo admin_url('credit_notes/list_credit_notes/' . $cnid); ?>" title="View"><i class="fa fa-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel ipms-fi-section ipms-fi-section-warning mbot20">
                    <div class="panel-heading">
                        <strong>Payments Awaiting GM Approval</strong>
                        <span class="badge pull-right"><?php echo count($pending_payments ?? []); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php if (!$isGm) { ?>
                            <p class="text-muted mbot0">
                                <i class="fa fa-info-circle"></i>
                                <?php echo (int) count($pending_payments ?? []); ?> payment(s) pending GM approval.
                            </p>
                        <?php } elseif (empty($pending_payments)) { ?>
                            <p class="text-muted mbot0">No payments awaiting approval.</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mbot0">
                                    <thead>
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Invoice #</th>
                                            <th>Client</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Submitted By</th>
                                            <th>Date</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_payments as $pm) {
                                            $pid     = (int) $pm->payment_id;
                                            $iid     = (int) $pm->invoiceid;
                                            $detail  = (string) ($pm->payment_method_detail ?? '');
                                            $methLbl = $ipms_pay_labels[$detail] ?? ($pm->perfex_payment_mode_name ?? $detail);
                                            $subUid  = (int) ($pm->ar_submitted_by ?? $pm->received_by ?? 0);
                                            $subAt   = !empty($pm->ar_submitted_at) ? $pm->ar_submitted_at : ($pm->daterecorded ?? $pm->payment_date ?? '');
                                            $cur     = $pm->invoice_currency ?? '';
                                            ?>
                                            <tr>
                                                <td>#<?php echo $pid; ?></td>
                                                <td>
                                                    <a href="<?php echo admin_url('invoices/list_invoices/' . $iid); ?>">
                                                        <?php echo html_escape(format_invoice_number($iid)); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo html_escape($pm->client_company ?? '—'); ?></td>
                                                <td><?php echo html_escape(app_format_money($pm->amount ?? 0, $cur)); ?></td>
                                                <td><?php echo html_escape($methLbl); ?></td>
                                                <td><?php echo $subUid > 0 ? html_escape(get_staff_full_name($subUid)) : '—'; ?></td>
                                                <td><?php echo $subAt !== '' ? html_escape(_d($subAt)) : '—'; ?></td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-success btn-sm billing-approve-payment" data-id="<?php echo $pid; ?>">Approve</button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel ipms-fi-section ipms-fi-section-danger mbot20">
                    <div class="panel-heading">
                        <strong>Overdue Invoices</strong>
                        <span class="badge pull-right"><?php echo count($overdue_invoices ?? []); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($overdue_invoices)) { ?>
                            <p class="text-success mbot0"><i class="fa fa-check-circle"></i> No overdue invoices.</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mbot0">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Client</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Days Overdue</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($overdue_invoices as $inv) {
                                            $iid = (int) $inv->id;
                                            if (function_exists('get_total_days_overdue')) {
                                                $daysO = (int) get_total_days_overdue($inv->duedate);
                                            } else {
                                                $dueTs = $inv->duedate ? strtotime($inv->duedate) : false;
                                                $daysO = ($dueTs && $dueTs < time()) ? (int) floor((time() - $dueTs) / 86400) : 0;
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo html_escape(format_invoice_number($iid)); ?></td>
                                                <td><?php echo html_escape($inv->client_company ?? '—'); ?></td>
                                                <td><?php echo html_escape(app_format_money($inv->total ?? 0, $inv->currency_name ?? '')); ?></td>
                                                <td><?php echo html_escape(_d($inv->duedate)); ?></td>
                                                <td><?php echo (int) $daysO; ?></td>
                                                <td class="text-right">
                                                    <a class="btn btn-default btn-sm" href="<?php echo admin_url('invoices/list_invoices/' . $iid); ?>">View Invoice</a>
                                                    <?php if (function_exists('is_invoices_overdue_reminders_enabled') && is_invoices_overdue_reminders_enabled()) { ?>
                                                        <a class="btn btn-warning btn-sm" href="<?php echo admin_url('invoices/send_overdue_notice/' . $iid); ?>">Send Reminder</a>
                                                    <?php } ?>
                                                </td>
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

<div class="modal fade" id="billingRejectCnModal" tabindex="-1" role="dialog" aria-labelledby="billingRejectCnModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="billingRejectCnModalLabel">Reject credit note</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="billingRejectCnId" value="" />
                <div class="form-group">
                    <label for="billingRejectCnReason">Reason for rejection <span class="text-danger">*</span></label>
                    <textarea id="billingRejectCnReason" class="form-control" rows="4" required placeholder="Required"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="billingRejectCnConfirm">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

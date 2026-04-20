<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Recording Payment for Invoice #<?= e($invoice_number ?? format_invoice_number((int) $invoice->id)); ?></strong>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="ipms-rp-metric">
                                    <div class="metric-label">Invoice Total</div>
                                    <div class="metric-value"><?= billing_format_mwk($invoice->total); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ipms-rp-metric">
                                    <div class="metric-label">Already Paid</div>
                                    <div class="metric-value text-success">
                                        <?= billing_format_mwk($total_paid ?? 0); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ipms-rp-metric">
                                    <div class="metric-label">Balance Due</div>
                                    <div class="metric-value text-danger" id="balance-due-display">
                                        <?= billing_format_mwk($balance_due); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ipms-rp-metric">
                                    <div class="metric-label">Status</div>
                                    <div class="metric-value"><?= billing_get_invoice_status_badge($invoice->status); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($previous_payments)) { ?>
                            <div class="mtop15">
                                <h5>Previous Payments</h5>
                                <table class="table table-condensed table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($previous_payments as $pmt) { ?>
                                            <tr>
                                                <td><?= e(_d($pmt['date'])); ?></td>
                                                <td class="text-right"><strong><?= billing_format_mwk($pmt['amount']); ?></strong></td>
                                                <td><?= e($pmt['method_display'] ?? $pmt['name'] ?? ''); ?></td>
                                                <td class="text-mono small"><?= e($pmt['transactionid'] !== '' && $pmt['transactionid'] !== null ? $pmt['transactionid'] : '—'); ?></td>
                                                <td><?= !empty($pmt['received_by']) ? e(get_staff_full_name((int) $pmt['received_by'])) : '—'; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Record New Payment</strong></div>
                    <div class="panel-body">
                        <?= form_open(admin_url('billing/record_payment/' . (int) $invoice->id), ['id' => 'payment-form']); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Payment Method <span class="required">*</span></label>
                                    <div class="row">
                                        <?php
                                        $methods = [
                                            'cash'          => ['label' => 'Cash', 'icon' => 'fa-money'],
                                            'bank_transfer' => ['label' => 'Bank Transfer / EFT', 'icon' => 'fa-bank'],
                                            'cheque'        => ['label' => 'Cheque', 'icon' => 'fa-file-text-o'],
                                            'airtel_money'  => ['label' => 'Airtel Money', 'icon' => 'fa-mobile'],
                                            'tnm_mpamba'    => ['label' => 'TNM Mpamba', 'icon' => 'fa-mobile'],
                                        ];
                                        foreach ($methods as $key => $m) {
                                            ?>
                                            <div class="col-md-4 col-sm-6" style="margin-bottom:8px">
                                                <div class="payment-method-tile <?= $key === 'cash' ? 'selected' : ''; ?>"
                                                    data-method="<?= e($key); ?>">
                                                    <i class="fa <?= e($m['icon']); ?>"></i>
                                                    <span><?= e($m['label']); ?></span>
                                                    <input type="radio" name="billing_payment_method_detail"
                                                        value="<?= e($key); ?>" <?= $key === 'cash' ? 'checked' : ''; ?> style="display:none">
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="form-group" id="reference-group" style="display:none">
                                    <label class="control-label">Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control"
                                        placeholder="EFT reference, cheque number, mobile transaction ID...">
                                    <small class="text-muted">Required for Bank Transfer, Cheque, Mobile Money</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Payment Date <span class="required">*</span></label>
                                    <input type="text" name="payment_date" class="form-control datepicker"
                                        value="<?= e(_d(date('Y-m-d'))); ?>" autocomplete="off">
                                </div>

                                <div class="form-group">
                                    <label class="control-label">Amount (MWK) <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon">MWK</span>
                                        <?php
                                        $bal = (float) $balance_due;
                                        ?>
                                        <input type="number" name="amount" id="payment-amount" class="form-control"
                                            value="<?= e((string) $bal); ?>" min="0.01" step="0.01"
                                            max="<?= e((string) $bal); ?>" data-balance="<?= e((string) $bal); ?>">
                                    </div>
                                    <small class="text-muted">Balance due: <?= billing_format_mwk($balance_due); ?></small>
                                    <div id="partial-indicator" class="mtop5 hide">
                                        <span class="label label-info">
                                            <i class="fa fa-info-circle"></i>
                                            Partial payment — remaining balance:
                                            <strong id="remaining-balance">MWK 0.00</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"
                                        placeholder="Payment reference, bank details, etc."></textarea>
                                </div>
                            </div>
                        </div>

                        <div id="gm-approval-warning" class="alert alert-warning hide">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>GM Approval Required:</strong>
                            This payment amount exceeds MWK <?= e(number_format((float) billing_setting('payment_threshold_gm', '5000000'), 0, '.', ',')); ?>.
                            It will be submitted to the General Manager for approval before posting.
                        </div>

                        <div class="text-right mtop15">
                            <a href="<?= admin_url('invoices/list_invoices/' . (int) $invoice->id); ?>"
                                class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Record Payment
                            </button>
                        </div>

                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    if (typeof init_datepicker === 'function') {
        init_datepicker();
    }
    $('#payment-amount').trigger('input');
});
</script>

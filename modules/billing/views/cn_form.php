<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
init_head();

$invoice_items = isset($invoice_items) && is_array($invoice_items) ? $invoice_items : (array) ($invoice->items ?? []);
$vatRatePct    = isset($vat_rate_default) ? (float) $vat_rate_default : (float) billing_setting('vat_rate', '16.5');
$vatFracJs     = $vatRatePct / 100;

$clientLabel = '';
if (!empty($client) && is_object($client)) {
    $clientLabel = (string) ($client->company ?? '');
    if ($clientLabel === '') {
        $clientLabel = trim((string) ($client->firstname ?? '') . ' ' . (string) ($client->lastname ?? ''));
    }
}
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title mbot20">
                    <i class="fa fa-file-text"></i> <?= e($title); ?>
                </h4>

                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <h4><i class="fa fa-gavel"></i> GM Approval Required</h4>
                    <p>
                        <strong>All credit notes at MW require General Manager approval before they take effect.</strong>
                        The credit note will be locked in "Pending Approval" status until the General Manager
                        reviews and approves it. The client will not be notified until after GM approval.
                    </p>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Original Invoice Being Credited</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Invoice #:</strong> <?= e(format_invoice_number((int) $invoice->id)); ?><br>
                                <strong>Client:</strong> <?= e($clientLabel); ?><br>
                                <strong>Invoice Date:</strong> <?= e(_d($invoice->date)); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Invoice Total:</strong> <?= billing_format_mwk($invoice->total); ?><br>
                                <strong>Amount Paid:</strong> <?= billing_format_mwk($total_paid ?? 0); ?><br>
                                <strong>Balance Due:</strong> <?= billing_format_mwk($balance_due ?? 0); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Status:</strong> <?= billing_get_invoice_status_badge($invoice->status); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?= form_open(admin_url('billing/create_credit_note/' . (int) $invoice->id), ['id' => 'cn-form']); ?>
                <input type="hidden" name="original_invoice_id" value="<?= (int) $invoice->id; ?>">
                <input type="hidden" name="clientid" value="<?= (int) $invoice->clientid; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">Reason Category <span class="required">*</span></label>
                            <select name="reason_category" class="form-control selectpicker" data-width="100%" required>
                                <option value="">-- Select Reason --</option>
                                <option value="return_of_goods">Return of Goods</option>
                                <option value="billing_error">Billing Error</option>
                                <option value="pricing_adjustment">Pricing Adjustment</option>
                                <option value="goodwill">Goodwill</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Detailed Reason <span class="required">*</span></label>
                            <textarea name="reason_detail" class="form-control" rows="4" required
                                minlength="20"
                                placeholder="Provide a clear, specific reason for this credit note.&#10;This will be reviewed by the General Manager before approval..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Credit Note Date</label>
                            <input type="text" name="date" class="form-control datepicker"
                                value="<?= e(_d(date('Y-m-d'))); ?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">VAT Adjustment</label>
                            <div class="onoffswitch">
                                <input type="checkbox" id="vat_adjusted" class="onoffswitch-checkbox"
                                    name="vat_adjusted" value="1">
                                <label class="onoffswitch-label" for="vat_adjusted"
                                    data-toggle="tooltip"
                                    title="Toggle ON if VAT should be reversed on this credit note">
                                </label>
                            </div>
                            <small class="text-muted">
                                Toggle ON if the original invoice included VAT that should be reversed.
                            </small>
                        </div>
                        <div id="vat-adj-amount" class="form-group hide">
                            <label class="control-label">VAT Amount to Reverse</label>
                            <div class="input-group">
                                <span class="input-group-addon">MWK</span>
                                <input type="number" name="vat_adjustment_amount" class="form-control"
                                    step="0.01" min="0" value="<?= e((string) $invoice->total_tax); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <h5>Items Being Credited</h5>
                <p class="text-muted small">
                    Select which line items to credit. You can credit the full line or a partial quantity.
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered" id="cn-items-table">
                        <thead>
                            <tr>
                                <th style="width:3%"><input type="checkbox" id="check-all-cn" checked></th>
                                <th style="width:35%">Item Description</th>
                                <th style="width:10%">Original Qty</th>
                                <th style="width:10%">Original Rate (MWK)</th>
                                <th style="width:12%">Qty to Credit</th>
                                <th style="width:12%">Credit Amount</th>
                                <th style="width:12%">VAT (<?= e(rtrim(rtrim(number_format($vatRatePct, 2, '.', ''), '0'), '.')); ?>%)</th>
                                <th style="width:12%">Line Total Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rowIdx = 0;
                            foreach ($invoice_items as $item) {
                                ++$rowIdx;
                                $taxes = function_exists('get_invoice_item_taxes') ? get_invoice_item_taxes((int) $item['id']) : [];
                                $sub    = (float) $item['qty'] * (float) $item['rate'];
                                $taxFrac = 0.0;
                                if (!empty($taxes)) {
                                    foreach ($taxes as $tx) {
                                        $taxFrac += ((float) ($tx['taxrate'] ?? 0)) / 100;
                                    }
                                } else {
                                    $taxFrac = $vatRatePct / 100;
                                }
                                $lineVat   = $sub * $taxFrac;
                                $lineTotal = $sub + $lineVat;
                                ?>
                                <tr class="cn-item-row" data-tax-frac="<?= e((string) $taxFrac); ?>"
                                    data-rate="<?= e((string) $item['rate']); ?>"
                                    data-max-qty="<?= e((string) $item['qty']); ?>">
                                    <td>
                                        <input type="checkbox" name="credit_items[]" value="<?= (int) $item['id']; ?>"
                                            checked class="cn-check">
                                    </td>
                                    <td><?= e($item['description']); ?></td>
                                    <td class="text-right"><?= e((string) $item['qty']); ?></td>
                                    <td class="text-right"><?= billing_format_mwk($item['rate']); ?></td>
                                    <td>
                                        <input type="number" name="newitems[<?= (int) $rowIdx; ?>][qty]"
                                            class="form-control input-sm cn-qty cn-row-input"
                                            value="<?= e((string) $item['qty']); ?>" min="0" step="0.001"
                                            max="<?= e((string) $item['qty']); ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][description]" class="cn-row-input"
                                            value="<?= e($item['description']); ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][long_description]" class="cn-row-input"
                                            value="<?= e(clear_textarea_breaks($item['long_description'] ?? '')); ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][unit]" class="cn-row-input"
                                            value="<?= e($item['unit'] ?? ''); ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][order]" class="cn-row-input"
                                            value="<?= (int) $rowIdx; ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][rate]" class="cn-row-input cn-rate"
                                            value="<?= e((string) $item['rate']); ?>">
                                        <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][invoice_item_id]" class="cn-row-input"
                                            value="<?= (int) $item['id']; ?>">
                                        <?php foreach ($taxes as $tax) { ?>
                                            <input type="hidden" name="newitems[<?= (int) $rowIdx; ?>][taxname][]" class="cn-row-input"
                                                value="<?= e($tax['taxname']); ?>">
                                        <?php } ?>
                                    </td>
                                    <td class="text-right cn-line-amount"><?= billing_format_mwk($sub); ?></td>
                                    <td class="text-right cn-line-vat"><?= billing_format_mwk($lineVat); ?></td>
                                    <td class="text-right cn-line-total">
                                        <strong><?= billing_format_mwk($lineTotal); ?></strong>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><strong>Total Credit:</strong></td>
                                <td class="text-right" id="cn-subtotal"><?= billing_format_mwk(0); ?></td>
                                <td class="text-right" id="cn-vat-total"><?= billing_format_mwk(0); ?></td>
                                <td class="text-right"><strong id="cn-grand-total"><?= billing_format_mwk(0); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-right mtop20">
                    <a href="<?= admin_url('invoices/list_invoices/' . (int) $invoice->id); ?>"
                        class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fa fa-paper-plane"></i>
                        Submit Credit Note for GM Approval
                    </button>
                </div>

                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function($) {
    'use strict';

    var CN_VAT_FRAC_FALLBACK = <?= json_encode($vatFracJs); ?>;

    function billing_fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseCellAmount($cell) {
        var t = ($cell.text() || '').replace(/MWK/gi, '').replace(/,/g, '').trim();
        return parseFloat(t) || 0;
    }

    function rowTaxFrac($row) {
        var f = parseFloat($row.data('tax-frac'));
        return isFinite(f) && f >= 0 ? f : CN_VAT_FRAC_FALLBACK;
    }

    function syncRow($input) {
        var $row = $input.closest('tr');
        var qty = parseFloat($input.val()) || 0;
        var rate = parseFloat($row.data('rate')) || 0;
        var maxQ = parseFloat($row.data('max-qty'));
        if (isFinite(maxQ) && qty > maxQ) {
            qty = maxQ;
            $input.val(maxQ);
        }
        var amount = qty * rate;
        var vat = amount * rowTaxFrac($row);
        var total = amount + vat;
        $row.find('.cn-line-amount').text('MWK ' + billing_fmt(amount));
        $row.find('.cn-line-vat').text('MWK ' + billing_fmt(vat));
        $row.find('.cn-line-total').html('<strong>MWK ' + billing_fmt(total) + '</strong>');
        billing_recalc_cn_totals();
    }

    function billing_recalc_cn_totals() {
        var sub = 0;
        var vatTotal = 0;
        $('.cn-check:checked').each(function() {
            var $row = $(this).closest('tr');
            sub += parseCellAmount($row.find('.cn-line-amount'));
            vatTotal += parseCellAmount($row.find('.cn-line-vat'));
        });
        var grand = sub + vatTotal;
        $('#cn-subtotal').text('MWK ' + billing_fmt(sub));
        $('#cn-vat-total').text('MWK ' + billing_fmt(vatTotal));
        $('#cn-grand-total').text('MWK ' + billing_fmt(grand));
    }

    function setRowInputsEnabled($row, on) {
        $row.find('.cn-row-input').prop('disabled', !on);
    }

    function syncCheckAllState() {
        var $boxes = $('.cn-check');
        if ($boxes.length === 0) {
            return;
        }
        var allOn = $boxes.filter(':checked').length === $boxes.length;
        $('#check-all-cn').prop('checked', allOn);
    }

    $(document).on('input', '.cn-qty', function() {
        syncRow($(this));
    });

    $('#vat_adjusted').on('change', function() {
        $('#vat-adj-amount').toggleClass('hide', !$(this).is(':checked'));
    });

    $('#check-all-cn').on('change', function() {
        var on = $(this).is(':checked');
        $('.cn-check').prop('checked', on).each(function() {
            setRowInputsEnabled($(this).closest('tr'), on);
        });
        billing_recalc_cn_totals();
    });

    $(document).on('change', '.cn-check', function() {
        var on = $(this).is(':checked');
        setRowInputsEnabled($(this).closest('tr'), on);
        syncCheckAllState();
        billing_recalc_cn_totals();
    });

    $(function() {
        if (typeof init_datepicker === 'function') {
            init_datepicker();
        }
        if ($.fn.selectpicker) {
            $('.selectpicker').selectpicker();
        }
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
        }

        $('.cn-check').each(function() {
            setRowInputsEnabled($(this).closest('tr'), $(this).is(':checked'));
        });
        $('.cn-qty').each(function() {
            syncRow($(this));
        });
        syncCheckAllState();
        billing_recalc_cn_totals();

        $('#cn-form').on('submit', function() {
            if ($('.cn-check:checked').length < 1) {
                alert('Select at least one line item to credit.');
                return false;
            }
            return true;
        });
    });
})(jQuery);
</script>

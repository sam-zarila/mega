<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$s          = isset($settings) && is_array($settings) ? $settings : [];
$checklist  = isset($setup_checklist) && is_array($setup_checklist) ? $setup_checklist : [];
$vatRate    = $s['vat_rate'] ?? '16.5';
$vatReg     = $s['vat_registration_number'] ?? '';
$thrGm      = isset($s['payment_threshold_gm']) ? (float) $s['payment_threshold_gm'] : 5000000;
$thrFmt     = number_format($thrGm, 0, '.', ',');
$finOnly    = !isset($s['finance_only_edit']) || (string) $s['finance_only_edit'] !== '0';
$terms      = $s['invoice_terms'] ?? '';
$footer     = $s['invoice_footer'] ?? '';
$acctActive = function_exists('billing_accounting_is_active') && billing_accounting_is_active();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <h4 class="page-title mbot20">
                    <i class="fa fa-cogs"></i> <?= e($title); ?>
                </h4>

                <?= form_open(admin_url('billing/settings')); ?>
                <?= form_hidden('proforma_prefix', $s['proforma_prefix'] ?? 'PROF'); ?>
                <?= form_hidden('proforma_next_number', $s['proforma_next_number'] ?? '1'); ?>
                <?= form_hidden('auto_populate_from_dn', $s['auto_populate_from_dn'] ?? '1'); ?>
                <?= form_hidden('cn_always_requires_gm', $s['cn_always_requires_gm'] ?? '1'); ?>
                <input type="hidden" name="finance_only_edit" value="0">

                <div class="panel panel-default mbot20">
                    <div class="panel-heading"><strong>Card 1 — VAT configuration</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">VAT rate (%)</label>
                                    <input type="number" name="vat_rate" class="form-control" step="0.01" min="0" max="100"
                                        value="<?= e($vatRate); ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="control-label">VAT registration number</label>
                                    <input type="text" name="vat_registration_number" class="form-control"
                                        value="<?= e($vatReg); ?>"
                                        placeholder="Printed on invoices and proformas">
                                    <p class="text-muted small mtop8 mbot0">
                                        This VAT number will appear on all invoices and proformas. MRA EIS integration uses this.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default mbot20">
                    <div class="panel-heading"><strong>Card 2 — Payment settings</strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">GM approval threshold (MWK)</label>
                            <p class="text-muted small">
                                Payments <strong>above</strong> this amount require GM approval before posting.
                                Current value (reference): <strong>MWK <?= e($thrFmt); ?></strong>
                            </p>
                            <input type="number" name="payment_threshold_gm" class="form-control" step="1" min="0"
                                value="<?= e((string) (int) $thrGm); ?>">
                        </div>
                        <hr class="hr-panel-heading">
                        <p class="bold mbot10">Payment methods</p>
                        <p class="text-muted small">
                            Base payment modes are managed in Perfex (not editable here).
                            IPMS billing maps <strong>Airtel Money</strong> and <strong>TNM Mpamba</strong> to dedicated tiles on Record Payment.
                        </p>
                        <a href="<?= admin_url('paymentmodes'); ?>" class="btn btn-default btn-sm" target="_blank" rel="noopener">
                            <i class="fa fa-external-link"></i> Open Payment Modes
                        </a>
                        <p class="text-muted small mtop10 mbot0">
                            Add or edit modes under <strong>Setup → Payment Modes</strong>.
                        </p>
                    </div>
                </div>

                <div class="panel panel-default mbot20">
                    <div class="panel-heading"><strong>Card 3 — Invoice settings</strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="finance_only_edit" id="finance_only_edit" value="1" <?= $finOnly ? 'checked' : ''; ?>>
                                <label for="finance_only_edit">Finance only editing</label>
                            </div>
                            <p class="text-muted small mbot0">
                                When enabled, only <strong>Finance Manager</strong>, <strong>General Manager</strong>, and <strong>system administrators</strong>
                                can create or edit invoices (per IPMS billing rules).
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Default invoice terms</label>
                            <textarea name="invoice_terms" class="form-control" rows="4"><?= e($terms); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Invoice footer text</label>
                            <input type="text" name="invoice_footer" class="form-control" value="<?= e($footer); ?>"
                                placeholder="Short line printed at the bottom of invoices">
                        </div>
                    </div>
                </div>

                <div class="panel panel-default mbot20">
                    <div class="panel-heading"><strong>Card 4 — GL account mapping</strong></div>
                    <div class="panel-body">
                        <p class="text-muted">
                            These accounts are configured in the <strong>Accounting</strong> add-on. IPMS billing posts using that mapping:
                        </p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Typical posting pattern</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Invoice posted</td>
                                        <td>Dr <span class="text-mono">Debtors Control</span> / Cr <span class="text-mono">Revenue</span> / Cr <span class="text-mono">VAT Output</span></td>
                                    </tr>
                                    <tr>
                                        <td>Payment received</td>
                                        <td>Dr <span class="text-mono">Bank/Cash</span> / Cr <span class="text-mono">Debtors Control</span></td>
                                    </tr>
                                    <tr>
                                        <td>Credit note posted</td>
                                        <td>Dr <span class="text-mono">Revenue</span> / Cr <span class="text-mono">Debtors Control</span> (and VAT as applicable)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($acctActive) { ?>
                            <a href="<?= admin_url('accounting/setting'); ?>" class="btn btn-info btn-sm" target="_blank" rel="noopener">
                                <i class="fa fa-external-link"></i> Configure GL accounts
                            </a>
                        <?php } else { ?>
                            <p class="text-muted small mbot0">
                                Accounting module not detected. Install/activate Accounting to configure GL accounts.
                            </p>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel panel-default mbot20">
                    <div class="panel-heading"><strong>Card 5 — Setup checklist (Perfex admin)</strong></div>
                    <div class="panel-body">
                        <p class="text-muted mbot15">
                            One-time steps your <strong>Finance Manager</strong> should complete in Perfex. Status reflects the live database where possible.
                        </p>
                        <ul class="list-unstyled billing-setup-checklist">
                            <?php foreach ($checklist as $idx => $row) {
                                $ok = !empty($row['ok']);
                                $icon = $ok ? '<span class="text-success billing-check-ic" title="Looks good">✓</span>' : '<span class="text-warning billing-check-ic" title="Action needed">⚠</span>';
                                ?>
                                <li class="mbot15 clearfix">
                                    <div class="pull-left mright10" style="font-size:18px;line-height:1.2;"><?= $icon; ?></div>
                                    <div>
                                        <strong><?= (int) ($idx + 1); ?>. <?= e($row['title'] ?? ''); ?></strong>
                                        <div class="text-muted small mtop5"><?= e($row['detail'] ?? ''); ?></div>
                                        <?php if (!empty($row['url'])) { ?>
                                            <a href="<?= e($row['url']); ?>" class="btn btn-xs btn-default mtop5" target="_blank" rel="noopener">Open</a>
                                        <?php } ?>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>

                <div class="text-right mbot30">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save"></i> Save settings
                    </button>
                </div>

                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

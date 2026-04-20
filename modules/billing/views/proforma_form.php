<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo html_escape($title); ?></h4>
                        <hr class="hr-panel-heading" />

                        <?php if (!empty($invoice) && !empty($meta)) { ?>
                            <div class="alert alert-success">
                                Proforma reference: <strong><?php echo html_escape($meta->proforma_ref ?? ''); ?></strong><br />
                                Invoice draft: <strong><?php echo html_escape(format_invoice_number((int) $invoice->id)); ?></strong>
                            </div>
                            <a class="btn btn-default" href="<?php echo admin_url('invoices/invoice/' . (int) $invoice->id); ?>">Edit proforma</a>
                            <a class="btn btn-info" target="_blank" href="<?php echo admin_url('invoices/pdf/' . (int) $invoice->id); ?>">PDF</a>
                        <?php } else { ?>
                            <?php echo form_open(admin_url('billing/create_proforma')); ?>
                            <input type="hidden" name="dn_id" value="<?php echo (int) ($dn_id ?? 0); ?>" />
                            <?php if (empty($populate)) { ?>
                                <p class="text-muted">Choose a delivery note from the Finance Inbox, or append <code>?dn_id=</code> to this URL.</p>
                            <?php } else { ?>
                                <p>Client: <strong><?php echo html_escape($populate['client_name'] ?? ''); ?></strong></p>
                                <p>DN: <?php echo html_escape($populate['dn_ref'] ?? ''); ?> | JC: <?php echo html_escape($populate['jc_ref'] ?? ''); ?></p>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th width="100">Qty</th>
                                                <th width="120">Rate</th>
                                                <th width="80">Taxable</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $idx        = 1;
                                            $defaultTax = isset($default_line_tax) ? (string) $default_line_tax : '';
                                            if (!empty($populate['lines']) && is_array($populate['lines'])) {
                                                foreach ($populate['lines'] as $tabLines) {
                                                    if (!is_array($tabLines)) {
                                                        continue;
                                                    }
                                                    foreach ($tabLines as $line) {
                                                        $desc = isset($line->description) ? strip_tags((string) $line->description) : '';
                                                        $qty  = isset($line->quantity) ? (float) $line->quantity : 1;
                                                        $rate = isset($line->sell_price) ? (float) $line->sell_price : 0;
                                                        $taxb = isset($line->is_taxable) ? (int) $line->is_taxable : 1;
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <textarea name="newitems[<?php echo $idx; ?>][description]" class="form-control" rows="2"><?php echo html_escape($desc); ?></textarea>
                                                            </td>
                                                            <td><input type="number" step="0.0001" class="form-control" name="newitems[<?php echo $idx; ?>][qty]" value="<?php echo html_escape((string) $qty); ?>" /></td>
                                                            <td><input type="number" step="0.01" class="form-control" name="newitems[<?php echo $idx; ?>][rate]" value="<?php echo html_escape((string) $rate); ?>" /></td>
                                                            <td class="text-center">
                                                                <input type="hidden" name="newitems[<?php echo $idx; ?>][order]" value="<?php echo $idx; ?>" />
                                                                <input type="hidden" name="newitems[<?php echo $idx; ?>][unit]" value="" />
                                                                <input type="hidden" name="newitems[<?php echo $idx; ?>][long_description]" value="" />
                                                                <?php if ($taxb === 1 && $defaultTax !== '') { ?>
                                                                    <input type="hidden" name="newitems[<?php echo $idx; ?>][taxname][]" value="<?php echo html_escape($defaultTax); ?>" />
                                                                    <span class="label label-info">Tax</span>
                                                                <?php } else { ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                        ++$idx;
                                                    }
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-muted">Tax is applied per line when the taxable box is checked, using the default VAT tax from your tax settings.</p>
                            <?php } ?>
                            <button type="submit" class="btn btn-primary" <?php echo empty($populate) ? 'disabled' : ''; ?>>Save proforma (draft)</button>
                            <?php echo form_close(); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>

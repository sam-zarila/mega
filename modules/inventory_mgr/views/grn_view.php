<?php defined('BASEPATH') or exit('No direct script access allowed');

$grn = isset($grn) ? $grn : null;
if (!$grn) {
    show_404();
}
$lines = isset($grn->lines) && is_array($grn->lines) ? $grn->lines : [];
$wacImpact = 0;
foreach ($lines as $ln) {
    $b = (float) ($ln['wac_before'] ?? 0);
    $a = (float) ($ln['wac_after'] ?? 0);
    if (abs($a - $b) > 0.0001) {
        $wacImpact++;
    }
}
$receivedBy = isset($received_by_name) ? (string) $received_by_name : '';

init_head();
?>
<style>
.grn-wac-arrow { white-space: nowrap; }
.grn-wac-up { color: #f39c12; font-weight: 600; }
.grn-wac-down { color: #27ae60; font-weight: 600; }
.grn-wac-flat { color: #95a5a6; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/grn'); ?>" class="btn btn-default">Back to list</a>
            </div>
        </div>

        <div class="row mtop20">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row mbot15">
                            <div class="col-sm-6">
                                <p class="text-muted mbot0">GRN Ref</p>
                                <p><strong><?php echo html_escape($grn->grn_ref ?? ''); ?></strong></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted mbot0">Warehouse</p>
                                <p><strong><?php echo html_escape($grn->warehouse_name ?? ''); ?></strong></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted mbot0">Supplier</p>
                                <p><?php echo html_escape($grn->supplier_name ?? '—'); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted mbot0">Date Received</p>
                                <p><?php echo html_escape($grn->received_at ?? ''); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted mbot0">Status</p>
                                <p><span class="label label-default"><?php echo html_escape($grn->status ?? ''); ?></span></p>
                            </div>
                        </div>

                        <?php if ($wacImpact > 0) { ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-balance-scale"></i>
                                This receipt updated Weighted Average Cost for <?php echo (int) $wacImpact; ?> item<?php echo $wacImpact === 1 ? '' : 's'; ?>.
                            </div>
                        <?php } ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th class="text-right">Qty Received</th>
                                        <th class="text-right">Unit Price</th>
                                        <th class="text-right">Line Total</th>
                                        <th class="text-right">WAC Before</th>
                                        <th class="text-right">WAC After</th>
                                        <th class="text-right">Stock Before</th>
                                        <th class="text-right">Stock After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lines as $ln) {
                                        $wb = (float) ($ln['wac_before'] ?? 0);
                                        $wa = (float) ($ln['wac_after'] ?? 0);
                                        $cls = 'grn-wac-flat';
                                        if ($wa > $wb + 0.0001) {
                                            $cls = 'grn-wac-up';
                                        } elseif ($wa < $wb - 0.0001) {
                                            $cls = 'grn-wac-down';
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-mono small"><?php echo html_escape($ln['item_code'] ?? ''); ?></td>
                                            <td><?php echo html_escape($ln['item_name'] ?? ''); ?></td>
                                            <td><?php echo html_escape($ln['unit_symbol'] ?? ''); ?></td>
                                            <td class="text-right"><?php echo inv_mgr_format_qty((float) ($ln['qty_received'] ?? 0)); ?></td>
                                            <td class="text-right"><?php echo number_format((float) ($ln['unit_price'] ?? 0), 4); ?></td>
                                            <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($ln['line_total'] ?? 0)); ?></td>
                                            <td class="text-right grn-wac-arrow <?php echo $cls; ?>"><?php echo inv_mgr_format_mwk($wb); ?></td>
                                            <td class="text-right grn-wac-arrow <?php echo $cls; ?>">
                                                <span class="text-muted">→</span> <?php echo inv_mgr_format_mwk($wa); ?>
                                            </td>
                                            <td class="text-right"><?php echo inv_mgr_format_qty((float) ($ln['stock_before'] ?? 0)); ?></td>
                                            <td class="text-right"><?php echo inv_mgr_format_qty((float) ($ln['stock_after'] ?? 0)); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>Total Receipt Value:</strong></td>
                                        <td class="text-right"><strong><?php echo inv_mgr_format_mwk((float) ($grn->total_cost_value ?? 0)); ?></strong></td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><strong>GRN information</strong></div>
                    <div class="panel-body">
                        <p><strong>Received By</strong><br><?php echo html_escape($receivedBy !== '' ? $receivedBy : ('Staff #' . (int) ($grn->received_by ?? 0))); ?></p>
                        <p><strong>Date</strong><br><?php echo html_escape($grn->received_at ?? ''); ?></p>
                        <p><strong>Warehouse</strong><br><?php echo html_escape($grn->warehouse_name ?? ''); ?></p>
                        <hr>
                        <p><strong>Total Items</strong><br><?php echo count($lines); ?></p>
                        <p><strong>Total Value</strong><br><?php echo inv_mgr_format_mwk((float) ($grn->total_cost_value ?? 0)); ?></p>
                        <?php if (!empty($grn->po_ref)) { ?>
                            <p><strong>PO Reference</strong><br><?php echo html_escape($grn->po_ref); ?></p>
                        <?php } ?>
                        <?php if (!empty($grn->supplier_ref)) { ?>
                            <p><strong>Supplier Ref</strong><br><?php echo html_escape($grn->supplier_ref); ?></p>
                        <?php } ?>
                        <?php if (!empty($grn->notes)) { ?>
                            <p><strong>Notes</strong><br><?php echo nl2br(html_escape($grn->notes)); ?></p>
                        <?php } ?>
                        <button type="button" class="btn btn-default btn-block mtop10" onclick="window.print();">
                            <i class="fa fa-print"></i> Print GRN
                        </button>
                        <p class="text-muted small mtop10">Use your browser print dialog to save as PDF if needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

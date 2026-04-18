<?php defined('BASEPATH') or exit('No direct script access allowed');

$item = isset($item) ? $item : null;
if (!$item) {
    show_404();
}

$stocks = isset($item->stock_by_warehouse) && is_array($item->stock_by_warehouse) ? $item->stock_by_warehouse : [];
$movements = isset($item->recent_movements) ? $item->recent_movements : [];
$grnHist = isset($item->recent_grn) ? $item->recent_grn : [];

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/items'); ?>" class="btn btn-default">Back</a>
                <a href="<?php echo admin_url('inventory_mgr/edit_item/' . (int) $item->id); ?>" class="btn btn-info">Edit</a>
                <?php if (function_exists('is_admin') && is_admin() && (float) ($item->total_stock ?? 0) <= 0.0001) { ?>
                    <?php echo form_open(admin_url('inventory_mgr/delete_item/' . (int) $item->id), ['style' => 'display:inline', 'onsubmit' => "return confirm('Delete this item permanently?');"]); ?>
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <?php echo form_close(); ?>
                <?php } ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Details</div>
            <div class="panel-body">
                <p><strong>Code:</strong> <?php echo html_escape($item->commodity_code ?? ''); ?></p>
                <p><strong>WAC:</strong> <?php echo inv_mgr_format_mwk((float) ($item->purchase_price ?? 0)); ?></p>
                <p><strong>Sell:</strong> <?php echo inv_mgr_format_mwk((float) ($item->rate ?? 0)); ?></p>
                <p><strong>Total stock:</strong> <?php echo inv_mgr_format_qty((float) ($item->total_stock ?? 0)); ?></p>
                <?php if (isset($item->reorder_level) && $item->reorder_level !== null) { ?>
                    <p><strong>Reorder level:</strong> <?php echo inv_mgr_format_qty((float) $item->reorder_level); ?></p>
                <?php } ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Stock by warehouse</div>
            <div class="panel-body">
                <table class="table table-bordered">
                    <thead><tr><th>Warehouse ID</th><th class="text-right">Qty</th></tr></thead>
                    <tbody>
                        <?php foreach ($stocks as $s) { ?>
                            <tr>
                                <td><?php echo (int) ($s['warehouse_id'] ?? 0); ?></td>
                                <td class="text-right"><?php echo inv_mgr_format_qty((float) ($s['qty'] ?? 0)); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if (empty($stocks)) { ?>
                            <tr><td colspan="2" class="text-muted">No stock rows.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Recent movements</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Type</th>
                                <th class="text-right">Qty Δ</th>
                                <th class="text-right">WAC</th>
                                <th>Ref</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $m) { ?>
                                <tr>
                                    <td><?php echo html_escape($m->performed_at ?? ''); ?></td>
                                    <td><?php echo html_escape($m->movement_type ?? ''); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_qty((float) ($m->qty_change ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($m->wac_at_movement ?? 0)); ?></td>
                                    <td><?php echo html_escape($m->rel_ref ?? ''); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Recent GRN lines</div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr><th>GRN</th><th>Date</th><th class="text-right">Qty</th><th class="text-right">WAC before</th><th class="text-right">WAC after</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grnHist as $g) { ?>
                            <tr>
                                <td><?php echo html_escape($g->grn_ref ?? ''); ?></td>
                                <td><?php echo html_escape($g->received_at ?? ''); ?></td>
                                <td class="text-right"><?php echo inv_mgr_format_qty((float) ($g->qty_received ?? 0)); ?></td>
                                <td class="text-right"><?php echo number_format((float) ($g->wac_before ?? 0), 4); ?></td>
                                <td class="text-right"><?php echo number_format((float) ($g->wac_after ?? 0), 4); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<?php defined('BASEPATH') or exit('No direct script access allowed');

$movements  = isset($movements) && is_array($movements) ? $movements : [];
$filters    = isset($filters) && is_array($filters) ? $filters : [];
$warehouses = isset($warehouses) && is_array($warehouses) ? $warehouses : [];

$types = ['', 'grn', 'issue', 'adjustment_in', 'adjustment_out', 'transfer_out', 'transfer_in', 'opening_balance', 'stock_count_adj'];

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><i class="fa fa-history"></i> <?php echo html_escape($title); ?></h4>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open(admin_url('inventory_mgr/movements'), ['method' => 'get']); ?>
                <div class="row">
                    <div class="col-md-2">
                        <label>Item ID</label>
                        <input type="number" name="item_id" class="form-control" value="<?php echo html_escape((string) ($filters['item_id'] ?? '')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Warehouse</label>
                        <select name="warehouse_id" class="form-control">
                            <option value="">—</option>
                            <?php foreach ($warehouses as $w) {
                                $wid = (int) ($w['warehouse_id'] ?? 0);
                                $sel = !empty($filters['warehouse_id']) && (int) $filters['warehouse_id'] === $wid ? 'selected' : ''; ?>
                                <option value="<?php echo $wid; ?>" <?php echo $sel; ?>><?php echo html_escape($w['warehouse_name'] ?? ''); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Type</label>
                        <select name="movement_type" class="form-control">
                            <?php foreach ($types as $t) {
                                $lab = $t === '' ? '—' : $t;
                                $sel = isset($filters['movement_type']) && (string) $filters['movement_type'] === $t ? 'selected' : ''; ?>
                                <option value="<?php echo html_escape($t); ?>" <?php echo $sel; ?>><?php echo html_escape($lab); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo html_escape($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo html_escape($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-default">Filter</button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Type</th>
                                <th>Item</th>
                                <th>WH</th>
                                <th class="text-right">Qty Δ</th>
                                <th class="text-right">Value</th>
                                <th>Ref</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $m) {
                                $fn = trim(($m['staff_firstname'] ?? '') . ' ' . ($m['staff_lastname'] ?? '')); ?>
                                <tr>
                                    <td><?php echo html_escape($m['performed_at'] ?? ''); ?></td>
                                    <td><?php echo html_escape($m['movement_type'] ?? ''); ?></td>
                                    <td><?php echo html_escape($m['commodity_code'] ?? ''); ?> <?php echo html_escape($m['item_name'] ?? ''); ?></td>
                                    <td><?php echo html_escape($m['warehouse_name'] ?? ''); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_qty((float) ($m['qty_change'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($m['value_change'] ?? 0)); ?></td>
                                    <td><?php echo html_escape($m['rel_ref'] ?? ''); ?></td>
                                    <td><small><?php echo html_escape($fn); ?></small></td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($movements)) { ?>
                                <tr><td colspan="8" class="text-center text-muted">No movements.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

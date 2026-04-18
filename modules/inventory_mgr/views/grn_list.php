<?php defined('BASEPATH') or exit('No direct script access allowed');

$grns       = isset($grns) && is_array($grns) ? $grns : [];
$filters    = isset($filters) && is_array($filters) ? $filters : [];
$warehouses = isset($warehouses) && is_array($warehouses) ? $warehouses : [];

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><i class="fa fa-truck"></i> <?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/add_grn'); ?>" class="btn btn-info pull-right mbot15"><i class="fa fa-plus"></i> New GRN</a>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open(admin_url('inventory_mgr/grn'), ['method' => 'get']); ?>
                <div class="row">
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
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['' => '—', 'draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled'] as $k => $lab) {
                                $sel = isset($filters['status']) && (string) $filters['status'] === (string) $k ? 'selected' : ''; ?>
                                <option value="<?php echo html_escape($k); ?>" <?php echo $sel; ?>><?php echo html_escape($lab); ?></option>
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
                    <div class="col-md-3">
                        <label>Supplier</label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo html_escape($filters['supplier_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-default">Filter</button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Warehouse</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th class="text-right">Lines</th>
                            <th class="text-right">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grns as $g) {
                            $gid = (int) ($g['id'] ?? 0); ?>
                            <tr>
                                <td><?php echo html_escape($g['grn_ref'] ?? ''); ?></td>
                                <td><?php echo html_escape($g['warehouse_name'] ?? ''); ?></td>
                                <td><?php echo html_escape($g['received_at'] ?? ''); ?></td>
                                <td><?php echo html_escape($g['status'] ?? ''); ?></td>
                                <td class="text-right"><?php echo (int) ($g['total_qty_lines'] ?? 0); ?></td>
                                <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($g['total_cost_value'] ?? 0)); ?></td>
                                <td><a class="btn btn-default btn-xs" href="<?php echo admin_url('inventory_mgr/view_grn/' . $gid); ?>">View</a></td>
                            </tr>
                        <?php } ?>
                        <?php if (empty($grns)) { ?>
                            <tr><td colspan="7" class="text-center text-muted">No GRNs found.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

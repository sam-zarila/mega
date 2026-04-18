<?php defined('BASEPATH') or exit('No direct script access allowed');

$warehouses = isset($warehouses) && is_array($warehouses) ? $warehouses : [];

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/adjustments'); ?>" class="btn btn-default">Back</a>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open(admin_url('inventory_mgr/add_adjustment')); ?>
                <div class="row">
                    <div class="col-md-3">
                        <label>Warehouse *</label>
                        <select name="warehouse_id" class="form-control" required>
                            <option value="">—</option>
                            <?php foreach ($warehouses as $w) {
                                $wid = (int) ($w['warehouse_id'] ?? 0); ?>
                                <option value="<?php echo $wid; ?>"><?php echo html_escape($w['warehouse_name'] ?? ''); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Type *</label>
                        <select name="adj_type" class="form-control" required>
                            <option value="write_off">Write off (shortage)</option>
                            <option value="write_up">Write up (surplus)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                </div>

                <hr>
                <h5>Lines</h5>
                <?php for ($i = 0; $i < 5; $i++) { ?>
                <div class="row mtop10">
                    <div class="col-md-3">
                        <input type="number" name="lines[<?php echo $i; ?>][item_id]" class="form-control" placeholder="Item ID">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.001" name="lines[<?php echo $i; ?>][qty_adjust]" class="form-control" placeholder="Qty adjust">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="lines[<?php echo $i; ?>][reason_notes]" class="form-control" placeholder="Line notes">
                    </div>
                </div>
                <?php } ?>

                <div class="mtop20">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

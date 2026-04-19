<?php defined('BASEPATH') or exit('No direct script access allowed');

$warehouses       = isset($warehouses) && is_array($warehouses) ? $warehouses : [];
$prefill_item_id  = isset($prefill_item_id) ? (int) $prefill_item_id : 0;
$supplier_history = isset($supplier_history) && is_array($supplier_history) ? $supplier_history : [];

init_head();
?>
<script type="application/json" id="grn-supplier-history-data"><?php echo json_encode($supplier_history); ?></script>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/grn'); ?>" class="btn btn-default">Back</a>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open(admin_url('inventory_mgr/add_grn'), [
                    'id'                 => 'grn-form',
                    'data-prefill-item' => (string) (int) $prefill_item_id,
                ]); ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Supplier Name</label>
                            <input type="text" name="supplier_name" id="grn-supplier-name" class="form-control" autocomplete="off" placeholder="Supplier">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Supplier Reference / Invoice No</label>
                            <input type="text" name="supplier_ref" class="form-control" placeholder="Invoice / ref">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Receiving Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="grn-warehouse-id" class="form-control" required>
                                <option value="">— Select —</option>
                                <?php foreach ($warehouses as $w) {
                                    $wid = (int) ($w['warehouse_id'] ?? 0); ?>
                                    <option value="<?php echo $wid; ?>"><?php echo html_escape($w['warehouse_name'] ?? ''); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" name="received_at" class="form-control" required value="<?php echo html_escape(date('Y-m-d')); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">PO Reference</label>
                            <input type="text" name="po_ref" class="form-control" placeholder="Optional — link to purchase order">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="control-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Notes"></textarea>
                        </div>
                    </div>
                </div>

                <h5 class="mtop20">Items to Receive</h5>
                <p class="text-muted mbot15">
                    Rows appear here after you choose a match from the <strong>search box below</strong> (not from typing directly in the table).
                    Order: set <strong>Receiving Warehouse</strong> above first, then search by code or name, then enter quantity and cost on each line.
                </p>
                <div class="table-responsive">
                    <table id="grn-items-table" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th class="text-right">Current WAC</th>
                                <th class="text-right">Current Stock</th>
                                <th class="text-right">Qty Receiving <span class="text-danger">*</span></th>
                                <th class="text-right">Unit Price (Cost) <span class="text-danger">*</span></th>
                                <th class="text-right">Line Total</th>
                                <th class="text-right">New WAC (Preview)</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="grn-items-body">
                            <tr class="grn-empty-row">
                                <td colspan="10" class="text-center text-muted" style="padding:20px">
                                    <i class="fa fa-arrow-down"></i> No lines yet — use the search field <strong>under this table</strong>, pick an item, then fill Qty and Unit Price on the new row.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-right"><strong>Total Receipt Value:</strong></td>
                                <td colspan="2"><strong id="grn-total-value"><?php echo inv_mgr_format_mwk(0); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="well mtop20">
                    <label class="control-label">Add a line — search by item code or name</label>
                    <p class="text-muted small mtop5 mbot10">Requires a warehouse selected above. Type 2+ characters, click a result to add one row; repeat for more items.</p>
                    <input type="text" id="grn-item-search" class="form-control" placeholder="Type at least 2 characters, then choose a match from the list" autocomplete="off">
                </div>

                <div class="mtop20">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-check"></i> Post GRN
                    </button>
                </div>
                <p class="text-muted mtop10">
                    Posting will immediately update stock quantities and recalculate Weighted Average Costs.
                </p>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<?php defined('BASEPATH') or exit('No direct script access allowed');

$edit          = !empty($edit);
$item          = $edit && isset($item) ? $item : null;
$categories    = isset($categories) && is_array($categories) ? $categories : [];
$units         = isset($units) && is_array($units) ? $units : [];
$warehouses    = isset($warehouses) && is_array($warehouses) ? $warehouses : [];
$item_groups   = isset($item_groups) && is_array($item_groups) ? $item_groups : [];
$has_barcode   = !empty($has_barcode);
$has_active    = !empty($has_active);

$url = $edit ? admin_url('inventory_mgr/edit_item/' . (int) ($item->id ?? 0)) : admin_url('inventory_mgr/add_item');
$title = isset($title) ? $title : ($edit ? ('Edit: ' . ($item->description ?? 'Item')) : 'Add New Inventory Item');

$csrf = [
    'name' => $this->security->get_csrf_token_name(),
    'hash' => $this->security->get_csrf_hash(),
];

init_head();
?>
<style>
.inv-mini-help { font-size: 12px; color: #777; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?= html_escape($title); ?></h4>
                <a href="<?= admin_url('inventory_mgr/items'); ?>" class="btn btn-default pull-right">Cancel</a>
            </div>
        </div>

        <?= form_open($url, ['id' => 'inv-item-form']); ?>

        <div class="row">
            <div class="col-md-7">

                <div class="panel_s">
                    <div class="panel-heading">Item Details</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Item Code *</label>
                            <div class="input-group">
                                <input type="text" name="commodity_code" id="commodity_code" class="form-control" required
                                    pattern="[^\s]+" title="No spaces"
                                    value="<?= html_escape($item->commodity_code ?? ''); ?>">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="generate-code">Generate Code</button>
                                </span>
                            </div>
                            <p class="inv-mini-help mtop5">Unique code, no spaces.</p>
                        </div>
                        <div class="form-group">
                            <label>Item Name *</label>
                            <input type="text" name="description" class="form-control" required value="<?= html_escape($item->description ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <div class="input-group">
                                <select name="commodity_type" id="commodity_type" class="form-control">
                                    <option value="">—</option>
                                    <?php foreach ($categories as $c) {
                                        $cid = (int) ($c['commodity_type_id'] ?? 0);
                                        $sel = $item && (int) ($item->commodity_type ?? 0) === $cid ? 'selected' : ''; ?>
                                        <option value="<?= $cid; ?>" <?= $sel; ?>><?= html_escape($c['commondity_name'] ?? ''); ?></option>
                                    <?php } ?>
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modal-add-category">+ Add Category</button>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Unit of Measure *</label>
                            <div class="input-group">
                                <select name="unit_id" id="unit_id" class="form-control" required>
                                    <option value="">—</option>
                                    <?php foreach ($units as $u) {
                                        $uid = (int) ($u['unit_type_id'] ?? 0);
                                        $un  = html_escape($u['unit_name'] ?? '');
                                        $sym = isset($u['unit_symbol']) && (string) $u['unit_symbol'] !== '' ? ' (' . html_escape((string) $u['unit_symbol']) . ')' : '';
                                        $sel = $item && (int) ($item->unit_id ?? 0) === $uid ? 'selected' : ''; ?>
                                        <option value="<?= $uid; ?>" <?= $sel; ?>><?= $un . $sym; ?></option>
                                    <?php } ?>
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modal-add-unit">+ Add Unit</button>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Item Group</label>
                            <select name="group_id" class="form-control">
                                <option value="0">—</option>
                                <?php foreach ($item_groups as $g) {
                                    $gid = (int) ($g['id'] ?? 0);
                                    $sel = $item && (int) ($item->group_id ?? 0) === $gid ? 'selected' : ''; ?>
                                    <option value="<?= $gid; ?>" <?= $sel; ?>><?= html_escape($g['name'] ?? ''); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description / Notes</label>
                            <textarea name="long_description" class="form-control" rows="3"><?= html_escape($item->long_description ?? ''); ?></textarea>
                        </div>
                        <?php if ($has_barcode) { ?>
                        <div class="form-group">
                            <label>Barcode</label>
                            <input type="text" name="commodity_barcode" class="form-control" value="<?= html_escape($item->commodity_barcode ?? ''); ?>">
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">Pricing</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Opening WAC / Cost Price * (MWK)</label>
                            <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" required
                                value="<?= html_escape((string) ($item->purchase_price ?? '')); ?>">
                            <p class="inv-mini-help mtop5">Weighted Average Cost — recalculated automatically each time stock is received via GRN.</p>
                        </div>
                        <div class="form-group">
                            <label>Selling Price (MWK)</label>
                            <input type="number" step="0.01" name="rate" id="rate" class="form-control"
                                value="<?= html_escape((string) ($item->rate ?? '')); ?>">
                            <p class="inv-mini-help mtop5">Used in quotation pricing. Separate from cost price.</p>
                        </div>
                    </div>
                </div>

                <?php if (!$edit) { ?>
                <div class="panel_s">
                    <div class="panel-heading">Opening Stock</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Opening Stock Warehouse</label>
                            <select name="opening_warehouse_id" class="form-control">
                                <option value="">—</option>
                                <?php foreach ($warehouses as $w) {
                                    $wid = (int) ($w['warehouse_id'] ?? 0); ?>
                                    <option value="<?= $wid; ?>"><?= html_escape($w['warehouse_name'] ?? ('WH ' . $wid)); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Opening Quantity</label>
                            <input type="number" step="0.001" name="opening_qty" class="form-control" value="">
                        </div>
                        <p class="inv-mini-help">A stock receipt (opening balance movement) will be posted for this quantity.</p>
                    </div>
                </div>
                <?php } ?>

            </div>

            <div class="col-md-5">

                <div class="panel_s">
                    <div class="panel-heading">Inventory Control</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Reorder Level</label>
                            <input type="number" step="0.001" name="reorder_level" class="form-control"
                                value="<?= $edit && isset($item->reorder_level) ? html_escape((string) $item->reorder_level) : ''; ?>">
                            <p class="inv-mini-help mtop5">Alerts Store Manager when total stock falls at or below this level.</p>
                        </div>
                        <div class="form-group">
                            <label>Default Warehouse</label>
                            <select name="default_warehouse_id" class="form-control">
                                <option value="">—</option>
                                <?php foreach ($warehouses as $w) {
                                    $wid = (int) ($w['warehouse_id'] ?? 0);
                                    $sel = $item && (int) ($item->warehouse_id ?? 0) === $wid ? 'selected' : ''; ?>
                                    <option value="<?= $wid; ?>" <?= $sel; ?>><?= html_escape($w['warehouse_name'] ?? ('WH ' . $wid)); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php if ($has_active) {
                            $act = !$edit || (isset($item->active) && (int) $item->active === 1); ?>
                        <input type="hidden" name="active" value="0">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="active" value="1" <?= $act ? 'checked' : ''; ?>> Active
                            </label>
                        </div>
                        <?php } else { ?>
                            <input type="hidden" name="active" value="1">
                        <?php } ?>
                    </div>
                </div>

                <?php if ($edit && $item) {
                    $stocks = isset($item->stock_by_warehouse) && is_array($item->stock_by_warehouse) ? $item->stock_by_warehouse : [];
                    $q1 = 0.0;
                    $q2 = 0.0;
                    foreach ($stocks as $s) {
                        if ((int) ($s['warehouse_id'] ?? 0) === 1) {
                            $q1 = (float) ($s['qty'] ?? 0);
                        }
                        if ((int) ($s['warehouse_id'] ?? 0) === 2) {
                            $q2 = (float) ($s['qty'] ?? 0);
                        }
                    }
                    $usym = html_escape($item->unit_symbol ?? '');
                    $tot = (float) ($item->total_stock ?? 0);
                    ?>
                <div class="panel_s">
                    <div class="panel-heading">Stock Status</div>
                    <div class="panel-body">
                        <p><strong>Blantyre:</strong> <?= inv_mgr_format_qty($q1); ?> <?= $usym; ?></p>
                        <p><strong>Lilongwe:</strong> <?= inv_mgr_format_qty($q2); ?> <?= $usym; ?></p>
                        <p><strong>Total:</strong> <?= inv_mgr_format_qty($tot); ?> <?= $usym; ?></p>
                        <p><strong>WAC:</strong> <?= inv_mgr_format_mwk((float) ($item->purchase_price ?? 0)); ?></p>
                        <a href="<?= admin_url('inventory_mgr/movements?item_id=' . (int) $item->id); ?>" class="btn btn-default btn-sm">View Full History</a>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">Quick Actions</div>
                    <div class="panel-body">
                        <a href="<?= admin_url('inventory_mgr/add_grn?item_id=' . (int) $item->id); ?>" class="btn btn-info btn-block mbot10"><i class="fa fa-truck"></i> Receive Stock (GRN)</a>
                        <a href="<?= admin_url('inventory_mgr/add_adjustment'); ?>" class="btn btn-default btn-block mbot10">Make Adjustment</a>
                        <a href="<?= admin_url('inventory_mgr/movements?item_id=' . (int) $item->id); ?>" class="btn btn-default btn-block">View Movements</a>
                    </div>
                </div>
                <?php } ?>

            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mtop15 mbot30">
                <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Save Item</button>
                <button type="submit" name="save_and_add" value="1" class="btn btn-default">Save &amp; Add Another</button>
                <a href="<?= admin_url('inventory_mgr/items'); ?>" class="btn btn-link">Cancel</a>
            </div>
        </div>

        <?= form_close(); ?>

    </div>
</div>

<div class="modal fade" id="modal-add-category" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add Category</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" class="form-control" id="cat-name">
        </div>
        <div class="form-group">
          <label>Code</label>
          <input type="text" class="form-control" id="cat-code">
        </div>
        <p class="text-danger mtop10" id="cat-err" style="display:none;"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="cat-save">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-add-unit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add Unit</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" class="form-control" id="unit-name">
        </div>
        <div class="form-group">
          <label>Code</label>
          <input type="text" class="form-control" id="unit-code">
        </div>
        <div class="form-group">
          <label>Symbol</label>
          <input type="text" class="form-control" id="unit-symbol">
        </div>
        <p class="text-danger mtop10" id="unit-err" style="display:none;"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="unit-save">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
(function ($) {
  var urlAjaxCode = <?= json_encode(admin_url('inventory_mgr/ajax_next_item_code')); ?>;
  var urlQuickCat = <?= json_encode(admin_url('inventory_mgr/quick_add_category')); ?>;
  var urlQuickUnit = <?= json_encode(admin_url('inventory_mgr/quick_add_unit')); ?>;
  var csrfName = <?= json_encode($csrf['name']); ?>;
  var csrfHash = <?= json_encode($csrf['hash']); ?>;

  $('#generate-code').on('click', function () {
    $.get(urlAjaxCode, function (res) {
      if (res && res.next_code) {
        $('#commodity_code').val(res.next_code);
      }
    }, 'json');
  });

  $('#purchase_price').on('change', function () {
    var cost = parseFloat($(this).val()) || 0;
    var currentSell = parseFloat($('#rate').val()) || 0;
    if (currentSell === 0 || currentSell < cost) {
      $('#rate').val((cost * 1.25).toFixed(2));
    }
  });

  function postQuick(url, data, onOk) {
    data[csrfName] = csrfHash;
    $.post(url, data, function (res) {
      if (res && res.success) {
        if (res.csrf_hash) { csrfHash = res.csrf_hash; }
        onOk(res);
      } else {
        alert(res && res.message ? res.message : 'Save failed');
      }
    }, 'json').fail(function () { alert('Request failed'); });
  }

  $('#cat-save').on('click', function () {
    $('#cat-err').hide();
    postQuick(urlQuickCat, {
      commondity_name: $('#cat-name').val(),
      commondity_code: $('#cat-code').val()
    }, function (res) {
      var $sel = $('#commodity_type');
      $sel.append($('<option/>', { value: res.id, text: res.label, selected: true }));
      $('#modal-add-category').modal('hide');
      $('#cat-name,#cat-code').val('');
    });
  });

  $('#unit-save').on('click', function () {
    $('#unit-err').hide();
    postQuick(urlQuickUnit, {
      unit_name: $('#unit-name').val(),
      unit_code: $('#unit-code').val(),
      unit_symbol: $('#unit-symbol').val()
    }, function (res) {
      var $sel = $('#unit_id');
      $sel.append($('<option/>', { value: res.id, text: res.label, selected: true }));
      $('#modal-add-unit').modal('hide');
      $('#unit-name,#unit-code,#unit-symbol').val('');
    });
  });
})(jQuery);
</script>
<?php init_tail(); ?>

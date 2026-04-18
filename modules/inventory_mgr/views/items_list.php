<?php defined('BASEPATH') or exit('No direct script access allowed');

$items           = isset($items) && is_array($items) ? $items : [];
$low_stock_items = isset($low_stock_items) && is_array($low_stock_items) ? $low_stock_items : [];
$filters         = isset($filters) && is_array($filters) ? $filters : [];
$categories      = isset($categories) && is_array($categories) ? $categories : [];
$warehouses      = isset($warehouses) && is_array($warehouses) ? $warehouses : [];

$totalItems   = count($items);
$totalValue   = 0.0;
$zeroStockCnt = 0;
foreach ($items as $it) {
    $tq = (float) ($it['qty_total'] ?? $it['total_qty'] ?? 0);
    $pp = (float) ($it['purchase_price'] ?? 0);
    $totalValue += $tq * $pp;
    if ($tq <= 0.0001) {
        $zeroStockCnt++;
    }
}
$lowStockCnt = count($low_stock_items);

$whLabel = function ($wid) use ($warehouses) {
    foreach ($warehouses as $w) {
        if ((int) ($w['warehouse_id'] ?? 0) === (int) $wid) {
            return (string) ($w['warehouse_name'] ?? 'WH ' . $wid);
        }
    }

    return $wid === 1 ? 'Blantyre' : ($wid === 2 ? 'Lilongwe' : 'WH ' . (int) $wid);
};

init_head();
?>
<style>
.inv-stat-card .inv-stat-num { font-size: 26px; font-weight: 700; }
.inv-stat-card .inv-stat-label { font-size: 12px; color: #777; text-transform: uppercase; }
.inv-row-zero > td { background-color: #f2dede !important; }
.inv-row-low > td { background-color: #fcf8e3 !important; }
table.dataTable tbody tr.inv-row-zero > td { background-color: #f2dede !important; }
table.dataTable tbody tr.inv-row-low > td { background-color: #fcf8e3 !important; }
.inv-code { font-family: Consolas, Menlo, Monaco, monospace; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><i class="fa fa-cubes"></i> Stock Master</h4>
                <div class="pull-right mbot15">
                    <a href="<?= admin_url('inventory_mgr/add_item'); ?>" class="btn btn-success">
                        <i class="fa fa-plus"></i> Add New Item
                    </a>
                    <a href="<?= admin_url('inventory_mgr/add_grn'); ?>" class="btn btn-info">
                        <i class="fa fa-truck"></i> Receive Stock (GRN)
                    </a>
                </div>
            </div>
        </div>

        <div class="row mbot20">
            <div class="col-md-3 col-sm-6">
                <div class="panel_s inv-stat-card">
                    <div class="panel-body text-center">
                        <div class="inv-stat-label">Total Items</div>
                        <div class="inv-stat-num"><?= (int) $totalItems; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel_s inv-stat-card">
                    <div class="panel-body text-center">
                        <div class="inv-stat-label">Total Inventory Value</div>
                        <div class="inv-stat-num"><?= inv_mgr_format_mwk($totalValue); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel_s inv-stat-card">
                    <div class="panel-body text-center">
                        <div class="inv-stat-label">Low Stock <span class="label label-danger"><?= (int) $lowStockCnt; ?></span></div>
                        <div class="inv-stat-num text-danger"><?= (int) $lowStockCnt; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel_s inv-stat-card">
                    <div class="panel-body text-center">
                        <div class="inv-stat-label">Zero Stock</div>
                        <div class="inv-stat-num"><?= (int) $zeroStockCnt; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($lowStockCnt > 0) { ?>
        <div class="panel_s" style="border-color:#f0ad4e;">
            <div class="panel-heading" style="background:#fcf8e3;border-color:#f0ad4e;">
                <a role="button" data-toggle="collapse" href="#inv-low-stock-alert" aria-expanded="true">
                    ⚠ <?= (int) $lowStockCnt; ?> items are at or below their reorder level
                </a>
                <a href="<?= admin_url('inventory_mgr/items?low_stock=1'); ?>" class="btn btn-xs btn-warning pull-right">View All Low Stock</a>
            </div>
            <div id="inv-low-stock-alert" class="panel-collapse collapse in">
                <div class="panel-body">
                    <p class="text-muted mbot0">Use the filter below or click <strong>View All Low Stock</strong> to show only low-stock lines.</p>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class="panel_s">
            <div class="panel-body">
                <?= form_open(admin_url('inventory_mgr/items'), ['method' => 'get', 'class' => 'form-inline', 'id' => 'inv-filter-form', 'onsubmit' => "document.getElementById('inv-search-hidden').value=document.getElementById('inv-live-search').value;return true;"]); ?>
                <div class="form-group mright10">
                    <label class="mright5">Category</label>
                    <select name="category" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($categories as $c) {
                            $cid = (int) ($c['commodity_type_id'] ?? 0);
                            $sel = isset($filters['category']) && (int) $filters['category'] === $cid ? 'selected' : ''; ?>
                            <option value="<?= $cid; ?>" <?= $sel; ?>><?= html_escape($c['commondity_name'] ?? ''); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group mright10">
                    <label class="mright5">Warehouse</label>
                    <select name="warehouse" class="form-control">
                        <option value="">All</option>
                        <option value="1" <?= isset($filters['warehouse']) && (int) $filters['warehouse'] === 1 ? 'selected' : ''; ?>><?= html_escape($whLabel(1)); ?></option>
                        <option value="2" <?= isset($filters['warehouse']) && (int) $filters['warehouse'] === 2 ? 'selected' : ''; ?>><?= html_escape($whLabel(2)); ?></option>
                    </select>
                </div>
                <div class="checkbox mright10">
                    <label>
                        <input type="checkbox" name="low_stock" value="1" <?= !empty($filters['low_stock_only']) ? 'checked' : ''; ?>> Low stock only
                    </label>
                </div>
                <div class="form-group mright10">
                    <label class="mright5">Search</label>
                    <input type="text" id="inv-live-search" class="form-control" placeholder="Filter table…" autocomplete="off">
                </div>
                <input type="hidden" name="search" id="inv-search-hidden" value="<?= html_escape($filters['search_term'] ?? ''); ?>">
                <button type="submit" class="btn btn-default">Apply</button>
                <?= form_close(); ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inv-items-table" width="100%">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th class="text-right">WAC / Cost</th>
                                <th class="text-right">Sell</th>
                                <th class="text-right"><?= html_escape($whLabel(1)); ?></th>
                                <th class="text-right"><?= html_escape($whLabel(2)); ?></th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Reorder</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it) {
                                $tid  = (int) ($it['id'] ?? 0);
                                $tq   = (float) ($it['qty_total'] ?? $it['total_qty'] ?? 0);
                                $qb   = (float) ($it['qty_blantyre'] ?? 0);
                                $ql   = (float) ($it['qty_lilongwe'] ?? 0);
                                $r1   = isset($it['reorder_wh1']) && $it['reorder_wh1'] !== null ? (float) $it['reorder_wh1'] : null;
                                $r2   = isset($it['reorder_wh2']) && $it['reorder_wh2'] !== null ? (float) $it['reorder_wh2'] : null;
                                $rmin = isset($it['reorder_level']) && $it['reorder_level'] !== null ? (float) $it['reorder_level'] : null;
                                $low  = !empty($it['is_low_flag']);
                                $rowClass = 'inv-row-ok';
                                if ($tq <= 0.0001) {
                                    $rowClass = 'inv-row-zero';
                                } elseif ($low) {
                                    $rowClass = 'inv-row-low';
                                }
                                $redB = $r1 !== null && $r1 > 0 && $qb <= $r1 + 0.0001;
                                $redL = $r2 !== null && $r2 > 0 && $ql <= $r2 + 0.0001;
                                ?>
                            <tr class="<?= $rowClass; ?>" data-item-id="<?= $tid; ?>">
                                <td class="inv-code"><?= html_escape($it['commodity_code'] ?? ''); ?></td>
                                <td>
                                    <strong><a href="<?= admin_url('inventory_mgr/view_item/' . $tid); ?>"><?= html_escape($it['description'] ?? ''); ?></a></strong>
                                </td>
                                <td><span class="label label-default"><?= html_escape($it['category_name'] ?? '—'); ?></span></td>
                                <td><?= html_escape($it['unit_symbol'] ?? ''); ?></td>
                                <td class="text-right"><small class="text-muted"><?= inv_mgr_format_mwk((float) ($it['purchase_price'] ?? 0)); ?></small></td>
                                <td class="text-right"><?= inv_mgr_format_mwk((float) ($it['rate'] ?? 0)); ?></td>
                                <td class="text-right <?= $redB ? 'text-danger' : ''; ?>"><?= inv_mgr_format_qty($qb); ?></td>
                                <td class="text-right <?= $redL ? 'text-danger' : ''; ?>"><?= inv_mgr_format_qty($ql); ?></td>
                                <td class="text-right">
                                    <strong><?= inv_mgr_format_qty($tq); ?></strong>
                                    <?php if ($low && $tq > 0.0001) { ?>
                                        <span class="label label-danger">LOW</span>
                                    <?php } ?>
                                </td>
                                <td class="text-right"><?= $rmin !== null ? inv_mgr_format_qty($rmin) : '—'; ?></td>
                                <td class="text-right text-nowrap">
                                    <a href="<?= admin_url('inventory_mgr/view_item/' . $tid); ?>" class="btn btn-default btn-xs" title="View"><i class="fa fa-eye"></i></a>
                                    <a href="<?= admin_url('inventory_mgr/edit_item/' . $tid); ?>" class="btn btn-default btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                                    <a href="<?= admin_url('inventory_mgr/add_grn?item_id=' . $tid); ?>" class="btn btn-default btn-xs" title="GRN"><i class="fa fa-truck"></i></a>
                                    <?php if (function_exists('is_admin') && is_admin()) {
                                        $canDel = $tq <= 0.0001; ?>
                                        <?= form_open(admin_url('inventory_mgr/delete_item/' . $tid), ['style' => 'display:inline', 'onsubmit' => "return confirm('Delete this item?');"]); ?>
                                        <button type="submit" class="btn btn-default btn-xs text-danger" title="Delete" <?= $canDel ? '' : 'disabled'; ?>><i class="fa fa-trash"></i></button>
                                        <?= form_close(); ?>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
  if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) { return; }
  var table = jQuery('#inv-items-table').DataTable({
    pageLength: 25,
    order: [[1, 'asc']],
    dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    language: typeof app !== 'undefined' && app.lang && app.lang.datatables ? app.lang.datatables : {}
  });
  jQuery('#inv-live-search').on('keyup', function () {
    table.search(this.value).draw();
  });
  var hid = jQuery('#inv-search-hidden');
  if (hid.length && hid.val()) {
    jQuery('#inv-live-search').val(hid.val());
    table.search(hid.val()).draw();
  }
})();
</script>
<?php init_tail(); ?>

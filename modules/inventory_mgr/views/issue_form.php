<?php defined('BASEPATH') or exit('No direct script access allowed');

$job_card    = isset($job_card) ? $job_card : null;
$qt_items    = isset($qt_items) && is_array($qt_items) ? $qt_items : [];
$warehouses  = isset($warehouses) && is_array($warehouses) ? $warehouses : [];
$client_name = isset($client_name) ? (string) $client_name : '';

if (!$job_card) {
    show_404();
}

$statusBadge = '';
if (function_exists('jc_get_status_badge')) {
    $statusBadge = jc_get_status_badge((int) ($job_card->status ?? 0));
}

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <p class="text-muted">
                    <strong>Client:</strong> <?php echo html_escape($client_name !== '' ? $client_name : '—'); ?>
                    <span class="mleft10 mright10">|</span>
                    <strong>Job:</strong> <?php echo html_escape($job_card->jc_ref ?? ''); ?>
                    <span class="mleft10 mright10">|</span>
                    <strong>Status:</strong> <?php echo $statusBadge; ?>
                    <span class="mleft10 mright10">|</span>
                    <strong>Proposal:</strong> <?php echo html_escape($job_card->qt_ref ?? '—'); ?>
                </p>
            </div>
        </div>

        <div class="panel_s mtop15">
            <div class="panel-body">
                <?php echo form_open(admin_url('inventory_mgr/process_issue/' . (int) $job_card->id), ['id' => 'issue-material-form']); ?>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Select the warehouse to issue from. Available stock is shown per warehouse.
                </div>
                <div class="form-group">
                    <label class="control-label">Issue From Warehouse <span class="text-danger">*</span></label>
                    <div class="row">
                        <?php foreach ($warehouses as $wh) {
                            $wid  = (int) ($wh['warehouse_id'] ?? 0);
                            $wname = (string) ($wh['warehouse_name'] ?? '');
                            $wcode = (string) ($wh['warehouse_code'] ?? $wh['code'] ?? '');
                            ?>
                            <div class="col-md-4">
                                <div class="wh-select-card" data-wh="<?php echo $wid; ?>">
                                    <input type="radio" name="warehouse_id" value="<?php echo $wid; ?>"
                                           id="wh_<?php echo $wid; ?>" class="wh-radio" required>
                                    <label for="wh_<?php echo $wid; ?>" class="wh-card-label">
                                        <strong><?php echo html_escape($wname); ?></strong>
                                        <?php if ($wcode !== '') { ?>
                                            <br><small class="text-muted"><?php echo html_escape($wcode); ?></small>
                                        <?php } ?>
                                    </label>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <h5 class="mtop20"><i class="fa fa-clipboard"></i> Items from Approved Quotation</h5>
                <p class="text-muted small">These items were specified in the approved quotation.
                    Quantities shown are what was quoted. Adjust if actual requirement differs.</p>

                <div class="table-responsive">
                    <table class="table table-bordered" id="qt-items-table">
                        <thead class="bg-primary">
                            <tr>
                                <th width="3%" class="text-white"><input type="checkbox" id="check-all-qt" checked></th>
                                <th width="10%" class="text-white">Item Code</th>
                                <th width="25%" class="text-white">Item Name</th>
                                <th width="8%" class="text-white">Unit</th>
                                <th width="10%" class="text-white">Quoted Qty</th>
                                <th width="12%" class="text-white">Available Stock<br><small style="opacity:.85">(selected warehouse)</small></th>
                                <th width="10%" class="text-white">Qty to Issue</th>
                                <th width="12%" class="text-white">WAC / Unit</th>
                                <th width="10%" class="text-white">Issue Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qt_items as $item) {
                                $iid = isset($item->inventory_item_id) ? (int) $item->inventory_item_id : 0;
                                $hasInv = $iid > 0;
                                ?>
                                <tr class="qt-item-row <?php echo $hasInv ? '' : 'no-inventory'; ?>"
                                    data-item-id="<?php echo $iid; ?>"
                                    data-wac="<?php echo htmlspecialchars((string) ($item->wac ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-code="<?php echo htmlspecialchars((string) ($item->commodity_code ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td>
                                        <?php if ($hasInv) { ?>
                                            <input type="checkbox" name="qt_issue_items[]" value="<?php echo $iid; ?>" checked class="qt-check">
                                        <?php } else { ?>
                                            <span title="Not linked to inventory" class="text-muted"><i class="fa fa-chain-broken"></i></span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-mono small"><?php echo html_escape($item->commodity_code ?? '—'); ?></td>
                                    <td>
                                        <?php echo html_escape($item->item_name ?? ''); ?>
                                        <?php if (!$hasInv) { ?>
                                            <br><small class="text-warning">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                Not linked to inventory. Issue manually below.
                                            </small>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo html_escape($item->unit_symbol ?? ''); ?></td>
                                    <td class="text-center"><?php echo inv_mgr_format_qty((float) ($item->quoted_qty ?? 0)); ?></td>
                                    <td class="text-center stock-available-cell" data-item="<?php echo $iid; ?>">
                                        <?php if ($hasInv) { ?>
                                            <span class="loading-stock text-muted"><i class="fa fa-refresh fa-spin"></i> Loading…</span>
                                        <?php } else { ?>—<?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($hasInv) { ?>
                                            <input type="number" step="0.001" min="0"
                                                   name="qt_qty[<?php echo $iid; ?>]"
                                                   class="form-control input-sm qty-input"
                                                   value="<?php echo htmlspecialchars((string) ($item->quoted_qty ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-max-default="<?php echo htmlspecialchars((string) ($item->quoted_qty ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-wac="<?php echo htmlspecialchars((string) ($item->wac ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-item="<?php echo $iid; ?>">
                                            <input type="hidden" name="qt_line_id[<?php echo $iid; ?>]"
                                                   value="<?php echo (int) ($item->qt_line_id ?? 0); ?>">
                                        <?php } else { ?>
                                            <span class="text-muted">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($hasInv) { ?>
                                            <span class="item-wac"><?php echo inv_mgr_format_mwk((float) ($item->wac ?? 0)); ?></span>
                                        <?php } else { ?>—<?php } ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($hasInv) { ?>
                                            <strong>MWK <span class="issue-cost">0.00</span></strong>
                                        <?php } else { ?>
                                            <span class="text-muted">—</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($qt_items)) { ?>
                                <tr><td colspan="9" class="text-muted">No quotation lines for this job.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <h5 class="mtop20"><i class="fa fa-plus-circle"></i> Additional Items from Inventory</h5>
                <p class="text-muted small">Add items not listed in the quotation that are needed for this job.</p>

                <div class="table-responsive">
                    <table class="table table-condensed table-bordered" id="extra-items-table">
                        <thead>
                            <tr>
                                <th style="min-width:220px">Item Search</th>
                                <th>Code</th>
                                <th>Unit</th>
                                <th class="text-center">Available<br><small class="text-muted">(selected WH)</small></th>
                                <th>Qty to Issue</th>
                                <th class="text-right">WAC</th>
                                <th class="text-right">Cost</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="extra-items-body"></tbody>
                    </table>
                </div>
                <button type="button" id="add-extra-item-btn" class="btn btn-default btn-sm">
                    <i class="fa fa-search"></i> Search &amp; Add Item from Inventory
                </button>

                <div id="stock-issues-panel" class="alert alert-danger hide mtop15">
                    <strong><i class="fa fa-exclamation-triangle"></i> Stock Issues Found:</strong>
                    <ul id="stock-issues-list"></ul>
                    <p class="mtop5">You can still proceed with reduced quantities, or issue from a different warehouse.</p>
                </div>

                <div class="panel panel-default mtop20" id="issue-cost-panel">
                    <div class="panel-heading"><strong>Issue Summary</strong></div>
                    <div class="panel-body">
                        <table class="table table-condensed table-borderless" style="max-width:400px;margin-left:auto">
                            <tr>
                                <td>Items from Quotation:</td>
                                <td class="text-right" id="qt-cost-total"><?php echo inv_mgr_format_mwk(0); ?></td>
                            </tr>
                            <tr>
                                <td>Additional Items:</td>
                                <td class="text-right" id="extra-cost-total"><?php echo inv_mgr_format_mwk(0); ?></td>
                            </tr>
                            <tr class="active">
                                <td><strong>Total Issue Cost (at WAC):</strong></td>
                                <td class="text-right"><strong id="grand-issue-total"><?php echo inv_mgr_format_mwk(0); ?></strong></td>
                            </tr>
                        </table>
                        <p class="text-muted small">
                            <i class="fa fa-info-circle"></i>
                            Cost calculated at Weighted Average Cost at time of issue.
                            This will be used for WIP/COGS accounting entries.
                        </p>
                    </div>
                </div>

                <div class="row mtop20">
                    <div class="col-md-6">
                        <a href="<?php echo admin_url('job_cards/view/' . (int) $job_card->id); ?>" class="btn btn-default">Cancel</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" id="validate-btn" class="btn btn-warning">
                            <i class="fa fa-check-circle"></i> Validate Stock
                        </button>
                        <button type="submit" id="issue-btn" class="btn btn-success btn-lg" disabled>
                            <i class="fa fa-cubes"></i> Confirm Issue Materials
                        </button>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

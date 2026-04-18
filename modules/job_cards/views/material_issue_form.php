<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php echo form_open(admin_url('job_cards/create_material_issue/' . (int) $job_card->id), ['id' => 'jc-material-issue-form']); ?>

                <h4>Issue Materials — <?php echo e($job_card->jc_ref); ?></h4>
                <p class="text-muted">
                    Proposal: <?php echo e($job_card->qt_ref); ?> |
                    Client: <?php echo e($client->company ?? 'Unknown Client'); ?> |
                    Job Type: <?php echo e($job_card->job_type); ?>
                </p>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Issue From Warehouse <span class="required">*</span></label>
                            <select name="warehouse_id" class="form-control selectpicker" required data-width="100%">
                                <option value="">-- Select Warehouse --</option>
                                <?php if (!empty($warehouses) && is_array($warehouses)) {
                                    foreach ($warehouses as $wh) {
                                        $id   = isset($wh['warehouse_id']) ? (int) $wh['warehouse_id'] : (int) ($wh['id'] ?? 0);
                                        $name = $wh['warehouse_name'] ?? ($wh['name'] ?? ('Warehouse #' . $id));
                                        if ($id < 1) {
                                            continue;
                                        } ?>
                                        <option value="<?php echo $id; ?>"><?php echo e($name); ?></option>
                                    <?php }
                                } else { ?>
                                    <option value="1">Blantyre Main Warehouse</option>
                                    <option value="2">Lilongwe Branch Warehouse</option>
                                <?php } ?>
                            </select>
                        </div>

                        <p class="text-info">
                            <i class="fa fa-info-circle"></i>
                            Items below are pulled from the approved quotation. Verify quantities
                            and enter the actual amount being issued. Items without an inventory link
                            are shown for reference only and cannot be issued here.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="jc-issue-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="check-all"></th>
                                        <th>Item Code</th>
                                        <th>Description</th>
                                        <th>Unit</th>
                                        <th>Qty Required<br><small>(from quotation)</small></th>
                                        <th>Current Stock<br><small>(selected warehouse)</small></th>
                                        <th>Qty to Issue <span class="required">*</span></th>
                                        <th>WAC Price</th>
                                        <th>Line Cost</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ((array) $qt_lines as $line) {
                                    $itemId = (int) ($line['commodity_id'] ?? ($line['inventory_item_id'] ?? 0));
                                    $qtyReq = (float) ($line['quantity'] ?? 0);
                                    $wac    = (float) ($line['wac_price'] ?? 0);
                                    $lineId = (int) ($line['id'] ?? 0);
                                    $code   = $line['commodity_code'] ?? ($line['item_code'] ?? '');
                                    ?>
                                    <tr data-item-id="<?php echo $itemId; ?>" class="<?php echo $itemId < 1 ? 'text-muted' : ''; ?>">
                                        <td>
                                            <?php if ($itemId > 0) { ?>
                                                <input type="checkbox" name="issue_items[]" value="<?php echo $itemId; ?>" checked>
                                            <?php } else { ?>
                                                <span class="text-muted" title="No inventory link">—</span>
                                            <?php } ?>
                                        </td>
                                        <td><small><?php echo e($code !== '' ? $code : '—'); ?></small></td>
                                        <td><?php echo e($line['description'] ?? ''); ?></td>
                                        <td><?php echo e($line['unit'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo e(number_format($qtyReq, 3, '.', '')); ?></td>
                                        <td class="text-center stock-cell" data-item="<?php echo $itemId; ?>">
                                            <?php if ($itemId > 0) { ?>
                                                <span class="loading-stock"><i class="fa fa-spinner fa-spin"></i></span>
                                            <?php } else { ?>—<?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($itemId > 0) { ?>
                                                <input type="number"
                                                       name="qty_issued[<?php echo $itemId; ?>]"
                                                       class="form-control input-sm qty-issue-input"
                                                       value="<?php echo e(number_format($qtyReq, 3, '.', '')); ?>"
                                                       min="0"
                                                       step="0.001"
                                                       data-required="<?php echo e(number_format($qtyReq, 3, '.', '')); ?>"
                                                       data-wac="<?php echo e($wac); ?>">
                                            <?php } else { ?>
                                                <span class="text-muted">N/A</span>
                                            <?php } ?>
                                        </td>
                                        <td class="wac-cell">
                                            MWK <?php echo e(number_format($wac, 2, '.', ',')); ?>
                                            <?php if ($itemId > 0) { ?>
                                                <input type="hidden" name="wac_at_issue[<?php echo $itemId; ?>]" value="<?php echo e($wac); ?>">
                                                <input type="hidden" name="qt_line_id[<?php echo $itemId; ?>]" value="<?php echo $lineId; ?>">
                                            <?php } ?>
                                        </td>
                                        <td class="line-cost-cell text-right">
                                            <strong>MWK <span class="line-cost-display">0.00</span></strong>
                                        </td>
                                        <td>
                                            <?php if ($itemId > 0) { ?>
                                                <input type="text" name="line_notes[<?php echo $itemId; ?>]" class="form-control input-sm" placeholder="Optional note">
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="8" class="text-right"><strong>Total Issue Cost:</strong></td>
                                        <td class="text-right"><strong>MWK <span id="issue-total-cost">0.00</span></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <h5>Additional Items (not in quotation)</h5>
                        <p class="text-muted small">You can add items here that were not in the original quotation.</p>
                        <table id="jc-extra-items-table" class="table table-condensed table-bordered">
                            <thead>
                                <tr>
                                    <th>Item Search</th><th>Code</th><th>Unit</th><th>Qty</th><th>WAC</th><th>Line Cost</th><th></th>
                                </tr>
                            </thead>
                            <tbody id="extra-items-body"></tbody>
                        </table>
                        <button type="button" class="btn btn-xs btn-default" id="add-extra-item">
                            <i class="fa fa-plus"></i> Add Item
                        </button>

                        <div class="form-group mtop15">
                            <label>Issue Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this material issue..."></textarea>
                        </div>

                        <div id="stock-warning" class="alert alert-danger hide">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Insufficient Stock:</strong>
                            <ul id="stock-warning-list"></ul>
                            You can proceed with reduced quantities or source from the other warehouse.
                        </div>

                        <div class="text-right">
                            <a href="<?php echo admin_url('job_cards/view/' . (int) $job_card->id); ?>" class="btn btn-default">Cancel</a>
                            <button type="button" id="validate-issue" class="btn btn-warning">
                                <i class="fa fa-check-circle"></i> Validate Stock
                            </button>
                            <button type="submit" id="confirm-issue" class="btn btn-success" disabled>
                                <i class="fa fa-cubes"></i> Confirm Issue
                            </button>
                        </div>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
(function($){
    var stockCache = {};

    function fmt2(n){ return (parseFloat(n) || 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function fmt3(n){ return (parseFloat(n) || 0).toFixed(3); }

    function recalc_issue_total(){
        var total = 0;
        $('.line-cost-display').each(function(){
            total += parseFloat(($(this).text() || '0').replace(/,/g,'')) || 0;
        });
        $('.jc-extra-line-cost').each(function(){
            total += parseFloat(($(this).text() || '0').replace(/,/g,'')) || 0;
        });
        $('#issue-total-cost').text(fmt2(total));
    }

    function recalc_row_line_cost($row){
        var $qty = $row.find('.qty-issue-input');
        if (!$qty.length) { return; }
        var wac = parseFloat($qty.data('wac')) || 0;
        var qty = parseFloat($qty.val()) || 0;
        var line = qty * wac;
        $row.find('.line-cost-display').text(fmt2(line));
    }

    function loadStockLevels(warehouse_id){
        stockCache = {};
        $('#jc-issue-table tbody tr[data-item-id]').each(function(){
            var $row = $(this);
            var item_id = parseInt($row.data('item-id'), 10) || 0;
            if (!item_id) { return; }
            var $cell = $row.find('.stock-cell[data-item="'+item_id+'"]');
            $cell.html('<i class="fa fa-spinner fa-spin"></i>');
        });

        $.get(admin_url + 'job_cards/search_inventory_for_issue', {warehouse_id: warehouse_id}, function(items){
            var map = {};
            $.each(items || [], function(_, item){
                map[parseInt(item.commodity_id,10)] = parseFloat(item.current_quantity) || 0;
            });

            $('#jc-issue-table tbody tr[data-item-id]').each(function(){
                var $row = $(this);
                var item_id = parseInt($row.data('item-id'), 10) || 0;
                if (!item_id) { return; }
                var stock = map[item_id] || 0;
                stockCache[item_id] = stock;
                var required = parseFloat($row.find('.qty-issue-input').data('required')) || 0;
                var cls = stock >= required ? 'stock-ok' : 'stock-low';
                $row.find('.stock-cell').html('<span class="'+cls+'">'+fmt3(stock)+'</span>');
                $row.toggleClass('stock-insufficient', stock < required);
            });
        }, 'json');
    }

    function validateStockLevels(){
        var warnings = [];
        $('#jc-issue-table tbody tr').each(function(){
            var $row = $(this);
            var itemId = parseInt($row.data('item-id'), 10) || 0;
            var checked = $row.find('input[name="issue_items[]"]').is(':checked');
            if (!itemId || !checked) { return; }
            var qty = parseFloat($row.find('.qty-issue-input').val()) || 0;
            if (qty <= 0) { return; }
            var stock = stockCache[itemId] || 0;
            if (stock < qty) {
                var desc = $.trim($row.find('td:nth-child(3)').text());
                warnings.push('<li><strong>'+desc+'</strong>: need '+fmt3(qty)+', available '+fmt3(stock)+' (shortfall: '+fmt3(qty-stock)+')</li>');
            }
        });

        if (warnings.length > 0) {
            $('#stock-warning-list').html(warnings.join(''));
            $('#stock-warning').removeClass('hide');
            $('#confirm-issue').prop('disabled', true);
        } else {
            $('#stock-warning').addClass('hide');
            $('#stock-warning-list').empty();
            $('#confirm-issue').prop('disabled', false);
            alert_float('success', 'All items have sufficient stock. Ready to issue.');
        }
    }

    function addExtraRow(){
        var idx = $('#extra-items-body tr').length;
        var row = '' +
            '<tr>' +
              '<td>' +
                '<input type="hidden" name="extra_commodity_id['+idx+']" value="">' +
                '<input type="text" class="form-control input-sm jc-extra-item-search" placeholder="Search item...">' +
              '</td>' +
              '<td><input type="text" class="form-control input-sm jc-extra-code" readonly></td>' +
              '<td><input type="text" class="form-control input-sm jc-extra-unit" readonly></td>' +
              '<td><input type="number" name="extra_qty['+idx+']" class="form-control input-sm jc-extra-qty" value="0" min="0" step="0.001"></td>' +
              '<td><input type="number" name="extra_wac['+idx+']" class="form-control input-sm jc-extra-wac" value="0" min="0" step="0.0001"></td>' +
              '<td><strong>MWK <span class="jc-extra-line-cost">0.00</span></strong></td>' +
              '<td><button type="button" class="btn btn-xs btn-danger jc-remove-extra"><i class="fa fa-times"></i></button></td>' +
            '</tr>';
        $('#extra-items-body').append(row);
    }

    function recalcExtraRow($row){
        var qty = parseFloat($row.find('.jc-extra-qty').val()) || 0;
        var wac = parseFloat($row.find('.jc-extra-wac').val()) || 0;
        $row.find('.jc-extra-line-cost').text(fmt2(qty * wac));
        recalc_issue_total();
    }

    function initExtraAutocomplete($input){
        if (!$.fn.autocomplete) { return; }
        $input.autocomplete({
            source: function(req, res){
                $.get(admin_url + 'job_cards/search_inventory_for_issue', {term: req.term}, function(items){
                    res($.map(items || [], function(it){
                        return {
                            label: (it.commodity_code ? it.commodity_code + ' - ' : '') + it.commodity_name,
                            value: it.commodity_name,
                            commodity_id: it.commodity_id,
                            commodity_code: it.commodity_code,
                            unit: it.unit,
                            wac_price: it.wac_price
                        };
                    }));
                }, 'json');
            },
            minLength: 2,
            select: function(e, ui){
                var $row = $(this).closest('tr');
                $row.find('input[name^="extra_commodity_id"]').val(ui.item.commodity_id);
                $row.find('.jc-extra-code').val(ui.item.commodity_code || '');
                $row.find('.jc-extra-unit').val(ui.item.unit || '');
                $row.find('.jc-extra-wac').val(parseFloat(ui.item.wac_price || 0).toFixed(4));
                $(this).val(ui.item.value);
                recalcExtraRow($row);
                return false;
            }
        });
    }

    $(function(){
        if ($.fn.selectpicker) { $('.selectpicker').selectpicker(); }

        $('#jc-issue-table tbody tr').each(function(){
            recalc_row_line_cost($(this));
        });
        recalc_issue_total();

        loadStockLevels($('select[name="warehouse_id"]').val());

        $('select[name="warehouse_id"]').on('change', function(){
            loadStockLevels($(this).val());
        });

        $('#check-all').on('change', function(){
            var checked = $(this).is(':checked');
            $('input[name="issue_items[]"]').prop('checked', checked);
        });

        $(document).on('input', '.qty-issue-input', function(){
            recalc_row_line_cost($(this).closest('tr'));
            recalc_issue_total();
        });

        $('#validate-issue').on('click', function(){
            validateStockLevels();
        });

        $('#add-extra-item').on('click', function(){
            addExtraRow();
            initExtraAutocomplete($('#extra-items-body tr:last .jc-extra-item-search'));
        });

        $(document).on('click', '.jc-remove-extra', function(){
            $(this).closest('tr').remove();
            recalc_issue_total();
        });

        $(document).on('input', '.jc-extra-qty, .jc-extra-wac', function(){
            recalcExtraRow($(this).closest('tr'));
        });

        $(document).on('focus', '.jc-extra-item-search', function(){
            if (!$(this).data('ui-autocomplete')) {
                initExtraAutocomplete($(this));
            }
        });

        $('#jc-material-issue-form').on('submit', function(){
            var $btn = $('#confirm-issue');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        });
    });
})(jQuery);
</script>
</body>
</html>

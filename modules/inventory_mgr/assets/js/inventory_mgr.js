var InvMgr = InvMgr || {};

// =====================================================
// ISSUE FORM
// =====================================================
InvMgr.issueForm = {
  lastValidateOk: false,

  init: function () {
    this.bindWarehouseCards();
    this.loadStockOnWarehouseSelect();
    this.bindQtyInputs();
    this.bindValidateBtn();
    this.bindExtraItemSearch();
    this.bindQtLineMapSearch();
    this.bindCheckboxes();
    this.bindFormSubmit();
    this.recalcTotals();
  },

  invalidateValidation: function () {
    this.lastValidateOk = false;
    $('#issue-btn').prop('disabled', true);
  },

  bindWarehouseCards: function () {
    $(document).on('click', '.wh-select-card', function (e) {
      e.preventDefault();
      $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
  },

  loadStockOnWarehouseSelect: function () {
    var self = this;
    $('input[name="warehouse_id"]').on('change', function () {
      $('.wh-select-card').removeClass('active');
      $('input[name="warehouse_id"]:checked').closest('.wh-select-card').addClass('active');
      self.invalidateValidation();
      $('#stock-issues-panel').addClass('hide');
      self.loadAllStockLevels($(this).val());
    });
    var checked = $('input[name="warehouse_id"]:checked');
    if (checked.length) {
      checked.closest('.wh-select-card').addClass('active');
      this.loadAllStockLevels(checked.val());
    }
  },

  loadAllStockLevels: function (warehouse_id) {
    if (!warehouse_id) return;
    $('#qt-items-table tr.qt-item-row[data-item-id]').each(function () {
      var item_id = parseInt($(this).attr('data-item-id'), 10) || 0;
      if (!item_id) return;
      var $row = $(this);
      var $cell = $row.find('.stock-available-cell');
      $cell.html('<i class="fa fa-refresh fa-spin"></i>');
      $.get(
        admin_url + 'inventory_mgr/get_item_info',
        { item_id: item_id, warehouse_id: warehouse_id },
        function (res) {
          var qty = parseFloat(res.stock_qty) || 0;
          var required = parseFloat($row.find('.qty-input').data('max-default')) || 0;
          var ok = qty >= required;
          var html =
            '<span class="' +
            (ok ? 'text-success' : 'text-danger') +
            ' stock-qty-display" data-stock="' +
            qty +
            '">' +
            qty.toFixed(3) +
            '</span>';
          if (!ok && required > 0) {
            html += ' <span class="label label-danger low-stock-badge">LOW</span>';
          }
          $cell.html(html);
          $row.find('.qty-input').data('max-available', qty);
          $row.find('.item-wac').text(
            'MWK ' +
              parseFloat(res.wac).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
          );
          $row.find('.qty-input').data('wac', res.wac);
          InvMgr.issueForm.recalcRow($row);
        },
        'json'
      );
    });
    $('#extra-items-body tr').each(function () {
      var $tr = $(this);
      var eid = parseInt($tr.find('.extra-item-id').val(), 10);
      if (!eid) return;
      $.get(
        admin_url + 'inventory_mgr/get_item_info',
        { item_id: eid, warehouse_id: warehouse_id },
        function (res) {
          var qty = parseFloat(res.stock_qty) || 0;
          $tr.find('.extra-stock').text(qty.toFixed(3));
          $tr.data('wac', parseFloat(res.wac) || 0);
          $tr.find('.extra-wac').text(
            'MWK ' +
              parseFloat(res.wac).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
          );
          $tr.find('.extra-qty').data('wac', res.wac).data('max-available', qty);
          InvMgr.issueForm.recalcExtraQtyRow($tr);
        },
        'json'
      );
    });
  },

  bindQtyInputs: function () {
    var self = this;
    $(document).on('input change', '.qty-input', function () {
      self.invalidateValidation();
      self.recalcRow($(this).closest('tr'));
    });
  },

  recalcRow: function ($row) {
    var $cb = $row.find('.qt-check');
    var qty = 0;
    var $qi = $row.find('.qty-input').not(':disabled');
    if ($cb.length && !$cb.is(':checked')) {
      qty = 0;
    } else {
      qty = parseFloat($qi.val()) || 0;
    }
    var wac = parseFloat($qi.data('wac')) || 0;
    var cost = qty * wac;
    $row.find('.issue-cost').text(
      cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
    var available = parseFloat($qi.data('max-available')) || 0;
    if (available > 0 && qty > available) {
      $qi.addClass('input-danger');
    } else {
      $qi.removeClass('input-danger');
    }
    this.recalcTotals();
  },

  recalcExtraQtyRow: function ($tr) {
    var qty = parseFloat($tr.find('.extra-qty').val()) || 0;
    var wac = parseFloat($tr.find('.extra-qty').data('wac')) || parseFloat($tr.data('wac')) || 0;
    var cost = qty * wac;
    $tr.find('.extra-cost').text(
      cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
    var available = parseFloat($tr.find('.extra-qty').data('max-available')) || 0;
    if (available > 0 && qty > available) {
      $tr.find('.extra-qty').addClass('input-danger');
    } else {
      $tr.find('.extra-qty').removeClass('input-danger');
    }
    this.recalcTotals();
  },

  recalcTotals: function () {
    var qt_total = 0;
    var extra_total = 0;
    $('#qt-items-table .issue-cost').each(function () {
      var $row = $(this).closest('tr');
      if ($row.find('.qt-check').length && !$row.find('.qt-check').is(':checked')) {
        return;
      }
      qt_total += parseFloat($(this).text().replace(/,/g, '')) || 0;
    });
    $('#extra-items-body .extra-cost').each(function () {
      extra_total += parseFloat($(this).text().replace(/,/g, '')) || 0;
    });
    var fmt = function (n) {
      return (
        'MWK ' +
        n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      );
    };
    $('#qt-cost-total').text(fmt(qt_total));
    $('#extra-cost-total').text(fmt(extra_total));
    $('#grand-issue-total').text(fmt(qt_total + extra_total));
  },

  bindValidateBtn: function () {
    var self = this;
    $('#validate-btn').on('click', function () {
      var warehouse_id = $('input[name="warehouse_id"]:checked').val();
      if (!warehouse_id) {
        alert_float('danger', 'Please select a warehouse first.');
        return;
      }
      var lines = [];
      $('.qty-input').each(function () {
        if ($(this).prop('disabled')) {
          return;
        }
        var $row = $(this).closest('tr');
        if ($row.find('.qt-check').length && !$row.find('.qt-check').is(':checked')) {
          return;
        }
        var qty = parseFloat($(this).val()) || 0;
        if (qty > 0) {
          lines.push({
            item_id: $(this).data('item'),
            qty_issued: qty,
          });
        }
      });
      $('#extra-items-body tr').each(function () {
        var item_id = $(this).find('.extra-item-id').val();
        var qty = parseFloat($(this).find('.extra-qty').val()) || 0;
        if (item_id && qty > 0) {
          lines.push({ item_id: item_id, qty_issued: qty });
        }
      });

      if (lines.length === 0) {
        alert_float('warning', 'Enter at least one quantity to validate.');
        return;
      }

      var payload = {
        warehouse_id: warehouse_id,
        lines: JSON.stringify(lines),
      };
      if (typeof csrfData !== 'undefined' && csrfData.formatted) {
        $.extend(payload, csrfData.formatted);
      }

      $.post(admin_url + 'inventory_mgr/confirm_issue', payload, function (res) {
        if (res.can_proceed) {
          $('#stock-issues-panel').addClass('hide');
          $('#issue-btn').prop('disabled', false);
          self.lastValidateOk = true;
          alert_float('success', 'Stock validated. Total cost: ' + res.total_cost);
        } else {
          var html = '';
          $.each(res.warnings || [], function (i, w) {
            html +=
              '<li><strong>' +
              (w.item_code || '') +
              ' — ' +
              (w.item_name || '') +
              '</strong>: Need ' +
              w.requested +
              ', have ' +
              w.available +
              ' <span class="text-danger">(shortfall: ' +
              w.shortfall +
              ')</span></li>';
          });
          $('#stock-issues-list').html(html);
          $('#stock-issues-panel').removeClass('hide');
          $('#issue-btn').prop('disabled', true);
          self.lastValidateOk = false;
        }
      }, 'json').fail(function () {
        alert_float('danger', 'Validation request failed.');
        self.lastValidateOk = false;
        $('#issue-btn').prop('disabled', true);
      });
    });
  },

  bindExtraItemSearch: function () {
    var self = this;
    $('#add-extra-item-btn').on('click', function () {
      if (!$('input[name="warehouse_id"]:checked').val()) {
        alert_float('warning', 'Please select a warehouse first.');
        return;
      }
      InvMgr.issueForm.addExtraItemRow();
    });

    $(document).on('focus', '.extra-item-search', function () {
      if ($(this).data('ac-init')) return;
      $(this)
        .data('ac-init', true)
        .autocomplete({
          source: function (req, res) {
            var wh_id = $('input[name="warehouse_id"]:checked').val();
            if (!wh_id) {
              res([]);
              return;
            }
            $.get(
              admin_url + 'inventory_mgr/search_items_ajax',
              { term: req.term, warehouse_id: wh_id, with_stock: 1 },
              function (data) {
                var mapped = $.map(data || [], function (item) {
                  return {
                    label: (item.commodity_code || '') + ' — ' + (item.description || ''),
                    value: item.description || '',
                    data: item,
                  };
                });
                res(mapped);
              },
              'json'
            );
          },
          minLength: 2,
          select: function (e, ui) {
            var $row = $(this).closest('tr');
            var d = ui.item.data;
            $row.find('.extra-item-id').val(d.id);
            $row.find('.extra-code').text(d.commodity_code || '');
            $row.find('.extra-unit').text(d.unit_symbol || '');
            var qh = parseFloat(d.qty_on_hand || 0);
            $row
              .find('.extra-stock')
              .text(qh.toFixed(3))
              .toggleClass('text-danger', qh <= 0);
            $row.find('.extra-wac').text(
              'MWK ' +
                parseFloat(d.purchase_price || 0).toLocaleString('en-US', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })
            );
            $row.find('.extra-qty').data('wac', d.purchase_price).data('max-available', d.qty_on_hand || 0);
            $row.data('wac', parseFloat(d.purchase_price) || 0);
            self.invalidateValidation();
            InvMgr.issueForm.recalcExtraQtyRow($row);
            return false;
          },
        });
      var ac = $(this).data('ui-autocomplete');
      if (ac) {
        ac._renderItem = function (ul, item) {
          return $('<li>').append($('<div>').text(item.label)).appendTo(ul);
        };
      }
    });

    $(document).on('input change', '.extra-qty', function () {
      self.invalidateValidation();
      InvMgr.issueForm.recalcExtraQtyRow($(this).closest('tr'));
    });

    $(document).on('click', '.extra-remove', function () {
      $(this).closest('tr').remove();
      self.invalidateValidation();
      InvMgr.issueForm.recalcTotals();
    });
  },

  bindQtLineMapSearch: function () {
    var self = this;
    $(document).on('focus', '.qt-line-map-search', function () {
      var $input = $(this);
      if ($input.data('ac-init')) return;
      $input.data('ac-init', true).autocomplete({
        source: function (req, res) {
          var wh_id = $('input[name="warehouse_id"]:checked').val();
          if (!wh_id) {
            res([]);
            return;
          }
          $.get(
            admin_url + 'inventory_mgr/search_items_ajax',
            { term: req.term, warehouse_id: wh_id, with_stock: 0 },
            function (data) {
              var mapped = $.map(data || [], function (item) {
                return {
                  label: (item.commodity_code || '') + ' — ' + (item.description || ''),
                  value: item.description || '',
                  data: item,
                };
              });
              res(mapped);
            },
            'json'
          );
        },
        minLength: 2,
        select: function (e, ui) {
          var d = ui.item.data;
          var $tr = $input.closest('tr');
          $tr.find('.qt-mapped-item-id').val(d.id);
          $tr.removeClass('qt-unmapped-row').addClass('qt-mapped-linked');
          $tr.attr('data-item-id', d.id).removeData('item-id');
          $tr.find('.qt-code-cell').text(d.commodity_code || '—');
          $tr.find('.qt-unit-cell').text(d.unit_symbol || '');
          var $qty = $tr.find('.qt-map-qty');
          $qty.prop('disabled', false)
            .addClass('qty-input')
            .attr('data-item', d.id)
            .data('max-default', $qty.val() || $qty.data('max-default') || 0);
          $tr.find('.qt-unmapped-cb-slot').html(
            '<input type="checkbox" class="qt-check" checked title="Include in issue">'
          );
          $tr.find('.qt-cost-placeholder').html(
            '<strong>MWK <span class="issue-cost">0.00</span></strong>'
          );
          self.invalidateValidation();
          var wh = $('input[name="warehouse_id"]:checked').val();
          if (wh) {
            var $cell = $tr.find('.stock-available-cell');
            $cell.html('<i class="fa fa-refresh fa-spin"></i>');
            $.get(
              admin_url + 'inventory_mgr/get_item_info',
              { item_id: d.id, warehouse_id: wh },
              function (res) {
                var qty = parseFloat(res.stock_qty) || 0;
                var required = parseFloat($qty.data('max-default')) || 0;
                var ok = qty >= required;
                var html =
                  '<span class="' +
                  (ok ? 'text-success' : 'text-danger') +
                  ' stock-qty-display" data-stock="' +
                  qty +
                  '">' +
                  qty.toFixed(3) +
                  '</span>';
                if (!ok && required > 0) {
                  html += ' <span class="label label-danger low-stock-badge">LOW</span>';
                }
                $cell.html(html);
                $qty.data('max-available', qty);
                $qty.data('wac', res.wac);
                $tr.find('.qt-wac-cell .item-wac').text(
                  'MWK ' +
                    parseFloat(res.wac).toLocaleString('en-US', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })
                );
                InvMgr.issueForm.recalcRow($tr);
              },
              'json'
            );
          } else {
            InvMgr.issueForm.recalcRow($tr);
          }
          return false;
        },
      });
      var ac = $input.data('ui-autocomplete');
      if (ac) {
        ac._renderItem = function (ul, item) {
          return $('<li>').append($('<div>').text(item.label)).appendTo(ul);
        };
      }
    });
  },

  addExtraItemRow: function () {
    var row =
      '<tr>' +
      '<td><input class="form-control input-sm extra-item-search" placeholder="Search item...">' +
      '<input type="hidden" class="extra-item-id" name="extra_item_id[]" value=""></td>' +
      '<td class="extra-code text-mono small">—</td>' +
      '<td class="extra-unit">—</td>' +
      '<td class="extra-stock text-center">—</td>' +
      '<td><input type="number" class="form-control input-sm extra-qty" name="extra_qty[]" min="0" step="0.001" value="1" style="width:90px"></td>' +
      '<td class="extra-wac">—</td>' +
      '<td class="text-right">MWK <span class="extra-cost">0.00</span></td>' +
      '<td><button type="button" class="btn btn-xs btn-danger extra-remove">' +
      '<i class="fa fa-times"></i></button></td>' +
      '</tr>';
    $('#extra-items-body').append(row);
  },

  bindCheckboxes: function () {
    var self = this;
    $('#check-all-qt').on('change', function () {
      $('.qt-check').prop('checked', $(this).is(':checked'));
      self.invalidateValidation();
      $('#qt-items-table tr.qt-item-row').each(function () {
        InvMgr.issueForm.recalcRow($(this));
      });
    });
    $(document).on('change', '.qt-check', function () {
      self.invalidateValidation();
      InvMgr.issueForm.recalcRow($(this).closest('tr'));
    });
  },

  bindFormSubmit: function () {
    var self = this;
    $('#issue-material-form').on('submit', function () {
      if (!self.lastValidateOk) {
        alert_float('warning', 'Validate stock before confirming.');
        return false;
      }
    });
  },
};

// =====================================================
// GRN FORM
// =====================================================
InvMgr.grnForm = {
  lineSeq: 0,

  init: function () {
    this.bindSupplierAutocomplete();
    this.bindWarehouseChange();
    this.bindItemSearch();
    this.bindInputs();
    this.bindSubmitGuard();
    this.tryPrefill();
  },

  bindSupplierAutocomplete: function () {
    var $src = $('#grn-supplier-history-data');
    if (!$src.length || !$.fn.autocomplete) return;
    var list = [];
    try {
      list = JSON.parse($src.text()) || [];
    } catch (e) {
      list = [];
    }
    if (list.length) {
      $('#grn-supplier-name').autocomplete({ source: list, minLength: 0 });
    }
  },

  bindWarehouseChange: function () {
    var self = this;
    $('select[name="warehouse_id"]').on('change', function () {
      self.reloadAllRowStock();
      self.tryPrefill();
    });
  },

  tryPrefill: function () {
    var $form = $('#grn-form');
    if (!$form.length) return;
    var pid = parseInt($form.attr('data-prefill-item'), 10);
    var wh = $('select[name="warehouse_id"]').val();
    if (!pid || !wh) return;
    if ($('#grn-items-body tr.grn-row[data-item-id="' + pid + '"]').length) return;
    $.getJSON(admin_url + 'inventory_mgr/get_item_info', { item_id: pid, warehouse_id: wh }, function (d) {
      if (!d || d.error) return;
      InvMgr.grnForm.addRow({
        id: d.id,
        commodity_code: d.commodity_code,
        description: d.description,
        unit_symbol: d.unit_symbol,
        purchase_price: d.wac,
        qty_on_hand: d.stock_qty,
      });
      $form.attr('data-prefill-item', '0');
    });
  },

  reloadAllRowStock: function () {
    var wh = $('select[name="warehouse_id"]').val();
    if (!wh) return;
    $('#grn-items-body tr.grn-row').each(function () {
      var $tr = $(this);
      var id = $tr.data('item-id');
      if (!id) return;
      $.getJSON(admin_url + 'inventory_mgr/get_item_info', { item_id: id, warehouse_id: wh }, function (d) {
        if (!d || d.error) return;
        var st = parseFloat(d.stock_qty) || 0;
        var w = parseFloat(d.wac) || 0;
        $tr.data('current-stock', st).data('current-wac', w);
        $tr.find('.grn-cur-stock').text(st.toFixed(3));
        $tr.find('.grn-cur-wac').text(
          'MWK ' +
            w.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })
        );
        $tr.find('.grn-qty, .grn-price').first().trigger('input');
      });
    });
  },

  calculateNewWAC: function (current_qty, current_wac, new_qty, new_price) {
    current_qty = parseFloat(current_qty) || 0;
    current_wac = parseFloat(current_wac) || 0;
    new_qty = parseFloat(new_qty) || 0;
    new_price = parseFloat(new_price) || 0;
    var total_qty = current_qty + new_qty;
    if (total_qty <= 0) return new_price;
    return (current_qty * current_wac + new_qty * new_price) / total_qty;
  },

  bindItemSearch: function () {
    var self = this;
    if (!$.fn.autocomplete) {
      console.error('InvMgr GRN: jQuery UI autocomplete is not loaded; load jquery-ui.js on admin pages that use item search.');
      return;
    }
    $('#grn-item-search').autocomplete({
      minLength: 2,
      source: function (req, res) {
        var wh = $('select[name="warehouse_id"]').val();
        if (!wh) {
          res([]);
          return;
        }
        $.getJSON(
          admin_url + 'inventory_mgr/search_items_ajax',
          { term: req.term, warehouse_id: wh, with_stock: 1 },
          function (data) {
            var rows = data || [];
            if (rows.length === 0) {
              res([
                {
                  label:
                    'No items match "' +
                    req.term +
                    '". Use text from item code or description (Inventory → Items).',
                  value: '',
                  invMgrNoMatch: true,
                },
              ]);
              return;
            }
            res(
              $.map(rows, function (item) {
                return {
                  label: (item.commodity_code || '') + ' — ' + (item.description || ''),
                  value: '',
                  data: item,
                };
              })
            );
          }
        ).fail(function () {
          res([
            {
              label: 'Search failed — check you are logged in and try again.',
              value: '',
              invMgrNoMatch: true,
            },
          ]);
          if (typeof alert_float === 'function') {
            alert_float('danger', 'Item search request failed.');
          }
        });
      },
      select: function (e, ui) {
        if (ui.item.invMgrNoMatch) {
          return false;
        }
        var wh = $('select[name="warehouse_id"]').val();
        if (!wh) {
          alert_float('warning', 'Select receiving warehouse first.');
          return false;
        }
        InvMgr.grnForm.addRow(ui.item.data);
        $(this).val('');
        return false;
      },
    });
    var ac = $('#grn-item-search').data('ui-autocomplete');
    if (ac) {
      ac._renderItem = function (ul, item) {
        var $li = $('<li>').append($('<div>').text(item.label));
        if (item.invMgrNoMatch) {
          $li.addClass('inv-mgr-ac-nomatch text-muted');
        }
        return $li.appendTo(ul);
      };
    }
  },

  addRow: function (item) {
    if (!item || !item.id) return;
    var wh = $('select[name="warehouse_id"]').val();
    if (!wh) {
      alert_float('warning', 'Select receiving warehouse first.');
      return;
    }
    if ($('#grn-items-body tr.grn-row[data-item-id="' + item.id + '"]').length) {
      alert_float('warning', 'This item is already on the GRN.');
      return;
    }
    var wac = parseFloat(item.purchase_price != null ? item.purchase_price : item.wac) || 0;
    var stock = parseFloat(item.qty_on_hand) || 0;
    var idx = this.lineSeq++;
    var pfx = 'items[' + idx + ']';

    $('#grn-items-body .grn-empty-row').hide();

    var $tr = $('<tr class="grn-row"></tr>')
      .attr('data-item-id', item.id)
      .data('current-wac', wac)
      .data('current-stock', stock);

    var $tdCode = $('<td class="text-mono small"></td>');
    $tdCode.append(
      $('<input type="hidden">').attr('name', pfx + '[item_id]').val(item.id),
      $('<input type="hidden">').attr('name', pfx + '[item_code]').val(item.commodity_code || ''),
      $('<span></span>').text(item.commodity_code || '')
    );

    var $tdName = $('<td></td>');
    $tdName.append(
      $('<input type="hidden">').attr('name', pfx + '[item_name]').val(item.description || ''),
      $('<span></span>').text(item.description || '')
    );

    var $tdUnit = $('<td></td>');
    $tdUnit.append(
      $('<input type="hidden">').attr('name', pfx + '[unit_symbol]').val(item.unit_symbol || ''),
      $('<span></span>').text(item.unit_symbol || '')
    );

    $tr.append($tdCode);
    $tr.append($tdName);
    $tr.append($tdUnit);
    $tr.append(
      $('<td class="text-right grn-cur-wac"></td>').html(
        'MWK ' +
          wac.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })
      )
    );
    $tr.append($('<td class="text-right grn-cur-stock"></td>').text(stock.toFixed(3)));
    $tr.append(
      $('<td></td>').append(
        $('<input type="number" class="form-control input-sm grn-qty">')
          .attr('name', pfx + '[qty_received]')
          .attr({ min: 0, step: 0.001 })
          .css('width', '90px')
      )
    );
    $tr.append(
      $('<td></td>').append(
        $('<input type="number" class="form-control input-sm grn-price">')
          .attr('name', pfx + '[unit_price]')
          .attr({ min: 0, step: 0.01 })
          .val(wac.toFixed(2))
          .css('width', '100px')
      )
    );
    $tr.append($('<td class="text-right grn-line-total"></td>').text('MWK 0.00'));
    $tr.append($('<td class="text-right grn-wac-preview"></td>').text('—'));
    $tr.append(
      $('<td></td>').append(
        $('<button type="button" class="btn btn-xs btn-danger grn-remove"><i class="fa fa-times"></i></button>')
      )
    );

    $('#grn-items-body').append($tr);
    $tr.find('.grn-qty, .grn-price').trigger('input');
  },

  bindInputs: function () {
    $(document).on('input change', '.grn-qty, .grn-price', function () {
      var $row = $(this).closest('.grn-row');
      var qty = parseFloat($row.find('.grn-qty').val()) || 0;
      var price = parseFloat($row.find('.grn-price').val()) || 0;
      var line_total = qty * price;
      $row.find('.grn-line-total').text(
        'MWK ' +
          line_total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      );
      var current_qty = parseFloat($row.data('current-stock')) || 0;
      var current_wac = parseFloat($row.data('current-wac')) || 0;
      var new_wac = InvMgr.grnForm.calculateNewWAC(current_qty, current_wac, qty, price);
      var wac_text =
        'MWK ' +
        new_wac.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
      var diff_pct = current_wac > 0 ? ((new_wac - current_wac) / current_wac) * 100 : 0;
      var wac_class =
        diff_pct > 20 ? 'text-danger' : diff_pct > 0 ? 'text-warning' : 'text-success';
      $row.find('.grn-wac-preview').html('<span class="' + wac_class + '">' + wac_text + '</span>');
      InvMgr.grnForm.recalcTotal();
    });

    $(document).on('click', '.grn-remove', function () {
      $(this).closest('tr').remove();
      InvMgr.grnForm.recalcTotal();
      if ($('#grn-items-body .grn-row').length === 0) {
        $('#grn-items-body .grn-empty-row').show();
      }
    });
  },

  recalcTotal: function () {
    var total = 0;
    $('.grn-line-total').each(function () {
      var t = $(this).text().replace('MWK', '').replace(/,/g, '').trim();
      total += parseFloat(t) || 0;
    });
    $('#grn-total-value').text(
      'MWK ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
  },

  bindSubmitGuard: function () {
    $('#grn-form').on('submit', function () {
      if ($('#grn-items-body .grn-row').length === 0) {
        alert_float('danger', 'Add at least one line item.');
        return false;
      }
    });
  },
};

// =====================================================
// INIT
// =====================================================
$(document).ready(function () {
  if ($('#issue-btn').length) {
    InvMgr.issueForm.init();
  }
  if ($('#grn-items-body').length && $('#grn-form').length) {
    InvMgr.grnForm.init();
  }
});

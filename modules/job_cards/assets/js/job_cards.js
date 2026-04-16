/* Global namespace */
var JC = JC || {};

JC.init = function() {
  JC.bindStatusAdvance();
  JC.bindNotesSave();
  JC.bindAcknowledge();
  JC.bindStatCardFilters();
  JC.initMaterialIssueForm();
};

JC.bindStatusAdvance = function() {
  $('#jc-advance-status').on('click', function() {
    if ($(this).hasClass('disabled')) return;
    var btn = $(this);
    var jc_id = btn.data('jc');
    var new_status = btn.data('next');
    var notes = $('#jc-status-notes').val();
    btn.addClass('disabled').html('<i class="fa fa-spinner fa-spin"></i> Updating...');
    $.post(admin_url + 'job_cards/update_status', {
      job_card_id: jc_id,
      new_status: new_status,
      notes: notes
    }, function(res) {
      if (res.success) {
        alert_float('success', res.message || 'Status updated');
        setTimeout(function() { location.reload(); }, 800);
      } else {
        alert_float('danger', res.message || 'Failed to update status');
        btn.removeClass('disabled').html(btn.data('original-text'));
      }
    }, 'json');
  });
};

JC.bindNotesSave = function() {
  $(document).on('click', '.jc-save-notes', function() {
    var btn = $(this);
    var type = btn.data('type');
    var jc_id = btn.data('jc');
    var value = $('[name="' + type + '"]').val();
    btn.addClass('disabled');
    $.post(admin_url + 'job_cards/update_notes', {
      job_card_id: jc_id,
      note_type: type,
      value: value
    }, function(res) {
      btn.removeClass('disabled');
      if (res.success) alert_float('success', 'Notes saved');
      else alert_float('danger', res.message);
    }, 'json');
  });
};

JC.bindAcknowledge = function() {
  $(document).on('click', '.jc-acknowledge', function() {
    var jc_id = $(this).data('jc');
    var dept = $(this).data('dept');
    $.post(admin_url + 'job_cards/acknowledge_department', {
      job_card_id: jc_id,
      department: dept
    }, function(res) {
      if (res.success) location.reload();
    }, 'json');
  });
};

JC.bindStatCardFilters = function() {
  $(document).on('click', '.jc-stat-card', function() {
    var status = $(this).data('status');
    $('.jc-stat-card').removeClass('active');
    $(this).addClass('active');
    if (typeof jc_table !== 'undefined') {
      if (status === 'all') {
        jc_table.column(8).search('').draw();
      } else {
        jc_table.column(8).search(status).draw();
      }
    }
  });
};

JC.initMaterialIssueForm = function() {
  if ($('#jc-issue-table').length === 0) return;

  // Load stock on warehouse change
  $('select[name="warehouse_id"]').on('change', function() {
    JC.loadStockLevels($(this).val());
  });

  // Qty input → recalc line cost
  $(document).on('input', '.qty-issue-input', function() {
    var wac = parseFloat($(this).data('wac')) || 0;
    var qty = parseFloat($(this).val()) || 0;
    var total = qty * wac;
    $(this).closest('tr').find('.line-cost-display').text(
      total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
    );
    JC.recalcIssueTotal();
  });

  // Validate button
  $('#validate-issue').on('click', function() {
    JC.validateStockLevels();
  });

  // Extra item search autocomplete
  JC.initExtraItemSearch();
};

JC.loadStockLevels = function(warehouse_id) {
  $('#jc-issue-table tbody tr[data-item-id]').each(function() {
    var item_id = $(this).data('item-id');
    if (!item_id) return;
    var $cell = $(this).find('.stock-cell[data-item="'+item_id+'"]');
    $cell.html('<i class="fa fa-spinner fa-spin"></i>');
    $.get(admin_url + 'job_cards/get_item_stock', {
      commodity_id: item_id,
      warehouse_id: warehouse_id
    }, function(res) {
      var qty = res.current_quantity || 0;
      var required = parseFloat($cell.closest('tr').find('.qty-issue-input').data('required')) || 0;
      var html = '<span class="' + (qty >= required ? 'stock-ok' : 'stock-low') + '">' +
                 parseFloat(qty).toFixed(3) + '</span>';
      $cell.html(html);
      $cell.closest('tr').toggleClass('stock-insufficient', qty < required);
    }, 'json');
  });
};

JC.validateStockLevels = function() {
  var warnings = [];
  $('#jc-issue-table tbody tr').each(function() {
    var $row = $(this);
    var stock = parseFloat($row.find('.stock-cell span').text()) || 0;
    var qty = parseFloat($row.find('.qty-issue-input').val()) || 0;
    var desc = $row.find('td:nth-child(3)').text().trim();
    if (qty > 0 && stock < qty) {
      warnings.push('<li><strong>' + desc + '</strong>: need ' + qty +
                    ', available ' + stock + ' (shortfall: ' + (qty-stock).toFixed(3) + ')</li>');
    }
  });
  if (warnings.length > 0) {
    $('#stock-warning-list').html(warnings.join(''));
    $('#stock-warning').removeClass('hide');
    $('#confirm-issue').prop('disabled', true);
  } else {
    $('#stock-warning').addClass('hide');
    $('#confirm-issue').prop('disabled', false);
    alert_float('success', 'All items have sufficient stock. Ready to issue.');
  }
};

JC.recalcIssueTotal = function() {
  var total = 0;
  $('.line-cost-display').each(function() {
    total += parseFloat($(this).text().replace(/,/g,'')) || 0;
  });
  $('#issue-total-cost').text(
    total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
  );
};

JC.initExtraItemSearch = function() {
  $(document).on('focus', '.jc-extra-item-search', function() {
    $(this).autocomplete({
      source: function(req, res) {
        $.get(admin_url + 'job_cards/search_inventory_for_issue',
              {term: req.term}, res, 'json');
      },
      minLength: 2,
      select: function(e, ui) {
        var $row = $(this).closest('tr');
        $row.find('.jc-extra-code').val(ui.item.commodity_code);
        $row.find('.jc-extra-unit').val(ui.item.unit);
        $row.find('.jc-extra-wac').val(ui.item.wac_price).data('wac', ui.item.wac_price);
        $(this).val(ui.item.commodity_name);
        $row.find('[name^="extra_commodity_id"]').val(ui.item.commodity_id);
        return false;
      }
    });
  });
};

$(document).ready(function() {
  JC.init();
});

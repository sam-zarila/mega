/**
 * Quotation worksheet — proposal screen integration
 */
/* global jQuery, qt_config, csrfData, admin_url */

function qt_get_active_proposal_id() {
  var m = window.location.pathname.match(/proposals\/proposal\/(\d+)/);
  if (m && m[1]) {
    return parseInt(m[1], 10) || 0;
  }
  var q = window.location.search.match(/[?&]id=(\d+)/);
  if (q && q[1]) {
    return parseInt(q[1], 10) || 0;
  }
  var isedit = $('input[name="isedit"]').val();
  if (isedit) {
    var v = parseInt(isedit, 10);
    if (v) {
      return v;
    }
  }
  var hid = $('input[name="id"]').val() || $('input[name="proposal_id"]').val();
  if (hid) {
    return parseInt(hid, 10) || 0;
  }
  var dp = $('#qt-worksheet-panel').attr('data-proposal-id');
  if (dp) {
    return parseInt(dp, 10) || 0;
  }
  var qp = $('#qt_proposal_id').val();
  if (qp) {
    return parseInt(qp, 10) || 0;
  }
  return 0;
}

function qt_apply_proposal_id_to_panel(pid) {
  pid = parseInt(pid, 10) || 0;
  $('#qt-worksheet-panel').attr('data-proposal-id', pid);
  $('#qt_proposal_id').val(pid);
  $('.qt-btn-pdf,.qt-btn-submit-approval').attr('data-proposal-id', pid);
  if (pid > 0) {
    $('.qt-draft-warning').slideUp();
  }
}

function qt_sync_proposal_id_from_form() {
  var pid = qt_get_active_proposal_id();
  qt_apply_proposal_id_to_panel(pid);
  return pid;
}

function qt_csrf_payload(data) {
  data = data || {};
  if (typeof csrfData !== 'undefined' && csrfData.token_name) {
    data[csrfData.token_name] = csrfData.hash;
  }
  return data;
}

function qt_ajax_post(action, data, done) {
  data = qt_csrf_payload(data || {});
  jQuery.post((qt_config && qt_config.ajax_url ? qt_config.ajax_url : admin_url('quotation_worksheet/')) + action, data, done, 'json');
}

function qt_find_tbody(tab, section) {
  section = section || '';
  return jQuery('.qt-lines-tbody[data-tab="' + tab + '"]')
    .filter(function () {
      return (jQuery(this).attr('data-section') || '') === section;
    })
    .first();
}

function qt_refresh_line_sortable($scope) {
  var $root = $scope && $scope.length ? $scope : jQuery('#qt-worksheet-panel');
  if (!$root.length || !jQuery.fn.sortable) {
    return;
  }
  $root.find('.qt-lines-tbody').each(function () {
    var $el = jQuery(this);
    if ($el.data('ui-sortable')) {
      $el.sortable('destroy');
    }
    $el.sortable({
      handle: '.qt-drag-handle',
      helper: function (e, tr) {
        var $orig = tr.children();
        var $helper = tr.clone();
        $helper.children().each(function (i) {
          jQuery(this).width($orig.eq(i).width());
        });
        return $helper;
      },
      update: function () {
        qt_sync_proposal_id_from_form();
        var pid = qt_get_active_proposal_id();
        if (pid < 1) {
          return;
        }
        var order = [];
        $el.find('tr.qt-line-row').each(function () {
          var id = parseInt(jQuery(this).attr('data-line-id'), 10);
          if (id > 0) {
            order.push(id);
          }
        });
        qt_ajax_post('reorder_lines', { proposal_id: pid, order: JSON.stringify(order) });
      },
    });
  });
}

function qt_collect_row_payload($row) {
  var tab = $row.attr('data-tab');
  var payload = {
    proposal_id: qt_get_active_proposal_id(),
    id: parseInt($row.attr('data-line-id'), 10) || 0,
    tab: tab,
    section_name: $row.attr('data-section') || $row.closest('.qt-lines-tbody').attr('data-section') || '',
  };
  $row.find('.qt-field').each(function () {
    var $f = jQuery(this);
    var field = $f.data('field');
    if (!field) {
      return;
    }
    payload[field] = $f.val();
  });
  return payload;
}

function qt_save_line_row($row) {
  qt_sync_proposal_id_from_form();
  var pid = qt_get_active_proposal_id();
  if (pid < 1) {
    return;
  }
  var payload = qt_collect_row_payload($row);
  qt_ajax_post('save_line', payload, function (res) {
    if (!res || !res.success) {
      return;
    }
    if (res.id) {
      $row.attr('data-line-id', res.id);
    }
    if (res.html) {
      var $new = jQuery(res.html);
      $row.replaceWith($new);
      qt_refresh_line_sortable($new.closest('#qt-worksheet-panel'));
    }
    if (res.totals) {
      qt_apply_totals(res.totals);
    }
  });
}

var qt_line_timer = null;

function qt_schedule_save($row) {
  clearTimeout(qt_line_timer);
  qt_line_timer = setTimeout(function () {
    qt_save_line_row($row);
  }, 450);
}

function qt_apply_totals(t) {
  if (!t) {
    return;
  }
  jQuery('#qt_disp_total_cost').text('MWK ' + qt_fmt(t.total_cost));
  jQuery('#qt_disp_total_sell').text('MWK ' + qt_fmt(t.total_sell));
  jQuery('#qt_disp_contingency').text('MWK ' + qt_fmt(t.contingency_amount));
  jQuery('#qt_disp_discount').text('MWK ' + qt_fmt(t.discount_amount));
  jQuery('#qt_disp_vat').text('MWK ' + qt_fmt(t.vat_amount));
  jQuery('#qt_disp_grand').text('MWK ' + qt_fmt(t.grand_total));
  jQuery('#qt_grand_total').val(t.grand_total);
  jQuery('#qt_subtotal').val(t.total_sell);
  jQuery('#qt_discount_total').val(t.discount_amount);
}

function qt_fmt(n) {
  var x = parseFloat(n);
  if (isNaN(x)) {
    x = 0;
  }
  return x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function qt_recalc_signage_row($row) {
  var w = parseFloat($row.find('[data-field="width_m"]').val()) || 0;
  var h = parseFloat($row.find('[data-field="height_m"]').val()) || 0;
  var q = parseFloat($row.find('[data-field="quantity"]').val()) || 1;
  var area = w * h;
  $row.find('[data-field="computed_area"]').val(area.toFixed(2));
  var cost = parseFloat($row.find('[data-field="cost_price"]').val()) || 0;
  var mk = parseFloat($row.find('[data-field="markup_percent"]').val()) || 0;
  var sell = parseFloat($row.find('[data-field="sell_price"]').val()) || 0;
  if (!sell && cost) {
    sell = cost * (1 + mk / 100);
    $row.find('[data-field="sell_price"]').val(sell.toFixed(2));
  }
  var mult = area > 0 ? area * q : q;
  var lineSell = sell * mult;
  $row.find('[data-field="line_total_sell"]').val(lineSell.toFixed(2));
}

function qt_recalc_install_row($row) {
  var rv = parseFloat($row.find('[data-field="rate_value"]').val()) || 0;
  var du = parseFloat($row.find('[data-field="duration"]').val()) || 1;
  var cost = rv * du;
  $row.find('[data-field="cost_price"]').val(cost.toFixed(2));
  var mk = parseFloat($row.find('[data-field="markup_percent"]').val()) || 0;
  var sell = parseFloat($row.find('[data-field="sell_price"]').val()) || 0;
  if (!sell && cost) {
    sell = cost * (1 + mk / 100);
    $row.find('[data-field="sell_price"]').val(sell.toFixed(2));
  }
  $row.find('[data-field="line_total_sell"]').val(sell.toFixed(2));
}

function qt_recalc_construction_row($row) {
  var q = parseFloat($row.find('[data-field="quantity"]').val()) || 1;
  var sell = parseFloat($row.find('[data-field="sell_price"]').val()) || 0;
  var mk = parseFloat($row.find('[data-field="markup_percent"]').val()) || 0;
  var costUnit = mk >= 0 && sell > 0 ? sell / (1 + mk / 100) : 0;
  $row.find('[data-field="line_total_cost"]').val((costUnit * q).toFixed(2));
  $row.find('[data-field="line_total_sell"]').val((sell * q).toFixed(2));
}

function qt_default_add_payload(tab, section) {
  var base = {
    proposal_id: qt_get_active_proposal_id(),
    id: 0,
    tab: tab,
    section_name: section || '',
    description: '',
    quantity: 1,
    unit: '',
    cost_price: 0,
    markup_percent: qt_config && qt_config.default_markup ? qt_config.default_markup : 25,
    sell_price: 0,
    width_m: 0,
    height_m: 0,
    substrate: '',
    print_type: '',
    activity_type: 'Labour',
    rate_type: 'per Hour',
    rate_value: 0,
    duration: 1,
    item_code: '',
    commodity_id: 0,
    is_taxable: 1,
  };
  return base;
}

function qt_add_row(e) {
  if (e) {
    e.preventDefault();
  }
  var $btn = jQuery(this);
  var tab = $btn.attr('data-tab');
  var section = $btn.attr('data-section') || '';
  qt_sync_proposal_id_from_form();
  var pid = qt_get_active_proposal_id();
  var $tbody = qt_find_tbody(tab, section);
  if (!$tbody.length) {
    $tbody = $btn.closest('.tab-pane').find('.qt-lines-tbody').first();
  }
  if (!tab || !$tbody.length) {
    return;
  }
  if (pid < 1) {
    var $blank = jQuery('<tr class="qt-line-row qt-unsaved" data-line-id="0" data-tab="' + tab + '" data-section="' + section + '"><td colspan="20" class="text-muted">Save the proposal to persist this row.</td></tr>');
    $tbody.append($blank);
    return;
  }
  var payload = qt_default_add_payload(tab, section);
  qt_ajax_post('save_line', payload, function (res) {
    if (!res || !res.success || !res.html) {
      return;
    }
    var $r = jQuery(res.html);
    $tbody.append($r);
    qt_refresh_line_sortable(jQuery('#qt-worksheet-panel'));
    qt_apply_totals(res.totals);
  });
}

function qt_update_totals_config() {
  qt_sync_proposal_id_from_form();
  var pid = qt_get_active_proposal_id();
  if (pid < 1) {
    return;
  }
  qt_ajax_post(
    'update_totals_config',
    {
      proposal_id: pid,
      contingency_percent: jQuery('#qt_contingency_input').val(),
      discount_percent: jQuery('#qt_discount_input').val(),
    },
    function (res) {
      if (res && res.totals) {
        qt_apply_totals(res.totals);
      }
      jQuery('#qt_contingency').val(jQuery('#qt_contingency_input').val());
      jQuery('#qt_discount').val(jQuery('#qt_discount_input').val());
      jQuery('#qt_discount_percent').val(jQuery('#qt_discount_input').val());
    }
  );
}

function qt_save_meta() {
  qt_sync_proposal_id_from_form();
  var pid = qt_get_active_proposal_id();
  if (pid < 1) {
    return;
  }
  qt_ajax_post('save_worksheet_meta', {
    proposal_id: pid,
    validity_days: jQuery('.qt-meta[data-meta="validity_days"]').val(),
    terms: jQuery('.qt-meta[data-meta="terms"]').val(),
    internal_notes: '',
  });
}

function qt_init_worksheet() {
  var $panel = jQuery('#qt-worksheet-panel');
  if (!$panel.length) {
    return;
  }

  qt_sync_proposal_id_from_form();

  setInterval(function () {
    var cur = qt_get_active_proposal_id();
    var prev = parseInt($panel.attr('data-proposal-id'), 10) || 0;
    if (cur > 0 && cur !== prev) {
      qt_apply_proposal_id_to_panel(cur);
    }
  }, 1500);

  qt_refresh_line_sortable($panel);

  $panel.on('click', '.qt-add-row', qt_add_row);

  $panel.on('click', '.qt-delete-line', function () {
    var $row = jQuery(this).closest('tr.qt-line-row');
    var id = parseInt($row.attr('data-line-id'), 10);
    var pid = qt_get_active_proposal_id();
    if (id > 0 && pid > 0) {
      qt_ajax_post('delete_line', { proposal_id: pid, id: id }, function (res) {
        if (res && res.success) {
          $row.remove();
          if (res.totals) {
            qt_apply_totals(res.totals);
          }
        }
      });
    } else {
      $row.remove();
    }
  });

  $panel.on('input change', '.qt-field', function () {
    var $row = jQuery(this).closest('tr.qt-line-row');
    var tab = $row.attr('data-tab');
    if (tab === 'signage') {
      qt_recalc_signage_row($row);
    } else if (tab === 'installation') {
      qt_recalc_install_row($row);
    } else if (tab === 'construction' || tab === 'retrofitting') {
      qt_recalc_construction_row($row);
    }
    qt_schedule_save($row);
  });

  $panel.on('change', '#qt_contingency_input, #qt_discount_input', function () {
    qt_update_totals_config();
  });

  $panel.on('blur', '.qt-meta', function () {
    qt_save_meta();
  });

  $panel.on('click', '.qt-btn-pdf', function () {
    qt_sync_proposal_id_from_form();
    var pid = qt_get_active_proposal_id();
    if (pid < 1) {
      alert_float('warning', 'Save the proposal first.');
      return;
    }
    window.open(admin_url + 'quotation_worksheet/pdf/' + pid, '_blank');
  });

  $panel.on('click', '.qt-btn-submit-approval', function () {
    qt_sync_proposal_id_from_form();
    var pid = qt_get_active_proposal_id();
    if (pid < 1) {
      alert_float('warning', 'Save the proposal first.');
      return;
    }
    qt_ajax_post('submit_for_approval', { proposal_id: pid }, function (res) {
      if (res && res.success) {
        alert_float('success', 'Submitted for approval.');
      } else {
        alert_float('danger', (res && res.message) || 'Unable to submit.');
      }
    });
  });

  /* Promotional autocomplete */
  if (jQuery.fn.autocomplete) {
    $panel.on('focus', '.qt-item-autocomplete', function () {
      var $inp = jQuery(this);
      if ($inp.data('qt-ac')) {
        return;
      }
      $inp.data('qt-ac', 1);
      $inp.autocomplete({
        source: function (request, response) {
          jQuery.getJSON(
            (qt_config.ajax_url || admin_url('quotation_worksheet/')) + 'search_inventory',
            { q: request.term },
            function (rows) {
              if (!rows || !rows.length) {
                response([]);
                return;
              }
              response(
                jQuery.map(rows, function (x) {
                  return {
                    label: x.label || x.code || '',
                    value: x.label || x.code || '',
                    code: x.code || '',
                    id: x.id,
                  };
                })
              );
            }
          );
        },
        minLength: 2,
        select: function (event, ui) {
          var $row = $inp.closest('tr.qt-line-row');
          $row.find('[data-field="description"]').val(ui.item.label);
          $row.find('[data-field="item_code"]').val(ui.item.code);
          var $cid = $row.find('[data-field="commodity_id"]');
          if (!$cid.length) {
            $row.find('td').first().append('<input type="hidden" class="qt-field" data-field="commodity_id" value="0">');
            $cid = $row.find('[data-field="commodity_id"]');
          }
          $cid.val(ui.item.id);
          jQuery.getJSON((qt_config.ajax_url || admin_url('quotation_worksheet/')) + 'get_item_wac', { id: ui.item.id }, function (r) {
            if (r && r.wac) {
              $row.find('[data-field="cost_price"]').val(r.wac);
            }
          });
          qt_save_line_row($row);
        },
      }).data('ui-autocomplete')._renderItem = function (ul, item) {
        return jQuery('<li>').append('<div>' + item.code + ' — ' + item.label + '</div>').appendTo(ul);
      };
    });
  }
}

window.qt_init_worksheet = qt_init_worksheet;

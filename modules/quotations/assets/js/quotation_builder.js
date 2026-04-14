/**
 * IPMS Quotation builder — AJAX lines, totals, sortable rows, autosave.
 */
(function ($) {
  'use strict';

  var cfg = window.qtBuilderConfig || {};
  var dirty = false;
  var userEditing = { markup: false, sell: false };
  var autosaveTimer = null;

  function csrfPayload() {
    var o = {};
    if (typeof csrfData !== 'undefined') {
      o[csrfData.token_name] = csrfData.hash;
    }
    return o;
  }

  function setDirty(v) {
    dirty = !!v;
  }

  function formatMwk(n) {
    var x = parseFloat(n) || 0;
    var parts = x.toFixed(2).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return 'MWK ' + parts.join('.');
  }

  function rowTab($tr) {
    return $tr.closest('tbody.qt-sortable').data('tab') || $tr.data('tab');
  }

  function ajaxInventory(term, done) {
    $.ajax({
      url: cfg.urlSearchInventory,
      data: { term: term },
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .done(function (rows) {
        done(rows);
      })
      .fail(function () {
        done(null);
      });
  }

  function updatePromoStockWarn($tr) {
    var raw = $tr.find('.qt-stock-qty').val();
    var st = parseFloat(raw);
    var q = parseFloat($tr.find('.qt-qty').val()) || 0;
    var $c = $tr.find('.qt-stock');
    if (raw === '' || isNaN(st)) {
      $c.removeClass('text-danger').addClass('text-warning');
      return;
    }
    $c.toggleClass('text-danger', q > st).toggleClass('text-warning', q <= st);
  }

  function applyFullTotals(ft) {
    if (!ft || !ft.tabs) return;
    var map = {
      signage: 'Signage & Printing',
      installation: 'Installation',
      construction: 'Construction Works',
      retrofitting: 'Shop Retrofitting',
      promotional: 'Promotional Items',
      additional: 'Additional Charges'
    };
    var $list = $('#qt-totals-tab-lines').empty();
    var k;
    for (k in map) {
      if (!map.hasOwnProperty(k)) continue;
      var v = parseFloat(ft.tabs[k]) || 0;
      if (v <= 0) continue;
      $list.append(
        $('<div class="clearfix mbot5"></div>')
          .append($('<span class="pull-left text-muted"></span>').text(map[k] + ':'))
          .append($('<span class="pull-right bold"></span>').text(formatMwk(v)))
      );
    }
    $('#qt-subtotal-lines').text(formatMwk(ft.subtotal_lines || 0));
    $('#qt-subtotal-after').text(formatMwk(ft.subtotal || 0));
    var cp = parseFloat(ft.contingency_percent) || 0;
    var subCont = (parseFloat(ft.subtotal_lines) || 0) * (1 + cp / 100);
    $('#qt-contingency-amt').text(formatMwk(subCont - (parseFloat(ft.subtotal_lines) || 0)));
    $('#qt-sub-cont').text(formatMwk(subCont));
    $('#qt-discount-amt').text('-' + formatMwk(ft.discount_applied || 0));
    $('#qt-after-disc').text(formatMwk(ft.subtotal || 0));
    $('#qt-vat-amt').text(formatMwk(ft.vat || 0));
    $('#qt-grand-total').text(formatMwk(ft.grand_total || 0));
    var gt = parseFloat(ft.grand_total) || 0;
    var margin = gt - (parseFloat(ft.total_cost) || 0);
    $('#qt-margin-amt').text(formatMwk(margin));
    var pct = gt > 0 ? (margin / gt) * 100 : 0;
    $('#qt-margin-pct').text(pct.toFixed(1) + '%');

    var thr = parseFloat(cfg.discountThreshold) || 10;
    var dp = parseFloat($('#qt-discount-percent').val()) || 0;
    $('#qt-discount-warning').toggle(
      $('#qt-discount-mode-pct').is(':checked') && dp > thr
    );
    updateTabDots(ft.tabs);
    if (ft.tabs) {
      for (var tk in ft.tabs) {
        if (ft.tabs.hasOwnProperty(tk)) {
          $('.qt-tab-subtotal[data-tab="' + tk + '"]').text(formatMwk(ft.tabs[tk]));
        }
      }
    }
  }

  function refreshBoqGrand(tab) {
    var pane = tab === 'construction' ? '#tab-construction' : '#tab-retrofitting';
    var sum = 0;
    $(pane + ' tbody.qt-sortable[data-tab="' + tab + '"]').each(function () {
      $(this)
        .find('tr.qt-line-row')
        .each(function () {
          var $r = $(this);
          sum += (parseFloat($r.find('.qt-qty').val()) || 0) * (parseFloat($r.find('.qt-sell').val()) || 0);
        });
    });
    $('#qt-boq-grand-' + tab).text(formatMwk(sum));
    $('.qt-tab-subtotal[data-tab="' + tab + '"]').text(formatMwk(sum));
  }

  function refreshBoqSectionFromTbody($tbody) {
    var tab = $tbody.data('tab');
    var sec = $tbody.data('boqSection') || $tbody.data('boq-section');
    if (!tab || !sec) return;
    var sum = 0;
    $tbody.find('tr.qt-line-row').each(function () {
      var $r = $(this);
      sum += (parseFloat($r.find('.qt-qty').val()) || 0) * (parseFloat($r.find('.qt-sell').val()) || 0);
    });
    $('.qt-boq-sec-subtotal[data-boq-tab="' + tab + '"][data-boq-section="' + sec + '"]').text(formatMwk(sum));
    $tbody.closest('.qt-boq-panel').find('.qt-boq-sec-count').text($tbody.find('tr.qt-line-row').length);
    refreshBoqGrand(tab);
  }

  function installToggleVisibility($tr) {
    var type = $tr.find('.qt-inst-type').val() || 'Labour';
    var showMap = {
      Labour: ['.qt-ic-staff', '.qt-ic-rate-type', '.qt-ic-rate', '.qt-ic-duration', '.qt-ic-qty'],
      Travel: ['.qt-ic-distance', '.qt-ic-rate', '.qt-ic-qty'],
      Equipment: ['.qt-ic-equip', '.qt-ic-duration', '.qt-ic-rate', '.qt-ic-qty'],
      'Lump Sum': ['.qt-ic-lump']
    };
    var show = showMap[type] || showMap.Labour;
    $tr.find('td.qt-ic').hide();
    show.forEach(function (sel) {
      $tr.find('td' + sel).show();
    });
    if (type === 'Lump Sum') {
      $tr.find('.qt-qty').val('1');
    }
  }

  function installSuggestCost($tr) {
    var type = $tr.find('.qt-inst-type').val() || 'Labour';
    var rate = parseFloat($tr.find('.qt-rate').val()) || 0;
    var staff = parseFloat($tr.find('.qt-staff-count').val()) || 0;
    var dur = parseFloat($tr.find('.qt-duration').val()) || 0;
    var dist = parseFloat($tr.find('.qt-distance').val()) || 0;
    var lump = parseFloat($tr.find('.qt-lump-amt').val()) || 0;
    var cost = 0;
    if (type === 'Labour') {
      cost = staff * rate * dur;
    } else if (type === 'Travel') {
      cost = dist * rate;
    } else if (type === 'Equipment') {
      cost = dur * rate;
    } else if (type === 'Lump Sum') {
      cost = lump;
    }
    $tr.find('.qt-cost').val(cost >= 0 ? cost.toFixed(4) : '0');
  }

  function updateTabDots(tabs) {
    $('.qt-tab-dot').removeClass('text-success').addClass('hide');
    if (!tabs) return;
    for (var k in tabs) {
      if (tabs.hasOwnProperty(k) && parseFloat(tabs[k]) > 0) {
        $('.qt-tab-dot[data-tab="' + k + '"]')
          .removeClass('hide')
          .addClass('text-success');
      }
    }
  }

  function postJSON(url, data, done) {
    data = $.extend({}, csrfPayload(), data || {});
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json, text/javascript, */*; q=0.01' }
    })
      .done(function (r) {
        if (typeof done === 'function') done(r);
      })
      .fail(function (xhr) {
        var msg = 'Request failed';
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        } else if (xhr && xhr.status === 404) {
          msg = 'Not found (404). Check admin URL and quotations routes.';
        } else if (xhr && (xhr.status === 403 || xhr.status === 419)) {
          msg = 'Session or CSRF expired — refresh the page and try again.';
        } else if (xhr && xhr.status === 500) {
          msg = 'Server error (500). Check application/logs or PHP error log.';
        } else if (xhr && xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') === 0) {
          msg = 'Unexpected HTML response (often login redirect or PHP error). Refresh and retry.';
        }
        if (typeof done === 'function') done({ success: false, message: msg });
      });
  }

  function saveBuilderSilent(cb) {
    if (!cfg.quotationId) return;
    var discPct = $('#qt-discount-percent').val();
    var discAmt = $('#qt-discount-amount').val();
    if ($('#qt-discount-mode-mwk').length && $('#qt-discount-mode-mwk').is(':checked')) {
      discPct = 0;
    } else if ($('#qt-discount-mode-pct').length) {
      discAmt = 0;
    }
    postJSON(
      cfg.urlSaveBuilder,
      {
        quotation_id: cfg.quotationId,
        client_id: $('#clientid').val(),
        quote_date: $('#qt_quote_date').val(),
        valid_until: $('#qt_valid_until').val(),
        internal_notes: $('#qt_internal_notes').val(),
        contingency_percent: $('#qt-contingency-percent').val(),
        discount_percent: discPct,
        discount_amount: discAmt
      },
      function (r) {
        if (r && r.success && r.full_totals) applyFullTotals(r.full_totals);
        if (cb) cb(r);
      }
    );
  }

  function collectRowPayload($tr) {
    var tab = rowTab($tr);
    var notesObj = {};
    try {
      notesObj = JSON.parse($tr.find('input.qt-notes-json').val() || '{}') || {};
    } catch (e) {}

    if (tab === 'installation') {
      notesObj.inst_type = $tr.find('.qt-inst-type').val() || 'Labour';
      notesObj.staff_count = $tr.find('.qt-staff-count').val();
      notesObj.rate_type = $tr.find('.qt-rate-type').val();
      notesObj.duration = $tr.find('.qt-duration').val();
      notesObj.distance_km = $tr.find('.qt-distance').val();
      notesObj.equip_type = $tr.find('.qt-equip-type').val();
      notesObj.lump_amount = $tr.find('.qt-lump-amt').val();
      notesObj.inst_rate = $tr.find('.qt-rate').val();
    }

    if (tab === 'signage') {
      notesObj.substrate = $tr.find('input[name="substrate"]').val() || '';
      notesObj.print_type = $tr.find('input[name="print_type"]').val() || '';
      notesObj.laminate = $tr.find('input[name="laminate"]').val() || '';
      notesObj.size_based = $tr.find('.qt-size-based').is(':checked') ? 1 : 0;
    }

    if (tab === 'construction' || tab === 'retrofitting') {
      var $tb = $tr.closest('tbody');
      var sec = $tb.data('boqSection') || $tb.data('boq-section');
      if (sec) notesObj.boq_section = sec;
      notesObj.item_no = $tr.find('input[name="item_no"]').val() || '';
      var $bp = $tr.closest('.qt-boq-panel');
      var btitle = $bp.data('boqSectionTitle') || $bp.data('boq-section-title');
      if (btitle) notesObj.boq_section_title = btitle;
    }

    var effQty;
    if (tab === 'signage') {
      var q0 = parseFloat($tr.find('.qt-qty-base').val()) || 0;
      if ($tr.find('.qt-size-based').length && $tr.find('.qt-size-based').is(':checked')) {
        var ar = parseFloat($tr.find('.qt-area').val()) || 0;
        effQty = q0 * (ar > 0 ? ar : 1);
      } else {
        effQty = q0;
      }
      $tr.find('input.qt-qty[type="hidden"]').val(effQty);
    } else {
      effQty = parseFloat($tr.find('.qt-qty').val()) || 0;
      if ($tr.find('.qt-size-based').length && $tr.find('.qt-size-based').is(':checked')) {
        var ar2 = parseFloat($tr.find('.qt-area').val()) || 0;
        var qb = parseFloat($tr.find('.qt-qty-base').val()) || 1;
        effQty = qb * (ar2 > 0 ? ar2 : 1);
      }
    }

    var $tax = $tr.find('.qt-taxable').first();
    var taxable;
    if (tab === 'additional') {
      taxable = $tr.find('.qt-taxable-cb').is(':checked') ? 1 : 0;
      $tr.find('.qt-taxable').val(String(taxable));
    } else if ($tax.is(':checkbox')) {
      taxable = $tax.is(':checked') ? 1 : 0;
    } else {
      taxable = String($tax.val()) === '1' ? 1 : 0;
    }

    var mkEl = $tr.find('.qt-markup').first();
    var markupVal = tab === 'additional' ? '0' : mkEl.val();

    return {
      quotation_id: cfg.quotationId,
      line_id: $tr.data('line-id') || 0,
      tab: tab,
      description: $tr.find('.qt-desc').first().val() || '—',
      item_code: $tr.find('.qt-item-code').val() || '',
      inventory_item_id: $tr.find('.qt-inv-id').val(),
      unit: $tr.find('.qt-unit').first().val(),
      quantity: effQty,
      width_m: $tr.find('.qt-width').val(),
      height_m: $tr.find('.qt-height').val(),
      computed_area: $tr.find('.qt-area').val(),
      cost_price: tab === 'additional' ? $tr.find('.qt-sell').val() : $tr.find('.qt-cost').val(),
      markup_percent: markupVal,
      sell_price: $tr.find('.qt-sell').val(),
      sell_price_manual: $tr.find('.qt-sell-manual').val() === '1' ? '1' : '',
      is_taxable: taxable,
      notes: JSON.stringify(notesObj),
      line_order: $tr.index()
    };
  }

  function saveLineRow($tr, cb) {
    if (!cfg.quotationId || cfg.linesLocked) return;
    var p = collectRowPayload($tr);
    postJSON(cfg.urlSaveLine, p, function (r) {
      if (r && r.success) {
        if (r.line_id) $tr.attr('data-line-id', r.line_id);
        if (r.full_totals) applyFullTotals(r.full_totals);
        setDirty(false);
      }
      if (cb) cb(r);
    });
  }

  function recalcLineUI($tr) {
    var tab = rowTab($tr);

    if (tab === 'construction' || tab === 'retrofitting') {
      var qty = parseFloat($tr.find('.qt-qty').val()) || 0;
      var cost = parseFloat($tr.find('.qt-cost').val()) || 0;
      var sellIn = parseFloat($tr.find('.qt-sell').val());
      var mkHidden = parseFloat($tr.find('input.qt-markup').val()) || 0;
      if (!userEditing.sell && cost > 0 && mkHidden > 0) {
        var sell1 = cost * (1 + mkHidden / 100);
        $tr.find('.qt-sell').val(sell1.toFixed(4));
      } else if (userEditing.sell && cost > 0 && !isNaN(sellIn)) {
        var mk2 = ((sellIn - cost) / cost) * 100;
        if (!userEditing.markup && isFinite(mk2)) {
          $tr.find('input.qt-markup').val(mk2.toFixed(2));
        }
      }
      var sell = parseFloat($tr.find('.qt-sell').val()) || 0;
      var cost2 = parseFloat($tr.find('.qt-cost').val()) || 0;
      if (cost2 > 0 && isFinite(sell) && !userEditing.sell) {
        var mkf = ((sell - cost2) / cost2) * 100;
        if (isFinite(mkf)) $tr.find('input.qt-markup').val(mkf.toFixed(2));
      }
      $tr.find('.qt-boq-cost-amt').text(formatMwk(qty * cost2));
      $tr.find('.qt-line-total').text(formatMwk(qty * sell));
      refreshBoqSectionFromTbody($tr.closest('tbody'));
      return;
    }

    if (tab === 'additional') {
      var sellA = parseFloat($tr.find('.qt-sell').val()) || 0;
      $tr.find('.qt-cost').val(sellA);
      $tr.find('.qt-line-total').text(formatMwk(sellA));
      return;
    }

    var cost = parseFloat($tr.find('.qt-cost').val()) || 0;
    var markup = parseFloat($tr.find('.qt-markup').val()) || 0;
    var sellIn2 = parseFloat($tr.find('.qt-sell').val());
    if (!userEditing.sell && markup > 0 && cost > 0) {
      var sell2 = cost * (1 + markup / 100);
      $tr.find('.qt-sell').val(sell2.toFixed(4));
    } else if (userEditing.sell && cost > 0 && !isNaN(sellIn2)) {
      var mk3 = ((sellIn2 - cost) / cost) * 100;
      if (!userEditing.markup) $tr.find('.qt-markup').val(mk3.toFixed(2));
    }

    var sellF = parseFloat($tr.find('.qt-sell').val()) || 0;
    var qtyLine;
    if (tab === 'signage') {
      var qb0 = parseFloat($tr.find('.qt-qty-base').val()) || 0;
      if ($tr.find('.qt-size-based').length && $tr.find('.qt-size-based').is(':checked')) {
        var ar3 = parseFloat($tr.find('.qt-area').val()) || 0;
        qtyLine = qb0 * (ar3 > 0 ? ar3 : 1);
      } else {
        qtyLine = qb0;
      }
      $tr.find('input.qt-qty[type="hidden"]').val(qtyLine);
    } else {
      qtyLine = parseFloat($tr.find('.qt-qty').val()) || 0;
    }
    var lt = sellF * qtyLine;
    $tr.find('.qt-line-total').text(formatMwk(lt));
  }

  function bindRow($tr) {
    $tr.find('.qt-width,.qt-height').on('blur', function () {
      var w = parseFloat($tr.find('.qt-width').val()) || 0;
      var h = parseFloat($tr.find('.qt-height').val()) || 0;
      var a = w * h;
      $tr.find('.qt-area').val(a > 0 ? a.toFixed(3) : '');
      recalcLineUI($tr);
      saveLineRow($tr);
      setDirty(true);
    });
    $tr.find('.qt-markup').on('focus', function () {
      userEditing.markup = true;
    });
    $tr.find('.qt-markup').on('blur change', function () {
      userEditing.markup = false;
      userEditing.sell = false;
      recalcLineUI($tr);
      saveLineRow($tr);
      setDirty(true);
    });
    $tr.find('.qt-sell').on('focus', function () {
      userEditing.sell = true;
      $tr.find('.qt-sell-manual').val('1');
    });
    $tr.find('.qt-sell').on('blur change', function () {
      userEditing.sell = false;
      recalcLineUI($tr);
      saveLineRow($tr);
      setDirty(true);
    });
    $tr
      .find(
        '.qt-cost,.qt-qty,.qt-qty-base,.qt-size-based,.qt-taxable,.qt-rate,.qt-inst-type,.qt-duration,.qt-distance,.qt-lump-amt,.qt-staff-count,.qt-rate-type,.qt-equip-type'
      )
      .on('change blur', function () {
        if ($tr.find('.qt-promo-wrap').length) updatePromoStockWarn($tr);
        if (rowTab($tr) === 'installation') {
          if ($(this).hasClass('qt-inst-type')) {
            installToggleVisibility($tr);
          }
          if (
            $(this).is(
              '.qt-inst-type,.qt-rate,.qt-duration,.qt-distance,.qt-lump-amt,.qt-staff-count,.qt-rate-type,.qt-equip-type'
            )
          ) {
            installSuggestCost($tr);
          }
        }
        recalcLineUI($tr);
        saveLineRow($tr);
        setDirty(true);
      });
    $tr.find('input[name="substrate"],input[name="print_type"],input[name="laminate"],input[name="item_no"]').on('change blur', function () {
      recalcLineUI($tr);
      saveLineRow($tr);
      setDirty(true);
    });
    $tr.find('.qt-taxable-cb').on('change', function () {
      $tr.find('.qt-taxable').val($(this).is(':checked') ? '1' : '0');
      recalcLineUI($tr);
      saveLineRow($tr);
      setDirty(true);
    });
    $tr.find('.qt-item-code').on('blur', function () {
      if (rowTab($tr) === 'promotional') return;
      var code = $(this).val();
      if (!code || !code.trim()) return;
      $.ajax({
        url: cfg.urlGetInventoryItem,
        data: { code: code.trim() },
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).done(function (r) {
        if (r && r.success) {
          $tr.find('.qt-inv-id').val(r.commodity_id || '');
          if (r.commodity_code) $tr.find('.qt-item-code').val(r.commodity_code);
          $tr.find('.qt-cost').val(parseFloat(r.wac_price) || 0);
          if (r.unit) $tr.find('.qt-unit').val(r.unit);
          recalcLineUI($tr);
          saveLineRow($tr);
        }
      });
    });
    $tr.find('.qt-del-row').on('click', function () {
      var delTab = rowTab($tr);
      var $tbod = $tr.closest('tbody');
      var lid = parseInt($tr.data('line-id'), 10);
      if (lid > 0) {
        postJSON(
          cfg.urlDeleteLine,
          { quotation_id: cfg.quotationId, line_id: lid },
          function (r) {
            if (r && r.success && r.full_totals) applyFullTotals(r.full_totals);
          }
        );
      }
      $tr.remove();
      if (delTab === 'construction' || delTab === 'retrofitting') {
        refreshBoqSectionFromTbody($tbod);
      }
      setDirty(true);
      saveBuilderSilent();
    });
  }

  function initSortableOne($tb) {
    if (!$tb || !$tb.length || !$.fn.sortable) return;
    try {
      if ($tb.data('ui-sortable')) $tb.sortable('destroy');
    } catch (e1) {}
    $tb.sortable({
      handle: '.qt-drag',
      axis: 'y',
      helper: function (e, ui) {
        ui.children().each(function () {
          $(this).width($(this).width());
        });
        return ui;
      },
      update: function () {
        var orders = {};
        $tb.find('tr.qt-line-row').each(function (i) {
          var id = parseInt($(this).data('line-id'), 10);
          if (id > 0) orders[id] = i;
        });
        postJSON(
          cfg.urlSaveLineOrder,
          { quotation_id: cfg.quotationId, orders: JSON.stringify(orders) },
          function (r) {
            if (r && r.full_totals) applyFullTotals(r.full_totals);
          }
        );
        setDirty(true);
      }
    });
  }

  function initSortable() {
    if (!$.fn.sortable) return;
    $('tbody.qt-sortable').each(function () {
      var $tb = $(this);
      try {
        if ($tb.data('ui-sortable')) $tb.sortable('destroy');
      } catch (e2) {}
      $tb.sortable({
        handle: '.qt-drag',
        axis: 'y',
        helper: function (e, ui) {
          ui.children().each(function () {
            $(this).width($(this).width());
          });
          return ui;
        },
        update: function () {
          var orders = {};
          $tb.find('tr.qt-line-row').each(function (i) {
            var id = parseInt($(this).data('line-id'), 10);
            if (id > 0) orders[id] = i;
          });
          postJSON(
            cfg.urlSaveLineOrder,
            { quotation_id: cfg.quotationId, orders: JSON.stringify(orders) },
            function (r) {
              if (r && r.full_totals) applyFullTotals(r.full_totals);
            }
          );
          setDirty(true);
        }
      });
    });
  }

  function addRow(tab, $tbody, boqSection) {
    var tplKey = tab === 'retrofitting' ? 'construction' : tab;
    var $tpl = $('#qt-tpl-row-' + tplKey);
    if (!$tpl.length) return;
    var html = $tpl.html().replace(/__IDX__/g, String(Date.now()));
    var $tr = $(html);
    if (boqSection) {
      var o = { boq_section: boqSection };
      var $panel = $tbody.closest('.qt-boq-panel');
      var ptitle = $panel.data('boqSectionTitle') || $panel.data('boq-section-title');
      if (ptitle) o.boq_section_title = ptitle;
      try {
        $.extend(o, JSON.parse($tr.find('input.qt-notes-json').val() || '{}'));
      } catch (e) {}
      $tr.find('input.qt-notes-json').val(JSON.stringify(o));
    }
    $tbody.append($tr);
    bindRow($tr);
    recalcLineUI($tr);
    if (tab === 'promotional') {
      wirePromoSearch($tr.find('.qt-promo-wrap'));
    }
    if (tab === 'construction' || tab === 'retrofitting') {
      initSortableOne($tbody);
      refreshBoqSectionFromTbody($tbody);
    }
    if (tab === 'installation') {
      installToggleVisibility($tr);
      installSuggestCost($tr);
    }
    setDirty(true);
  }

  function startQuotation() {
    var cid = $('#clientid').val();
    if (!cid) {
      alert_float('danger', 'Please select a client.');
      return;
    }
    postJSON(
      cfg.urlAjaxCreate,
      { client_id: cid, internal_notes: $('#qt_internal_notes').val() },
      function (r) {
        if (r && r.success && r.redirect) {
          window.location.href = r.redirect;
        } else {
          alert_float('danger', (r && r.message) || 'Could not create quotation');
        }
      }
    );
  }

  function wirePromoSearch($wrap) {
    var $inp = $wrap.find('.qt-promo-search');
    var $menu = $wrap.find('.qt-promo-menu');
    var tmo;
    $inp.on('input', function () {
      var t = $inp.val().trim();
      clearTimeout(tmo);
      if (t.length < 3) {
        $menu.hide().empty();
        return;
      }
      tmo = setTimeout(function () {
        ajaxInventory(t, function (rows) {
          $menu.empty();
          if (!rows || !rows.length) {
            $menu.hide();
            return;
          }
          rows.forEach(function (row) {
            var $a = $('<a href="#" class="list-group-item"></a>')
              .text((row.commodity_name || '') + ' (' + (row.commodity_code || '') + ')')
              .data('row', row);
            $menu.append($a);
          });
          $menu.show();
        });
      }, 250);
    });
    $menu.on('click', 'a', function (e) {
      e.preventDefault();
      var row = $(this).data('row');
      var $tr = $wrap.closest('tr');
      $tr.find('.qt-inv-id').val(row.commodity_id || '');
      $tr.find('.qt-item-code').val(row.commodity_code || '');
      $tr.find('.qt-desc').val(row.commodity_name || '');
      $tr.find('.qt-unit').val(row.unit || '');
      $tr.find('.qt-cost').val(parseFloat(row.wac_price) || 0);
      var sq = row.stock_qty != null ? row.stock_qty : '';
      $tr.find('.qt-stock-qty').val(sq);
      $tr.find('.qt-stock').text(sq !== '' ? sq : '—');
      $inp.val(row.commodity_name || '');
      $menu.hide();
      updatePromoStockWarn($tr);
      recalcLineUI($tr);
      saveLineRow($tr);
    });
  }

  $(function () {
    if (!$('#qt-builder-root').length) return;

    $('.qt-line-row').each(function () {
      bindRow($(this));
      recalcLineUI($(this));
      updatePromoStockWarn($(this));
    });
    $('.qt-inst-row').each(function () {
      installToggleVisibility($(this));
    });
    $('.qt-promo-wrap').each(function () {
      wirePromoSearch($(this));
    });
    initSortable();
    $('tbody.qt-sortable[data-tab="construction"],tbody.qt-sortable[data-tab="retrofitting"]').each(function () {
      refreshBoqSectionFromTbody($(this));
    });

    if (typeof init_ajax_search === 'function' && $('#clientid.ajax-search').length) {
      init_ajax_search('customer', '#clientid.ajax-search');
    }
    if ($.fn.selectpicker && $('.selectpicker').length) {
      $('.selectpicker').selectpicker();
    }

    var $qtTabs = $('#quotation-tabs');
    if ($qtTabs.length) {
      $qtTabs.find('a[data-toggle="tab"]').on('click', function (e) {
        var href = $(this).attr('href');
        if (!href || href.indexOf('#') !== 0) {
          return;
        }
        e.preventDefault();
        if ($.fn.tab) {
          try {
            $(this).tab('show');
            return;
          } catch (err) {}
        }
        $qtTabs.find('li').removeClass('active');
        $(this).closest('li').addClass('active');
        $('#qt-builder-root .tab-content > .tab-pane').removeClass('active');
        $('#qt-builder-root .tab-content').find(href).addClass('active');
      });
    }

    $('#qt-btn-start').on('click', startQuotation);

    $('.qt-add-row').on('click', function () {
      var tab = $(this).data('tab');
      var sec = $(this).data('boqSection');
      var $tb = $(this).closest('.panel').find('tbody.qt-sortable[data-tab="' + tab + '"]').first();
      if (!$tb.length) $tb = $('tbody.qt-sortable[data-tab="' + tab + '"]').first();
      if ($tb.length) addRow(tab, $tb, sec || '');
    });

    $('#qt-save-draft').on('click', function () {
      saveBuilderSilent(function () {
        alert_float('success', 'Draft saved');
      });
    });

    $('#qt-save-pdf').on('click', function () {
      saveBuilderSilent(function () {
        window.open(cfg.urlPdf, '_blank');
      });
    });

    function syncDiscountModeUi(skipSave) {
      var mwk = $('#qt-discount-mode-mwk').is(':checked');
      $('.qt-discount-pct-row').toggleClass('hide', mwk);
      $('.qt-discount-mwk-row').toggleClass('hide', !mwk);
      if (!skipSave) {
        saveBuilderSilent();
        setDirty(true);
      }
    }
    $('input[name="qt_discount_mode"]').on('change', function () {
      syncDiscountModeUi(false);
    });
    if ($('#qt-discount-amount').val() && parseFloat($('#qt-discount-amount').val()) > 0 && parseFloat($('#qt-discount-percent').val()) === 0) {
      $('#qt-discount-mode-mwk').prop('checked', true);
    }
    syncDiscountModeUi(true);

    $('#qt-contingency-percent,#qt-discount-percent,#qt-discount-amount').on('change blur', function () {
      saveBuilderSilent();
      setDirty(true);
    });

    $('#qt_quote_date,#qt_valid_until,#qt_internal_notes').on('change blur', function () {
      if (cfg.quotationId) {
        saveBuilderSilent();
        setDirty(true);
      }
    });

    $('#qt-btn-open-submit-modal').on('click', function () {
      $('#qt-modal-submit-approval').modal('show');
    });
    $('#qt-confirm-submit-approval').on('click', function () {
      $('#qt-modal-submit-approval').modal('hide');
      $('#qt-form-submit-approval').trigger('submit');
    });

    $('.qt-add-boq-section').on('click', function () {
      var tab = $(this).data('tab');
      var name = window.prompt('New section name');
      if (!name || !String(name).trim()) return;
      name = String(name).trim();
      var sec = 'U' + Date.now();
      var panelId = 'boq-' + tab + '-' + sec;
      var esc = name.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
      var html = $('#qt-tpl-boq-section')
        .html()
        .replace(/__TAB__/g, tab)
        .replace(/__SEC__/g, sec)
        .replace(/__TITLE__/g, esc)
        .replace(/__PANELID__/g, panelId);
      $('#qt-boq-extra-' + tab).append(html);
      var $tb = $('#' + panelId).find('tbody.qt-sortable').first();
      initSortableOne($tb);
      refreshBoqSectionFromTbody($tb);
      setDirty(true);
    });

    $('#qt-btn-send-client').on('click', function () {
      if (!cfg.urlSendEmail) {
        alert_float('danger', 'Send email is not available for this quotation.');
        return;
      }
      $('#qt-email-to').val(cfg.clientEmail || '');
      $('#qt-email-subject').val('Quotation ' + cfg.quotationRef + ' from MW');
      $('#qt-email-body').val(
        'Dear Customer,\n\nPlease find our quotation ' +
          cfg.quotationRef +
          ' attached.\n\nKind regards,\n' +
          (cfg.companyName || '')
      );
      $('#qt-modal-send-client').modal('show');
    });

    $('#qt-email-send-btn').on('click', function () {
      if (!cfg.urlSendEmail) {
        alert_float('danger', 'Send email is not available.');
        return;
      }
      var $btn = $(this);
      $btn.prop('disabled', true);
      $('#qt-email-spin').removeClass('hide');
      var data = {
        recipient_email: $('#qt-email-to').val(),
        cc: $('#qt-email-cc').val(),
        subject: $('#qt-email-subject').val(),
        message: $('#qt-email-body').val(),
        attach_pdf: $('#qt-email-attach').is(':checked') ? 1 : 0
      };
      $.extend(data, csrfPayload());
      $.ajax({
        url: cfg.urlSendEmail,
        type: 'POST',
        data: data,
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .done(function (r) {
          if (r && r.success) {
            alert_float('success', r.message || 'Sent');
            $('#qt-modal-send-client').modal('hide');
          } else {
            alert_float('danger', (r && r.message) || 'Failed');
          }
        })
        .fail(function () {
          alert_float('danger', 'Request failed');
        })
        .always(function () {
          $btn.prop('disabled', false);
          $('#qt-email-spin').addClass('hide');
        });
    });

    if (cfg.fullTotals) applyFullTotals(cfg.fullTotals);

    autosaveTimer = setInterval(function () {
      if (cfg.quotationId && dirty) {
        saveBuilderSilent();
        dirty = false;
      }
    }, 60000);

    $(window).on('beforeunload', function () {
      if (dirty) return 'You have unsaved changes.';
    });
  });
})(jQuery);

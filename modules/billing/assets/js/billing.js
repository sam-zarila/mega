/**
 * IPMS Billing — payment UI, finance inbox actions, invoice tab, DN prefill.
 */
var Billing = Billing || {};

function billing_fmt(n) {
  return parseFloat(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function billing_admin_url(path) {
  var u = typeof admin_url !== 'undefined' && admin_url ? admin_url : '';
  if (!path) {
    return u;
  }
  path = String(path).replace(/^\//, '');
  if (u.slice(-1) !== '/') {
    return u + '/' + path;
  }
  return u + path;
}

function billing_parse_json_response(res) {
  if (typeof res === 'string') {
    try {
      return JSON.parse(res);
    } catch (e) {
      return {};
    }
  }
  return res || {};
}

// Payment method tiles
Billing.initPaymentTiles = function () {
  $(document).on('click', '.payment-method-tile', function () {
    $('.payment-method-tile').removeClass('selected');
    $(this).addClass('selected').find('input[type=radio]').prop('checked', true);
    var method = $(this).data('method') || 'cash';
    var needsRef = ['bank_transfer', 'cheque', 'airtel_money', 'tnm_mpamba'];
    var showRef = needsRef.indexOf(method) !== -1;
    $('#reference-group').toggle(showRef);
    $('#reference-group input[name="reference_number"]').prop('required', showRef);
  });
};

// Payment amount — partial payment indicator + GM warning
Billing.initPaymentAmount = function () {
  $('#payment-amount').on('input change', function () {
    var amount = parseFloat($(this).val()) || 0;
    var balance = parseFloat($(this).data('balance')) || 0;
    var threshold =
      typeof billing_config !== 'undefined' && billing_config.payment_threshold
        ? parseFloat(billing_config.payment_threshold)
        : 5000000;
    var remaining = balance - amount;
    if (amount < balance && amount > 0) {
      $('#partial-indicator').removeClass('hide');
      $('#remaining-balance').text('MWK ' + billing_fmt(remaining));
    } else {
      $('#partial-indicator').addClass('hide');
    }
    if (amount > threshold) {
      $('#gm-approval-warning').removeClass('hide');
    } else {
      $('#gm-approval-warning').addClass('hide');
    }
  });
};

Billing.initRecordPaymentForm = function () {
  $('#payment-form').on('submit', function (e) {
    var method =
      $('input[name="billing_payment_method_detail"]:checked').val() || 'cash';
    var ref = $.trim($('input[name="reference_number"]').val() || '');
    if (method !== 'cash' && ref === '') {
      e.preventDefault();
      alert('Please enter a reference number for this payment method.');
      $('#reference-group').show();
      $('input[name="reference_number"]').focus();
      return false;
    }
    return true;
  });
};

// Finance inbox — approve payment AJAX
Billing.initApprovePayment = function () {
  $(document).on('click', '.approve-payment-btn, .billing-approve-payment', function () {
    var btn = $(this);
    var payment_id = btn.data('payment') || btn.data('id');
    if (!payment_id || !confirm('Approve this payment?')) {
      return;
    }
    btn.addClass('disabled');
    $.post(
      billing_admin_url('billing/approve_payment/' + payment_id),
      {},
      function (res) {
        res = billing_parse_json_response(res);
        if (res.success) {
          btn.closest('tr').fadeOut(300);
          if (typeof alert_float === 'function') {
            alert_float('success', res.message || 'OK');
          } else {
            alert(res.message || 'OK');
          }
        } else {
          if (typeof alert_float === 'function') {
            alert_float('danger', res.message || 'Failed');
          } else {
            alert(res.message || 'Failed');
          }
          btn.removeClass('disabled');
        }
      },
      'json'
    ).fail(function () {
      btn.removeClass('disabled');
      if (typeof alert_float === 'function') {
        alert_float('danger', 'Request failed');
      }
    });
  });
};

// Finance inbox — reject CN AJAX
Billing.initRejectCN = function () {
  $(document).on('click', '.reject-cn-btn, .billing-reject-cn-open', function () {
    var cn_id = $(this).data('cn') || $(this).data('id');
    if ($('#cn-reject-modal').length) {
      $('#cn-reject-modal').data('cn-id', cn_id).modal('show');
    } else {
      $('#billingRejectCnId').val(cn_id || '');
      $('#billingRejectCnReason').val('');
      $('#billingRejectCnModal').modal('show');
    }
  });

  $(document).on('click', '#confirm-reject-cn-btn, #billingRejectCnConfirm', function () {
    var btn = $(this);
    var cn_id = $('#cn-reject-modal').data('cn-id') || $('#billingRejectCnId').val();
    var reason = $('#cn-rejection-reason').length
      ? $('#cn-rejection-reason').val().trim()
      : $('#billingRejectCnReason').val().trim();
    if (!reason) {
      alert('Please provide a rejection reason.');
      return;
    }
    btn.addClass('disabled');
    $.post(
      billing_admin_url('billing/reject_cn/' + cn_id),
      { rejection_reason: reason },
      function (res) {
        res = billing_parse_json_response(res);
        if (res.success) {
          $('#cn-reject-modal, #billingRejectCnModal').modal('hide');
          $('[data-cn="' + cn_id + '"], [data-id="' + cn_id + '"]')
            .closest('tr')
            .fadeOut(300);
          if (typeof alert_float === 'function') {
            alert_float('success', res.message || 'OK');
          } else {
            alert(res.message || 'OK');
          }
        } else {
          if (typeof alert_float === 'function') {
            alert_float('danger', res.message || 'Failed');
          } else {
            alert(res.message || 'Failed');
          }
        }
      },
      'json'
    ).always(function () {
      btn.removeClass('disabled');
    });
  });
};

// Approve CN button
Billing.initApproveCN = function () {
  $(document).on('click', '.approve-cn-btn, .billing-approve-cn', function () {
    var cn_id = $(this).data('cn') || $(this).data('id');
    if (
      !cn_id ||
      !confirm(
        'Approve this credit note? This cannot be undone. GL entries will be posted.'
      )
    ) {
      return;
    }
    var btn = $(this);
    btn.addClass('disabled');
    $.post(
      billing_admin_url('billing/approve_cn/' + cn_id),
      {},
      function (res) {
        res = billing_parse_json_response(res);
        if (res.success) {
          btn.closest('tr').fadeOut(300);
          if (typeof alert_float === 'function') {
            alert_float('success', res.message || 'OK');
          } else {
            alert(res.message || 'OK');
          }
        } else {
          if (typeof alert_float === 'function') {
            alert_float('danger', res.message || 'Failed');
          } else {
            alert(res.message || 'Failed');
          }
          btn.removeClass('disabled');
        }
      },
      'json'
    ).fail(function () {
      btn.removeClass('disabled');
    });
  });
};

// Load billing info tab (IPMS pane on invoice preview)
Billing.initBillingTab = function () {
  var $pane = $('#tab_ipms_billing');
  if (!$pane.length) {
    return;
  }
  $('a[href="#tab_ipms_billing"]').on('shown.bs.tab', function () {
    if ($pane.data('loaded')) {
      return;
    }
    var invoice_id = $pane.data('invoice-id');
    if (!invoice_id) {
      return;
    }
    $pane.html(
      '<div class="text-center mtop20"><i class="fa fa-spinner fa-spin fa-2x"></i></div>'
    );
    $.get(billing_admin_url('billing/get_invoice_billing_tab/' + invoice_id), function (html) {
      $pane.html(html).data('loaded', true);
    });
  });
};

// Invoice create — prefill lines from delivery note (session flash)
Billing.initPrefillInvoiceFromDn = function () {
  function prefillInvoiceFromDn() {
    if (!window.BILLING_DN_PREFILL || !$('#invoice-form').length) {
      return;
    }
    var pf = window.BILLING_DN_PREFILL;
    if (pf.terms && $('textarea[name="terms"]').length) {
      $('textarea[name="terms"]').val(pf.terms);
    }
    if (!pf.lines || !pf.lines.length) {
      return;
    }
    if (typeof add_item_to_table !== 'function') {
      return;
    }
    $.each(pf.lines, function (_i, line) {
      var data = {
        description: line.description || '',
        long_description: line.long_description || '',
        qty: line.qty,
        rate: line.rate,
        taxname: line.taxname || [],
        unit: '',
        is_optional: false,
        is_selected: true,
      };
      add_item_to_table(data, null, false, false);
    });
  }

  $(function () {
    prefillInvoiceFromDn();
  });
};

$(document).ready(function () {
  Billing.initPrefillInvoiceFromDn();
  Billing.initPaymentTiles();
  Billing.initPaymentAmount();
  Billing.initRecordPaymentForm();
  Billing.initApprovePayment();
  Billing.initRejectCN();
  Billing.initApproveCN();
  Billing.initBillingTab();

  if ($('.payment-method-tile').length) {
    var $sel = $('.payment-method-tile.selected').first();
    if (!$sel.length) {
      $sel = $('.payment-method-tile').first();
    }
    if ($sel.length) {
      $sel.trigger('click');
    }
  }
});

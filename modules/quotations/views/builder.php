<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!function_exists('qt_notes_arr')) {
    function qt_notes_arr($notes)
    {
        $d = json_decode($notes ?? '', true);

        return is_array($d) ? $d : [];
    }
}

if (!function_exists('qt_boq_section_meta')) {
    /**
     * @param array<int, array> $lines
     * @param array<string, string> $default_sections
     *
     * @return array{order: string[], titles: array<string, string>}
     */
    function qt_boq_section_meta($lines, $default_sections)
    {
        $titles = $default_sections;
        $order  = array_keys($default_sections);
        foreach ($lines as $ln) {
            $m = qt_notes_arr($ln['notes'] ?? '');
            $c = isset($m['boq_section']) ? (string) $m['boq_section'] : 'A';
            if (!isset($titles[$c])) {
                $titles[$c] = !empty($m['boq_section_title']) ? $m['boq_section_title'] : ('Section ' . $c);
                $order[]    = $c;
            } elseif (!array_key_exists($c, $default_sections) && !empty($m['boq_section_title'])) {
                $titles[$c] = $m['boq_section_title'];
            }
        }
        $seen = [];
        $uniq = [];
        foreach ($order as $k) {
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $uniq[]   = $k;
        }

        return ['order' => $uniq, 'titles' => $titles];
    }
}

if (!function_exists('qt_boq_line_sell')) {
    function qt_boq_line_sell($ln)
    {
        $q = (float) ($ln['quantity'] ?? 0);
        $s = (float) ($ln['sell_price'] ?? 0);

        return $q * $s;
    }
}

if (!function_exists('qt_boq_line_cost')) {
    function qt_boq_line_cost($ln)
    {
        $q = (float) ($ln['quantity'] ?? 0);
        $c = (float) ($ln['cost_price'] ?? 0);

        return $q * $c;
    }
}

$quotation    = isset($quotation) ? $quotation : null;
$estimate     = isset($estimate) ? $estimate : null;
$lines_by_tab = isset($lines_by_tab) && is_array($lines_by_tab) ? $lines_by_tab : array_fill_keys(['signage', 'installation', 'construction', 'retrofitting', 'promotional', 'additional'], []);
$edit         = !empty($edit);
$locked       = !empty($lines_locked);
$qid          = $quotation ? (int) $quotation->id : 0;
$ref          = $quotation ? $quotation->quotation_ref : '';
$version      = $quotation ? (int) $quotation->version : 1;
$status       = $quotation ? $quotation->status : 'draft';
$customer_id  = isset($customer_id) ? (int) $customer_id : ($quotation ? (int) $quotation->client_id : 0);

$quoteDate = date('Y-m-d');
$validUntil = date('Y-m-d', strtotime('+' . (int) ($default_validity_days ?: 30) . ' days'));
if ($estimate) {
    $quoteDate  = $estimate->date ?: $quoteDate;
    $validUntil = $estimate->expirydate ?: $validUntil;
} elseif ($quotation) {
    $quoteDate  = date('Y-m-d', strtotime($quotation->created_at));
    $validUntil = date('Y-m-d', strtotime($quotation->created_at . ' +' . (int) $quotation->validity_days . ' days'));
}

$contPct    = $quotation ? (float) $quotation->contingency_percent : (float) ($default_contingency ?: 0);
$discPct    = $quotation ? (float) $quotation->discount_percent : 0;
$discAmt    = $quotation ? (float) $quotation->discount_amount : 0;
$intNotes   = $quotation ? $quotation->internal_notes : '';

$builderConfig = [
    'quotationId'        => $qid,
    'linesLocked'        => $locked,
    'quotationRef'       => $ref,
    'clientEmail'        => isset($client_primary_email) ? $client_primary_email : '',
    'companyName'        => get_option('companyname'),
    'discountThreshold'  => isset($qt_discount_threshold) ? (float) $qt_discount_threshold : 10,
    'vatRate'            => isset($qt_vat_rate) ? (float) $qt_vat_rate : 16.5,
    'urlSaveLine'        => admin_url('quotations/save_line'),
    'urlDeleteLine'      => admin_url('quotations/delete_line'),
    'urlSaveLineOrder'   => admin_url('quotations/save_line_order'),
    'urlSaveBuilder'     => admin_url('quotations/save_builder'),
    'urlAjaxCreate'      => admin_url('quotations/ajax_create'),
    'urlSearchInventory' => admin_url('quotations/search_inventory'),
    'urlGetInventoryItem'=> admin_url('quotations/get_inventory_item'),
    'urlSendEmail'       => $qid ? admin_url('quotations/send_email/' . $qid) : '',
    'urlPdf'             => $qid ? admin_url('quotations/pdf/' . $qid) : '',
    'fullTotals'         => isset($full_totals) ? $full_totals : null,
];

init_head();
?>
<link href="<?php echo base_url('assets/plugins/jquery-ui/jquery-ui.css'); ?>" rel="stylesheet" type="text/css" />
<style>
/* Tab strip + panes: some admin themes override Bootstrap tabs; keep builder usable */
#qt-builder-root #quotation-tabs.nav-tabs {
  display: flex;
  flex-wrap: wrap;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 0;
  padding-left: 0;
  list-style: none;
}
#qt-builder-root #quotation-tabs.nav-tabs > li {
  float: none;
  margin-bottom: -1px;
}
#qt-builder-root #quotation-tabs.nav-tabs > li > a {
  display: block;
  padding: 10px 14px;
  margin-right: 2px;
  border: 1px solid transparent;
  border-radius: 4px 4px 0 0;
  color: #374151;
}
#qt-builder-root #quotation-tabs.nav-tabs > li.active > a,
#qt-builder-root #quotation-tabs.nav-tabs > li > a:focus,
#qt-builder-root #quotation-tabs.nav-tabs > li > a:hover {
  color: #111827;
  background: #fff;
  border-color: #e5e7eb #e5e7eb #fff;
}
#qt-builder-root .tab-content > .tab-pane:not(.active) {
  display: none !important;
}
#qt-builder-root .tab-content > .tab-pane.active {
  display: block !important;
}
@media (max-width: 991px) {
  #qt-totals-sticky { position: fixed !important; left: 0; right: 0; bottom: 0; top: auto !important; z-index: 1005; max-height: 45vh; overflow-y: auto; box-shadow: 0 -2px 8px rgba(0,0,0,.15); }
  #qt-builder-root { padding-bottom: 120px; }
}
</style>
<div id="wrapper">
    <div class="content">
        <div id="qt-builder-root" class="row">
            <div class="col-md-9">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="page-title mtop0 mbot20"><?php echo html_escape($title); ?></h4>
                                <div class="form-group select-placeholder">
                                    <label for="clientid" class="control-label"><?php echo _l('estimate_select_customer'); ?></label>
                                    <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                                        <?php
                                        if ($customer_id > 0) {
                                            $rel_data = get_relation_data('customer', $customer_id);
                                            $rel_val  = get_relation_values($rel_data, 'customer');
                                            echo '<option value="' . (int) $rel_val['id'] . '" selected="selected">' . e($rel_val['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Quotation Reference</label>
                                    <input type="text" class="form-control" readonly value="<?php echo $ref ? e($ref) : '(generated on save)'; ?>">
                                </div>
                                <p>
                                    <span class="label label-default">Version v<?php echo (int) $version; ?></span>
                                    <?php echo qt_get_status_label($status); ?>
                                </p>
                                <?php if (!$qid) { ?>
                                    <div class="alert alert-info">Select a client, then click <strong>Start quotation</strong> to create a draft and enable line entry.</div>
                                    <button type="button" class="btn btn-info" id="qt-btn-start">Start quotation</button>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="text" class="form-control datepicker" id="qt_quote_date" name="quote_date" value="<?php echo e($quoteDate); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label>Valid Until</label>
                                    <input type="text" class="form-control datepicker" id="qt_valid_until" name="valid_until" value="<?php echo e($validUntil); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label>Prepared By</label>
                                    <input type="text" class="form-control" readonly value="<?php echo e(get_staff_full_name(get_staff_user_id())); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Internal Notes <small class="text-muted">(not on PDF)</small></label>
                                    <textarea name="internal_notes" id="qt_internal_notes" class="form-control" rows="3" <?php echo $locked ? 'disabled' : ''; ?>><?php echo e($intNotes); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <ul class="nav nav-tabs mtop20" role="tablist" id="quotation-tabs">
                            <li class="active" role="presentation"><a href="#tab-signage" aria-controls="tab-signage" role="tab" data-toggle="tab"><i class="fa fa-print"></i> Signage & Printing <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="signage" style="font-size:8px;"></span></a></li>
                            <li role="presentation"><a href="#tab-installation" aria-controls="tab-installation" role="tab" data-toggle="tab"><i class="fa fa-wrench"></i> Installation <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="installation" style="font-size:8px;"></span></a></li>
                            <li role="presentation"><a href="#tab-construction" aria-controls="tab-construction" role="tab" data-toggle="tab"><i class="fa fa-building"></i> Construction Works <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="construction" style="font-size:8px;"></span></a></li>
                            <li role="presentation"><a href="#tab-retrofitting" aria-controls="tab-retrofitting" role="tab" data-toggle="tab"><i class="fa fa-home"></i> Shop Retrofitting <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="retrofitting" style="font-size:8px;"></span></a></li>
                            <li role="presentation"><a href="#tab-promotional" aria-controls="tab-promotional" role="tab" data-toggle="tab"><i class="fa fa-tag"></i> Promotional Items <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="promotional" style="font-size:8px;"></span></a></li>
                            <li role="presentation"><a href="#tab-additional" aria-controls="tab-additional" role="tab" data-toggle="tab"><i class="fa fa-plus-circle"></i> Additional Charges <span class="qt-tab-dot hide fa fa-circle text-success" data-tab="additional" style="font-size:8px;"></span></a></li>
                        </ul>

                        <div class="tab-content panel-body mtop0 bt0">
                            <div class="tab-pane active" id="tab-signage">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;"></th>
                                                <th>Item / Description</th>
                                                <th>Substrate</th>
                                                <th>Print Type</th>
                                                <th>Laminate</th>
                                                <th>Width (m)</th>
                                                <th>Height (m)</th>
                                                <th>Area (m²)</th>
                                                <th>Qty</th>
                                                <th>Size-based</th>
                                                <th>Cost Price (MWK)</th>
                                                <th>Markup %</th>
                                                <th>Sell Price (MWK)</th>
                                                <th>Line Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="qt-sortable" data-tab="signage">
                                            <?php foreach ($lines_by_tab['signage'] as $ln) {
                                                $m = qt_notes_arr($ln['notes'] ?? '');
                                                ?>
                                                <tr class="qt-line-row" data-line-id="<?php echo (int) $ln['id']; ?>" data-tab="signage">
                                                    <td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
                                                    <td>
                                                        <input type="text" class="form-control input-sm qt-desc mbot5" value="<?php echo e($ln['description']); ?>" placeholder="Description" <?php echo $locked ? 'disabled' : ''; ?>>
                                                        <input type="text" class="form-control input-sm qt-item-code" value="<?php echo e($ln['item_code']); ?>" placeholder="Item code (WAC lookup)" <?php echo $locked ? 'disabled' : ''; ?>>
                                                    </td>
                                                    <td><input type="text" class="form-control input-sm" name="substrate" value="<?php echo e($m['substrate'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="text" class="form-control input-sm" name="print_type" value="<?php echo e($m['print_type'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="text" class="form-control input-sm" name="laminate" value="<?php echo e($m['laminate'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.001" class="form-control input-sm qt-width" value="<?php echo e($ln['width_m']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.001" class="form-control input-sm qt-height" value="<?php echo e($ln['height_m']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.001" class="form-control input-sm qt-area" value="<?php echo e($ln['computed_area']); ?>" readonly></td>
                                                    <td><input type="number" step="0.001" class="form-control input-sm qt-qty qt-qty-base" value="<?php echo e($ln['quantity']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="text-center"><input type="checkbox" class="qt-size-based" <?php echo !empty($m['size_based']) ? 'checked' : ''; ?> <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="<?php echo e($ln['cost_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($ln['markup_percent']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="<?php echo e($ln['sell_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>><input type="hidden" class="qt-sell-manual" value="0"></td>
                                                    <td class="qt-line-total text-right bold">—</td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-xs qt-del-row" <?php echo $locked ? 'disabled' : ''; ?>><i class="fa fa-times"></i></button>
                                                        <input type="hidden" class="qt-notes-json" value="<?php echo e(htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8')); ?>">
                                                        <input type="hidden" class="qt-inv-id" value="<?php echo (int) ($ln['inventory_item_id'] ?? 0); ?>">
                                                        <input type="hidden" class="qt-unit" value="<?php echo e($ln['unit']); ?>">
                                                        <input type="hidden" class="qt-qty" value="<?php echo e($ln['quantity']); ?>">
                                                        <input type="hidden" class="qt-taxable" value="1">
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="signage"><i class="fa fa-plus"></i> Add Row</button>
                                <?php } ?>
                                <?php
                                $stSign = 0.0;
                                foreach ($lines_by_tab['signage'] ?? [] as $_sl) {
                                    $stSign += (float) ($_sl['line_total_sell'] ?? 0);
                                }
                                ?>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="signage"><?php echo e(qt_format_mwk($stSign)); ?></span></p>
                            </div>

                            <div class="tab-pane" id="tab-installation">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Activity Description</th>
                                                <th>Type</th>
                                                <th class="qt-ic qt-ic-staff">Staff Count</th>
                                                <th class="qt-ic qt-ic-rate-type">Rate Type</th>
                                                <th class="qt-ic qt-ic-rate">Rate (MWK)</th>
                                                <th class="qt-ic qt-ic-duration">Duration / Qty</th>
                                                <th class="qt-ic qt-ic-distance">Distance (km)</th>
                                                <th class="qt-ic qt-ic-equip">Equipment</th>
                                                <th class="qt-ic qt-ic-lump">Lump sum (MWK)</th>
                                                <th class="qt-ic qt-ic-qty">Qty</th>
                                                <th>Cost Price (MWK)</th>
                                                <th>Markup %</th>
                                                <th>Sell Price (MWK)</th>
                                                <th>Line Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="qt-sortable" data-tab="installation">
                                            <?php foreach ($lines_by_tab['installation'] as $ln) {
                                                $m = qt_notes_arr($ln['notes'] ?? '');
                                                $instRate = isset($m['inst_rate']) ? $m['inst_rate'] : $ln['cost_price'];
                                                ?>
                                                <tr class="qt-line-row qt-inst-row" data-line-id="<?php echo (int) $ln['id']; ?>">
                                                    <td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
                                                    <td><input type="text" class="form-control input-sm qt-desc" value="<?php echo e($ln['description']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td>
                                                        <select class="form-control input-sm qt-inst-type" <?php echo $locked ? 'disabled' : ''; ?>>
                                                            <?php foreach (['Labour', 'Travel', 'Equipment', 'Lump Sum'] as $t) {
                                                                echo '<option value="' . e($t) . '"' . (($m['inst_type'] ?? 'Labour') === $t ? ' selected' : '') . '>' . e($t) . '</option>';
                                                            } ?>
                                                        </select>
                                                    </td>
                                                    <td class="qt-ic qt-ic-staff"><input type="number" class="form-control input-sm qt-staff-count" value="<?php echo e($m['staff_count'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-rate-type">
                                                        <select class="form-control input-sm qt-rate-type" <?php echo $locked ? 'disabled' : ''; ?>>
                                                            <?php foreach (['per Hour', 'per Day', 'Lump Sum'] as $t) {
                                                                echo '<option value="' . e($t) . '"' . (($m['rate_type'] ?? 'per Hour') === $t ? ' selected' : '') . '>' . e($t) . '</option>';
                                                            } ?>
                                                        </select>
                                                    </td>
                                                    <td class="qt-ic qt-ic-rate"><input type="number" step="0.01" class="form-control input-sm qt-rate" value="<?php echo e($instRate); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-duration"><input type="number" step="0.01" class="form-control input-sm qt-duration" value="<?php echo e($m['duration'] ?? $ln['quantity']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-distance"><input type="number" step="0.01" class="form-control input-sm qt-distance" value="<?php echo e($m['distance_km'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-equip"><input type="text" class="form-control input-sm qt-equip-type" value="<?php echo e($m['equip_type'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-lump"><input type="number" step="0.01" class="form-control input-sm qt-lump-amt" value="<?php echo e($m['lump_amount'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-ic qt-ic-qty"><input type="number" step="0.01" class="form-control input-sm qt-qty" value="<?php echo e($ln['quantity']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="<?php echo e($ln['cost_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($ln['markup_percent']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="<?php echo e($ln['sell_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>><input type="hidden" class="qt-sell-manual" value="0"></td>
                                                    <td class="qt-line-total text-right bold">—</td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-xs qt-del-row" <?php echo $locked ? 'disabled' : ''; ?>><i class="fa fa-times"></i></button>
                                                        <input type="hidden" class="qt-notes-json" value="<?php echo e(htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8')); ?>">
                                                        <input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id"><input type="hidden" class="qt-unit">
                                                        <input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
                                                        <input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
                                                        <input type="hidden" class="qt-taxable" value="1">
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="installation"><i class="fa fa-plus"></i> Add Row</button>
                                <?php } ?>
                                <?php
                                $stInst = 0.0;
                                foreach ($lines_by_tab['installation'] ?? [] as $_il) {
                                    $stInst += (float) ($_il['line_total_sell'] ?? 0);
                                }
                                ?>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="installation"><?php echo e(qt_format_mwk($stInst)); ?></span></p>
                            </div>

                            <?php
                            $render_boq = static function ($tabKey, $default_sections, $lines, $locked) {
                                $meta = qt_boq_section_meta($lines, $default_sections);
                                foreach ($meta['order'] as $code) {
                                    $title = $meta['titles'][$code] ?? $code;
                                    $secLines = array_values(array_filter($lines, static function ($ln) use ($code) {
                                        $m = qt_notes_arr($ln['notes'] ?? '');

                                        return (string) ($m['boq_section'] ?? 'A') === (string) $code;
                                    }));
                                    $secSell = 0.0;
                                    foreach ($secLines as $ln) {
                                        $secSell += qt_boq_line_sell($ln);
                                    }
                                    $secId = 'boq-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $tabKey . '-' . $code);
                                    $panelTitleAttr = array_key_exists($code, $default_sections) ? '' : ' data-boq-section-title="' . e($title) . '"';
                                    ?>
                                    <div class="panel panel-default mtop15 qt-boq-panel" data-boq-tab="<?php echo e($tabKey); ?>" data-boq-section="<?php echo e($code); ?>"<?php echo $panelTitleAttr; ?>>
                                        <div class="panel-heading" role="tab">
                                            <a data-toggle="collapse" href="#<?php echo e($secId); ?>">
                                                <?php echo e($title); ?>
                                                <span class="badge"><?php echo count($secLines); ?></span>
                                                <span class="pull-right text-muted small mright20">Subtotal: <strong class="qt-boq-sec-subtotal" data-boq-tab="<?php echo e($tabKey); ?>" data-boq-section="<?php echo e($code); ?>"><?php echo e(qt_format_mwk($secSell)); ?></strong></span>
                                            </a>
                                        </div>
                                        <div id="<?php echo e($secId); ?>" class="panel-collapse collapse in">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-condensed mbot0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:28px;"></th>
                                                            <th>Item No</th>
                                                            <th>Description</th>
                                                            <th>Unit</th>
                                                            <th>Qty</th>
                                                            <th>Unit rate cost (MWK)</th>
                                                            <th>Unit rate sell (MWK)</th>
                                                            <th>Cost amount</th>
                                                            <th>Sell amount</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="qt-sortable" data-tab="<?php echo e($tabKey); ?>" data-boq-section="<?php echo e($code); ?>">
                                                        <?php foreach ($secLines as $ln) {
                                                            $m = qt_notes_arr($ln['notes'] ?? '');
                                                            $cAmt = (float) ($ln['line_total_cost'] ?? qt_boq_line_cost($ln));
                                                            $sAmt = (float) ($ln['line_total_sell'] ?? qt_boq_line_sell($ln));
                                                            ?>
                                                            <tr class="qt-line-row" data-line-id="<?php echo (int) $ln['id']; ?>">
                                                                <td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
                                                                <td><input type="text" class="form-control input-sm" name="item_no" value="<?php echo e($m['item_no'] ?? ''); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                                <td><input type="text" class="form-control input-sm qt-desc" value="<?php echo e($ln['description']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                                <td><input type="text" class="form-control input-sm qt-unit" value="<?php echo e($ln['unit']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                                <td><input type="number" step="0.001" class="form-control input-sm qt-qty" value="<?php echo e($ln['quantity']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                                <td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="<?php echo e($ln['cost_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                                <td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="<?php echo e($ln['sell_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>><input type="hidden" class="qt-sell-manual" value="0"></td>
                                                                <td class="text-right small qt-boq-cost-amt"><?php echo e(qt_format_mwk($cAmt)); ?></td>
                                                                <td class="text-right small qt-line-total"><?php echo e(qt_format_mwk($sAmt)); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-danger btn-xs qt-del-row" <?php echo $locked ? 'disabled' : ''; ?>><i class="fa fa-times"></i></button>
                                                                    <input type="hidden" class="qt-notes-json" value="<?php echo e(htmlspecialchars(json_encode(array_merge($m, ['boq_section' => $code])), ENT_QUOTES, 'UTF-8')); ?>">
                                                                    <input type="hidden" class="qt-markup" value="<?php echo e($ln['markup_percent']); ?>">
                                                                    <input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id">
                                                                    <input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
                                                                    <input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
                                                                    <input type="hidden" class="qt-taxable" value="1">
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php if (!$locked) { ?>
                                                <div class="panel-footer">
                                                    <button type="button" class="btn btn-default btn-xs qt-add-row" data-tab="<?php echo e($tabKey); ?>" data-boq-section="<?php echo e($code); ?>"><i class="fa fa-plus"></i> Add Row</button>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php
                                }
                            };
                            ?>

                            <div class="tab-pane" id="tab-construction">
                                <?php
                                $render_boq('construction', [
                                    'A' => 'Section A: Materials',
                                    'B' => 'Section B: Labour',
                                    'C' => 'Section C: Plant & Equipment',
                                    'D' => 'Section D: Subcontractors',
                                ], $lines_by_tab['construction'] ?? [], $locked);
                                ?>
                                <div id="qt-boq-extra-construction"></div>
                                <?php
                                $gc = 0.0;
                                foreach ($lines_by_tab['construction'] ?? [] as $_ln) {
                                    $gc += qt_boq_line_sell($_ln);
                                }
                                ?>
                                <p class="bold mtop15 qt-boq-grand-wrap" data-boq-tab="construction">Grand BOQ total: <span class="qt-boq-grand-total" id="qt-boq-grand-construction"><?php echo e(qt_format_mwk($gc)); ?></span></p>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="construction"><?php echo e(qt_format_mwk($gc)); ?></span></p>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-boq-section" data-tab="construction"><i class="fa fa-plus"></i> Add Section</button>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab-retrofitting">
                                <?php
                                $render_boq('retrofitting', [
                                    'A' => 'Section A: Carpentry & Joinery',
                                    'B' => 'Section B: Electrical Works',
                                    'C' => 'Section C: Signage Works',
                                    'D' => 'Section D: Painting & Finishing',
                                ], $lines_by_tab['retrofitting'] ?? [], $locked);
                                ?>
                                <div id="qt-boq-extra-retrofitting"></div>
                                <?php
                                $gr = 0.0;
                                foreach ($lines_by_tab['retrofitting'] ?? [] as $_ln) {
                                    $gr += qt_boq_line_sell($_ln);
                                }
                                ?>
                                <p class="bold mtop15 qt-boq-grand-wrap" data-boq-tab="retrofitting">Grand BOQ total: <span class="qt-boq-grand-total" id="qt-boq-grand-retrofitting"><?php echo e(qt_format_mwk($gr)); ?></span></p>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="retrofitting"><?php echo e(qt_format_mwk($gr)); ?></span></p>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-boq-section" data-tab="retrofitting"><i class="fa fa-plus"></i> Add Section</button>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab-promotional">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Item</th>
                                                <th>Item Code</th>
                                                <th>UOM</th>
                                                <th>Available Stock</th>
                                                <th>Qty</th>
                                                <th>Cost Price (WAC)</th>
                                                <th>Markup %</th>
                                                <th>Sell Price (MWK)</th>
                                                <th>Line Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="qt-sortable" data-tab="promotional">
                                            <?php foreach ($lines_by_tab['promotional'] as $ln) { ?>
                                                <tr class="qt-line-row" data-line-id="<?php echo (int) $ln['id']; ?>">
                                                    <td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
                                                    <td class="qt-promo-wrap" style="min-width:200px;">
                                                        <input type="text" class="form-control input-sm qt-promo-search qt-desc" value="<?php echo e($ln['description']); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                                                        <div class="list-group qt-promo-menu" style="position:absolute;z-index:50;display:none;max-height:200px;overflow:auto;"></div>
                                                    </td>
                                                    <td><input type="text" class="form-control input-sm qt-item-code" value="<?php echo e($ln['item_code']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="text" class="form-control input-sm qt-unit" value="<?php echo e($ln['unit']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="qt-stock text-warning small">—</td>
                                                    <td><input type="number" step="0.001" class="form-control input-sm qt-qty" value="<?php echo e($ln['quantity']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="<?php echo e($ln['cost_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($ln['markup_percent']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="<?php echo e($ln['sell_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>><input type="hidden" class="qt-sell-manual" value="0"></td>
                                                    <td class="qt-line-total text-right bold">—</td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-xs qt-del-row" <?php echo $locked ? 'disabled' : ''; ?>><i class="fa fa-times"></i></button>
                                                        <input type="hidden" class="qt-notes-json" value="{}">
                                                        <input type="hidden" class="qt-inv-id" value="<?php echo (int) ($ln['inventory_item_id'] ?? 0); ?>">
                                                        <input type="hidden" class="qt-stock-qty" value="">
                                                        <input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
                                                        <input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
                                                        <input type="hidden" class="qt-taxable" value="1">
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="promotional"><i class="fa fa-plus"></i> Add Row</button>
                                <?php } ?>
                                <?php
                                $stPr = 0.0;
                                foreach ($lines_by_tab['promotional'] ?? [] as $_pl) {
                                    $stPr += (float) ($_pl['line_total_sell'] ?? 0);
                                }
                                ?>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="promotional"><?php echo e(qt_format_mwk($stPr)); ?></span></p>
                            </div>

                            <div class="tab-pane" id="tab-additional">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Charge</th>
                                                <th>Amount (MWK)</th>
                                                <th>Taxable</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="qt-sortable" data-tab="additional">
                                            <?php foreach ($lines_by_tab['additional'] as $ln) { ?>
                                                <tr class="qt-line-row" data-line-id="<?php echo (int) $ln['id']; ?>">
                                                    <td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
                                                    <td><input type="text" class="form-control input-sm qt-desc" value="<?php echo e($ln['description']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td><input type="number" step="0.01" class="form-control input-sm qt-sell" value="<?php echo e($ln['sell_price']); ?>" <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td class="text-center"><input type="checkbox" class="qt-taxable-cb" <?php echo !empty($ln['is_taxable']) ? 'checked' : ''; ?> <?php echo $locked ? 'disabled' : ''; ?>></td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-xs qt-del-row" <?php echo $locked ? 'disabled' : ''; ?>><i class="fa fa-times"></i></button>
                                                        <input type="hidden" class="qt-notes-json" value="{}">
                                                        <input type="hidden" class="qt-cost" value="<?php echo e($ln['cost_price']); ?>">
                                                        <input type="hidden" class="qt-markup" value="0">
                                                        <input type="hidden" class="qt-sell-manual" value="1">
                                                        <input type="hidden" class="qt-qty" value="1">
                                                        <input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id"><input type="hidden" class="qt-unit">
                                                        <input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
                                                        <input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
                                                        <input type="hidden" class="qt-taxable" value="<?php echo !empty($ln['is_taxable']) ? '1' : '0'; ?>">
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$locked) { ?>
                                    <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="additional"><i class="fa fa-plus"></i> Add Row</button>
                                <?php } ?>
                                <?php
                                $stAd = 0.0;
                                foreach ($lines_by_tab['additional'] ?? [] as $_al) {
                                    $stAd += (float) ($_al['line_total_sell'] ?? 0);
                                }
                                ?>
                                <p class="mtop10 bold">Tab Total: <span class="qt-tab-subtotal" data-tab="additional"><?php echo e(qt_format_mwk($stAd)); ?></span></p>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-6 text-left">
                                <?php if ($qid && !$locked) { ?>
                                    <button type="button" class="btn btn-primary" id="qt-save-draft"><i class="fa fa-save"></i> Save Draft</button>
                                    <button type="button" class="btn btn-default mleft5" id="qt-save-pdf"><i class="fa fa-file-pdf"></i> Save &amp; Preview PDF</button>
                                <?php } ?>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php if ($qid && $status === 'draft' && !$locked) { ?>
                                    <?php echo form_open(admin_url('quotations/submit_for_approval/' . $qid), ['id' => 'qt-form-submit-approval', 'class' => 'inline-block']); ?>
                                    <?php echo form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash()); ?>
                                    <button type="button" class="btn btn-success" id="qt-btn-open-submit-modal"><i class="fa fa-check"></i> Submit for Approval</button>
                                    <?php echo form_close(); ?>
                                <?php } ?>
                                <?php if ($qid && $status === 'approved') { ?>
                                    <button type="button" class="btn btn-info" id="qt-btn-send-client"><i class="fa fa-envelope"></i> Send to Client</button>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if (!empty($version_history) && is_array($version_history) && count($version_history) > 0) { ?>
                            <div class="panel panel-default mtop25">
                                <div class="panel-heading">
                                    <a data-toggle="collapse" href="#qt-version-history"><i class="fa fa-history"></i> Version History</a>
                                </div>
                                <div id="qt-version-history" class="panel-collapse collapse">
                                    <ul class="list-group">
                                        <?php foreach ($version_history as $vh) {
                                            $vid = (int) ($vh['id'] ?? 0);
                                            $isCurrent = $quotation && $vid === (int) $quotation->id;
                                            ?>
                                            <li class="list-group-item">
                                                <strong>v<?php echo (int) ($vh['version'] ?? 1); ?></strong>
                                                — <?php echo e(get_staff_full_name((int) ($vh['created_by'] ?? 0))); ?>
                                                — <?php echo e(_dt($vh['created_at'] ?? '')); ?>
                                                — Status: <?php echo e($vh['status'] ?? ''); ?>
                                                <?php if ($isCurrent) { ?><span class="label label-success">current</span><?php } ?>
                                                <span class="pull-right">
                                                    <a href="<?php echo admin_url('quotations/view/' . $vid); ?>">View</a>
                                                    <?php if (($vh['status'] ?? '') === 'draft') { ?>
                                                        | <a href="<?php echo admin_url('quotations/edit/' . $vid); ?>">Edit</a>
                                                    <?php } ?>
                                                </span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div id="qt-totals-sticky" style="position:sticky;top:20px;">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Totals</div>
                        <div class="panel-body">
                            <div id="qt-totals-tab-lines" class="small mbot10"></div>
                            <hr class="hr-10">
                            <div class="clearfix mbot5"><span class="pull-left bold">Subtotal</span><span class="pull-right bold" id="qt-subtotal-lines"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix mbot5">
                                <span class="pull-left">Contingency (<input type="number" step="0.01" id="qt-contingency-percent" class="form-control input-sm" style="display:inline-block;width:55px;" value="<?php echo e($contPct); ?>" <?php echo $locked ? 'disabled' : ''; ?>)%</span>
                                <span class="pull-right" id="qt-contingency-amt"><?php echo qt_format_mwk(0); ?></span>
                            </div>
                            <div class="clearfix mbot5 text-muted"><span class="pull-left">Subtotal + Contingency</span><span class="pull-right" id="qt-sub-cont"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix mbot5">
                                <span class="pull-left">Discount</span>
                                <span class="pull-right small">
                                    <label class="radio-inline mtop0"><input type="radio" name="qt_discount_mode" id="qt-discount-mode-pct" value="percent" checked <?php echo $locked ? 'disabled' : ''; ?>> %</label>
                                    <label class="radio-inline mtop0"><input type="radio" name="qt_discount_mode" id="qt-discount-mode-mwk" value="mwk" <?php echo $locked ? 'disabled' : ''; ?>> MWK</label>
                                </span>
                            </div>
                            <div class="clearfix mbot5 qt-discount-pct-row">
                                <span class="pull-left">Discount (%)</span>
                                <input type="number" step="0.01" id="qt-discount-percent" class="form-control input-sm pull-right" style="width:70px;" value="<?php echo e($discPct); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                            </div>
                            <div class="clearfix mbot5 qt-discount-mwk-row hide">
                                <span class="pull-left">Discount (MWK)</span>
                                <input type="number" step="0.01" id="qt-discount-amount" class="form-control input-sm pull-right" style="width:100px;" value="<?php echo e($discAmt); ?>" <?php echo $locked ? 'disabled' : ''; ?>>
                            </div>
                            <div class="clearfix mbot5 text-warning small hide" id="qt-discount-warning">&#9888; Discount above threshold — manager approval required</div>
                            <div class="clearfix mbot5"><span class="pull-left">Discount (applied)</span><span class="pull-right" id="qt-discount-amt"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix mbot5"><span class="pull-left">After Discount</span><span class="pull-right bold" id="qt-after-disc"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix mbot5"><span class="pull-left">VAT (<?php echo e((string) ($qt_vat_rate ?? 16.5)); ?>%)</span><span class="pull-right" id="qt-vat-amt"><?php echo qt_format_mwk(0); ?></span></div>
                            <hr>
                            <div class="clearfix mbot10"><span class="pull-left bold">Grand Total</span><span class="pull-right bold text-success" id="qt-grand-total"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix mbot5"><span class="pull-left">Gross Margin</span><span class="pull-right" id="qt-margin-amt"><?php echo qt_format_mwk(0); ?></span></div>
                            <div class="clearfix"><span class="pull-left">Margin %</span><span class="pull-right" id="qt-margin-pct">0%</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qt-modal-submit-approval" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Submit for approval</h4>
            </div>
            <div class="modal-body">
                <p>Submit this quotation for manager approval? You will not be able to edit line items while it is pending.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-success" id="qt-confirm-submit-approval"><i class="fa fa-check"></i> Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qt-modal-send-client" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Send quotation to client</h4>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>To</label><input type="email" class="form-control" id="qt-email-to"></div>
                <div class="form-group"><label>CC</label><input type="text" class="form-control" id="qt-email-cc" placeholder="optional"></div>
                <div class="form-group"><label>Subject</label><input type="text" class="form-control" id="qt-email-subject"></div>
                <div class="form-group"><label>Message</label><textarea class="form-control" rows="6" id="qt-email-body"></textarea></div>
                <div class="checkbox"><label><input type="checkbox" id="qt-email-attach" checked> Attach PDF</label></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-info" id="qt-email-send-btn"><span id="qt-email-spin" class="hide"><i class="fa fa-spinner fa-spin"></i></span> Send</button>
            </div>
        </div>
    </div>
</div>

<script type="text/template" id="qt-tpl-row-signage">
<tr class="qt-line-row" data-line-id="0">
<td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
<td>
<input type="text" class="form-control input-sm qt-desc mbot5" value="" placeholder="Description">
<input type="text" class="form-control input-sm qt-item-code" placeholder="Item code (WAC lookup)">
</td>
<td><input type="text" class="form-control input-sm" name="substrate"></td>
<td><input type="text" class="form-control input-sm" name="print_type"></td>
<td><input type="text" class="form-control input-sm" name="laminate"></td>
<td><input type="number" step="0.001" class="form-control input-sm qt-width"></td>
<td><input type="number" step="0.001" class="form-control input-sm qt-height"></td>
<td><input type="number" step="0.001" class="form-control input-sm qt-area" readonly></td>
<td><input type="number" step="0.001" class="form-control input-sm qt-qty qt-qty-base" value="1"></td>
<td class="text-center"><input type="checkbox" class="qt-size-based"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="0"></td>
<td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($default_markup ?? '25'); ?>"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="0"><input type="hidden" class="qt-sell-manual" value="0"></td>
<td class="qt-line-total text-right bold">MWK 0.00</td>
<td>
<button type="button" class="btn btn-danger btn-xs qt-del-row"><i class="fa fa-times"></i></button>
<input type="hidden" class="qt-notes-json" value="{}">
<input type="hidden" class="qt-inv-id" value="0">
<input type="hidden" class="qt-unit" value="">
<input type="hidden" class="qt-qty" value="1">
<input type="hidden" class="qt-taxable" value="1">
</td>
</tr>
</script>

<script type="text/template" id="qt-tpl-row-installation">
<tr class="qt-line-row qt-inst-row" data-line-id="0">
<td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
<td><input type="text" class="form-control input-sm qt-desc"></td>
<td><select class="form-control input-sm qt-inst-type"><?php foreach (['Labour','Travel','Equipment','Lump Sum'] as $t) {
    echo '<option value="' . e($t) . '">' . e($t) . '</option>';
} ?></select></td>
<td class="qt-ic qt-ic-staff"><input type="number" class="form-control input-sm qt-staff-count" value="1"></td>
<td class="qt-ic qt-ic-rate-type"><select class="form-control input-sm qt-rate-type"><?php foreach (['per Hour','per Day','Lump Sum'] as $t) {
    echo '<option value="' . e($t) . '">' . e($t) . '</option>';
} ?></select></td>
<td class="qt-ic qt-ic-rate"><input type="number" step="0.01" class="form-control input-sm qt-rate" value="0"></td>
<td class="qt-ic qt-ic-duration"><input type="number" step="0.01" class="form-control input-sm qt-duration" value="1"></td>
<td class="qt-ic qt-ic-distance"><input type="number" step="0.01" class="form-control input-sm qt-distance" value=""></td>
<td class="qt-ic qt-ic-equip"><input type="text" class="form-control input-sm qt-equip-type"></td>
<td class="qt-ic qt-ic-lump"><input type="number" step="0.01" class="form-control input-sm qt-lump-amt" value=""></td>
<td class="qt-ic qt-ic-qty"><input type="number" step="0.01" class="form-control input-sm qt-qty" value="1"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="0"></td>
<td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($default_markup ?? '25'); ?>"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="0"><input type="hidden" class="qt-sell-manual" value="0"></td>
<td class="qt-line-total text-right bold">MWK 0.00</td>
<td>
<button type="button" class="btn btn-danger btn-xs qt-del-row"><i class="fa fa-times"></i></button>
<input type="hidden" class="qt-notes-json" value="{}">
<input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id"><input type="hidden" class="qt-unit">
<input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
<input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
<input type="hidden" class="qt-taxable" value="1">
</td>
</tr>
</script>

<script type="text/template" id="qt-tpl-row-construction">
<tr class="qt-line-row" data-line-id="0">
<td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
<td><input type="text" class="form-control input-sm" name="item_no"></td>
<td><input type="text" class="form-control input-sm qt-desc"></td>
<td><input type="text" class="form-control input-sm qt-unit"></td>
<td><input type="number" step="0.001" class="form-control input-sm qt-qty" value="1"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="0"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="0"><input type="hidden" class="qt-sell-manual" value="0"></td>
<td class="text-right small qt-boq-cost-amt">—</td>
<td class="qt-line-total text-right small">—</td>
<td>
<button type="button" class="btn btn-danger btn-xs qt-del-row"><i class="fa fa-times"></i></button>
<input type="hidden" class="qt-notes-json" value="{}">
<input type="hidden" class="qt-markup" value="<?php echo e($default_markup ?? '25'); ?>">
<input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id">
<input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
<input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
<input type="hidden" class="qt-taxable" value="1">
</td>
</tr>
</script>

<script type="text/template" id="qt-tpl-boq-section">
<div class="panel panel-default mtop15 qt-boq-panel" data-boq-tab="__TAB__" data-boq-section="__SEC__" data-boq-section-title="__TITLE__">
<div class="panel-heading" role="tab"><a data-toggle="collapse" href="#__PANELID__">__TITLE__ <span class="badge qt-boq-sec-count">0</span>
<span class="pull-right text-muted small mright20">Subtotal: <strong class="qt-boq-sec-subtotal" data-boq-tab="__TAB__" data-boq-section="__SEC__">MWK 0.00</strong></span></a></div>
<div id="__PANELID__" class="panel-collapse collapse in">
<div class="table-responsive">
<table class="table table-bordered table-condensed mbot0">
<thead><tr>
<th style="width:28px;"></th><th>Item No</th><th>Description</th><th>Unit</th><th>Qty</th>
<th>Unit rate cost (MWK)</th><th>Unit rate sell (MWK)</th><th>Cost amount</th><th>Sell amount</th><th></th>
</tr></thead>
<tbody class="qt-sortable" data-tab="__TAB__" data-boq-section="__SEC__"></tbody>
</table>
</div>
<div class="panel-footer"><button type="button" class="btn btn-default btn-xs qt-add-row" data-tab="__TAB__" data-boq-section="__SEC__"><i class="fa fa-plus"></i> Add Row</button></div>
</div>
</div>
</script>

<script type="text/template" id="qt-tpl-row-promotional">
<tr class="qt-line-row" data-line-id="0">
<td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
<td class="qt-promo-wrap" style="position:relative;min-width:200px;">
<input type="text" class="form-control input-sm qt-promo-search qt-desc">
<div class="list-group qt-promo-menu" style="position:absolute;z-index:50;display:none;max-height:200px;overflow:auto;width:100%;"></div>
</td>
<td><input type="text" class="form-control input-sm qt-item-code"></td>
<td><input type="text" class="form-control input-sm qt-unit"></td>
<td class="qt-stock text-warning small">—</td>
<td><input type="number" step="0.001" class="form-control input-sm qt-qty" value="1"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-cost" value="0"></td>
<td><input type="number" step="0.01" class="form-control input-sm qt-markup" value="<?php echo e($default_markup ?? '25'); ?>"></td>
<td><input type="number" step="0.0001" class="form-control input-sm qt-sell" value="0"><input type="hidden" class="qt-sell-manual" value="0"></td>
<td class="qt-line-total text-right bold">MWK 0.00</td>
<td>
<button type="button" class="btn btn-danger btn-xs qt-del-row"><i class="fa fa-times"></i></button>
<input type="hidden" class="qt-notes-json" value="{}">
<input type="hidden" class="qt-inv-id" value="0">
<input type="hidden" class="qt-stock-qty" value="">
<input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
<input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
<input type="hidden" class="qt-taxable" value="1">
</td>
</tr>
</script>

<script type="text/template" id="qt-tpl-row-additional">
<tr class="qt-line-row" data-line-id="0">
<td class="qt-drag text-muted"><i class="fa fa-arrows"></i></td>
<td><input type="text" class="form-control input-sm qt-desc" placeholder="Delivery / Design / Permit…"></td>
<td><input type="number" step="0.01" class="form-control input-sm qt-sell" value="0"></td>
<td class="text-center"><input type="checkbox" class="qt-taxable-cb" checked></td>
<td>
<button type="button" class="btn btn-danger btn-xs qt-del-row"><i class="fa fa-times"></i></button>
<input type="hidden" class="qt-notes-json" value="{}">
<input type="hidden" class="qt-cost" value="0">
<input type="hidden" class="qt-markup" value="0">
<input type="hidden" class="qt-sell-manual" value="1">
<input type="hidden" class="qt-qty" value="1">
<input type="hidden" class="qt-item-code"><input type="hidden" class="qt-inv-id"><input type="hidden" class="qt-unit">
<input type="hidden" class="qt-width"><input type="hidden" class="qt-height"><input type="hidden" class="qt-area">
<input type="hidden" class="qt-size-based"><input type="hidden" class="qt-qty-base" value="1">
<input type="hidden" class="qt-taxable" value="1">
</td>
</tr>
</script>

<?php init_tail(); ?>
<script src="<?php echo base_url('assets/plugins/jquery-ui/jquery-ui.js'); ?>"></script>
<script>window.qtBuilderConfig = <?php echo json_encode($builderConfig); ?>;</script>
<script src="<?php echo module_dir_url('quotations', 'assets/js/quotation_builder.js'); ?>?v=5"></script>
<script>
$(function(){
  if ($('.datepicker').length && typeof init_datepicker === 'function') { init_datepicker(); }
});
</script>

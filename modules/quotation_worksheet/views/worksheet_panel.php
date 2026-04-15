<?php defined('BASEPATH') or exit('No direct script access allowed');

$proposal_id   = isset($proposal_id) ? (int) $proposal_id : 0;
$worksheet     = isset($worksheet) ? $worksheet : (object) [];
$lines_by_tab  = isset($lines_by_tab) && is_array($lines_by_tab) ? $lines_by_tab : [];
$can_see_margins = !empty($can_see_margins);
$tab_labels    = qt_get_tab_labels();
$status_label  = qt_get_status_badge($worksheet);
?>

<div id="qt-worksheet-panel" class="panel panel-default qt-worksheet-panel" data-proposal-id="<?php echo $proposal_id; ?>">
  <div class="panel-heading">
    <h4 class="panel-title pull-left"><?php echo _l('quotation_worksheet'); ?></h4>
    <span class="label label-default qt-status-badge pull-right mtop5"><?php echo e($status_label); ?></span>
    <div class="clearfix"></div>
  </div>
  <div class="panel-body">
    <?php if ($proposal_id < 1) : ?>
      <div class="alert alert-info qt-draft-warning">
        You can start typing worksheet lines now. Save proposal to persist worksheet lines and totals.
      </div>
    <?php endif; ?>

    <input type="hidden" name="qt_worksheet" value="1">
    <input type="hidden" name="qt_proposal_id" id="qt_proposal_id" value="<?php echo $proposal_id; ?>">
    <input type="hidden" name="qt_grand_total" id="qt_grand_total" value="<?php echo e((float) ($worksheet->grand_total ?? 0)); ?>">
    <input type="hidden" name="qt_discount_percent" id="qt_discount_percent" value="<?php echo e((float) ($worksheet->discount_percent ?? 0)); ?>">
    <input type="hidden" name="qt_discount_total" id="qt_discount_total" value="<?php echo e((float) ($worksheet->discount_amount ?? 0)); ?>">
    <input type="hidden" name="qt_subtotal" id="qt_subtotal" value="<?php echo e((float) ($worksheet->total_sell ?? 0)); ?>">
    <input type="hidden" name="qt_contingency" id="qt_contingency" value="<?php echo e((float) ($worksheet->contingency_percent ?? 0)); ?>">
    <input type="hidden" name="qt_discount" id="qt_discount" value="<?php echo e((float) ($worksheet->discount_percent ?? 0)); ?>">

    <div class="row qt-meta-row">
      <div class="col-md-3">
        <?php echo render_input('qt_ref_display', 'qt_ref', $worksheet->qt_ref ?? '', 'text', ['disabled' => true]); ?>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label class="control-label"><?php echo _l('qt_validity_days'); ?></label>
          <input type="number" min="1" class="form-control qt-meta" data-meta="validity_days" value="<?php echo (int) ($worksheet->validity_days ?? 30); ?>">
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="control-label"><?php echo _l('qt_terms'); ?></label>
          <textarea class="form-control qt-meta" data-meta="terms" rows="2"><?php echo e($worksheet->terms ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <ul class="nav nav-tabs qt-tabs" role="tablist">
      <?php $ti = 0;
        foreach ($tab_labels as $key => $label) :
            $active = $ti === 0 ? 'active' : '';
            ++$ti; ?>
        <li role="presentation" class="<?php echo $active; ?>">
          <a href="#qt-tab-<?php echo e($key); ?>" aria-controls="qt-tab-<?php echo e($key); ?>" role="tab" data-toggle="tab"><?php echo e($label); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="tab-content qt-tab-content">
      <!-- Signage -->
      <div role="tabpanel" class="tab-pane active" id="qt-tab-signage">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed qt-lines-table">
            <thead>
              <tr>
                <th style="width:28px;"></th>
                <th><?php echo _l('qt_col_description'); ?></th>
                <th><?php echo _l('qt_col_substrate'); ?></th>
                <th><?php echo _l('qt_col_print'); ?></th>
                <th>W (m)</th>
                <th>H (m)</th>
                <th><?php echo _l('qt_col_area'); ?></th>
                <th><?php echo _l('qt_col_qty'); ?></th>
                <th><?php echo _l('qt_col_cost'); ?></th>
                <th><?php echo _l('qt_col_markup'); ?></th>
                <th><?php echo _l('qt_col_sell'); ?></th>
                <th><?php echo _l('qt_col_line'); ?></th>
                <th style="width:40px;"></th>
              </tr>
            </thead>
            <tbody class="qt-lines-tbody" data-tab="signage" data-section="">
              <?php
                foreach ($lines_by_tab['signage'] ?? [] as $line) {
                    echo qt_render_line_row('signage', $line);
                }
                ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="signage" data-section=""><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
      </div>

      <!-- Installation -->
      <div role="tabpanel" class="tab-pane" id="qt-tab-installation">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed qt-lines-table">
            <thead>
              <tr>
                <th style="width:28px;"></th>
                <th><?php echo _l('qt_col_description'); ?></th>
                <th><?php echo _l('qt_col_activity'); ?></th>
                <th><?php echo _l('qt_col_qty'); ?></th>
                <th><?php echo _l('qt_col_rate_type'); ?></th>
                <th><?php echo _l('qt_col_rate'); ?></th>
                <th><?php echo _l('qt_col_duration'); ?></th>
                <th><?php echo _l('qt_col_cost'); ?></th>
                <th><?php echo _l('qt_col_markup'); ?></th>
                <th><?php echo _l('qt_col_sell'); ?></th>
                <th><?php echo _l('qt_col_line'); ?></th>
                <th style="width:40px;"></th>
              </tr>
            </thead>
            <tbody class="qt-lines-tbody" data-tab="installation" data-section="">
              <?php foreach ($lines_by_tab['installation'] ?? [] as $line) {
                    echo qt_render_line_row('installation', $line);
              } ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="installation" data-section=""><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
      </div>

      <!-- Construction -->
      <div role="tabpanel" class="tab-pane" id="qt-tab-construction">
        <?php foreach (qt_get_boq_sections('construction') as $section) : ?>
          <h5 class="qt-boq-heading"><?php echo e($section); ?></h5>
          <div class="table-responsive">
            <table class="table table-bordered table-condensed qt-lines-table">
              <thead>
                <tr>
                  <th style="width:28px;"></th>
                  <th><?php echo _l('qt_col_description'); ?></th>
                  <th><?php echo _l('qt_col_unit'); ?></th>
                  <th><?php echo _l('qt_col_qty'); ?></th>
                  <th><?php echo _l('qt_col_unit_sell'); ?></th>
                  <th><?php echo _l('qt_col_line_cost'); ?></th>
                  <th><?php echo _l('qt_col_markup'); ?></th>
                  <th><?php echo _l('qt_col_line'); ?></th>
                  <th style="width:40px;"></th>
                </tr>
              </thead>
              <tbody class="qt-lines-tbody" data-tab="construction" data-section="<?php echo e($section); ?>">
                <?php
                foreach ($lines_by_tab['construction'] ?? [] as $line) {
                    if (($line['section_name'] ?? '') !== $section) {
                        continue;
                    }
                    echo qt_render_line_row('construction', $line);
                }
                ?>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="construction" data-section="<?php echo e($section); ?>"><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Retrofitting -->
      <div role="tabpanel" class="tab-pane" id="qt-tab-retrofitting">
        <?php foreach (qt_get_boq_sections('retrofitting') as $section) : ?>
          <h5 class="qt-boq-heading"><?php echo e($section); ?></h5>
          <div class="table-responsive">
            <table class="table table-bordered table-condensed qt-lines-table">
              <thead>
                <tr>
                  <th style="width:28px;"></th>
                  <th><?php echo _l('qt_col_description'); ?></th>
                  <th><?php echo _l('qt_col_unit'); ?></th>
                  <th><?php echo _l('qt_col_qty'); ?></th>
                  <th><?php echo _l('qt_col_unit_sell'); ?></th>
                  <th><?php echo _l('qt_col_line_cost'); ?></th>
                  <th><?php echo _l('qt_col_markup'); ?></th>
                  <th><?php echo _l('qt_col_line'); ?></th>
                  <th style="width:40px;"></th>
                </tr>
              </thead>
              <tbody class="qt-lines-tbody" data-tab="retrofitting" data-section="<?php echo e($section); ?>">
                <?php
                foreach ($lines_by_tab['retrofitting'] ?? [] as $line) {
                    if (($line['section_name'] ?? '') !== $section) {
                        continue;
                    }
                    echo qt_render_line_row('retrofitting', $line);
                }
                ?>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="retrofitting" data-section="<?php echo e($section); ?>"><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Promotional -->
      <div role="tabpanel" class="tab-pane" id="qt-tab-promotional">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed qt-lines-table">
            <thead>
              <tr>
                <th style="width:28px;"></th>
                <th><?php echo _l('qt_col_description'); ?></th>
                <th><?php echo _l('qt_col_code'); ?></th>
                <th><?php echo _l('qt_col_unit'); ?></th>
                <th><?php echo _l('qt_col_stock'); ?></th>
                <th><?php echo _l('qt_col_qty'); ?></th>
                <th><?php echo _l('qt_col_cost'); ?></th>
                <th><?php echo _l('qt_col_markup'); ?></th>
                <th><?php echo _l('qt_col_sell'); ?></th>
                <th><?php echo _l('qt_col_line'); ?></th>
                <th style="width:40px;"></th>
              </tr>
            </thead>
            <tbody class="qt-lines-tbody" data-tab="promotional" data-section="">
              <?php foreach ($lines_by_tab['promotional'] ?? [] as $line) {
                    echo qt_render_line_row('promotional', $line);
              } ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="promotional" data-section=""><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
      </div>

      <!-- Additional -->
      <div role="tabpanel" class="tab-pane" id="qt-tab-additional">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed qt-lines-table">
            <thead>
              <tr>
                <th style="width:28px;"></th>
                <th><?php echo _l('qt_col_description'); ?></th>
                <th><?php echo _l('qt_col_sell'); ?></th>
                <th><?php echo _l('qt_col_taxable'); ?></th>
                <th style="width:40px;"></th>
              </tr>
            </thead>
            <tbody class="qt-lines-tbody" data-tab="additional" data-section="">
              <?php foreach ($lines_by_tab['additional'] ?? [] as $line) {
                    echo qt_render_line_row('additional', $line);
              } ?>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-default btn-sm qt-add-row" data-tab="additional" data-section=""><i class="fa fa-plus"></i> <?php echo _l('qt_add_row'); ?></button>
      </div>
    </div>

    <hr>
    <div class="row qt-totals-row">
      <div class="col-md-6">
        <div class="form-group">
          <label><?php echo _l('qt_contingency'); ?> (%)</label>
          <input type="number" step="0.01" min="0" class="form-control qt-totals-input" id="qt_contingency_input" value="<?php echo e((float) ($worksheet->contingency_percent ?? 0)); ?>">
        </div>
        <div class="form-group">
          <label><?php echo _l('qt_discount'); ?> (%)</label>
          <input type="number" step="0.01" min="0" class="form-control qt-totals-input" id="qt_discount_input" value="<?php echo e((float) ($worksheet->discount_percent ?? 0)); ?>">
        </div>
      </div>
      <div class="col-md-6">
        <table class="table table-condensed qt-totals-table">
          <tr class="qt-margin-cost-row" <?php echo $can_see_margins ? '' : 'style="display:none"'; ?>><td><?php echo _l('qt_total_cost'); ?></td><td class="text-right" id="qt_disp_total_cost"><?php echo qt_format_mwk($worksheet->total_cost ?? 0); ?></td></tr>
          <tr><td><?php echo _l('qt_total_sell'); ?></td><td class="text-right" id="qt_disp_total_sell"><?php echo qt_format_mwk($worksheet->total_sell ?? 0); ?></td></tr>
          <tr><td><?php echo _l('qt_contingency'); ?></td><td class="text-right" id="qt_disp_contingency"><?php echo qt_format_mwk($worksheet->contingency_amount ?? 0); ?></td></tr>
          <tr><td><?php echo _l('qt_discount'); ?></td><td class="text-right" id="qt_disp_discount"><?php echo qt_format_mwk($worksheet->discount_amount ?? 0); ?></td></tr>
          <tr><td>VAT</td><td class="text-right" id="qt_disp_vat"><?php echo qt_format_mwk($worksheet->vat_amount ?? 0); ?></td></tr>
          <tr class="qt-grand-row"><th><?php echo _l('qt_grand_total'); ?></th><th class="text-right" id="qt_disp_grand"><?php echo qt_format_mwk($worksheet->grand_total ?? 0); ?></th></tr>
        </table>
      </div>
    </div>

    <div class="qt-actions mtop15">
      <button type="button" class="btn btn-info qt-btn-pdf" data-proposal-id="<?php echo $proposal_id; ?>"><i class="fa fa-file-pdf-o"></i> <?php echo _l('qt_pdf'); ?></button>
      <button type="button" class="btn btn-primary qt-btn-submit-approval" data-proposal-id="<?php echo $proposal_id; ?>"><i class="fa fa-check"></i> <?php echo _l('qt_submit_approval'); ?></button>
    </div>
  </div>
</div>

<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('quotation_worksheet'); ?> — <?php echo _l('settings'); ?></h4>
        <?php echo form_open(admin_url('quotation_worksheet/settings')); ?>
        <?php echo render_input('qt_prefix', 'Prefix', qt_setting('qt_prefix', 'QT')); ?>
        <?php echo render_input('qt_default_markup', 'Default markup %', qt_setting('qt_default_markup', '25'), 'number', ['step' => '0.01']); ?>
        <?php echo render_input('qt_vat_rate', 'VAT rate %', qt_setting('qt_vat_rate', '16.5'), 'number', ['step' => '0.01']); ?>
        <?php echo render_textarea('qt_terms_and_conditions', 'Default terms', qt_setting('qt_terms_and_conditions', '')); ?>
        <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>

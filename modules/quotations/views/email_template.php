<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<p><?php echo _l('hello'); ?>,</p>
<p><?php echo !empty($quotation) ? 'Please find your quotation ' . html_escape($quotation->quotation_ref) . ' attached.' : ''; ?></p>
<p><?php echo html_escape(get_option('companyname')); ?></p>

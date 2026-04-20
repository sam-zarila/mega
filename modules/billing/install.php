<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_invoice_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `dn_id` int(11) DEFAULT NULL,
  `dn_ref` varchar(30) DEFAULT NULL,
  `job_card_id` int(11) DEFAULT NULL,
  `jc_ref` varchar(30) DEFAULT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `qt_ref` varchar(30) DEFAULT NULL,
  `is_proforma` tinyint(1) NOT NULL DEFAULT 0,
  `proforma_ref` varchar(30) DEFAULT NULL,
  `vat_registration_no` varchar(100) DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 16.50,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `finance_approved_by` int(11) DEFAULT NULL,
  `finance_approved_at` datetime DEFAULT NULL,
  `gl_posted` tinyint(1) NOT NULL DEFAULT 0,
  `gl_posted_at` datetime DEFAULT NULL,
  `edit_log` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_id` (`invoice_id`),
  KEY `idx_dn_id` (`dn_id`),
  KEY `idx_job_card_id` (`job_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_payment_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_method_detail` enum(\'cash\',\'bank_transfer\',\'cheque\',\'airtel_money\',\'tnm_mpamba\',\'other\') NOT NULL DEFAULT \'cash\',
  `reference_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `gm_approval_required` tinyint(1) NOT NULL DEFAULT 0,
  `gm_approved_by` int(11) DEFAULT NULL,
  `gm_approved_at` datetime DEFAULT NULL,
  `approval_request_id` int(11) DEFAULT NULL,
  `is_unallocated` tinyint(1) NOT NULL DEFAULT 0,
  `suspense_cleared_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_id` (`payment_id`),
  KEY `idx_invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_credit_note_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `credit_note_id` int(11) NOT NULL,
  `original_invoice_id` int(11) NOT NULL,
  `original_invoice_ref` varchar(50) DEFAULT NULL,
  `reason_category` enum(\'return_of_goods\',\'billing_error\',\'pricing_adjustment\',\'goodwill\') NOT NULL,
  `reason_detail` text,
  `gm_approval_status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
  `gm_approval_request_id` int(11) DEFAULT NULL,
  `gm_approved_by` int(11) DEFAULT NULL,
  `gm_approved_at` datetime DEFAULT NULL,
  `gm_rejection_reason` text,
  `vat_adjusted` tinyint(1) NOT NULL DEFAULT 0,
  `vat_adjustment_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gl_posted` tinyint(1) NOT NULL DEFAULT 0,
  `gl_posted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_credit_note_id` (`credit_note_id`),
  KEY `idx_original_invoice_id` (`original_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_billing_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $billing_defaults = [
        'proforma_prefix'           => 'PROF',
        'proforma_next_number'      => '1',
        'vat_rate'                  => '16.5',
        'payment_threshold_gm'      => '5000000',
        'finance_only_edit'         => '1',
        'auto_populate_from_dn'     => '1',
        'vat_registration_number'   => '',
        'invoice_terms'             => 'Payment due within 30 days of invoice date.',
        'invoice_footer'            => 'MW — Thank you for your business.',
        'cn_always_requires_gm'     => '1',
    ];

    foreach ($billing_defaults as $key => $val) {
        $CI->db->query(
            'INSERT IGNORE INTO `' . $p . 'ipms_billing_settings` (`setting_key`, `setting_value`) VALUES ('
            . $CI->db->escape($key) . ', ' . $CI->db->escape($val) . ')'
        );
    }

    if (!$CI->db->field_exists('ipms_meta_id', $p . 'invoices')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'invoices` ADD COLUMN `ipms_meta_id` int(11) DEFAULT NULL'
        );
    }

    if (!$CI->db->field_exists('ipms_meta_id', $p . 'creditnotes')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'creditnotes` ADD COLUMN `ipms_meta_id` int(11) DEFAULT NULL'
        );
    }

    if (!$CI->db->field_exists('ipms_meta_id', $p . 'invoicepaymentrecords')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'invoicepaymentrecords` ADD COLUMN `ipms_meta_id` int(11) DEFAULT NULL'
        );
    }
} catch (Throwable $e) {
    log_message('error', 'Billing module install failed: ' . $e->getMessage());
}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_ref` varchar(30) NOT NULL,
  `estimate_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `version` tinyint(4) DEFAULT 1,
  `is_latest` tinyint(1) DEFAULT 1,
  `client_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `service_type` set(\'signage\',\'installation\',\'construction\',\'retrofitting\',\'promotional\',\'additional\') DEFAULT \'signage\',
  `status` enum(\'draft\',\'submitted\',\'approved\',\'rejected\',\'converted\') DEFAULT \'draft\',
  `approval_request_id` int(11) DEFAULT NULL,
  `revision_notes` text,
  `total_cost` decimal(15,2) DEFAULT 0.00,
  `total_sell` decimal(15,2) DEFAULT 0.00,
  `vat_amount` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `markup_percent` decimal(5,2) DEFAULT 0.00,
  `contingency_percent` decimal(5,2) DEFAULT 0.00,
  `validity_days` int(11) DEFAULT 30,
  `terms` text,
  `internal_notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `converted_to_job_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_ref` (`quotation_ref`),
  KEY `idx_client_created_status` (`client_id`,`created_by`,`status`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_quotation_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `tab` enum(\'signage\',\'installation\',\'construction\',\'retrofitting\',\'promotional\',\'additional\') NOT NULL,
  `line_order` int(11) DEFAULT 0,
  `description` varchar(500) NOT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,3) DEFAULT 1.000,
  `width_m` decimal(8,3) DEFAULT NULL,
  `height_m` decimal(8,3) DEFAULT NULL,
  `computed_area` decimal(10,3) DEFAULT NULL,
  `cost_price` decimal(15,4) DEFAULT 0.0000,
  `markup_percent` decimal(5,2) DEFAULT 0.00,
  `sell_price` decimal(15,4) DEFAULT 0.0000,
  `line_total_cost` decimal(15,2) DEFAULT 0.00,
  `line_total_sell` decimal(15,2) DEFAULT 0.00,
  `is_taxable` tinyint(1) DEFAULT 1,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_quotation_tab` (`quotation_id`,`tab`),
  KEY `idx_inventory_item_id` (`inventory_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_quotation_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $default_settings = [
        'qt_prefix'                      => 'QT',
        'qt_year_in_ref'                 => '1',
        'qt_next_number'                 => '1',
        'qt_vat_rate'                    => '16.5',
        'qt_default_validity_days'       => '30',
        'qt_default_markup'              => '25',
        'qt_default_contingency'         => '0',
        'qt_company_name'                => 'MW',
        'qt_company_address'             => 'Blantyre, Malawi',
        'qt_vat_number'                  => '',
        'qt_terms_and_conditions'        => 'This quotation is valid for 30 days from the date of issue. Prices are subject to change. 50% deposit required upon order confirmation.',
        'qt_pdf_footer_text'             => 'Thank you for your business.',
        'qt_payment_terms'               => "50% deposit required upon order confirmation.\nBalance payable upon completion/delivery.\nPayment methods: Cash, Bank Transfer (EFT), Mobile Money",
        'qt_discount_requires_approval_above' => '10',
    ];

    foreach ($default_settings as $key => $value) {
        $CI->db->query(
            'INSERT IGNORE INTO `' . $p . 'ipms_quotation_settings` (`setting_key`, `setting_value`) VALUES ('
            . $CI->db->escape($key) . ', ' . $CI->db->escape($value) . ')'
        );
    }

    if (!$CI->db->field_exists('ipms_quotation_id', $p . 'estimates')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'estimates` ADD COLUMN `ipms_quotation_id` int(11) DEFAULT NULL'
        );
    }

    if (!$CI->db->field_exists('last_pdf_generated', $p . 'ipms_quotations')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'ipms_quotations` ADD COLUMN `last_pdf_generated` datetime DEFAULT NULL'
        );
    }
} catch (Throwable $e) {
    log_message('error', 'Quotations module install failed: ' . $e->getMessage());
}

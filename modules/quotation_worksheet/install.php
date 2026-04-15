<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    if (!$CI->db->table_exists($p . 'ipms_qt_settings')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_qt_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    $defaults = [
        'qt_next_number'                 => '1',
        'qt_prefix'                      => 'QT',
        'qt_default_validity_days'       => '30',
        'qt_terms_and_conditions'        => '',
        'qt_default_markup'              => '25',
        'qt_vat_rate'                    => '16.5',
        'qt_discount_approval_threshold' => '10',
        'qt_company_name'                => '',
        'qt_company_address'             => '',
        'qt_tin'                         => '',
        'qt_vat_number'                  => '',
        'qt_pdf_footer'                  => '',
    ];
    foreach ($defaults as $key => $val) {
        $CI->db->query(
            'INSERT IGNORE INTO `' . $p . 'ipms_qt_settings` (`setting_key`, `setting_value`) VALUES ('
            . $CI->db->escape($key) . ', ' . $CI->db->escape($val) . ')'
        );
    }

    if (!$CI->db->table_exists($p . 'ipms_qt_worksheets')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_qt_worksheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `qt_ref` varchar(50) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `validity_days` int(11) NOT NULL DEFAULT 30,
  `terms` text,
  `internal_notes` text,
  `qt_status` varchar(30) NOT NULL DEFAULT \'draft\',
  `contingency_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_sell` decimal(15,2) NOT NULL DEFAULT 0.00,
  `contingency_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `service_tabs` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_id` (`proposal_id`),
  KEY `idx_qt_ref` (`qt_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    if (!$CI->db->table_exists($p . 'ipms_qt_lines')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_qt_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `tab` varchar(50) NOT NULL,
  `section_name` varchar(200) DEFAULT NULL,
  `description` text,
  `long_description` text,
  `quantity` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `unit` varchar(50) DEFAULT NULL,
  `width_m` decimal(15,4) DEFAULT NULL,
  `height_m` decimal(15,4) DEFAULT NULL,
  `size_based` tinyint(1) NOT NULL DEFAULT 0,
  `cost_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `markup_percent` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `sell_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `item_code` varchar(100) DEFAULT NULL,
  `commodity_id` int(11) NOT NULL DEFAULT 0,
  `line_order` int(11) NOT NULL DEFAULT 0,
  `notes` text,
  `line_total_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `line_total_sell` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `computed_area` decimal(15,4) DEFAULT NULL,
  `substrate` varchar(255) DEFAULT NULL,
  `print_type` varchar(255) DEFAULT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `rate_type` varchar(100) DEFAULT NULL,
  `rate_value` decimal(15,4) DEFAULT NULL,
  `duration` decimal(15,4) DEFAULT NULL,
  `stock_qty` decimal(15,4) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proposal_tab` (`proposal_id`,`tab`),
  KEY `idx_proposal_order` (`proposal_id`,`line_order`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    if (!$CI->db->field_exists('qt_worksheet_id', $p . 'proposals')) {
        $CI->db->query('ALTER TABLE `' . $p . 'proposals` ADD `qt_worksheet_id` int(11) DEFAULT NULL');
    }
    if (!$CI->db->field_exists('qt_ref', $p . 'proposals')) {
        $CI->db->query('ALTER TABLE `' . $p . 'proposals` ADD `qt_ref` varchar(50) DEFAULT NULL');
    }
    if (!$CI->db->field_exists('qt_status', $p . 'proposals')) {
        $CI->db->query('ALTER TABLE `' . $p . 'proposals` ADD `qt_status` varchar(30) DEFAULT NULL');
    }
} catch (Throwable $e) {
    log_message('error', 'Quotation Worksheet install failed: ' . $e->getMessage());
}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_grn_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_ref` varchar(30) NOT NULL,
  `warehouse_grn_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `supplier_name` varchar(300) DEFAULT NULL,
  `supplier_ref` varchar(100) DEFAULT NULL,
  `po_ref` varchar(100) DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `received_at` date NOT NULL,
  `status` enum(\'draft\',\'posted\',\'cancelled\') NOT NULL DEFAULT \'draft\',
  `total_qty_lines` int(11) NOT NULL DEFAULT 0,
  `total_cost_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wac_recalculated` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grn_ref` (`grn_ref`),
  KEY `idx_wh_status_received` (`warehouse_id`,`status`,`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_grn_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `item_name` varchar(500) DEFAULT NULL,
  `unit_symbol` varchar(50) DEFAULT NULL,
  `qty_ordered` decimal(10,3) NOT NULL DEFAULT 0.000,
  `qty_received` decimal(10,3) NOT NULL,
  `unit_price` decimal(15,4) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `wac_before` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `wac_after` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `stock_before` decimal(10,3) NOT NULL DEFAULT 0.000,
  `stock_after` decimal(10,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  KEY `idx_grn_id` (`grn_id`),
  KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movement_ref` varchar(30) DEFAULT NULL,
  `movement_type` enum(\'grn\',\'issue\',\'adjustment_in\',\'adjustment_out\',\'transfer_out\',\'transfer_in\',\'opening_balance\',\'stock_count_adj\') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `item_name` varchar(500) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `qty_change` decimal(10,3) NOT NULL,
  `qty_before` decimal(10,3) NOT NULL DEFAULT 0.000,
  `qty_after` decimal(10,3) NOT NULL DEFAULT 0.000,
  `wac_at_movement` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `value_change` decimal(15,2) NOT NULL DEFAULT 0.00,
  `rel_type` varchar(50) DEFAULT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `rel_ref` varchar(50) DEFAULT NULL,
  `notes` text,
  `performed_by` int(11) NOT NULL,
  `performed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_wh_performed` (`item_id`,`warehouse_id`,`performed_at`),
  KEY `idx_type_performed` (`movement_type`,`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_stock_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adj_ref` varchar(30) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `adj_type` enum(\'write_off\',\'write_up\') NOT NULL,
  `reason` text NOT NULL,
  `status` enum(\'draft\',\'pending_approval\',\'approved\',\'posted\',\'rejected\') NOT NULL DEFAULT \'draft\',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `total_qty_lines` int(11) NOT NULL DEFAULT 0,
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gl_journal_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adj_ref` (`adj_ref`),
  KEY `idx_wh_status` (`warehouse_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_stock_adjustment_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adj_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `item_name` varchar(500) DEFAULT NULL,
  `unit_symbol` varchar(50) DEFAULT NULL,
  `qty_current` decimal(10,3) DEFAULT NULL,
  `qty_adjust` decimal(10,3) NOT NULL,
  `wac_at_adj` decimal(15,4) DEFAULT NULL,
  `line_value` decimal(15,2) DEFAULT NULL,
  `reason_notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_adj_id` (`adj_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_inv_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $defaults = [
        'grn_prefix'                   => 'GRN',
        'grn_next_number'              => '1',
        'adj_prefix'                   => 'ADJ',
        'adj_next_number'              => '1',
        'mov_prefix'                   => 'MOV',
        'mov_next_number'              => '1',
        'adj_approval_threshold'        => '0',
        'default_category_raw'          => '',
        'default_category_finished'     => '',
        'default_category_promo'        => '',
        'default_category_consumable'   => '',
        'default_category_spare'        => '',
        'low_stock_alert_enabled'       => '1',
    ];

    foreach ($defaults as $key => $val) {
        $CI->db->query(
            'INSERT IGNORE INTO `' . $p . 'ipms_inv_settings` (`setting_key`, `setting_value`) VALUES ('
            . $CI->db->escape($key) . ', ' . $CI->db->escape($val) . ')'
        );
    }

    if (!$CI->db->field_exists('wac_qty', $p . 'items')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'items` ADD COLUMN `wac_qty` decimal(10,3) NOT NULL DEFAULT 0.000'
        );
    }

    if (!$CI->db->field_exists('warehouse_id', $p . 'inventory_commodity_min')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'inventory_commodity_min` ADD COLUMN `warehouse_id` int(11) DEFAULT NULL'
        );
    }
} catch (Throwable $e) {
    log_message('error', 'Inventory Mgr module install failed: ' . $e->getMessage());
}

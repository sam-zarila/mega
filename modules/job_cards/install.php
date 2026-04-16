<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_job_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jc_ref` varchar(30) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `qt_ref` varchar(30) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `assigned_sales_id` int(11) DEFAULT NULL,
  `job_description` text,
  `job_type` set(\'signage\',\'installation\',\'construction\',\'retrofitting\',\'promotional\',\'additional\') DEFAULT NULL,
  `department_routing` set(\'studio\',\'stores\',\'field_team\',\'warehouse\') DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `special_instructions` text,
  `approved_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `approved_sell` decimal(15,2) NOT NULL DEFAULT 0.00,
  `approved_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `materials_issued` tinyint(1) NOT NULL DEFAULT 0,
  `materials_issued_at` datetime DEFAULT NULL,
  `materials_issued_by` int(11) DEFAULT NULL,
  `production_notes` text,
  `quality_notes` text,
  `completed_at` datetime DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `delivery_note_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jc_ref` (`jc_ref`),
  KEY `idx_proposal_id` (`proposal_id`),
  KEY `idx_client_status` (`client_id`,`status`),
  KEY `idx_status_department` (`status`,`department_routing`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_jc_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_card_id` int(11) NOT NULL,
  `from_status` tinyint(4) DEFAULT NULL,
  `to_status` tinyint(4) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_by_name` varchar(200) DEFAULT NULL,
  `changed_by_role` varchar(100) DEFAULT NULL,
  `notes` text,
  `changed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_card_id` (`job_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_jc_material_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_card_id` int(11) NOT NULL,
  `issue_ref` varchar(30) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `status` enum(\'draft\',\'issued\',\'confirmed\') NOT NULL DEFAULT \'draft\',
  `total_cost_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_job_card_id` (`job_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_jc_material_issue_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `job_card_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `item_description` varchar(500) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `qty_required` decimal(10,3) DEFAULT NULL,
  `qty_issued` decimal(10,3) DEFAULT NULL,
  `wac_at_issue` decimal(15,4) DEFAULT NULL,
  `line_total_cost` decimal(15,2) DEFAULT NULL,
  `qt_line_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_issue_id` (`issue_id`),
  KEY `idx_inventory_item_id` (`inventory_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_jc_department_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_card_id` int(11) NOT NULL,
  `department` enum(\'studio\',\'stores\',\'field_team\',\'warehouse\') NOT NULL,
  `notified_at` datetime DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_job_card_department` (`job_card_id`,`department`),
  KEY `idx_job_card_id` (`job_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $CI->db->query(
        'CREATE TABLE IF NOT EXISTS `' . $p . 'ipms_jc_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
    );

    $defaults = [
        'jc_prefix'                 => 'JC',
        'jc_next_number'            => '1',
        'iss_prefix'                => 'ISS',
        'iss_next_number'           => '1',
        'jc_default_deadline_days'  => '7',
        'jc_auto_create_on_approval'=> '1',
        'jc_studio_role'            => 'Studio/Production',
        'jc_stores_role'            => 'Storekeeper/Stores Clerk',
        'jc_store_manager_role'     => 'Store Manager',
        'jc_field_team_role'        => 'Field Team',
        'jc_warehouse_role'         => 'Storekeeper/Stores Clerk',
    ];

    foreach ($defaults as $key => $val) {
        $CI->db->query(
            'INSERT IGNORE INTO `' . $p . 'ipms_jc_settings` (`setting_key`, `setting_value`) VALUES ('
            . $CI->db->escape($key) . ', ' . $CI->db->escape($val) . ')'
        );
    }

    if (!$CI->db->field_exists('jc_id', $p . 'proposals')) {
        $CI->db->query('ALTER TABLE `' . $p . 'proposals` ADD COLUMN `jc_id` int(11) DEFAULT NULL');
    }

    if (!$CI->db->field_exists('jc_id', $p . 'ipms_qt_worksheets')) {
        $CI->db->query('ALTER TABLE `' . $p . 'ipms_qt_worksheets` ADD COLUMN `jc_id` int(11) DEFAULT NULL');
    }
} catch (Throwable $e) {
    log_message('error', 'Job Cards module install failed: ' . $e->getMessage());
}

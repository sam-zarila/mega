<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

try {
    $p       = db_prefix();
    $charset = $CI->db->char_set;

    if (!$CI->db->table_exists($p . 'ipms_approval_requests')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_approval_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_ref` varchar(30) NOT NULL,
  `document_type` enum(\'quotation\',\'credit_note\',\'journal_entry\',\'payment\',\'purchase_requisition\') NOT NULL,
  `document_id` int(11) NOT NULL,
  `document_ref` varchar(50) DEFAULT NULL,
  `document_value` decimal(15,2) DEFAULT 0.00,
  `submitted_by` int(11) NOT NULL,
  `submitted_at` datetime NOT NULL,
  `current_approver_id` int(11) DEFAULT NULL,
  `current_approver_role` varchar(100) DEFAULT NULL,
  `status` enum(\'pending\',\'approved\',\'rejected\',\'revision_requested\',\'escalated\',\'cancelled\') DEFAULT \'pending\',
  `approval_stage` tinyint(4) DEFAULT 1,
  `total_stages` tinyint(4) DEFAULT 1,
  `sla_deadline` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_ref` (`request_ref`),
  KEY `idx_document` (`document_type`,`document_id`),
  KEY `idx_current_status` (`current_approver_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    if (!$CI->db->table_exists($p . 'ipms_approval_actions')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_approval_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_request_id` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_name` varchar(200) DEFAULT NULL,
  `actor_role` varchar(100) DEFAULT NULL,
  `action` enum(\'submitted\',\'approved\',\'rejected\',\'revision_requested\',\'escalated\',\'cancelled\',\'reminder_sent\') NOT NULL,
  `comments` text,
  `acted_at` datetime NOT NULL,
  `stage_number` tinyint(4) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approval_request_id` (`approval_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    if (!$CI->db->table_exists($p . 'ipms_approval_thresholds')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_approval_thresholds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) NOT NULL,
  `tier1_role` varchar(100) DEFAULT NULL,
  `tier1_max` decimal(15,2) DEFAULT NULL,
  `tier2_role` varchar(100) DEFAULT NULL,
  `tier2_max` decimal(15,2) DEFAULT NULL,
  `tier3_role` varchar(100) DEFAULT NULL,
  `always_gm` tinyint(1) DEFAULT 0,
  `sla_hours` int(11) DEFAULT 24,
  `total_stages` tinyint(4) DEFAULT 1,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    if (!$CI->db->table_exists($p . 'ipms_approval_notifications')) {
        $CI->db->query(
            'CREATE TABLE `' . $p . 'ipms_approval_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_request_id` int(11) NOT NULL,
  `notification_type` enum(\'in_app\',\'email\',\'sms\') NOT NULL,
  `sent_to_staff_id` int(11) NOT NULL,
  `sent_at` datetime NOT NULL,
  `status` enum(\'sent\',\'failed\') DEFAULT \'sent\',
  `error_message` text,
  PRIMARY KEY (`id`),
  KEY `idx_request_notification` (`approval_request_id`,`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ';'
        );
    }

    $CI->db->query(
        'INSERT IGNORE INTO `' . $p . 'ipms_approval_thresholds`
(`document_type`,`tier1_role`,`tier1_max`,`tier2_role`,`tier2_max`,`tier3_role`,`always_gm`,`sla_hours`,`total_stages`) VALUES
(\'quotation\',\'Sales Manager\',3000000.00,\'Finance Manager\',5000000.00,\'General Manager\',0,24,1),
(\'credit_note\',NULL,NULL,NULL,NULL,\'General Manager\',1,4,1),
(\'journal_entry\',NULL,NULL,NULL,NULL,\'General Manager\',1,4,1),
(\'payment\',NULL,NULL,NULL,NULL,\'General Manager\',1,4,1),
(\'purchase_requisition\',\'Finance Manager\',5000000.00,\'General Manager\',NULL,NULL,0,24,2)'
    );

    if (!$CI->db->field_exists('approval_request_id', $p . 'estimates')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'estimates` ADD COLUMN `approval_request_id` int(11) DEFAULT NULL'
        );
    }

    if (!$CI->db->field_exists('approval_request_id', $p . 'creditnotes')) {
        $CI->db->query(
            'ALTER TABLE `' . $p . 'creditnotes` ADD COLUMN `approval_request_id` int(11) DEFAULT NULL'
        );
    }
} catch (Throwable $e) {
    log_message('error', 'Approvals module install failed: ' . $e->getMessage());
}

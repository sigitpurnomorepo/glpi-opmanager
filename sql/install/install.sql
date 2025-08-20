-- OPManager Integration Plugin Database Schema
-- This script creates all necessary tables for the plugin

-- Configuration table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_config` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   `value` text,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alarm mapping table (links OPManager alarms to GLPI tickets)
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_alarms` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `tickets_id` int(11) NOT NULL,
   `opmanager_alarm_id` varchar(255) NOT NULL,
   `device_name` varchar(255) NOT NULL,
   `severity` varchar(50) NOT NULL,
   `status` varchar(50) NOT NULL DEFAULT 'active',
   `created_at` datetime NOT NULL,
   `cleared_at` datetime NULL,
   `last_sync` datetime NULL,
   `sync_status` varchar(50) NULL DEFAULT 'pending',
   `sync_error` text NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `opmanager_alarm_id` (`opmanager_alarm_id`),
   KEY `tickets_id` (`tickets_id`),
   KEY `status` (`status`),
   KEY `sync_status` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook retry table (for failed webhook deliveries)
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_webhook_retries` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `webhook_data` longtext NOT NULL,
   `retry_count` int(11) NOT NULL DEFAULT 0,
   `status` varchar(50) NOT NULL DEFAULT 'failed',
   `last_attempt` datetime NOT NULL,
   `error_message` text NULL,
   `success_at` datetime NULL,
   `created_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   KEY `status` (`status`),
   KEY `retry_count` (`retry_count`),
   KEY `last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs table (for audit trail)
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_logs` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `type` varchar(50) NOT NULL,
   `message` text NOT NULL,
   `data` longtext NULL,
   `timestamp` datetime NOT NULL,
   `level` varchar(20) NOT NULL DEFAULT 'info',
   PRIMARY KEY (`id`),
   KEY `type` (`type`),
   KEY `timestamp` (`timestamp`),
   KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration values
INSERT IGNORE INTO `glpi_plugin_opmanager_config` (`name`, `value`, `created_at`, `updated_at`) VALUES
('opmanager_server', '', NOW(), NOW()),
('opmanager_port', '443', NOW(), NOW()),
('opmanager_username', '', NOW(), NOW()),
('opmanager_password', '', NOW(), NOW()),
('webhook_secret', '', NOW(), NOW()),
('webhook_url', '', NOW(), NOW()),
('default_entity', '0', NOW(), NOW()),
('default_requesttype', '0', NOW(), NOW()),
('default_category', '0', NOW(), NOW()),
('enable_bidirectional_sync', '1', NOW(), NOW()),
('sync_interval', '5', NOW(), NOW()),
('max_retry_attempts', '3', NOW(), NOW()),
('retry_delay', '5', NOW(), NOW()),
('custom_fields_mapping', '', NOW(), NOW()),
('last_sync_time', '', NOW(), NOW());

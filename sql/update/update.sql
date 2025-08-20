-- OPManager Integration Plugin - Database Update Script
-- This script handles database schema updates between versions

-- Version 1.0.1 Updates
-- Add new fields for enhanced monitoring

-- Add monitoring fields to alarms table
ALTER TABLE `glpi_plugin_opmanager_alarms` 
ADD COLUMN `monitor_name` varchar(255) NULL AFTER `device_name`,
ADD COLUMN `monitor_type` varchar(100) NULL AFTER `monitor_name`,
ADD COLUMN `threshold_value` varchar(100) NULL AFTER `monitor_type`,
ADD COLUMN `current_value` varchar(100) NULL AFTER `threshold_value`;

-- Add index for better performance
ALTER TABLE `glpi_plugin_opmanager_alarms` 
ADD INDEX `idx_monitor_name` (`monitor_name`),
ADD INDEX `idx_monitor_type` (`monitor_type`);

-- Add new configuration options
INSERT IGNORE INTO `glpi_plugin_opmanager_config` (`name`, `value`, `created_at`, `updated_at`) VALUES
('enable_monitoring_metrics', '0', NOW(), NOW()),
('monitoring_retention_days', '30', NOW(), NOW()),
('enable_advanced_logging', '0', NOW(), NOW()),
('webhook_timeout', '30', NOW(), NOW()),
('max_webhook_size', '1048576', NOW(), NOW());

-- Version 1.0.2 Updates
-- Add support for custom field mapping

-- Create custom fields mapping table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_custom_fields` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `opmanager_field` varchar(255) NOT NULL,
   `glpi_field` varchar(255) NOT NULL,
   `field_type` varchar(50) NOT NULL DEFAULT 'text',
   `is_required` tinyint(1) NOT NULL DEFAULT 0,
   `default_value` text NULL,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `opmanager_field` (`opmanager_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default custom field mappings
INSERT IGNORE INTO `glpi_plugin_opmanager_custom_fields` 
(`opmanager_field`, `glpi_field`, `field_type`, `is_required`, `default_value`, `created_at`, `updated_at`) VALUES
('location', 'glpi_location', 'entity', 0, '', NOW(), NOW()),
('department', 'glpi_department', 'entity', 0, '', NOW(), NOW()),
('monitor_name', 'glpi_monitor_type', 'text', 0, '', NOW(), NOW()),
('device_ip', 'glpi_device_ip', 'ip', 0, '', NOW(), NOW()),
('device_type', 'glpi_device_type', 'text', 0, '', NOW(), NOW());

-- Version 1.0.3 Updates
-- Add support for webhook filtering and routing

-- Add webhook filters table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_webhook_filters` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `filter_name` varchar(255) NOT NULL,
   `filter_type` varchar(50) NOT NULL,
   `filter_condition` varchar(50) NOT NULL,
   `filter_value` text NOT NULL,
   `is_active` tinyint(1) NOT NULL DEFAULT 1,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   KEY `filter_type` (`filter_type`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default filters
INSERT IGNORE INTO `glpi_plugin_opmanager_webhook_filters` 
(`filter_name`, `filter_type`, `filter_condition`, `filter_value`, `is_active`, `created_at`, `updated_at`) VALUES
('Critical Alarms Only', 'severity', 'equals', 'Critical', 1, NOW(), NOW()),
('Network Devices Only', 'device_type', 'contains', 'router,switch,firewall', 1, NOW(), NOW()),
('Production Environment', 'location', 'contains', 'production,prod', 1, NOW(), NOW());

-- Add routing rules table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_routing_rules` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `rule_name` varchar(255) NOT NULL,
   `rule_priority` int(11) NOT NULL DEFAULT 0,
   `rule_conditions` text NOT NULL,
   `target_entity` int(11) NULL,
   `target_category` int(11) NULL,
   `target_assignee` int(11) NULL,
   `is_active` tinyint(1) NOT NULL DEFAULT 1,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   KEY `rule_priority` (`rule_priority`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default routing rules
INSERT IGNORE INTO `glpi_plugin_opmanager_routing_rules` 
(`rule_name`, `rule_priority`, `rule_conditions`, `target_entity`, `target_category`, `target_assignee`, `is_active`, `created_at`, `updated_at`) VALUES
('Critical Network Issues', 1, '{"severity": "Critical", "device_type": "router,switch"}', 1, 1, NULL, 1, NOW(), NOW()),
('Server Monitoring', 2, '{"device_type": "server", "severity": ["Critical", "Major"]}', 1, 2, NULL, 1, NOW(), NOW()),
('Database Issues', 3, '{"monitor_type": "database", "severity": "Critical"}', 1, 3, NULL, 1, NOW(), NOW());

-- Version 1.0.4 Updates
-- Add support for SLA and escalation

-- Add SLA configuration table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_sla_config` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `sla_name` varchar(255) NOT NULL,
   `severity_level` varchar(50) NOT NULL,
   `response_time` int(11) NOT NULL COMMENT 'Response time in minutes',
   `resolution_time` int(11) NOT NULL COMMENT 'Resolution time in minutes',
   `business_hours` text NULL COMMENT 'Business hours in JSON format',
   `is_active` tinyint(1) NOT NULL DEFAULT 1,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   KEY `severity_level` (`severity_level`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default SLA configurations
INSERT IGNORE INTO `glpi_plugin_opmanager_sla_config` 
(`sla_name`, `severity_level`, `response_time`, `resolution_time`, `business_hours`, `is_active`, `created_at`, `updated_at`) VALUES
('Critical Response', 'Critical', 15, 240, '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}}', 1, NOW(), NOW()),
('Major Response', 'Major', 60, 480, '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}}', 1, NOW(), NOW()),
('Minor Response', 'Minor', 240, 1440, '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}}', 1, NOW(), NOW());

-- Add escalation rules table
CREATE TABLE IF NOT EXISTS `glpi_plugin_opmanager_escalation_rules` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `rule_name` varchar(255) NOT NULL,
   `escalation_time` int(11) NOT NULL COMMENT 'Escalation time in minutes',
   `escalation_level` int(11) NOT NULL DEFAULT 1,
   `target_assignee` int(11) NULL,
   `notification_template` varchar(255) NULL,
   `is_active` tinyint(1) NOT NULL DEFAULT 1,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`),
   KEY `escalation_level` (`escalation_level`),
   KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default escalation rules
INSERT IGNORE INTO `glpi_plugin_opmanager_escalation_rules` 
(`rule_name`, `escalation_time`, `escalation_level`, `target_assignee`, `notification_template`, `is_active`, `created_at`, `updated_at`) VALUES
('Level 1 Escalation', 30, 1, NULL, 'escalation_level1', 1, NOW(), NOW()),
('Level 2 Escalation', 60, 2, NULL, 'escalation_level2', 1, NOW(), NOW()),
('Level 3 Escalation', 120, 3, NULL, 'escalation_level3', 1, NOW(), NOW());

-- Update existing configuration with new options
UPDATE `glpi_plugin_opmanager_config` SET 
   `value` = '1' 
WHERE `name` = 'enable_bidirectional_sync' AND `value` = '';

-- Add new configuration options for future versions
INSERT IGNORE INTO `glpi_plugin_opmanager_config` (`name`, `value`, `created_at`, `updated_at`) VALUES
('enable_sla_management', '0', NOW(), NOW()),
('enable_escalation', '0', NOW(), NOW()),
('enable_webhook_filtering', '0', NOW(), NOW()),
('enable_advanced_routing', '0', NOW(), NOW()),
('enable_performance_monitoring', '0', NOW(), NOW());

-- Update version information
INSERT IGNORE INTO `glpi_plugin_opmanager_config` (`name`, `value`, `created_at`, `updated_at`) VALUES
('plugin_version', '1.0.4', NOW(), NOW()),
('database_version', '1.0.4', NOW(), NOW()),
('last_update_check', NOW(), NOW());

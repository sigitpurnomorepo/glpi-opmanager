<?php

/**
 * GLPI OPManager Integration Plugin
 * 
 * This plugin provides bidirectional integration between GLPI (IT Service Management)
 * and OPManager (Network Management System) for automated ticket creation and synchronization.
 * 
 * @package   GLPI
 * @subpackage OPManager
 * @author    Your Name
 * @copyright 2024 Your Organization
 * @license   GPL v2+
 */

include_once(GLPI_ROOT . "/inc/includes.php");

/**
 * Plugin description
 */
function plugin_version_opmanager() {
   return [
      'name'           => 'OPManager Integration',
      'version'        => '1.0.0',
      'author'         => 'sigit',
      'license'        => 'GPL v2+',
      'homepage'       => 'https://github.com/sigitpurnomorepo/glpi-opmanager',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '11.0.0'
         ]
      ]
   ];
}

/**
 * Check if plugin can be installed
 */
function plugin_opmanager_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '10.0.0', 'lt')) {
      echo "This plugin requires GLPI >= 10.0.0";
      return false;
   }
   return true;
}

/**
 * Check if plugin can be activated
 */
function plugin_opmanager_check_config($verbose = false) {
   if ($verbose) {
      echo "OPManager Integration plugin configuration check passed";
   }
   return true;
}

/**
 * Plugin initialization
 */
function plugin_init_opmanager() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_protect']['opmanager'] = true;
   $PLUGIN_HOOKS['add_css']['opmanager'] = 'css/opmanager.css';
   $PLUGIN_HOOKS['add_javascript']['opmanager'] = 'js/opmanager.js';
   
   // Register plugin classes
   Plugin::registerClass('PluginOpmanagerConfig', [
      'addtabon' => ['Config']
   ]);
   
   Plugin::registerClass('PluginOpmanagerWebhook', [
      'addtabon' => ['Ticket']
   ]);
   
   Plugin::registerClass('PluginOpmanagerSync', [
      'addtabon' => ['Ticket']
   ]);
   
   // Add menu items
   $PLUGIN_HOOKS['menu_toadd']['opmanager'] = [
      'plugins' => 'PluginOpmanagerConfig'
   ];
   
   // Add cron tasks
   $PLUGIN_HOOKS['cron']['opmanager'] = [
      'PluginOpmanagerCron::syncTickets',
      'PluginOpmanagerCron::retryFailedWebhooks'
   ];
   
   // Add webhook endpoint
   $PLUGIN_HOOKS['webhook']['opmanager'] = 'PluginOpmanagerWebhook::handleWebhook';
}


<?php

/**
 * Hook definitions for OPManager Integration Plugin
 * 
 * This file defines all the integration points with GLPI
 */

// Define plugin hooks
$PLUGIN_HOOKS = [
   'csrf_protect' => [
      'opmanager' => true
   ],
   'add_css' => [
      'opmanager' => 'css/opmanager.css'
   ],
   'add_javascript' => [
      'opmanager' => 'js/opmanager.js'
   ],
   'menu_toadd' => [
      'plugins' => 'PluginOpmanagerConfig'
   ],
   'cron' => [
      'opmanager' => [
         'PluginOpmanagerCron::syncTickets',
         'PluginOpmanagerCron::retryFailedWebhooks'
      ]
   ],
   'webhook' => [
      'opmanager' => 'PluginOpmanagerWebhook::handleWebhook'
   ]
];

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

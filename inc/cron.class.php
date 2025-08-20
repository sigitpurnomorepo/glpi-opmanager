<?php

/**
 * Cron job class for automated synchronization and retry mechanisms
 */
class PluginOpmanagerCron extends CommonGLPI {
   
   static $rightname = 'config';
   
   /**
    * Sync tickets between GLPI and OPManager
    */
   static function syncTickets() {
      $config = PluginOpmanagerPlugin::getConfig();
      
      if (empty($config['enable_bidirectional_sync']) || $config['enable_bidirectional_sync'] !== '1') {
         return 0; // No sync needed
      }
      
      $syncInterval = intval($config['sync_interval'] ?? 5);
      
      // Check if it's time to sync
      $lastSync = self::getLastSyncTime();
      if ($lastSync && (time() - strtotime($lastSync)) < ($syncInterval * 60)) {
         return 0; // Not time to sync yet
      }
      
      $results = [];
      
      // Sync from OPManager to GLPI
      $opmanagerToGLPI = PluginOpmanagerSync::syncAlarmsFromOPManager();
      $results['opmanager_to_glpi'] = $opmanagerToGLPI;
      
      // Sync from GLPI to OPManager
      $glpiToOPManager = PluginOpmanagerSync::syncTicketsToOPManager();
      $results['glpi_to_opmanager'] = $glpiToOPManager;
      
      // Log sync results
      self::logSyncResults($results);
      
      // Update last sync time
      self::updateLastSyncTime();
      
      return 1; // Sync completed
   }
   
   /**
    * Retry failed webhook deliveries
    */
   static function retryFailedWebhooks() {
      global $DB;
      
      $config = PluginOpmanagerPlugin::getConfig();
      $maxRetries = intval($config['max_retry_attempts'] ?? 3);
      $retryDelay = intval($config['retry_delay'] ?? 5);
      
      // Get failed webhook attempts that need retry
      $failedWebhooks = $DB->request([
         'SELECT' => [
            'id',
            'webhook_data',
            'retry_count',
            'last_attempt',
            'error_message'
         ],
         'FROM' => 'glpi_plugin_opmanager_webhook_retries',
         'WHERE' => [
            'status' => 'failed',
            'retry_count:<' => $maxRetries,
            'last_attempt:<' => date('Y-m-d H:i:s', time() - ($retryDelay * 60))
         ]
      ]);
      
      $retryCount = 0;
      $successCount = 0;
      
      while ($webhook = $failedWebhooks->next()) {
         $retryCount++;
         
         // Attempt to process webhook again
         $result = self::retryWebhook($webhook);
         
         if ($result['success']) {
            $successCount++;
            
            // Mark as successful
            $DB->update('glpi_plugin_opmanager_webhook_retries', [
               'status' => 'success',
               'retry_count' => $webhook['retry_count'] + 1,
               'last_attempt' => date('Y-m-d H:i:s'),
               'success_at' => date('Y-m-d H:i:s')
            ], [
               'id' => $webhook['id']
            ]);
         } else {
            // Update retry count and error message
            $DB->update('glpi_plugin_opmanager_webhook_retries', [
               'retry_count' => $webhook['retry_count'] + 1,
               'last_attempt' => date('Y-m-d H:i:s'),
               'error_message' => $result['message']
            ], [
               'id' => $webhook['id']
            ]);
         }
      }
      
      // Log retry results
      if ($retryCount > 0) {
         self::logRetryResults($retryCount, $successCount);
      }
      
      return $retryCount;
   }
   
   /**
    * Retry individual webhook
    */
   private static function retryWebhook($webhook) {
      try {
         $webhookData = json_decode($webhook['webhook_data'], true);
         
         if (!$webhookData) {
            return [
               'success' => false,
               'message' => 'Invalid webhook data format'
            ];
         }
         
         // Process webhook
         $result = PluginOpmanagerWebhook::handleWebhook($webhookData);
         
         return $result;
         
      } catch (Exception $e) {
         return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
         ];
      }
   }
   
   /**
    * Get last sync time
    */
   private static function getLastSyncTime() {
      global $DB;
      
      $result = $DB->request([
         'SELECT' => ['value'],
         'FROM' => 'glpi_plugin_opmanager_config',
         'WHERE' => ['name' => 'last_sync_time']
      ])->next();
      
      return $result ? $result['value'] : null;
   }
   
   /**
    * Update last sync time
    */
   private static function updateLastSyncTime() {
      PluginOpmanagerConfig::setConfig('last_sync_time', date('Y-m-d H:i:s'));
   }
   
   /**
    * Log sync results
    */
   private static function logSyncResults($results) {
      global $DB;
      
      $logData = [
         'type' => 'sync',
         'data' => json_encode($results),
         'timestamp' => date('Y-m-d H:i:s'),
         'level' => 'info'
      ];
      
      $DB->insert('glpi_plugin_opmanager_logs', $logData);
   }
   
   /**
    * Log retry results
    */
   private static function logRetryResults($retryCount, $successCount) {
      global $DB;
      
      $logData = [
         'type' => 'retry',
         'data' => json_encode([
            'retry_count' => $retryCount,
            'success_count' => $successCount,
            'failure_count' => $retryCount - $successCount
         ]),
         'timestamp' => date('Y-m-d H:i:s'),
         'level' => 'info'
      ];
      
      $DB->insert('glpi_plugin_opmanager_logs', $logData);
   }
   
   /**
    * Get cron task information
    */
   static function cronInfo($name) {
      switch ($name) {
         case 'syncTickets':
            return [
               'description' => __('Sync tickets between GLPI and OPManager', 'opmanager'),
               'parameter' => __('Sync interval (minutes)', 'opmanager')
            ];
            
         case 'retryFailedWebhooks':
            return [
               'description' => __('Retry failed webhook deliveries', 'opmanager'),
               'parameter' => __('Retry delay (minutes)', 'opmanager')
            ];
            
         default:
            return [];
      }
   }
   
   /**
    * Get cron task frequency
    */
   static function getCronFrequency($name) {
      $config = PluginOpmanagerPlugin::getConfig();
      
      switch ($name) {
         case 'syncTickets':
            $interval = intval($config['sync_interval'] ?? 5);
            return $interval * 60; // Convert to seconds
            
         case 'retryFailedWebhooks':
            $delay = intval($config['retry_delay'] ?? 5);
            return $delay * 60; // Convert to seconds
            
         default:
            return 300; // Default 5 minutes
      }
   }
   
   /**
    * Check if cron task should run
    */
   static function shouldRunCron($name) {
      $config = PluginOpmanagerPlugin::getConfig();
      
      switch ($name) {
         case 'syncTickets':
            return !empty($config['enable_bidirectional_sync']) && $config['enable_bidirectional_sync'] === '1';
            
         case 'retryFailedWebhooks':
            return true; // Always run retry mechanism
            
         default:
            return false;
      }
   }
   
   /**
    * Get cron task statistics
    */
   static function getCronStats() {
      global $DB;
      
      $stats = [];
      
      // Last sync time
      $lastSync = self::getLastSyncTime();
      $stats['last_sync'] = $lastSync;
      
      // Sync frequency
      $config = PluginOpmanagerPlugin::getConfig();
      $stats['sync_interval'] = intval($config['sync_interval'] ?? 5);
      
      // Retry statistics
      $result = $DB->request([
         'SELECT' => [
            'COUNT(*) as total_retries',
            'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_retries',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_retries'
         ],
         'FROM' => 'glpi_plugin_opmanager_webhook_retries'
      ])->next();
      
      $stats['total_retries'] = $result['total_retries'] ?? 0;
      $stats['successful_retries'] = $result['successful_retries'] ?? 0;
      $stats['failed_retries'] = $result['failed_retries'] ?? 0;
      
      if ($stats['total_retries'] > 0) {
         $stats['retry_success_rate'] = round(($stats['successful_retries'] / $stats['total_retries']) * 100, 2);
      } else {
         $stats['retry_success_rate'] = 0;
      }
      
      return $stats;
   }
}

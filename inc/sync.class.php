<?php

/**
 * Synchronization class for bidirectional sync between GLPI and OPManager
 */
class PluginOpmanagerSync extends CommonGLPI {
   
   static $rightname = 'config';
   
   /**
    * Sync tickets from GLPI to OPManager
    */
   static function syncTicketsToOPManager() {
      global $DB;
      
      $config = PluginOpmanagerPlugin::getConfig();
      
      if (empty($config['enable_bidirectional_sync']) || $config['enable_bidirectional_sync'] !== '1') {
         return [
            'success' => false,
            'message' => 'Bidirectional sync is disabled'
         ];
      }
      
      // Get tickets that need sync
      $tickets = $DB->request([
         'SELECT' => [
            't.id',
            't.name',
            't.status',
            't.priority',
            't.urgency',
            't.impact',
            't.solution',
            't.solvedate',
            'oa.opmanager_alarm_id',
            'oa.device_name'
         ],
         'FROM' => 'glpi_tickets t',
         'LEFT JOIN' => [
            'glpi_plugin_opmanager_alarms oa' => [
               'FKEY' => [
                  't' => 'id',
                  'oa' => 'tickets_id'
               ]
            ]
         ],
         'WHERE' => [
            'oa.opmanager_alarm_id:!=' => null,
            't.status' => [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING, Ticket::SOLVED, Ticket::CLOSED]
         ]
      ]);
      
      $syncedCount = 0;
      $errors = [];
      
      while ($ticket = $tickets->next()) {
         $result = self::updateOPManagerAlarm($ticket);
         if ($result['success']) {
            $syncedCount++;
         } else {
            $errors[] = "Ticket {$ticket['id']}: {$result['message']}";
         }
      }
      
      return [
         'success' => true,
         'synced_count' => $syncedCount,
         'errors' => $errors
      ];
   }
   
   /**
    * Update alarm status in OPManager based on ticket status
    */
   private static function updateOPManagerAlarm($ticket) {
      $config = PluginOpmanagerPlugin::getConfig();
      
      if (empty($config['opmanager_server']) || empty($config['opmanager_username']) || empty($config['opmanager_password'])) {
         return [
            'success' => false,
            'message' => 'OPManager configuration missing'
         ];
      }
      
      // Map GLPI status to OPManager status
      $opmanagerStatus = self::mapGLPIStatusToOPManager($ticket['status']);
      
      $url = "https://{$config['opmanager_server']}:{$config['opmanager_port']}/api/alarms/{$ticket['opmanager_alarm_id']}";
      
      $updateData = [
         'status' => $opmanagerStatus,
         'notes' => self::formatOPManagerNotes($ticket)
      ];
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, "{$config['opmanager_username']}:{$config['opmanager_password']}");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      if ($httpCode === 200) {
         // Update sync timestamp
         global $DB;
         $DB->update('glpi_plugin_opmanager_alarms', [
            'last_sync' => date('Y-m-d H:i:s'),
            'sync_status' => 'success'
         ], [
            'tickets_id' => $ticket['id']
         ]);
         
         return [
            'success' => true,
            'message' => 'Alarm updated successfully'
         ];
      } else {
         // Update sync status to failed
         global $DB;
         $DB->update('glpi_plugin_opmanager_alarms', [
            'last_sync' => date('Y-m-d H:i:s'),
            'sync_status' => 'failed',
            'sync_error' => "HTTP {$httpCode}: {$response}"
         ], [
            'tickets_id' => $ticket['id']
         ]);
         
         return [
            'success' => false,
            'message' => "HTTP {$httpCode}: {$response}"
         ];
      }
   }
   
   /**
    * Map GLPI ticket status to OPManager alarm status
    */
   private static function mapGLPIStatusToOPManager($glpiStatus) {
      $mapping = [
         Ticket::INCOMING => 'active',
         Ticket::ASSIGNED => 'assigned',
         Ticket::PLANNED => 'scheduled',
         Ticket::WAITING => 'waiting',
         Ticket::SOLVED => 'resolved',
         Ticket::CLOSED => 'closed'
      ];
      
      return $mapping[$glpiStatus] ?? 'active';
   }
   
   /**
    * Format notes for OPManager
    */
   private static function formatOPManagerNotes($ticket) {
      $notes = "GLPI Ticket Status: {$ticket['status']}\n";
      $notes .= "Priority: {$ticket['priority']}\n";
      $notes .= "Urgency: {$ticket['urgency']}\n";
      $notes .= "Impact: {$ticket['impact']}\n";
      
      if (!empty($ticket['solution'])) {
         $notes .= "Solution: {$ticket['solution']}\n";
      }
      
      if (!empty($ticket['solvedate'])) {
         $notes .= "Resolved: {$ticket['solvedate']}\n";
      }
      
      return $notes;
   }
   
   /**
    * Sync alarms from OPManager to GLPI
    */
   static function syncAlarmsFromOPManager() {
      $config = PluginOpmanagerPlugin::getConfig();
      
      if (empty($config['opmanager_server']) || empty($config['opmanager_username']) || empty($config['opmanager_password'])) {
         return [
            'success' => false,
            'message' => 'OPManager configuration missing'
         ];
      }
      
      // Get active alarms from OPManager
      $alarms = self::getOPManagerAlarms();
      
      if (!$alarms['success']) {
         return $alarms;
      }
      
      $syncedCount = 0;
      $errors = [];
      
      foreach ($alarms['alarms'] as $alarm) {
         $result = self::syncAlarmToGLPI($alarm);
         if ($result['success']) {
            $syncedCount++;
         } else {
            $errors[] = "Alarm {$alarm['alarm_id']}: {$result['message']}";
         }
      }
      
      return [
         'success' => true,
         'synced_count' => $syncedCount,
         'errors' => $errors
      ];
   }
   
   /**
    * Get alarms from OPManager
    */
   private static function getOPManagerAlarms() {
      $config = PluginOpmanagerPlugin::getConfig();
      
      $url = "https://{$config['opmanager_server']}:{$config['opmanager_port']}/api/alarms";
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, "{$config['opmanager_username']}:{$config['opmanager_password']}");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      if ($httpCode === 200) {
         $alarms = json_decode($response, true);
         return [
            'success' => true,
            'alarms' => $alarms['alarms'] ?? []
         ];
      } else {
         return [
            'success' => false,
            'message' => "HTTP {$httpCode}: {$response}"
         ];
      }
   }
   
   /**
    * Sync individual alarm to GLPI
    */
   private static function syncAlarmToGLPI($alarm) {
      global $DB;
      
      // Check if alarm already exists
      $existingMapping = $DB->request([
         'SELECT' => ['tickets_id', 'status'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['opmanager_alarm_id' => $alarm['alarm_id']]
      ])->next();
      
      if ($existingMapping) {
         // Update existing ticket if needed
         return self::updateTicketFromAlarm($existingMapping['tickets_id'], $alarm);
      } else {
         // Create new ticket
         return PluginOpmanagerPlugin::createTicketFromAlarm($alarm);
      }
   }
   
   /**
    * Update existing ticket from alarm data
    */
   private static function updateTicketFromAlarm($ticketId, $alarm) {
      global $DB;
      
      $ticket = new Ticket();
      $updateData = [
         'id' => $ticketId,
         'name' => sprintf(__('OPManager Alarm: %s - %s', 'opmanager'), 
                          $alarm['device_name'], 
                          $alarm['message'])
      ];
      
      // Update priority if severity changed
      if (isset($alarm['severity'])) {
         $updateData['priority'] = PluginOpmanagerPlugin::mapSeverityToPriority($alarm['severity']);
         $updateData['urgency'] = PluginOpmanagerPlugin::mapSeverityToUrgency($alarm['severity']);
         $updateData['impact'] = PluginOpmanagerPlugin::mapSeverityToImpact($alarm['severity']);
      }
      
      $updateResult = $ticket->update($updateData);
      
      if ($updateResult) {
         // Update alarm mapping
         $DB->update('glpi_plugin_opmanager_alarms', [
            'status' => $alarm['status'],
            'last_sync' => date('Y-m-d H:i:s'),
            'sync_status' => 'success'
         ], [
            'tickets_id' => $ticketId
         ]);
         
         return [
            'success' => true,
            'message' => 'Ticket updated successfully'
         ];
      }
      
      return [
         'success' => false,
         'message' => 'Failed to update ticket'
      ];
   }
   
   /**
    * Get sync statistics
    */
   static function getSyncStats() {
      global $DB;
      
      $stats = [];
      
      // Total alarms
      $result = $DB->request([
         'SELECT' => ['COUNT(*) as total'],
         'FROM' => 'glpi_plugin_opmanager_alarms'
      ])->next();
      $stats['total_alarms'] = $result['total'];
      
      // Active alarms
      $result = $DB->request([
         'SELECT' => ['COUNT(*) as active'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['status' => 'active']
      ])->next();
      $stats['active_alarms'] = $result['active'];
      
      // Cleared alarms
      $result = $DB->request([
         'SELECT' => ['COUNT(*) as cleared'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['status' => 'cleared']
      ])->next();
      $stats['cleared_alarms'] = $result['cleared'];
      
      // Last sync
      $result = $DB->request([
         'SELECT' => ['MAX(last_sync) as last_sync'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['last_sync:!=' => null]
      ])->next();
      $stats['last_sync'] = $result['last_sync'];
      
      // Sync success rate
      $result = $DB->request([
         'SELECT' => ['COUNT(*) as total_syncs'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['last_sync:!=' => null]
      ])->next();
      $totalSyncs = $result['total_syncs'];
      
      if ($totalSyncs > 0) {
         $result = $DB->request([
            'SELECT' => ['COUNT(*) as successful_syncs'],
            'FROM' => 'glpi_plugin_opmanager_alarms',
            'WHERE' => ['sync_status' => 'success']
         ])->next();
         $successfulSyncs = $result['successful_syncs'];
         $stats['sync_success_rate'] = round(($successfulSyncs / $totalSyncs) * 100, 2);
      } else {
         $stats['sync_success_rate'] = 0;
      }
      
      return $stats;
   }
}

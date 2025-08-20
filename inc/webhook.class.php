<?php

/**
 * Webhook handler class for OPManager integration
 */
class PluginOpmanagerWebhook extends CommonGLPI {
   
   static $rightname = 'config';
   
   /**
    * Handle incoming webhook from OPManager
    */
   static function handleWebhook($data) {
      global $DB;
      
      // Log webhook receipt
      self::logWebhook('Received webhook from OPManager', $data);
      
      try {
         // Validate webhook data
         $validation = self::validateWebhookData($data);
         if (!$validation['valid']) {
            self::logWebhook('Webhook validation failed: ' . $validation['message'], $data);
            return [
               'success' => false,
               'message' => $validation['message']
            ];
         }
         
         // Process based on event type
         switch ($data['event_type']) {
            case 'alarm_raised':
               return self::handleAlarmRaised($data);
               
            case 'alarm_cleared':
               return self::handleAlarmCleared($data);
               
            case 'alarm_acknowledged':
               return self::handleAlarmAcknowledged($data);
               
            case 'alarm_updated':
               return self::handleAlarmUpdated($data);
               
            default:
               self::logWebhook('Unknown event type: ' . $data['event_type'], $data);
               return [
                  'success' => false,
                  'message' => 'Unknown event type'
               ];
         }
         
      } catch (Exception $e) {
         self::logWebhook('Webhook processing error: ' . $e->getMessage(), $data);
         return [
            'success' => false,
            'message' => 'Internal server error'
         ];
      }
   }
   
   /**
    * Validate incoming webhook data
    */
   private static function validateWebhookData($data) {
      // Required fields
      $requiredFields = ['event_type', 'alarm_id', 'device_name', 'severity', 'message', 'timestamp'];
      
      foreach ($requiredFields as $field) {
         if (empty($data[$field])) {
            return [
               'valid' => false,
               'message' => "Missing required field: {$field}"
            ];
         }
      }
      
      // Validate event type
      $validEventTypes = ['alarm_raised', 'alarm_cleared', 'alarm_acknowledged', 'alarm_updated'];
      if (!in_array($data['event_type'], $validEventTypes)) {
         return [
            'valid' => false,
            'message' => "Invalid event type: {$data['event_type']}"
         ];
      }
      
      // Validate severity
      $validSeverities = ['Critical', 'Major', 'Minor', 'Warning', 'Info', 'Clear'];
      if (!in_array($data['severity'], $validSeverities)) {
         return [
            'valid' => false,
            'message' => "Invalid severity: {$data['severity']}"
         ];
      }
      
      return ['valid' => true];
   }
   
   /**
    * Handle alarm raised event
    */
   private static function handleAlarmRaised($data) {
      // Create ticket from alarm
      $result = PluginOpmanagerPlugin::createTicketFromAlarm($data);
      
      if ($result['success']) {
         self::logWebhook('Ticket created successfully', [
            'alarm_id' => $data['alarm_id'],
            'ticket_id' => $result['ticket_id']
         ]);
         
         // Send acknowledgment back to OPManager
         self::acknowledgeAlarmInOPManager($data['alarm_id']);
      }
      
      return $result;
   }
   
   /**
    * Handle alarm cleared event
    */
   private static function handleAlarmCleared($data) {
      global $DB;
      
      // Find associated ticket
      $ticketMapping = $DB->request([
         'SELECT' => ['tickets_id', 'status'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['opmanager_alarm_id' => $data['alarm_id']]
      ])->next();
      
      if (!$ticketMapping) {
         return [
            'success' => false,
            'message' => 'No ticket found for this alarm'
         ];
      }
      
      // Update ticket status to resolved
      $ticket = new Ticket();
      $updateResult = $ticket->update([
         'id' => $ticketMapping['tickets_id'],
         'status' => Ticket::SOLVED,
         'solutiontypes_id' => self::getDefaultSolutionType(),
         'solution' => sprintf(__('Alarm cleared in OPManager at %s', 'opmanager'), $data['timestamp'])
      ]);
      
      if ($updateResult) {
         // Update alarm mapping status
         $DB->update('glpi_plugin_opmanager_alarms', [
            'status' => 'cleared',
            'cleared_at' => date('Y-m-d H:i:s')
         ], [
            'opmanager_alarm_id' => $data['alarm_id']
         ]);
         
         self::logWebhook('Ticket resolved successfully', [
            'alarm_id' => $data['alarm_id'],
            'ticket_id' => $ticketMapping['tickets_id']
         ]);
         
         return [
            'success' => true,
            'message' => 'Ticket resolved successfully'
         ];
      }
      
      return [
         'success' => false,
         'message' => 'Failed to resolve ticket'
      ];
   }
   
   /**
    * Handle alarm acknowledged event
    */
   private static function handleAlarmAcknowledged($data) {
      global $DB;
      
      // Find associated ticket
      $ticketMapping = $DB->request([
         'SELECT' => ['tickets_id'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['opmanager_alarm_id' => $data['alarm_id']]
      ])->next();
      
      if (!$ticketMapping) {
         return [
            'success' => false,
            'message' => 'No ticket found for this alarm'
         ];
      }
      
      // Add acknowledgment comment to ticket
      $ticket = new Ticket();
      $followup = new TicketFollowup();
      $followup->add([
         'tickets_id' => $ticketMapping['tickets_id'],
         'content' => sprintf(__('Alarm acknowledged in OPManager at %s', 'opmanager'), $data['timestamp']),
         'is_private' => 0
      ]);
      
      self::logWebhook('Alarm acknowledgment processed', [
         'alarm_id' => $data['alarm_id'],
         'ticket_id' => $ticketMapping['tickets_id']
      ]);
      
      return [
         'success' => true,
         'message' => 'Acknowledgment processed successfully'
      ];
   }
   
   /**
    * Handle alarm updated event
    */
   private static function handleAlarmUpdated($data) {
      global $DB;
      
      // Find associated ticket
      $ticketMapping = $DB->request([
         'SELECT' => ['tickets_id'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['opmanager_alarm_id' => $data['alarm_id']]
      ])->next();
      
      if (!$ticketMapping) {
         return [
            'success' => false,
            'message' => 'No ticket found for this alarm'
         ];
      }
      
      // Update ticket with new information
      $ticket = new Ticket();
      $updateData = [
         'id' => $ticketMapping['tickets_id'],
         'name' => sprintf(__('OPManager Alarm: %s - %s', 'opmanager'), 
                          $data['device_name'], 
                          $data['message'])
      ];
      
      // Update priority if severity changed
      if (isset($data['severity'])) {
         $updateData['priority'] = PluginOpmanagerPlugin::mapSeverityToPriority($data['severity']);
         $updateData['urgency'] = PluginOpmanagerPlugin::mapSeverityToUrgency($data['severity']);
         $updateData['impact'] = PluginOpmanagerPlugin::mapSeverityToImpact($data['severity']);
      }
      
      $updateResult = $ticket->update($updateData);
      
      if ($updateResult) {
         // Add update comment
         $followup = new TicketFollowup();
         $followup->add([
            'tickets_id' => $ticketMapping['tickets_id'],
            'content' => sprintf(__('Alarm updated in OPManager at %s', 'opmanager'), $data['timestamp']),
            'is_private' => 0
         ]);
         
         self::logWebhook('Ticket updated successfully', [
            'alarm_id' => $data['alarm_id'],
            'ticket_id' => $ticketMapping['tickets_id']
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
    * Acknowledge alarm in OPManager
    */
   private static function acknowledgeAlarmInOPManager($alarmId) {
      $config = PluginOpmanagerPlugin::getConfig();
      
      if (empty($config['opmanager_server']) || empty($config['opmanager_username']) || empty($config['opmanager_password'])) {
         self::logWebhook('Cannot acknowledge alarm: OPManager configuration missing', ['alarm_id' => $alarmId]);
         return false;
      }
      
      $url = "https://{$config['opmanager_server']}:{$config['opmanager_port']}/api/alarms/acknowledge";
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['alarm_id' => $alarmId]));
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
         self::logWebhook('Alarm acknowledged in OPManager successfully', ['alarm_id' => $alarmId]);
         return true;
      } else {
         self::logWebhook('Failed to acknowledge alarm in OPManager', [
            'alarm_id' => $alarmId,
            'http_code' => $httpCode,
            'response' => $response
         ]);
         return false;
      }
   }
   
   /**
    * Get default solution type
    */
   private static function getDefaultSolutionType() {
      // Return default solution type ID or 0
      return 0;
   }
   
   /**
    * Log webhook activities
    */
   private static function logWebhook($message, $data = []) {
      global $DB;
      
      $DB->insert('glpi_plugin_opmanager_logs', [
         'message' => $message,
         'data' => json_encode($data),
         'timestamp' => date('Y-m-d H:i:s'),
         'level' => 'info'
      ]);
   }
}

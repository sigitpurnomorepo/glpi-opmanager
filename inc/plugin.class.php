<?php

/**
 * Main plugin class for OPManager Integration
 */
class PluginOpmanagerPlugin extends CommonGLPI {
   
   static $rightname = 'config';
   
   /**
    * Get plugin name
    */
   static function getMenuName() {
      return __('OPManager Integration', 'opmanager');
   }
   
   /**
    * Get plugin version
    */
   static function getVersion() {
      return '1.0.0';
   }
   
   /**
    * Check if plugin is activated
    */
   static function isActivated() {
      return Plugin::isPluginActive('opmanager');
   }
   
   /**
    * Get plugin configuration
    */
   static function getConfig() {
      $config = new PluginOpmanagerConfig();
      return $config->getConfig();
   }
   
   /**
    * Test OPManager connection
    */
   static function testConnection($server, $port, $username, $password) {
      $url = "https://{$server}:{$port}/api/devices";
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      if ($httpCode === 200) {
         return [
            'success' => true,
            'message' => __('Connection successful', 'opmanager')
         ];
      } else {
         return [
            'success' => false,
            'message' => sprintf(__('Connection failed with HTTP code: %d', 'opmanager'), $httpCode)
         ];
      }
   }
   
   /**
    * Create ticket from OPManager alarm
    */
   static function createTicketFromAlarm($alarmData) {
      global $DB;
      
      // Validate alarm data
      if (empty($alarmData['alarm_id']) || empty($alarmData['device_name'])) {
         return [
            'success' => false,
            'message' => __('Invalid alarm data', 'opmanager')
         ];
      }
      
      // Check if ticket already exists for this alarm
      $existingTicket = $DB->request([
         'SELECT' => ['id'],
         'FROM' => 'glpi_plugin_opmanager_alarms',
         'WHERE' => ['opmanager_alarm_id' => $alarmData['alarm_id']]
      ])->next();
      
      if ($existingTicket) {
         return [
            'success' => false,
            'message' => __('Ticket already exists for this alarm', 'opmanager')
         ];
      }
      
      // Create ticket
      $ticket = new Ticket();
      $ticketData = [
         'name' => sprintf(__('OPManager Alarm: %s - %s', 'opmanager'), 
                          $alarmData['device_name'], 
                          $alarmData['message']),
         'content' => self::formatTicketContent($alarmData),
         'type' => Ticket::INCIDENT_TYPE,
         'priority' => self::mapSeverityToPriority($alarmData['severity']),
         'status' => Ticket::INCOMING,
         'entities_id' => self::getDefaultEntity(),
         'requesttypes_id' => self::getDefaultRequestType(),
         'categories_id' => self::getDefaultCategory(),
         'urgency' => self::mapSeverityToUrgency($alarmData['severity']),
         'impact' => self::mapSeverityToImpact($alarmData['severity'])
      ];
      
      $ticketId = $ticket->add($ticketData);
      
      if ($ticketId) {
         // Store alarm mapping
         $DB->insert('glpi_plugin_opmanager_alarms', [
            'tickets_id' => $ticketId,
            'opmanager_alarm_id' => $alarmData['alarm_id'],
            'device_name' => $alarmData['device_name'],
            'severity' => $alarmData['severity'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
         ]);
         
         // Add custom fields
         self::addCustomFields($ticketId, $alarmData);
         
         return [
            'success' => true,
            'ticket_id' => $ticketId,
            'message' => __('Ticket created successfully', 'opmanager')
         ];
      }
      
      return [
         'success' => false,
         'message' => __('Failed to create ticket', 'opmanager')
      ];
   }
   
   /**
    * Format ticket content from alarm data
    */
   private static function formatTicketContent($alarmData) {
      $content = "<h3>" . __('OPManager Alarm Details', 'opmanager') . "</h3>\n\n";
      $content .= "<p><strong>" . __('Device:', 'opmanager') . "</strong> {$alarmData['device_name']}</p>\n";
      $content .= "<p><strong>" . __('Severity:', 'opmanager') . "</strong> {$alarmData['severity']}</p>\n";
      $content .= "<p><strong>" . __('Message:', 'opmanager') . "</strong> {$alarmData['message']}</p>\n";
      $content .= "<p><strong>" . __('Timestamp:', 'opmanager') . "</strong> {$alarmData['timestamp']}</p>\n";
      
      if (!empty($alarmData['custom_fields'])) {
         $content .= "<h4>" . __('Additional Information', 'opmanager') . "</h4>\n";
         foreach ($alarmData['custom_fields'] as $key => $value) {
            $content .= "<p><strong>" . ucfirst($key) . ":</strong> {$value}</p>\n";
         }
      }
      
      return $content;
   }
   
   /**
    * Map OPManager severity to GLPI priority
    */
   private static function mapSeverityToPriority($severity) {
      $mapping = [
         'Critical' => Ticket::HIGH_PRIORITY,
         'Major' => Ticket::HIGH_PRIORITY,
         'Minor' => Ticket::MEDIUM_PRIORITY,
         'Warning' => Ticket::MEDIUM_PRIORITY,
         'Info' => Ticket::LOW_PRIORITY,
         'Clear' => Ticket::LOW_PRIORITY
      ];
      
      return $mapping[$severity] ?? Ticket::MEDIUM_PRIORITY;
   }
   
   /**
    * Map OPManager severity to GLPI urgency
    */
   private static function mapSeverityToUrgency($severity) {
      $mapping = [
         'Critical' => Ticket::HIGH_URGENCY,
         'Major' => Ticket::HIGH_URGENCY,
         'Minor' => Ticket::MEDIUM_URGENCY,
         'Warning' => Ticket::MEDIUM_URGENCY,
         'Info' => Ticket::LOW_URGENCY,
         'Clear' => Ticket::LOW_URGENCY
      ];
      
      return $mapping[$severity] ?? Ticket::MEDIUM_URGENCY;
   }
   
   /**
    * Map OPManager severity to GLPI impact
    */
   private static function mapSeverityToImpact($severity) {
      $mapping = [
         'Critical' => Ticket::HIGH_IMPACT,
         'Major' => Ticket::HIGH_IMPACT,
         'Minor' => Ticket::MEDIUM_IMPACT,
         'Warning' => Ticket::MEDIUM_IMPACT,
         'Info' => Ticket::LOW_IMPACT,
         'Clear' => Ticket::LOW_IMPACT
      ];
      
      return $mapping[$severity] ?? Ticket::MEDIUM_IMPACT;
   }
   
   /**
    * Get default entity
    */
   private static function getDefaultEntity() {
      global $CFG_GLPI;
      return $CFG_GLPI['default_entity'] ?? 0;
   }
   
   /**
    * Get default request type
    */
   private static function getDefaultRequestType() {
      // Return default request type ID or 0
      return 0;
   }
   
   /**
    * Get default category
    */
   private static function getDefaultCategory() {
      // Return default category ID or 0
      return 0;
   }
   
   /**
    * Add custom fields to ticket
    */
   private static function addCustomFields($ticketId, $alarmData) {
      // Implementation for adding custom fields
      // This would depend on your GLPI custom fields setup
   }
}

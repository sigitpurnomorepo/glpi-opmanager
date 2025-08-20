<?php

/**
 * Configuration management class for OPManager Integration
 */
class PluginOpmanagerConfig extends CommonGLPI {
   
   static $rightname = 'config';
   
   /**
    * Get configuration
    */
   static function getConfig() {
      global $DB;
      
      $config = [];
      $result = $DB->request([
         'SELECT' => ['name', 'value'],
         'FROM' => 'glpi_plugin_opmanager_config'
      ]);
      
      while ($row = $result->next()) {
         $config[$row['name']] = $row['value'];
      }
      
      return $config;
   }
   
   /**
    * Set configuration value
    */
   static function setConfig($name, $value) {
      global $DB;
      
      $existing = $DB->request([
         'SELECT' => ['id'],
         'FROM' => 'glpi_plugin_opmanager_config',
         'WHERE' => ['name' => $name]
      ])->next();
      
      if ($existing) {
         $DB->update('glpi_plugin_opmanager_config', [
            'value' => $value,
            'updated_at' => date('Y-m-d H:i:s')
         ], [
            'id' => $existing['id']
         ]);
      } else {
         $DB->insert('glpi_plugin_opmanager_config', [
            'name' => $name,
            'value' => $value,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
         ]);
      }
      
      return true;
   }
   
   /**
    * Get tab name for item
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Config') {
         return __('OPManager Integration', 'opmanager');
      }
      return '';
   }
   
   /**
    * Display tab content for item
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Config') {
         self::showConfigForm();
      }
   }
   
   /**
    * Show configuration form
    */
   static function showConfigForm() {
      global $CFG_GLPI;
      
      $config = self::getConfig();
      
      echo "<form method='post' action='{$CFG_GLPI['root_doc']}/plugins/opmanager/front/config.form.php'>";
      echo "<div class='spaced'>";
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . __('OPManager Configuration', 'opmanager') . "</th></tr>";
      
      // OPManager Server Settings
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('OPManager Server', 'opmanager') . "</td>";
      echo "<td><input type='text' name='opmanager_server' value='" . 
           htmlspecialchars($config['opmanager_server'] ?? '') . "' size='40'></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('OPManager Port', 'opmanager') . "</td>";
      echo "<td><input type='text' name='opmanager_port' value='" . 
           htmlspecialchars($config['opmanager_port'] ?? '443') . "' size='10'></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('OPManager Username', 'opmanager') . "</td>";
      echo "<td><input type='text' name='opmanager_username' value='" . 
           htmlspecialchars($config['opmanager_username'] ?? '') . "' size='40'></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('OPManager Password', 'opmanager') . "</td>";
      echo "<td><input type='password' name='opmanager_password' value='" . 
           htmlspecialchars($config['opmanager_password'] ?? '') . "' size='40'></td>";
      echo "</tr>";
      
      // Webhook Settings
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Webhook Secret Key', 'opmanager') . "</td>";
      echo "<td><input type='text' name='webhook_secret' value='" . 
           htmlspecialchars($config['webhook_secret'] ?? '') . "' size='40'></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Webhook URL', 'opmanager') . "</td>";
      echo "<td><input type='text' name='webhook_url' value='" . 
           htmlspecialchars($config['webhook_url'] ?? '') . "' size='60' readonly></td>";
      echo "</tr>";
      
      // Ticket Settings
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Default Entity', 'opmanager') . "</td>";
      echo "<td>";
      Entity::dropdown(['value' => $config['default_entity'] ?? 0]);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Default Request Type', 'opmanager') . "</td>";
      echo "<td>";
      RequestType::dropdown(['value' => $config['default_requesttype'] ?? 0]);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Default Category', 'opmanager') . "</td>";
      echo "<td>";
      ITILCategory::dropdown(['value' => $config['default_category'] ?? 0]);
      echo "</td>";
      echo "</tr>";
      
      // Sync Settings
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Enable Bidirectional Sync', 'opmanager') . "</td>";
      echo "<td><input type='checkbox' name='enable_bidirectional_sync' value='1' " . 
           (($config['enable_bidirectional_sync'] ?? '0') == '1' ? 'checked' : '') . "></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Sync Interval (minutes)', 'opmanager') . "</td>";
      echo "<td><input type='number' name='sync_interval' value='" . 
           htmlspecialchars($config['sync_interval'] ?? '5') . "' min='1' max='60'></td>";
      echo "</tr>";
      
      // Retry Settings
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Max Retry Attempts', 'opmanager') . "</td>";
      echo "<td><input type='number' name='max_retry_attempts' value='" . 
           htmlspecialchars($config['max_retry_attempts'] ?? '3') . "' min='1' max='10'></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Retry Delay (minutes)', 'opmanager') . "</td>";
      echo "<td><input type='number' name='retry_delay' value='" . 
           htmlspecialchars($config['retry_delay'] ?? '5') . "' min='1' max='60'></td>";
      echo "</tr>";
      
      // Custom Fields Mapping
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Custom Fields Mapping', 'opmanager') . "</td>";
      echo "<td><textarea name='custom_fields_mapping' rows='5' cols='60'>" . 
           htmlspecialchars($config['custom_fields_mapping'] ?? '') . "</textarea></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='submit' name='update' value='" . __('Save', 'opmanager') . "' class='submit'>";
      echo "</td>";
      echo "</tr>";
      
      echo "</table>";
      echo "</div>";
      echo "</form>";
      
      // Test Connection Button
      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . __('Test Connection', 'opmanager') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='button' id='test_connection' value='" . __('Test OPManager Connection', 'opmanager') . "' class='submit'>";
      echo "<div id='connection_result' style='margin-top: 10px;'></div>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</div>";
      
      // JavaScript for test connection
      echo "<script>
         document.getElementById('test_connection').addEventListener('click', function() {
            var server = document.querySelector('input[name=\"opmanager_server\"]').value;
            var port = document.querySelector('input[name=\"opmanager_port\"]').value;
            var username = document.querySelector('input[name=\"opmanager_username\"]').value;
            var password = document.querySelector('input[name=\"opmanager_password\"]').value;
            
            if (!server || !username || !password) {
               document.getElementById('connection_result').innerHTML = '<span style=\"color: red;\">" . 
                __('Please fill in server, username and password first', 'opmanager') . "</span>';
               return;
            }
            
            document.getElementById('connection_result').innerHTML = '<span style=\"color: blue;\">" . 
             __('Testing connection...', 'opmanager') . "</span>';
            
            fetch('{$CFG_GLPI['root_doc']}/plugins/opmanager/front/config.form.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
               },
               body: 'action=test_connection&server=' + encodeURIComponent(server) + 
                     '&port=' + encodeURIComponent(port) + 
                     '&username=' + encodeURIComponent(username) + 
                     '&password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
               if (data.success) {
                  document.getElementById('connection_result').innerHTML = '<span style=\"color: green;\">' + data.message + '</span>';
               } else {
                  document.getElementById('connection_result').innerHTML = '<span style=\"color: red;\">' + data.message + '</span>';
               }
            })
            .catch(error => {
               document.getElementById('connection_result').innerHTML = '<span style=\"color: red;\">" . 
                __('Connection test failed', 'opmanager') . ": ' + error.message + '</span>';
            });
         });
      </script>";
   }
   
   /**
    * Process configuration form
    */
   static function processConfigForm() {
      if (isset($_POST['update'])) {
         $configFields = [
            'opmanager_server',
            'opmanager_port',
            'opmanager_username',
            'opmanager_password',
            'webhook_secret',
            'default_entity',
            'default_requesttype',
            'default_category',
            'enable_bidirectional_sync',
            'sync_interval',
            'max_retry_attempts',
            'retry_delay',
            'custom_fields_mapping'
         ];
         
         foreach ($configFields as $field) {
            if (isset($_POST[$field])) {
               $value = $_POST[$field];
               if ($field === 'enable_bidirectional_sync') {
                  $value = isset($_POST[$field]) ? '1' : '0';
               }
               self::setConfig($field, $value);
            }
         }
         
         // Generate webhook URL
         $webhookUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . 
                      $_SERVER['REQUEST_URI'];
         $webhookUrl = str_replace('/config.form.php', '/webhook.php', $webhookUrl);
         self::setConfig('webhook_url', $webhookUrl);
         
         Session::addMessageAfterRedirect(__('Configuration updated successfully', 'opmanager'));
         Html::redirect($_SERVER['HTTP_REFERER']);
      }
      
      if (isset($_POST['action']) && $_POST['action'] === 'test_connection') {
         $result = PluginOpmanagerPlugin::testConnection(
            $_POST['server'],
            $_POST['port'],
            $_POST['username'],
            $_POST['password']
         );
         
         header('Content-Type: application/json');
         echo json_encode($result);
         exit;
      }
   }
}

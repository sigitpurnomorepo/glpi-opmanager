<?php

/**
 * Webhook endpoint for OPManager integration
 * 
 * This file handles incoming webhooks from OPManager and processes them
 * to create/update tickets in GLPI.
 */

include_once('../../../inc/includes.php');

// Check if plugin is active
if (!Plugin::isPluginActive('opmanager')) {
   http_response_code(503);
   echo json_encode(['error' => 'Plugin not active']);
   exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['error' => 'Method not allowed']);
   exit;
}

// Get webhook data
$input = file_get_contents('php://input');
$webhookData = json_decode($input, true);

if (!$webhookData) {
   http_response_code(400);
   echo json_encode(['error' => 'Invalid JSON data']);
   exit;
}

// Validate webhook secret if configured
$config = PluginOpmanagerPlugin::getConfig();
if (!empty($config['webhook_secret'])) {
   $headers = getallheaders();
   $signature = $headers['X-OPManager-Signature'] ?? '';
   
   if (empty($signature)) {
      http_response_code(401);
      echo json_encode(['error' => 'Missing signature']);
      exit;
   }
   
   $expectedSignature = hash_hmac('sha256', $input, $config['webhook_secret']);
   if (!hash_equals($expectedSignature, $signature)) {
      http_response_code(401);
      echo json_encode(['error' => 'Invalid signature']);
      exit;
   }
}

try {
   // Process webhook
   $result = PluginOpmanagerWebhook::handleWebhook($webhookData);
   
   if ($result['success']) {
      http_response_code(200);
      echo json_encode([
         'success' => true,
         'message' => $result['message'],
         'ticket_id' => $result['ticket_id'] ?? null
      ]);
   } else {
      http_response_code(400);
      echo json_encode([
         'success' => false,
         'message' => $result['message']
      ]);
   }
   
} catch (Exception $e) {
   // Log error
   error_log("OPManager webhook error: " . $e->getMessage());
   
   http_response_code(500);
   echo json_encode([
      'success' => false,
      'message' => 'Internal server error'
   ]);
}

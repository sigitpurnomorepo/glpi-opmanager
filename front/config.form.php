<?php

/**
 * Configuration form handler for OPManager Integration
 */

include_once('../../../inc/includes.php');

// Check if user has config rights
if (!Session::haveRight('config', UPDATE)) {
   http_response_code(403);
   echo json_encode(['error' => 'Access denied']);
   exit;
}

// Process configuration form
PluginOpmanagerConfig::processConfigForm();

<?php

/**
 * OPManager Integration Plugin - Main Index
 * 
 * This file serves as the main entry point for the plugin
 */

include_once('../../../inc/includes.php');

// Check if plugin is active
if (!Plugin::isPluginActive('opmanager')) {
   Html::displayErrorAndDie(__('Plugin not active'));
}

// Redirect to configuration page
Html::redirect($CFG_GLPI['root_doc'] . '/front/config.form.php?forcetab=PluginOpmanagerConfig$1');

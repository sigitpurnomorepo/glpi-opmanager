/**
 * OPManager Integration Plugin JavaScript
 * 
 * This file provides interactive functionality for the plugin
 */

(function() {
   'use strict';
   
   // Wait for DOM to be ready
   document.addEventListener('DOMContentLoaded', function() {
      initializePlugin();
   });
   
   function initializePlugin() {
      // Initialize test connection functionality
      initTestConnection();
      
      // Initialize form validation
      initFormValidation();
      
      // Initialize real-time updates
      initRealTimeUpdates();
   }
   
   function initTestConnection() {
      const testButton = document.getElementById('test_connection');
      if (testButton) {
         testButton.addEventListener('click', function() {
            testOPManagerConnection();
         });
      }
   }
   
   function testOPManagerConnection() {
      const server = document.querySelector('input[name="opmanager_server"]').value;
      const port = document.querySelector('input[name="opmanager_port"]').value;
      const username = document.querySelector('input[name="opmanager_username"]').value;
      const password = document.querySelector('input[name="opmanager_password"]').value;
      
      if (!server || !username || !password) {
         showStatus('Please fill in server, username and password first', 'error');
         return;
      }
      
      showStatus('Testing connection...', 'info');
      disableTestButton();
      
      // Make AJAX request to test connection
      fetch(window.location.href, {
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
            showStatus(data.message, 'success');
         } else {
            showStatus(data.message, 'error');
         }
      })
      .catch(error => {
         showStatus('Connection test failed: ' + error.message, 'error');
      })
      .finally(() => {
         enableTestButton();
      });
   }
   
   function showStatus(message, type) {
      const resultDiv = document.getElementById('connection_result');
      if (resultDiv) {
         resultDiv.innerHTML = '<div class="opmanager-status ' + type + '">' + message + '</div>';
      }
   }
   
   function disableTestButton() {
      const button = document.getElementById('test_connection');
      if (button) {
         button.disabled = true;
         button.textContent = 'Testing...';
      }
   }
   
   function enableTestButton() {
      const button = document.getElementById('test_connection');
      if (button) {
         button.disabled = false;
         button.textContent = 'Test OPManager Connection';
      }
   }
   
   function initFormValidation() {
      const form = document.querySelector('form');
      if (form) {
         form.addEventListener('submit', function(e) {
            if (!validateForm()) {
               e.preventDefault();
            }
         });
      }
   }
   
   function validateForm() {
      let isValid = true;
      const errors = [];
      
      // Check required fields
      const requiredFields = [
         'opmanager_server',
         'opmanager_username',
         'opmanager_password'
      ];
      
      requiredFields.forEach(fieldName => {
         const field = document.querySelector('input[name="' + fieldName + '"]');
         if (field && !field.value.trim()) {
            isValid = false;
            errors.push(fieldName.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + ' is required');
            highlightField(field, true);
         } else if (field) {
            highlightField(field, false);
         }
      });
      
      // Validate port number
      const portField = document.querySelector('input[name="opmanager_port"]');
      if (portField && portField.value) {
         const port = parseInt(portField.value);
         if (isNaN(port) || port < 1 || port > 65535) {
            isValid = false;
            errors.push('Port must be a number between 1 and 65535');
            highlightField(portField, true);
         } else {
            highlightField(portField, false);
         }
      }
      
      // Validate sync interval
      const syncIntervalField = document.querySelector('input[name="sync_interval"]');
      if (syncIntervalField && syncIntervalField.value) {
         const interval = parseInt(syncIntervalField.value);
         if (isNaN(interval) || interval < 1 || interval > 60) {
            isValid = false;
            errors.push('Sync interval must be between 1 and 60 minutes');
            highlightField(syncIntervalField, true);
         } else {
            highlightField(syncIntervalField, false);
         }
      }
      
      // Show errors if any
      if (!isValid) {
         showFormErrors(errors);
      }
      
      return isValid;
   }
   
   function highlightField(field, hasError) {
      if (hasError) {
         field.style.borderColor = '#dc3545';
         field.style.backgroundColor = '#fff5f5';
      } else {
         field.style.borderColor = '#ccc';
         field.style.backgroundColor = '#fff';
      }
   }
   
   function showFormErrors(errors) {
      // Remove existing error display
      const existingErrors = document.querySelector('.opmanager-form-errors');
      if (existingErrors) {
         existingErrors.remove();
      }
      
      // Create error display
      const errorDiv = document.createElement('div');
      errorDiv.className = 'opmanager-status error opmanager-form-errors';
      errorDiv.innerHTML = '<strong>Please fix the following errors:</strong><ul>' + 
                          errors.map(error => '<li>' + error + '</li>').join('') + '</ul>';
      
      // Insert before form
      const form = document.querySelector('form');
      if (form) {
         form.parentNode.insertBefore(errorDiv, form);
      }
   }
   
   function initRealTimeUpdates() {
      // Update webhook URL when server/port changes
      const serverField = document.querySelector('input[name="opmanager_server"]');
      const portField = document.querySelector('input[name="opmanager_port"]');
      
      if (serverField && portField) {
         const updateWebhookUrl = () => {
            const server = serverField.value.trim();
            const port = portField.value.trim();
            
            if (server && port) {
               const webhookUrlField = document.querySelector('input[name="webhook_url"]');
               if (webhookUrlField) {
                  const currentUrl = window.location.href;
                  const baseUrl = currentUrl.substring(0, currentUrl.indexOf('/plugins/'));
                  webhookUrlField.value = baseUrl + '/plugins/opmanager/front/webhook.php';
               }
            }
         };
         
         serverField.addEventListener('input', updateWebhookUrl);
         portField.addEventListener('input', updateWebhookUrl);
      }
      
      // Auto-save configuration changes
      const configFields = document.querySelectorAll('input, select, textarea');
      configFields.forEach(field => {
         if (field.name && !field.name.includes('password')) {
            field.addEventListener('change', function() {
               autoSaveConfig(field.name, field.value);
            });
         }
      });
   }
   
   function autoSaveConfig(name, value) {
      // Save configuration value via AJAX
      const formData = new FormData();
      formData.append('action', 'auto_save');
      formData.append('name', name);
      formData.append('value', value);
      
      fetch(window.location.href, {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            showAutoSaveStatus('Configuration saved', 'success');
         } else {
            showAutoSaveStatus('Failed to save configuration', 'error');
         }
      })
      .catch(error => {
         showAutoSaveStatus('Error saving configuration: ' + error.message, 'error');
      });
   }
   
   function showAutoSaveStatus(message, type) {
      // Remove existing status
      const existingStatus = document.querySelector('.opmanager-auto-save-status');
      if (existingStatus) {
         existingStatus.remove();
      }
      
      // Create status display
      const statusDiv = document.createElement('div');
      statusDiv.className = 'opmanager-status ' + type + ' opmanager-auto-save-status';
      statusDiv.textContent = message;
      
      // Insert at top of form
      const form = document.querySelector('form');
      if (form) {
         form.parentNode.insertBefore(statusDiv, form);
         
         // Auto-remove after 3 seconds
         setTimeout(() => {
            if (statusDiv.parentNode) {
               statusDiv.remove();
            }
         }, 3000);
      }
   }
   
   // Utility functions
   function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
         const later = () => {
            clearTimeout(timeout);
            func(...args);
         };
         clearTimeout(timeout);
         timeout = setTimeout(later, wait);
      };
   }
   
   function throttle(func, limit) {
      let inThrottle;
      return function() {
         const args = arguments;
         const context = this;
         if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
         }
      };
   }
   
   // Export functions for global access if needed
   window.OPManagerPlugin = {
      testConnection: testOPManagerConnection,
      validateForm: validateForm,
      showStatus: showStatus
   };
   
})();

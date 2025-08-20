# OPManager Integration Plugin - API Reference

## Overview

Plugin ini menyediakan integrasi bidirectional antara GLPI dan OPManager melalui REST API dan webhook. Dokumen ini menjelaskan semua endpoint API, format data, dan cara penggunaan.

## Base URL

```
https://your-glpi-server/plugins/opmanager/
```

## Authentication

### Webhook Authentication
Webhook menggunakan HMAC-SHA256 signature untuk validasi:

```http
X-OPManager-Signature: <hmac_sha256_signature>
```

### Configuration Authentication
Endpoint konfigurasi memerlukan session GLPI yang valid.

## API Endpoints

### 1. Webhook Endpoint

#### POST /front/webhook.php

Menerima webhook dari OPManager untuk membuat/mengupdate ticket.

**Headers:**
```http
Content-Type: application/json
X-OPManager-Signature: <signature>
```

**Request Body:**
```json
{
  "event_type": "alarm_raised",
  "alarm_id": "12345",
  "device_name": "Router-Core-01",
  "severity": "Critical",
  "message": "High CPU Utilization (95%)",
  "timestamp": "2024-01-15T10:30:00Z",
  "custom_fields": {
    "location": "Data Center 1",
    "department": "IT Infrastructure"
  }
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Ticket created successfully",
  "ticket_id": 67890
}
```

**Response Error (400/500):**
```json
{
  "success": false,
  "message": "Error description"
}
```

### 2. Configuration Endpoint

#### POST /front/config.form.php

Mengupdate konfigurasi plugin.

**Headers:**
```http
Content-Type: application/x-www-form-urlencoded
```

**Form Data:**
- `opmanager_server`: Server OPManager
- `opmanager_port`: Port OPManager
- `opmanager_username`: Username API
- `opmanager_password`: Password API
- `webhook_secret`: Secret key untuk webhook
- `default_entity`: Entity default
- `default_requesttype`: Request type default
- `default_category`: Kategori default
- `enable_bidirectional_sync`: Enable/disable sync
- `sync_interval`: Interval sinkronisasi (menit)
- `max_retry_attempts`: Jumlah retry maksimal
- `retry_delay`: Delay antar retry (menit)
- `custom_fields_mapping`: Mapping field kustom

**Response:**
Redirect ke halaman konfigurasi dengan pesan sukses.

### 3. Test Connection Endpoint

#### POST /front/config.form.php

Test koneksi ke OPManager.

**Form Data:**
- `action`: "test_connection"
- `server`: Server OPManager
- `port`: Port OPManager
- `username`: Username API
- `password`: Password API

**Response:**
```json
{
  "success": true,
  "message": "Connection successful"
}
```

## Webhook Event Types

### 1. alarm_raised
Alarm baru muncul di OPManager.

**Payload:**
```json
{
  "event_type": "alarm_raised",
  "alarm_id": "12345",
  "device_name": "Router-Core-01",
  "severity": "Critical",
  "message": "High CPU Utilization (95%)",
  "timestamp": "2024-01-15T10:30:00Z",
  "custom_fields": {
    "location": "Data Center 1",
    "department": "IT Infrastructure",
    "monitor_name": "CPU Monitor"
  }
}
```

**Action:** Membuat ticket baru di GLPI

### 2. alarm_cleared
Alarm di-clear di OPManager.

**Payload:**
```json
{
  "event_type": "alarm_cleared",
  "alarm_id": "12345",
  "device_name": "Router-Core-01",
  "severity": "Clear",
  "message": "CPU Utilization returned to normal (25%)",
  "timestamp": "2024-01-15T10:45:00Z"
}
```

**Action:** Update ticket status menjadi resolved

### 3. alarm_acknowledged
Alarm di-acknowledge di OPManager.

**Payload:**
```json
{
  "event_type": "alarm_acknowledged",
  "alarm_id": "12345",
  "device_name": "Router-Core-01",
  "severity": "Critical",
  "message": "High CPU Utilization (95%)",
  "timestamp": "2024-01-15T10:35:00Z"
}
```

**Action:** Tambah comment acknowledgment ke ticket

### 4. alarm_updated
Alarm diupdate di OPManager.

**Payload:**
```json
{
  "event_type": "alarm_updated",
  "alarm_id": "12345",
  "device_name": "Router-Core-01",
  "severity": "Major",
  "message": "High CPU Utilization (85%)",
  "timestamp": "2024-01-15T10:40:00Z"
}
```

**Action:** Update ticket dengan informasi baru

## Severity Mapping

### OPManager → GLPI

| OPManager Severity | GLPI Priority | GLPI Urgency | GLPI Impact |
|-------------------|---------------|--------------|-------------|
| Critical          | High          | High         | High        |
| Major             | High          | High         | High        |
| Minor             | Medium        | Medium       | Medium      |
| Warning           | Medium        | Medium       | Medium      |
| Info              | Low           | Low          | Low         |
| Clear             | Low           | Low          | Low         |

### GLPI → OPManager

| GLPI Status       | OPManager Status |
|-------------------|------------------|
| Incoming          | active           |
| Assigned          | assigned         |
| Planned           | scheduled        |
| Waiting           | waiting          |
| Solved            | resolved         |
| Closed            | closed           |

## Custom Fields

Plugin mendukung field kustom yang dapat dikonfigurasi:

### Konfigurasi Mapping
```json
{
  "location": "glpi_location",
  "department": "glpi_department",
  "monitor_name": "glpi_monitor_type"
}
```

### Penggunaan
Field kustom akan otomatis ditambahkan ke ticket content dan dapat digunakan untuk:
- Kategorisasi ticket
- Routing otomatis
- Reporting dan analytics

## Error Handling

### HTTP Status Codes

- **200**: Success
- **400**: Bad Request (invalid data)
- **401**: Unauthorized (invalid signature)
- **405**: Method Not Allowed
- **500**: Internal Server Error

### Error Response Format
```json
{
  "success": false,
  "message": "Detailed error description",
  "error_code": "ERROR_CODE",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Common Error Codes

- `INVALID_ALARM_DATA`: Data alarm tidak valid
- `DUPLICATE_ALARM`: Alarm sudah ada
- `TICKET_CREATION_FAILED`: Gagal membuat ticket
- `INVALID_SIGNATURE`: Signature tidak valid
- `CONFIGURATION_MISSING`: Konfigurasi tidak lengkap

## Rate Limiting

Plugin tidak menerapkan rate limiting, tetapi OPManager sebaiknya:
- Tidak mengirim webhook lebih dari 10 per detik
- Implement exponential backoff untuk retry
- Monitor response time dan error rate

## Security Considerations

### 1. Webhook Security
- Gunakan HTTPS untuk semua komunikasi
- Secret key minimal 32 karakter
- Rotate secret key secara berkala
- Monitor webhook access logs

### 2. API Security
- Validasi semua input data
- Sanitasi output data
- Implement proper error handling
- Log semua aktivitas mencurigakan

### 3. Network Security
- Firewall hanya allow IP OPManager
- VPN tunnel untuk komunikasi internal
- Monitor network traffic patterns

## Monitoring & Logging

### 1. Log Levels
- **INFO**: Normal operations
- **WARNING**: Potential issues
- **ERROR**: Errors that need attention
- **DEBUG**: Detailed debugging info

### 2. Log Categories
- `webhook`: Webhook processing
- `sync`: Synchronization activities
- `retry`: Retry mechanism
- `config`: Configuration changes
- `error`: Error conditions

### 3. Metrics
- Webhook success rate
- Sync performance
- Error frequency
- Response times

## Troubleshooting

### 1. Webhook Not Received
```bash
# Check webhook endpoint
curl -X POST https://your-glpi-server/plugins/opmanager/front/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'

# Check firewall rules
iptables -L | grep 443

# Check web server logs
tail -f /var/log/nginx/access.log
```

### 2. Authentication Issues
```bash
# Verify signature generation
echo -n '{"test":"data"}' | openssl dgst -sha256 -hmac "your_secret"

# Check webhook secret configuration
mysql -e "SELECT * FROM glpi_plugin_opmanager_config WHERE name='webhook_secret';"
```

### 3. Database Issues
```bash
# Check table structure
mysql -e "DESCRIBE glpi_plugin_opmanager_alarms;"

# Check for errors
mysql -e "SELECT * FROM glpi_plugin_opmanager_logs WHERE level='ERROR' ORDER BY timestamp DESC LIMIT 10;"
```

## Examples

### 1. Python Webhook Client
```python
import requests
import hmac
import hashlib
import json

def send_webhook(url, secret, data):
    # Generate signature
    payload = json.dumps(data)
    signature = hmac.new(
        secret.encode('utf-8'),
        payload.encode('utf-8'),
        hashlib.sha256
    ).hexdigest()
    
    # Send request
    headers = {
        'Content-Type': 'application/json',
        'X-OPManager-Signature': signature
    }
    
    response = requests.post(url, data=payload, headers=headers)
    return response.json()

# Example usage
webhook_data = {
    "event_type": "alarm_raised",
    "alarm_id": "12345",
    "device_name": "Router-Core-01",
    "severity": "Critical",
    "message": "High CPU Utilization",
    "timestamp": "2024-01-15T10:30:00Z"
}

result = send_webhook(
    "https://your-glpi-server/plugins/opmanager/front/webhook.php",
    "your_secret_key",
    webhook_data
)
print(result)
```

### 2. JavaScript Webhook Client
```javascript
const crypto = require('crypto');

function sendWebhook(url, secret, data) {
    const payload = JSON.stringify(data);
    const signature = crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    const headers = {
        'Content-Type': 'application/json',
        'X-OPManager-Signature': signature
    };
    
    return fetch(url, {
        method: 'POST',
        headers: headers,
        body: payload
    })
    .then(response => response.json());
}

// Example usage
const webhookData = {
    event_type: 'alarm_raised',
    alarm_id: '12345',
    device_name: 'Router-Core-01',
    severity: 'Critical',
    message: 'High CPU Utilization',
    timestamp: new Date().toISOString()
};

sendWebhook(
    'https://your-glpi-server/plugins/opmanager/front/webhook.php',
    'your_secret_key',
    webhookData
)
.then(result => console.log(result))
.catch(error => console.error(error));
```

## Support

Untuk bantuan teknis:
- GitHub Issues: [Repository Issues](https://github.com/yourusername/glpi-opmanager/issues)
- Documentation: [Plugin Wiki](https://github.com/yourusername/glpi-opmanager/wiki)
- Community: [GLPI Forum](https://forum.glpi-project.org/)

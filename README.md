# GLPI OPManager Integration Plugin

[![GLPI Version](https://img.shields.io/badge/GLPI-10.0.0+-blue.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2+-orange.svg)](LICENSE)

A comprehensive bidirectional integration plugin between GLPI (IT Service Management) and OPManager (Network Management System) for automated ticket creation and synchronization.

## 🚀 Features

- **Auto Ticket Creation**: OPManager alarms automatically trigger GLPI ticket creation
- **Bidirectional Sync**: Real-time synchronization of status between both systems
- **Custom Fields**: Special fields to store OPManager information in GLPI tickets
- **Retry Mechanism**: Automatic retry system for failed webhook deliveries
- **Comprehensive Logging**: Detailed logs for troubleshooting and monitoring
- **Easy Configuration**: User-friendly configuration interface within GLPI
- **Webhook Security**: HMAC-SHA256 signature validation for secure communication
- **Cron Jobs**: Automated synchronization and retry mechanisms

## 📋 Requirements

- **GLPI**: Version 10.0.0 or higher
- **PHP**: Version 7.4 or higher
- **Extensions**: cURL, JSON, MBString, MySQL
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **OPManager**: Version 12.0 or higher with REST API access

## 🛠️ Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Extract to `GLPI_ROOT/plugins/opmanager/`
3. Access GLPI as administrator
4. Go to Setup → Plugins
5. Install and activate the "OPManager Integration" plugin

### Method 2: Composer Installation
```bash
composer require yourusername/glpi-opmanager
```

### Method 3: Package Installation
```bash
# Download the latest release
wget https://github.com/yourusername/glpi-opmanager/releases/latest/download/opmanager.zip
# Extract and install
```

## ⚙️ Configuration

### 1. GLPI Configuration
1. Go to Setup → OPManager Integration
2. Configure OPManager connection details:
   - **Server URL**: OPManager server address
   - **Username**: API username
   - **Password**: API password or token
   - **Webhook Secret**: Secret key for webhook validation

### 2. OPManager Configuration
1. Configure webhook endpoint in OPManager:
   - **URL**: `https://your-glpi-server/plugins/opmanager/front/webhook.php`
   - **Events**: Select alarm events (Critical, Warning, Clear, Acknowledge)
   - **Custom Variables**: Include necessary alarm data

### 3. Database Setup
The plugin automatically creates required tables during installation:
- Configuration storage
- Alarm-ticket mapping
- Webhook retry queue
- Audit logs

## 🔧 Usage

### Webhook Endpoint
OPManager sends alarm notifications to:
```
POST /plugins/opmanager/front/webhook.php
```

### Supported Events
- `alarm_raised`: Creates new tickets
- `alarm_cleared`: Updates ticket status
- `alarm_acknowledged`: Marks tickets as acknowledged
- `alarm_updated`: Updates existing tickets

### Custom Fields Mapping
The plugin automatically maps OPManager data to GLPI custom fields:
- Device information
- Alarm details
- Network metrics
- Custom variables

## 📊 Monitoring

### Logs
- Webhook activities
- Synchronization results
- Error details
- Performance metrics

### Statistics
- Tickets created/updated
- Sync success rates
- Retry attempts
- Response times

## 🔒 Security

- **HMAC-SHA256**: Webhook signature validation
- **API Authentication**: Secure OPManager API access
- **Input Validation**: Comprehensive data sanitization
- **Access Control**: GLPI permission-based access

## 🚨 Troubleshooting

### Common Issues
1. **Connection Failed**: Check OPManager credentials and network access
2. **Webhook Not Working**: Verify webhook URL and secret key
3. **Tickets Not Syncing**: Check cron job configuration
4. **Database Errors**: Verify table permissions and structure

### Debug Mode
Enable debug logging in the configuration to get detailed error information.

## 📚 API Reference

See [API_REFERENCE.md](docs/API_REFERENCE.md) for detailed API documentation.

## 🔄 Development

### Project Structure
```
opmanager/
├── inc/           # Core classes
├── front/         # Web endpoints
├── sql/           # Database scripts
├── locales/       # Language files
├── css/           # Stylesheets
├── js/            # JavaScript
├── docs/          # Documentation
└── tests/         # Unit tests
```

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the GNU General Public License v2.0 - see the [LICENSE](LICENSE) file for details.

## 🤝 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/glpi-opmanager/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/glpi-opmanager/wiki)
- **Community**: [GLPI Forum](https://forum.glpi-project.org/)

## 🙏 Acknowledgments

- GLPI Project Team
- OPManager Community
- Open Source Contributors

## 📈 Roadmap

- [ ] Advanced filtering rules
- [ ] SLA integration
- [ ] Escalation workflows
- [ ] Mobile notifications
- [ ] Multi-tenant support
- [ ] Performance optimization

---

**Made with ❤️ for the GLPI and OPManager communities**

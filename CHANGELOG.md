# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial plugin structure
- Core integration classes
- Webhook handling system
- Configuration management
- Bidirectional synchronization
- Retry mechanism for failed webhooks
- Comprehensive logging system
- Custom fields mapping
- Cron job automation
- Security features (HMAC validation)
- User-friendly configuration interface
- API documentation
- Installation guides

### Changed
- N/A

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- N/A

### Security
- HMAC-SHA256 signature validation for webhooks
- Input sanitization and validation
- Secure API authentication

## [1.0.0] - 2024-01-15

### Added
- **Core Plugin Structure**
  - Main plugin class with OPManager integration logic
  - Webhook handler for incoming OPManager notifications
  - Configuration management system
  - Bidirectional synchronization engine
  - Cron job automation system

- **Webhook System**
  - Support for alarm events (raised, cleared, acknowledged, updated)
  - HMAC-SHA256 signature validation
  - Automatic ticket creation and updates
  - Custom fields mapping from OPManager to GLPI

- **Synchronization Features**
  - Real-time bidirectional sync between GLPI and OPManager
  - Automatic status updates
  - Alarm-ticket mapping
  - Conflict resolution

- **Configuration Interface**
  - OPManager connection settings
  - Webhook configuration
  - Ticket default values
  - Sync settings and intervals
  - Connection testing functionality

- **Database Schema**
  - Configuration storage
  - Alarm-ticket mapping table
  - Webhook retry queue
  - Comprehensive audit logging

- **Security Features**
  - Webhook signature validation
  - API authentication
  - Input sanitization
  - Access control integration

- **Monitoring & Logging**
  - Detailed activity logs
  - Performance metrics
  - Error tracking
  - Sync statistics

- **Documentation**
  - Comprehensive README
  - API reference documentation
  - Installation guides
  - Troubleshooting information

### Technical Details
- **GLPI Compatibility**: 10.0.0+
- **PHP Version**: 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **OPManager**: 12.0+ with REST API
- **Extensions**: cURL, JSON, MBString, MySQL

### Installation
- Manual installation via GLPI plugin system
- Composer package installation
- Automated database setup
- Configuration wizard

### Configuration
- OPManager server connection
- Webhook endpoint setup
- Ticket default values
- Sync intervals and retry settings
- Security keys and authentication

---

## Version Compatibility

| GLPI Version | Plugin Version | PHP Version | Status |
|--------------|----------------|-------------|---------|
| 10.0.x      | 1.0.0         | 7.4+       | ✅ Supported |
| 10.1.x      | 1.0.0         | 7.4+       | ✅ Supported |
| 10.2.x      | 1.0.0         | 7.4+       | ✅ Supported |
| 11.0.x      | 1.0.0         | 8.0+       | ✅ Supported |

## Upgrade Notes

### From Pre-1.0.0
- This is the initial release
- No upgrade path from previous versions
- Fresh installation required

### Database Changes
- All tables are created automatically during installation
- No manual database migration required
- Existing GLPI data is preserved

## Known Issues

- None reported in this version

## Future Plans

### Version 1.1.0 (Planned)
- Advanced filtering rules
- SLA integration
- Escalation workflows
- Performance optimizations

### Version 1.2.0 (Planned)
- Mobile notifications
- Multi-tenant support
- Advanced reporting
- API rate limiting

### Version 2.0.0 (Planned)
- Complete rewrite with modern architecture
- Enhanced security features
- Advanced customization options
- Plugin marketplace integration

---

For detailed information about each release, please refer to the [GitHub releases page](https://github.com/yourusername/glpi-opmanager/releases).


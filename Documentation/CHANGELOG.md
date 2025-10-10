# 📋 Changelog

All notable changes to SecStore are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased] - Next Version
### Planned
- Backup/restore functionality

---

## [1.4.0] - 2025-10-10
### ✨ Added
- **started implementing mail scheduler backround service**
- **Implemented Azure SSO login**
- **implemented truncate logs**
- **Implemented Logs Export function**
- **Implemented System-Info Dashboard**
- **Implemented DSGVO compliance (Art. 15, Art. 17)**


### 🐛 Fixed
- **fixed various translation typos and imports**
- **fixed various js errors**
- **updated prepareCommitMsg.sh to be mac bash compatible**
- **added missing columns to TestCase.php**
- **various Azure SSO fixes**
- **excluded privacy settings for super.admin user**
- **fixed error in prepareCommitMsg.sh**


### 📝 Documentation
- **updated Documentation**
- **updated README**
- **updated Documentation for GDPR Cronjob**

---

## [1.3.2] - 2025-10-03
### ✨ Added
- **implemented ip-whitelist feature**
- **Implemented 2fa Backup codes**
- **Implemented Register verification with token**
- **Implemented Git-Hooks**
- **Docker support with complete containerization**
- **Dockerfile for PHP 8.3** with all required extensions
- **docker-compose.yml** with MySQL 8.0 and phpMyAdmin
- **Automatic setup** via docker-entrypoint.sh
- **Persistent volumes** for config, cache, logs and database
- **Optional Makefile** for developer convenience
- **Comprehensive Docker documentation** ([README_DOCKER.md](README_DOCKER.md) and [DOCKER_QUICKSTART.md](DOCKER_QUICKSTART.md))


### 🐛 Fixed
- **ensure PSR compatibility**
- **prevent account enumeration**
- **avoid LDAP injection and added LDAP Security Tset**
- **implemented prepareCommitMsg.sh**
- **changelog**
- **removed debug messages from CHANGELOG**


### 📝 Documentation
- **Add quick-commit.sh documentation to DEVDOC**

---

## [1.3.1] - 2025-08-15
### ✨ Added
- **Advanced security analytics** Heatmap, Weekly Trends etc..
- **Implemented global Input validation**
- **4 step setup Wizard**
- **bulk user actions** 2fa Un/enforce, enable/disable, delete

### 🔄 Changed
- **various security fixes**
- **removed debug logging**
- **optimized sql queries fdor performance**
- **updated documentation**

---

## [1.3.0] - 2025-01-15
### ✨ Added
- **Security Dashboard** with real-time threat overview
- **Rate Limiting System** with configurable scopes based on sensitivity
- **Rate Limiting Statistics** and live monitoring
- **Session Fingerprinting** to protect against session hijacking
- **Automatic Session ID Regeneration** every 30 minutes
- **Content Security Policy (CSP)** with XSS protection and HSTS

### 🔄 Changed
- Improved admin interface with more modern UI
- Optimized database queries for better performance
- Extended logging categories (SQL, Security, Mail)

### 🐛 Fixed
- Session timeout issues with concurrent users
- CSRF token validation in AJAX requests
- Memory leaks during extended admin sessions

---

## [1.2.0] - 2024-12-20
### ✨ Added
- **LDAP Integration** with per-user configuration
- **Role-based Access Control** with flexible role system
- **Mail Template System** with Latte engine
- **Multi-language Support** (German and English)
- **Dark Mode** with user preferences

### 🔄 Changed
- Migration from PHP 8.1 to PHP 8.3 minimum requirement
- Redesigned database structure for better scalability
- Improved error handling with detailed log information

---

## [1.1.0] - 2024-11-10
### ✨ Added
- **Two-Factor Authentication (2FA)** with TOTP support
- **QR Code Generation** for 2FA setup
- **2FA Enforcement** by administrators per user
- **Brute-Force Protection** with configurable parameters
- **Audit Logging** for all security-relevant actions
- **Password Reset Functionality** via email

### 🔄 Changed
- Extended user profiles with 2FA management
- Improved email templates for better user experience
- Optimized session management with fingerprinting

### 🔒 Security
- BCRYPT password hashing (60 characters) implemented
- CSRF protection activated for all forms
- Session security through User-Agent/Accept-Language validation

---

## [1.0.0] - 2024-10-01 🎉
### ✨ First Release
- **Core Authentication System** with login/logout
- **User Registration** (optionally activatable)
- **Admin Panel** for user management
- **Email System** with SMTP support and welcome emails
- **Configurable Settings** via web interface
- **Automatic Database Migration** and setup
- **CLI Tool** for generating secure keys

### 🛠️ Technical Stack
- **PHP 8.3+** as minimum requirement
- **Flight PHP Microframework** for lean performance  
- **Latte Template Engine** for modern templates
- **MariaDB/MySQL** with UTF8MB4 support
- **Idiorm + Paris ORM** for database operations
- **PHPMailer** for reliable email sending

### 📦 Core Features
- Responsive design with Bootstrap 5
- Minimalist and modern interface
- Complete CRUD operations for users
- Configurable session timeouts
- Comprehensive error handling and logging
- PSR-12 compliant code quality

---

## [0.9.0] - 2024-09-15
### 🔧 Beta Release
- First public beta version
- Basic authentication system
- User management interface
- Email integration
- Initial security features

### 🐛 Known Issues
- Session handling needed improvement
- Limited error handling
- Basic logging only
- No 2FA support yet

---

## [0.8.0] - 2024-08-30
### 🔧 Alpha Release
- Initial alpha version
- Core authentication framework
- Basic database schema
- Simple user interface
- SMTP email functionality

### ⚠️ Development Status
- Alpha stage - not recommended for production
- Limited features
- Frequent breaking changes
- Basic security implementations

---

## [0.7.0] - 2024-08-15
### 🔧 Pre-Alpha
- Project initialization
- Basic PHP framework setup
- Database design
- Development environment setup
- Initial code structure

---

## 📝 Release Notes Guidelines

### Version Numbering
- **Major (X.0.0)**: Breaking changes, major new features
- **Minor (X.Y.0)**: New features, backwards compatible
- **Patch (X.Y.Z)**: Bug fixes, security patches

### Change Categories
- ✨ **Added**: New features
- 🔄 **Changed**: Changes in existing functionality
- 🗑️ **Deprecated**: Soon-to-be removed features
- 🐛 **Fixed**: Bug fixes
- 🔒 **Security**: Security improvements
- ⚠️ **Breaking**: Breaking changes

---

## 🔗 Links

- **Repository**: [GitHub](https://github.com/madcoda9000/SecStore)
- **Issues**: [Bug Reports](https://github.com/madcoda9000/SecStore/issues)
- **Discussions**: [Community](https://github.com/madcoda9000/SecStore/discussions)
- **Documentation**: [Wiki](https://github.com/madcoda9000/SecStore/wiki)

---


<div align="center">

# 🔐 SecStore
### *Modern, secure user management for the web*

[![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-success?style=for-the-badge)](LICENSE)
[![Last Commit](https://img.shields.io/github/last-commit/madcoda9000/SecStore?style=for-the-badge&color=blue)](https://github.com/madcoda9000/SecStore/commits/main)
[![Maintained](https://img.shields.io/maintenance/yes/2025?style=for-the-badge&color=green)](https://github.com/madcoda9000/SecStore)

**A complete authentication and user management platform with modern security features and enterprise-ready functionality.**

[📚 Documentation](Documentation/INSTALL.md) • [🧑‍💻 Developer Documentation](Documentation/DEVDOC.md) • [🐛 Bug Reports](https://github.com/madcoda9000/SecStore/issues) • [💬 Discussions](https://github.com/madcoda9000/SecStore/discussions)

</div>

---

## ✨ Why SecStore?

<table>
<tr>
<td width="50%">

### 🛡️ **Security first**
- **Zero-Trust architecture** with session fingerprinting
- **2FA support** (TOTP) with QR code setup
- **Advanced rate limiting** with intelligent scopes
- **Security dashboard** Login Ananlytics, Security Events, 

</td>
<td width="50%">

### ⚡ **Developer Experience**
- **One-click installation** with automatic setup script
- **PSR-12 compliant** with code quality tools
- **Modern PHP 8.3+** with type declarations
- **Latte templates** for clean, secure views

</td>
</tr>
<tr>
<td>

### 🌐 **Enterprise-Ready**
- **LDAP integration** for corporate environments
- **Granular role management** (RBAC)
- **Comprehensive audit logging** of all actions
- **Multi-language support** (DE/EN)

</td>
<td>

### 🎨 **Modern UI/UX**
- **Bootstrap 5** design
- **Dark/Light mode** with user preferences
- **Mobile first**
<br>
<br>

 
  

</td>
</tr>
</table>

---

## 🚀 Quick Start

### **1-Minute Setup (Automatic)**

```bash
# Clone repository
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# Run automatic setup script
chmod +x setup.sh && ./setup.sh

# Customize configuration
cp config.php_TEMPLATE config.php
# -> Enter DB credentials

# Start development server
php -S localhost:8000 -t public
```

**🎉 Done! SecStore is running at http://localhost:8000**

**Default Login:** `super.admin` / `Test1000!` *(⚠️ Change password immediately!)*

### **Manual Installation**

For detailed installation instructions and production setup see **[📖 INSTALL.md](Documentation/INSTALL.md)**

---

## 🧑‍💻 Extend SecStore

**SecStore is designed as a boilerplate** for building custom web applications with modern security features built-in.

<div align="center">

[![Developer Documentation](https://img.shields.io/badge/📖_Developer_Guide-Read_Now-blue?style=for-the-badge)](Documentation/DEVDOC.md)

**Learn to extend SecStore with custom pages, controllers, and features**  
*Complete step-by-step guide with practical examples*

</div>

### **What you'll learn:**
- 🔧 Development environment setup
- 📄 Creating Latte templates and controllers  
- 🛣️ Adding routes and navigation
- 💾 JavaScript integration (CSP-compliant)
- 🌍 Multilingual support implementation

---

## 🌟 Feature Highlights

<details>
<summary><b>🔐 Authentication & Security</b></summary>

- ✅ **Multi-Factor Authentication (MFA/2FA)** with TOTP standard
- ✅ **LDAP integration** for enterprise connectivity
- ✅ **Session security** with fingerprinting and auto-regeneration
- ✅ **Brute-force protection** with intelligent blocking mechanisms
- ✅ **Password security** with BCRYPT hashing (60 characters)
- ✅ **CSRF protection** for all forms
- ✅ **Content Security Policy (CSP)** against XSS attacks

</details>

<details>
<summary><b>⚡ Rate Limiting & DOS Protection</b></summary>

- ✅ **Granular rate limiting** with scope-based limits
- ✅ **Real-time statistics** and violation tracking  
- ✅ **Intelligent throttling** based on action sensitivity
- ✅ **Admin whitelist** functions
- ✅ **Automatic cleanup** and block management

</details>

<details>
<summary><b>👥 User Management</b></summary>

- ✅ **Role-based access control (RBAC)**
- ✅ **Flexible user management** with admin interface
- ✅ **Bulk actions** (Enforce and Unenforce 2fa, delte, Enable and Disable)
- ✅ **Self-service profile** management
- ✅ **Password reset** via email (can be enabled/disbaled)
- ✅ **Registration system** (can be enabled/disabled)
- ✅ **2FA enforcement** per user by admins

</details>

<details>
<summary><b>📊 Monitoring & Logging</b></summary>

- ✅ **Security dashboard** with Login Analytics (Heatmap, Hourly, Weekly, Pattern detection)
- ✅ **Comprehensive logging** (Audit, Security, System, Mail, DB)
- ✅ **Log categories** with granular configuration
- ✅ **Violation tracking** and threat intelligence
- ✅ **Performance metrics** and system health

</details>

<details>
<summary><b>🎨 User Experience</b></summary>

- ✅ **Dark/Light theme** with automatic detection
- ✅ **Multi-language** (German/English)
- ✅ **Intuitive admin interface**
- ✅ **Mobile first** every page is mobile optimized

</details>

---

## 📱 Screenshots

<div align="center">

### 🔑 Login & Authentication

<img src="Documentation/Screenshots/Login.png" width="400" alt="Modern Login Interface">

### 👤 User Dashboard & Profile  
<img src="Documentation/Screenshots/UserProfile.png" width="400" alt="User Profile Management">

</div>

<details>
<summary><b>🖼️ Show more screenshots</b></summary>

<div align="center">

| Admin Area | Security Dashboard |
|:---:|:---:|
| <img src="Documentation/Screenshots/Users.png" width="350" alt="User Management"> | <img src="Documentation/Screenshots/secDashboard.png" width="350" alt="Security Dashboard"> |

| Rate Limiting | Audit Logs |
|:---:|:---:|
| <img src="Documentation/Screenshots/Ratelimitstats.jpg" width="350" alt="Rate Limiting Stats"> | <img src="Documentation/Screenshots/AuditLogs.png" width="350" alt="Audit Logging"> |

| Settings | Registration |
|:---:|:---:|
| <img src="Documentation/Screenshots/Settings.png" width="350" alt="System Settings"> | <img src="Documentation/Screenshots/Register.png" width="350" alt="User Registration"> |

</div>

</details>

---

## 🏗️ Technology Stack

<table>
<tr>
<td><b>Backend</b></td>
<td><img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP"> <img src="https://img.shields.io/badge/Flight_PHP-Framework-orange" alt="Flight PHP"></td>
</tr>
<tr>
<td><b>Frontend</b></td>
<td><img src="https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white" alt="Bootstrap"> <img src="https://img.shields.io/badge/Latte-Templates-red" alt="Latte"></td>
</tr>
<tr>
<td><b>Database</b></td>
<td><img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white" alt="MySQL"> <img src="https://img.shields.io/badge/MariaDB-10.4+-003545?logo=mariadb&logoColor=white" alt="MariaDB"></td>
</tr>
<tr>
<td><b>Security</b></td>
<td><img src="https://img.shields.io/badge/2FA-TOTP-green" alt="2FA"> <img src="https://img.shields.io/badge/LDAP-Integration-blue" alt="LDAP"> <img src="https://img.shields.io/badge/CSRF-Protected-red" alt="CSRF"></td>
</tr>
<tr>
<td><b>Tools</b></td>
<td><img src="https://img.shields.io/badge/Composer-Dependency_Manager-brown?logo=composer" alt="Composer"> <img src="https://img.shields.io/badge/PHPMailer-Email-blue" alt="PHPMailer"></td>
</tr>
</table>

### 🔧 **System Requirements**

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **PHP** | 8.3+ | 8.3+ (latest) |
| **MySQL/MariaDB** | 8.0+ / 10.4+ | 8.0+ / 10.6+ |
| **Webserver** | Apache 2.4 / Nginx 1.18 | Apache 2.4+ / Nginx 1.20+ |
| **RAM** | 512 MB | 1 GB+ |
| **Storage** | 100 MB | 500 MB+ |

---

## 📂 Project Architecture

```
SecStore/
├── 📁 app/                    # Core Application
│   ├── Controllers/           # MVC Controllers
│   ├── Models/               # Data Models  
│   ├── Utils/                # Helper Classes
│   ├── Middleware/           # Request Middleware
│   └── views/                # Latte Templates
├── 📁 public/                # Web Root (Entry Point)
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript Files
│   └── index.php            # Application Bootstrap
├── 📁 Documentation/         # Project Documentation
│   ├── INSTALL.md           # Installation Guide
│   ├── CHANGELOG.md         # Version History
│   ├── SECURITY.md          # Security Policy
│   └── Screenshots/         # UI Screenshots
├── 📁 cache/                 # Template Cache
├── ⚙️ config.php            # Main Configuration
├── 🔐 generate_key.php      # Crypto Key Generator
├── 🚀 setup.sh             # Auto-Setup Script
└── 📋 composer.json         # Dependencies
```

---

## 🛠️ Development

### **Development Setup**

```bash
# Install dependencies with dev tools
composer install

# Check code quality
vendor/bin/phpcs                # PSR-12 Compliance Check
vendor/bin/php-cs-fixer fix     # Auto-Format Code

# Development Server
php -S localhost:8000 -t public
```

### **Contributing**

We welcome contributions! 🎉

1. **Fork** the repository
2. **Create branch** for new feature (`feature/amazing-feature`)
3. **Commit** changes (`git commit -m 'Add amazing feature'`)
4. **Push** to branch (`git push origin feature/amazing-feature`)
5. **Open Pull Request**

**Coding Standards:**
- ✅ PSR-12 compliant PHP code
- ✅ Meaningful commit messages  
- ✅ Tests for new features
- ✅ Update documentation

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| 📖 **[INSTALL.md](Documentation/INSTALL.md)** | Complete installation and setup guide |
| 📝 **[CHANGELOG.md](Documentation/CHANGELOG.md)** | Version history and release notes |
| 🔒 **[SECURITY.md](Documentation/SECURITY.md)** | Security policies and vulnerability reporting ||
| 🐳 **[DOCKER.md](#)** | Docker setup (planned) |

---

## 🤝 Community & Support

<div align="center">

### 💬 **Get Help**

[![GitHub Issues](https://img.shields.io/badge/GitHub-Issues-red?style=for-the-badge&logo=github)](https://github.com/madcoda9000/SecStore/issues)
[![GitHub Discussions](https://img.shields.io/badge/GitHub-Discussions-blue?style=for-the-badge&logo=github)](https://github.com/madcoda9000/SecStore/discussions)
[![Email Support](https://img.shields.io/badge/Email-Support-green?style=for-the-badge&logo=gmail)](mailto:support@example.com)

</div>

### **❓ Frequently Asked Questions**

<details>
<summary><b>Can SecStore be used in production?</b></summary>

Yes! SecStore was built for production environments and implements modern security standards. See [SECURITY.md](Documentation/SECURITY.md) for details.
</details>

<details>
<summary><b>Does SecStore support Single Sign-On (SSO)?</b></summary>

Through LDAP integration, you can connect SecStore to existing SSO solutions. Native SAML/OAuth2 support is planned.
</details>

<details>
<summary><b>How can I contribute to the project?</b></summary>

We welcome issues, pull requests, documentation, and feature suggestions! See our contributing guidelines above.
</details>

---

## 📊 Project Stats

<div align="center">

![GitHub stars](https://img.shields.io/github/stars/madcoda9000/SecStore?style=social)
![GitHub forks](https://img.shields.io/github/forks/madcoda9000/SecStore?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/madcoda9000/SecStore?style=social)

![GitHub repo size](https://img.shields.io/github/repo-size/madcoda9000/SecStore)
![Lines of code](https://img.shields.io/tokei/lines/github/madcoda9000/SecStore)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/madcoda9000/SecStore)

</div>

---

## ⭐ Give us a Star!

If you like SecStore, give us a ⭐ on GitHub! This motivates us to continue working on the project.

<div align="center">

### 🙏 **Thank you for your interest in SecStore!**

*Built with ❤️ for the open-source community*

---

**[⬆️ Back to top](#-secstore)**

</div>
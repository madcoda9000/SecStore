<div align="center">

# 🔐 SecStore
### *Moderne, sichere Benutzerverwaltung für das Web*

[![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-success?style=for-the-badge)](LICENSE)
[![Last Commit](https://img.shields.io/github/last-commit/madcoda9000/SecStore?style=for-the-badge&color=blue)](https://github.com/madcoda9000/SecStore/commits/main)
[![Maintained](https://img.shields.io/maintenance/yes/2025?style=for-the-badge&color=green)](https://github.com/madcoda9000/SecStore)

**Eine vollständige Authentifizierungs- und Benutzerverwaltungsplattform mit modernen Sicherheitsfeatures und Enterprise-Ready-Funktionen.**

[📚 Dokumentation](Documentation/INSTALL.md) • [🚀 Live Demo](#) • [🐛 Bug Reports](https://github.com/madcoda9000/SecStore/issues) • [💬 Diskussionen](https://github.com/madcoda9000/SecStore/discussions)

</div>

---

## ✨ Warum SecStore?

<table>
<tr>
<td width="50%">

### 🛡️ **Sicherheit zuerst**
- **Zero-Trust-Architektur** mit Session Fingerprinting
- **2FA-Unterstützung** (TOTP) mit QR-Code-Setup
- **Advanced Rate Limiting** mit intelligenten Scopes
- **Real-time Security Dashboard** für Bedrohungsüberwachung

</td>
<td width="50%">

### ⚡ **Developer Experience**
- **Ein-Klick-Installation** mit automatischem Setup-Script
- **PSR-12 konform** mit Code-Qualitäts-Tools
- **Modern PHP 8.3+** mit Type Declarations
- **Latte Templates** für saubere, sichere Views

</td>
</tr>
<tr>
<td>

### 🌐 **Enterprise-Ready**
- **LDAP-Integration** für Corporate Environments
- **Granulare Rollenverwaltung** (RBAC)
- **Umfassendes Audit-Logging** aller Aktionen
- **Multi-Language Support** (DE/EN)

</td>
<td>

### 🎨 **Moderne UI/UX**
- **Bootstrap 5** Design
- **Dark/Light Mode** mit Benutzerpräferenzen\
<br>
<br>

 
  

</td>
</tr>
</table>

---

## 🚀 Quick Start

### **1-Minute Setup (Automatisch)**

```bash
# Repository klonen
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# Automatisches Setup-Script ausführen
chmod +x setup.sh && ./setup.sh

# Konfiguration anpassen
cp config.php_TEMPLATE config.php
# -> DB-Zugangsdaten eintragen

# Development-Server starten
php -S localhost:8000 -t public
```

**🎉 Fertig! SecStore läuft unter http://localhost:8000**

**Standard-Login:** `super.admin` / `Test1000!` *(⚠️ Passwort sofort ändern!)*

### **Manuelle Installation**

Für detaillierte Installationsanweisungen und Produktions-Setup siehe **[📖 INSTALL.md](Documentation/INSTALL.md)**

---

## 🌟 Feature-Highlights

<details>
<summary><b>🔐 Authentifizierung & Sicherheit</b></summary>

- ✅ **Multi-Factor Authentication (MFA/2FA)** mit TOTP-Standard
- ✅ **LDAP-Integration** für Unternehmensanbindung
- ✅ **Session Security** mit Fingerprinting und Auto-Regeneration
- ✅ **Brute-Force-Schutz** mit intelligenten Sperrmechanismen
- ✅ **Password Security** mit BCRYPT-Hashing (60 Zeichen)
- ✅ **CSRF-Protection** für alle Formulare
- ✅ **Content Security Policy (CSP)** gegen XSS-Angriffe

</details>

<details>
<summary><b>⚡ Rate Limiting & DOS-Schutz</b></summary>

- ✅ **Granulares Rate Limiting** mit Scope-basierten Limits
- ✅ **Real-time Statistics** und Violation Tracking  
- ✅ **Intelligent Throttling** nach Sensitivität der Aktionen
- ✅ **Admin-Whitelist** Funktionen
- ✅ **Automatic Cleanup** und Block-Management

</details>

<details>
<summary><b>👥 Benutzerverwaltung</b></summary>

- ✅ **Rollenbasierte Zugriffskontrolle (RBAC)**
- ✅ **Flexible Benutzerverwaltung** mit Admin-Interface
- ✅ **Self-Service Profile** Management
- ✅ **Password Reset** via E-Mail (optional)
- ✅ **Registration System** (aktivierbar/deaktivierbar)
- ✅ **2FA-Enforcement** pro Benutzer durch Admins

</details>

<details>
<summary><b>📊 Monitoring & Logging</b></summary>

- ✅ **Security Dashboard** mit Real-time Übersicht
- ✅ **Comprehensive Logging** (Audit, Security, System, Mail, DB)
- ✅ **Log-Kategorien** mit granularer Konfiguration
- ✅ **Violation Tracking** und Threat Intelligence
- ✅ **Performance Metrics** und System Health

</details>

<details>
<summary><b>🎨 User Experience</b></summary>

- ✅ **Dark/Light Theme** mit automatischer Erkennung
- ✅ **Multi-Language** (Deutsch/Englisch)
- ✅ **Intuitive Admin-Interface**

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
<summary><b>🖼️ Mehr Screenshots anzeigen</b></summary>

<div align="center">

| Admin-Bereich | Security Dashboard |
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

## 🏗️ Technologie-Stack

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

### 🔧 **Systemanforderungen**

| Komponente | Minimum | Empfohlen |
|------------|---------|-----------|
| **PHP** | 8.3+ | 8.3+ (neueste) |
| **MySQL/MariaDB** | 8.0+ / 10.4+ | 8.0+ / 10.6+ |
| **Webserver** | Apache 2.4 / Nginx 1.18 | Apache 2.4+ / Nginx 1.20+ |
| **RAM** | 512 MB | 1 GB+ |
| **Storage** | 100 MB | 500 MB+ |

---

## 📂 Projekt-Architektur

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

## 🛠️ Entwicklung

### **Development Setup**

```bash
# Dependencies mit Dev-Tools installieren
composer install

# Code-Qualität prüfen
vendor/bin/phpcs                # PSR-12 Compliance Check
vendor/bin/php-cs-fixer fix     # Auto-Format Code

# Development Server
php -S localhost:8000 -t public
```

### **Beitragen**

Wir freuen uns über Beiträge! 🎉

1. **Fork** das Repository
2. **Branch** für neues Feature erstellen (`feature/amazing-feature`)
3. **Commit** Änderungen (`git commit -m 'Add amazing feature'`)
4. **Push** zum Branch (`git push origin feature/amazing-feature`)
5. **Pull Request** öffnen

**Coding Standards:**
- ✅ PSR-12 konformer PHP-Code
- ✅ Aussagekräftige Commit-Messages  
- ✅ Tests für neue Features
- ✅ Dokumentation aktualisieren

---

## 📖 Dokumentation

| Dokument | Beschreibung |
|----------|--------------|
| 📖 **[INSTALL.md](Documentation/INSTALL.md)** | Vollständige Installations- und Setup-Anleitung |
| 📝 **[CHANGELOG.md](Documentation/CHANGELOG.md)** | Versionshistorie und Release-Notes |
| 🔒 **[SECURITY.md](Documentation/SECURITY.md)** | Sicherheitsrichtlinien und Vulnerability-Reporting |
| 🏗️ **[API.md](#)** | API-Dokumentation (geplant) |
| 🐳 **[DOCKER.md](#)** | Docker-Setup (geplant) |

---

## 🤝 Community & Support

<div align="center">

### 💬 **Hol dir Hilfe**

[![GitHub Issues](https://img.shields.io/badge/GitHub-Issues-red?style=for-the-badge&logo=github)](https://github.com/madcoda9000/SecStore/issues)
[![GitHub Discussions](https://img.shields.io/badge/GitHub-Discussions-blue?style=for-the-badge&logo=github)](https://github.com/madcoda9000/SecStore/discussions)
[![Email Support](https://img.shields.io/badge/Email-Support-green?style=for-the-badge&logo=gmail)](mailto:support@example.com)

</div>

### **❓ Häufige Fragen**

<details>
<summary><b>Kann SecStore in der Produktion eingesetzt werden?</b></summary>

Ja! SecStore wurde für Produktionsumgebungen entwickelt und implementiert moderne Sicherheitsstandards. Siehe [SECURITY.md](Documentation/SECURITY.md) für Details.
</details>

<details>
<summary><b>Unterstützt SecStore Single Sign-On (SSO)?</b></summary>

Über die LDAP-Integration können Sie SecStore an bestehende SSO-Lösungen anbinden. Native SAML/OAuth2-Unterstützung ist geplant.
</details>

<details>
<summary><b>Wie kann ich zum Projekt beitragen?</b></summary>

Wir freuen uns über Issues, Pull Requests, Dokumentation und Feature-Vorschläge! Siehe unsere Contributing-Guidelines oben.
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

## ⭐ Gib uns einen Star!

Wenn dir SecStore gefällt, gib uns einen ⭐ auf GitHub! Das motiviert uns, weiter an dem Projekt zu arbeiten.

<div align="center">

### 🙏 **Danke für dein Interesse an SecStore!**

*Gebaut mit ❤️ für die Open-Source-Community*

---

**[⬆️ Nach oben](#-secstore)**

</div>
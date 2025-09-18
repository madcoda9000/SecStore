# 🔐 SecStore - Vollständige Installationsanleitung

![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-blue?logo=php)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-orange)
![License](https://img.shields.io/github/license/madcoda9000/SecStore?color=green)

**SecStore** ist eine moderne, sichere Benutzerverwaltungs- und Authentifizierungsplattform. Diese Anleitung führt Sie durch die komplette Installation und Konfiguration.

---

## 📋 Inhaltsverzeichnis

- [🔧 Systemanforderungen](#-systemanforderungen)
- [🚀 Schnellstart (Automatische Installation)](#-schnellstart-automatische-installation)
- [⚙️ Manuelle Installation](#️-manuelle-installation)
- [🗄️ Datenbank-Setup](#️-datenbank-setup)
- [📁 Konfiguration](#-konfiguration)
- [🌐 Webserver-Konfiguration](#-webserver-konfiguration)
- [✅ Installation überprüfen](#-installation-überprüfen)
- [🔒 Post-Installation Sicherheit](#-post-installation-sicherheit)
- [🛠️ Entwicklungsumgebung](#️-entwicklungsumgebung)
- [🔧 Troubleshooting](#-troubleshooting)
- [📞 Support](#-support)

---

## 🔧 Systemanforderungen

### **Mindestanforderungen:**

| Komponente | Version | Hinweise |
|------------|---------|----------|
| **PHP** | ≥ 8.3 | CLI-Version erforderlich |
| **Datenbank** | MySQL ≥ 8.0 oder MariaDB ≥ 10.4 | UTF8MB4-Unterstützung |
| **Webserver** | Apache 2.4+ oder Nginx 1.18+ | Optional für Produktion |
| **Composer** | Latest | Dependency-Management |
| **Speicher** | ≥ 512 MB RAM | Für PHP-Prozesse |
| **Festplatte** | ≥ 100 MB | Für Anwendung + Dependencies |

### **Erforderliche PHP-Extensions:**
```
✓ curl        ✓ json       ✓ openssl    ✓ pdo
✓ pdo_mysql   ✓ xml        ✓ zip        ✓ bcmath
✓ gd          ✓ mbstring   ✓ soap       ✓ intl
✓ ldap        ✓ imagick    ✓ redis      ✓ xdebug
```

### **Unterstützte Betriebssysteme:**
- ✅ **Ubuntu** 20.04+ / Debian 11+
- ✅ **Fedora** 35+ / CentOS Stream 9+
- ✅ **Rocky Linux** 9+ / AlmaLinux 9+
- ✅ **macOS** 12+ (mit Homebrew)
- ✅ **Windows** 10+ (mit WSL2 empfohlen)

---

## 🚀 Schnellstart (Automatische Installation)

Die einfachste Methode ist unser automatisches Setup-Script:

### **1. Script herunterladen und ausführen:**

```bash
# Script herunterladen (falls nicht bereits vorhanden)
curl -O https://your-domain.com/secstore_setup.sh

# Ausführbar machen
chmod +x secstore_setup.sh

# Installation starten
./secstore_setup.sh
```

### **2. Was das Script automatisch macht:**

- 🔍 **System-Erkennung**: Automatische Erkennung von Debian/Ubuntu/Fedora
- 📦 **PHP-Installation**: Neueste LTS-Version (8.3+) mit allen Modulen
- 🎼 **Composer**: Globale Installation und Konfiguration
- 🛠️ **Dev-Tools**: PHP CodeSniffer und PHP-CS-Fixer
- 📂 **Projekt-Setup**: Abhängigkeiten installieren mit `composer install`

### **3. Nach der automatischen Installation:**

Springe zu [🗄️ Datenbank-Setup](#️-datenbank-setup) und [📁 Konfiguration](#-konfiguration).

---

## ⚙️ Manuelle Installation

Falls Sie die Installation manuell durchführen möchten:

### **Schritt 1: PHP 8.3+ installieren**

#### **Ubuntu/Debian:**
```bash
# Ondrej PHP Repository hinzufügen
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# PHP 8.3 CLI installieren
sudo apt install -y php8.3-cli

# Erforderliche PHP-Module
sudo apt install -y php8.3-curl php8.3-mysql php8.3-zip \
    php8.3-xml php8.3-mbstring php8.3-bcmath php8.3-gd \
    php8.3-intl php8.3-soap php8.3-redis php8.3-imagick \
    php8.3-ldap php8.3-xdebug
```

#### **Fedora/CentOS/RHEL:**
```bash
# Remi Repository aktivieren
sudo dnf install -y https://rpms.remirepo.net/fedora/remi-release-$(rpm -E %fedora).rpm
sudo dnf module enable -y php:remi-8.3

# PHP und Module installieren
sudo dnf install -y php-cli php-curl php-mysqlnd php-zip \
    php-xml php-mbstring php-bcmath php-gd php-intl \
    php-soap php-redis php-imagick php-ldap php-xdebug
```

### **Schritt 2: Composer installieren**

```bash
# Composer herunterladen und installieren
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --quiet
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
rm composer-setup.php
```

### **Schritt 3: Development Tools installieren**

```bash
# PHP CodeSniffer und CS-Fixer global installieren
composer global require squizlabs/php_codesniffer=*
composer global require friendsofphp/php-cs-fixer

# Composer bin-Verzeichnis zu PATH hinzufügen
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### **Schritt 4: SecStore herunterladen**

```bash
# Projekt klonen oder herunterladen
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# Abhängigkeiten installieren
composer install --no-dev --optimize-autoloader
```

---

## 🗄️ Datenbank-Setup

**✨ Vollautomatisches Setup!**

SecStore übernimmt das komplette Datenbank-Setup automatisch beim ersten Start:

- 🗄️ **Datenbank-Erstellung:** Wird automatisch erstellt, falls nicht vorhanden
- 📋 **Tabellen:** Alle erforderlichen Tabellen werden automatisch angelegt  
- 👤 **Admin-Benutzer:** Standard-Admin wird automatisch erstellt
- 🔄 **Migration:** Datenbankstruktur wird bei Updates automatisch angepasst

> **📝 Wichtig:** Stellen Sie sicher, dass die Datenbank-Zugangsdaten in der `config.php` korrekt hinterlegt sind. SecStore benötigt einen MySQL/MariaDB-Benutzer mit ausreichenden Rechten zum Erstellen von Datenbanken und Tabellen.

**Empfohlene Datenbankbenutzer-Berechtigungen:**
```sql
-- Minimale Rechte für SecStore-Benutzer
GRANT CREATE, ALTER, SELECT, INSERT, UPDATE, DELETE ON *.* TO 'secstore_user'@'localhost';
```

---

## 📁 Konfiguration

### **1. Konfigurationsdatei erstellen:**

```bash
# Im SecStore-Projektverzeichnis
cp config.php_TEMPLATE config.php
```

### **2. Grundkonfiguration in `config.php`:**

```php
<?php
// Datenbank-Konfiguration
$db = [
    'host' => 'localhost',
    'name' => 'secstore',
    'user' => 'secstore_user',
    'pass' => 'IhrSicheresPasswort'
];

// Anwendungs-Einstellungen
$application = [
    'appUrl' => 'https://ihr-domain.com',
    'sessionTimeout' => 1800, // 30 Minuten
    'allowPublicRegister' => true,
    'allowPublicPasswordReset' => true,
];

// E-Mail-Konfiguration
$mail = [
    'host' => 'smtp.ihrmailserver.com',
    'username' => 'noreply@ihr-domain.com',
    'password' => 'IhrMailPasswort',
    'encryption' => 'tls', // oder 'ssl'
    'port' => 587,
    'fromEmail' => 'noreply@ihr-domain.com',
    'fromName' => 'SecStore System',
    'enableWelcomeMail' => true,
];

// LDAP-Einstellungen (optional)
$ldapSettings = [
    'host' => 'ldap.ihr-domain.com',
    'port' => 636,
    'domainPrefix' => 'DOMAIN\\',
];

// Brute-Force-Schutz
$bruteForceSettings = [
    'enabled' => true,
    'maxAttempts' => 5,
    'lockoutTime' => 900, // 15 Minuten
];

// Logging-Konfiguration
$logging = [
    'enableSystemLogging' => true,
    'enableAuditLogging' => true,
    'enableSqlLogging' => false, // Nur für Debugging
    'enableRequestLogging' => false, // Nur für Debugging
    'enableMailLogging' => true,
];

// Rate-Limiting
$rateLimiting = [
    'enabled' => true,
    
    // Custom Limits (überschreibt Defaults)
    'limits' => [
        // Authentifizierung - sehr restriktiv
        'login' => ['requests' => 5, 'window' => 300], // 5 Versuche in 5 Minuten
        'register' => ['requests' => 3, 'window' => 300], // 3 Registrierungen in 5 Minuten
        'forgot-password' => ['requests' => 3, 'window' => 600], // 3 Passwort-Resets in 10 Minuten
        'reset-password' => ['requests' => 3, 'window' => 300], // 3 Reset-Versuche in 5 Minuten
        '2fa' => ['requests' => 5, 'window' => 300], // 5 2FA-Versuche in 5 Minuten
        
        // Admin Bereiche - restriktiv
        'admin' => ['requests' => 50, 'window' => 3600], // 50 Admin-Actions pro Stunde
        
        // Globales Limit als Fallback
        'global' => ['requests' => 100, 'window' => 3600] // 100 Requests pro Stunde
    ],
    
    // Erweiterte Einstellungen
    'settings' => [
        'cleanup_interval' => 3600, // Bereinigung alle 60 Minuten
        'block_duration' => 300, // Blockierung für 5 Minuten
        'log_violations' => true, // Verstöße protokollieren
        'notify_admin' => false, // Admin bei wiederholten Verstößen benachrichtigen
        'whitelist_admin' => true, // Admin-IPs von Rate-Limiting ausschließen
        'grace_period' => 60, // Kulanzzeit in Sekunden
    ]
];

// Erlaubte Hosts für CORS
$allowedHosts = [
    'https://ihr-domain.com',
    'https://www.ihr-domain.com',
];

// Geheimer Schlüssel (mit generate_key.php generieren)
$secretKey = 'IhrGeheimer256BitSchluesselHier';

return [
    'db' => $db,
    'mail' => $mail,
    'application' => $application,
    'ldapSettings' => $ldapSettings,
    'bruteForceSettings' => $bruteForceSettings,
    'logging' => $logging,
    'rateLimiting' => $rateLimiting,
    'allowedHosts' => $allowedHosts,
    'secretKey' => $secretKey,
];
?>
```

### **3. Sicheren Schlüssel generieren:**

```bash
# Geheimen Schlüssel generieren
php generate_key.php
```

> **📝 Wichtig:** Kopieren Sie den generierten Schlüssel und tragen Sie ihn in Ihrer `config.php` als `$secretKey` ein!

### **4. Verzeichnis-Berechtigungen setzen:**

```bash
# Cache-Verzeichnis beschreibbar machen
chmod 755 cache
chown www-data:www-data cache  # oder entsprechender Webserver-User

# Konfigurationsdatei für Webserver beschreibbar machen (für Admin-Settings)
chmod 664 config.php
chown www-data:www-data config.php  # oder entsprechender Webserver-User
```

> **🔒 Sicherheitshinweis:** Die config.php muss vom Webserver beschreibbar sein, damit die Admin-Einstellungen im Web-Interface funktionieren. SecStore prüft automatisch, ob die Datei beschreibbar ist.

---

## 🌐 Webserver-Konfiguration

### **Option 1: PHP Built-in Server (Entwicklung)**

```bash
# Entwicklungsserver starten
php -S localhost:8000 -t public

# Anwendung öffnen
open http://localhost:8000
```

### **Option 2: Apache-Konfiguration**

```apache
<VirtualHost *:80>
    ServerName ihr-domain.com
    DocumentRoot /path/to/SecStore/public
    
    <Directory /path/to/SecStore/public>
        Options -Indexes
        AllowOverride All
        Require all granted
        
        # URL Rewriting aktivieren
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Sicherheit
    <Directory /path/to/SecStore>
        Require all denied
    </Directory>
    
    <Directory /path/to/SecStore/public>
        Require all granted
    </Directory>
</VirtualHost>
```

### **Option 3: Nginx-Konfiguration**

```nginx
server {
    listen 80;
    server_name ihr-domain.com;
    root /path/to/SecStore/public;
    index index.php;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ ^/(app|config\.php|composer\.(json|lock)) {
        deny all;
    }
}
```

---

## ✅ Installation überprüfen

### **1. Erste Anmeldung:**

- **URL:** `http://ihr-domain.com` (oder `http://localhost:8000`)
- **Benutzername:** `super.admin`
- **Passwort:** `Test1000!`

> **⚠️ Wichtig:** Ändern Sie das Standard-Passwort sofort nach der ersten Anmeldung!

### **2. System-Status prüfen:**

```bash
# PHP-Version überprüfen
php --version

# Erforderliche Module überprüfen
php -m | grep -E "(curl|mysql|zip|xml|mbstring|bcmath|gd|intl|soap|redis|imagick|ldap)"

# Composer-Version
composer --version

# Projektabhängigkeiten
composer show
```

### **3. Funktions-Tests:**

- ✅ Anmeldung mit Standard-Account
- ✅ Benutzer-Dashboard erreichbar
- ✅ Admin-Bereich zugänglich
- ✅ E-Mail-Versand (in Einstellungen testen)
- ✅ Registrierung (falls aktiviert)
- ✅ Passwort-Reset (falls aktiviert)

---

## 🔒 Post-Installation Sicherheit

### **1. Sofort nach Installation:**

```bash
# Standard-Admin-Passwort ändern
# -> Im Web-Interface: Profil -> Passwort ändern

# Konfigurationsdatei sichern (aber beschreibbar für Webserver lassen)
chmod 664 config.php
chown www-data:www-data config.php  # oder entsprechender Webserver-User

# Zusätzliche Sicherheit: Zugriff auf Konfiguration außerhalb von public/ verhindern
# (Wird automatisch durch .htaccess/Webserver-Konfiguration geregelt)
```

### **2. Produktionsumgebung:**

```bash
# Debug-Modus deaktivieren
# In config.php oder index.php:
ini_set('display_errors', '0');

# HTTPS erzwingen (in Webserver-Konfiguration)
# Apache: Redirect 301 / https://ihr-domain.com/
# Nginx: return 301 https://$server_name$request_uri;

# Firewall konfigurieren
sudo ufw enable
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
```

### **3. Regelmäßige Wartung:**

```bash
# Updates prüfen
composer update --no-dev

# Logs rotieren (z.B. mit logrotate)
sudo logrotate -f /etc/logrotate.d/secstore

# Backup-Strategie implementieren
mysqldump -u root -p secstore > backup_$(date +%Y%m%d).sql
```

---

## 🛠️ Entwicklungsumgebung

### **Code-Qualität Tools:**

```bash
# Git Pre-Commit Hook aktivieren (verhindert sensitive Datei-Commits)
cp preCommitHook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Hook testen
echo "test" > config-test.php && git add config-test.php
git commit -m "Security test"  # Sollte blockiert werden
rm config-test.php  # Test-Datei entfernen

> **🔒 Sicherheitshinweis:** Das Pre-Commit Hook blockiert automatisch Commits von:
> - `config*.php` (außer Templates)
> - `.env*` Dateien (außer Examples)  
> - `*.key`, `*.credentials`, `*copy*`, `*backup*` Dateien
> 
> **💡 Erlaubte Template-Dateien:** `config.php_TEMPLATE`, `config.php.example`, `.env.example`

# PHP-Syntax prüfen
vendor/bin/phpcs app/

# Code automatisch formatieren
vendor/bin/php-cs-fixer fix

# Entwicklungsserver mit Debugging
php -S localhost:8000 -t public -d xdebug.mode=debug
```

### **Nützliche Entwickler-Commands:**

```bash
# Abhängigkeiten mit Dev-Packages
composer install

# Autoloader aktualisieren
composer dump-autoload

# Neuen geheimen Schlüssel generieren
php generate_key.php

# Database-Schema prüfen
mysql -u secstore_user -p secstore -e "SHOW TABLES;"
```

---

## 🔧 Troubleshooting

### **Häufige Probleme und Lösungen:**

#### **Problem: "Cache directory is not writable"**
```bash
# Lösung:
mkdir -p cache
chmod 755 cache
chown www-data:www-data cache
```

#### **Problem: "Database connection failed"**
```bash
# Verbindung testen:
mysql -u secstore_user -p secstore

# Lösung: config.php Datenbankeinstellungen überprüfen
```

#### **Problem: "Config file not found"**
```bash
# Lösung:
cp config.example.php config.php
# Dann config.php entsprechend anpassen
```

#### **Problem: E-Mail-Versand funktioniert nicht**
```bash
# SMTP-Einstellungen testen:
telnet smtp.ihrmailserver.com 587

# Lösung: Mail-Konfiguration in config.php überprüfen
```

#### **Problem: 500 Internal Server Error**
```bash
# Error-Log prüfen:
tail -f /var/log/apache2/error.log
# oder
tail -f public/error.log

# PHP-Fehler aktivieren für Debugging:
ini_set('display_errors', '1');
```

#### **Problem: PHP-Module fehlen**
```bash
# Fehlende Module installieren:
sudo apt install php8.3-MODULNAME
# oder
sudo dnf install php-MODULNAME

# PHP neu starten:
sudo systemctl restart php8.3-fpm
```

### **Log-Dateien Überprüfen:**

```bash
# SecStore-Logs (im Admin-Panel)
# System-Logs
tail -f /var/log/syslog

# Apache-Logs
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log

# Nginx-Logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

---

## 📞 Support

### **Dokumentation:**
- 📖 [README.md](../README.md) - Projekt-Übersicht
- 📝 [CHANGELOG.md](CHANGELOG.md) - Versionshistorie und Updates
- 🔒 [SECURITY.md](SECURITY.md) - Sicherheitsrichtlinien (falls vorhanden)

### **Community & Hilfe:**
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/madcoda9000/SecStore/issues)
- 💡 **Feature Requests:** [GitHub Discussions](https://github.com/madcoda9000/SecStore/discussions)

### **Nützliche Links:**
- 🔗 [Flight PHP Dokumentation](https://docs.flightphp.com/)
- 🔗 [Latte Template Engine](https://latte.nette.org/en/)
- 🔗 [Composer Dokumentation](https://getcomposer.org/doc/)

---

## 🎉 Erfolgreich installiert!

Herzlichen Glückwunsch! SecStore ist jetzt einsatzbereit. 

### **Nächste Schritte:**

1. 🔐 **Admin-Passwort ändern** (sofort!)
2. 📧 **E-Mail-Einstellungen konfigurieren**
3. 👥 **Erste Benutzer anlegen**
4. 🛡️ **2FA für Admin-Accounts aktivieren**
5. 📊 **System-Monitoring einrichten**

---

> **💡 Tipp:** Bookmarken Sie diese Anleitung für zukünftige Updates und Wartungsarbeiten!

**SecStore Team** ❤️ *Danke, dass Sie SecStore verwenden!*
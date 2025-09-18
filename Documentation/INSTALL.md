# 🔐 SecStore - Complete Installation Guide

![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-blue?logo=php)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-orange)
![License](https://img.shields.io/github/license/madcoda9000/SecStore?color=green)

**SecStore** is a modern, secure user management and authentication platform. This guide walks you through the complete installation and configuration.

---

## 📋 Table of Contents

- [🔧 System Requirements](#-system-requirements)
- [🚀 Quick Start (Automatic Installation)](#-quick-start-automatic-installation)
- [⚙️ Manual Installation](#️-manual-installation)
- [🗄️ Database Setup](#️-database-setup)
- [📁 Configuration](#-configuration)
- [🌐 Webserver Configuration](#-webserver-configuration)
- [✅ Verify Installation](#-verify-installation)
- [🔒 Post-Installation Security](#-post-installation-security)
- [🛠️ Development Environment](#️-development-environment)
- [🔧 Troubleshooting](#-troubleshooting)
- [📞 Support](#-support)

---

## 🔧 System Requirements

### **Minimum Requirements:**

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | ≥ 8.3 | CLI version required |
| **Database** | MySQL ≥ 8.0 or MariaDB ≥ 10.4 | UTF8MB4 support |
| **Webserver** | Apache 2.4+ or Nginx 1.18+ | Optional for production |
| **Composer** | Latest | Dependency management |
| **Memory** | ≥ 512 MB RAM | For PHP processes |
| **Disk** | ≥ 100 MB | For application + dependencies |

### **Required PHP Extensions:**
```
✓ curl        ✓ json       ✓ openssl    ✓ pdo
✓ pdo_mysql   ✓ xml        ✓ zip        ✓ bcmath
✓ gd          ✓ mbstring   ✓ soap       ✓ intl
✓ ldap        ✓ imagick    ✓ redis      ✓ xdebug
```

### **Supported Operating Systems:**
- ✅ **Ubuntu** 20.04+ / Debian 11+
- ✅ **Fedora** 35+ / CentOS Stream 9+
- ✅ **Rocky Linux** 9+ / AlmaLinux 9+
- ✅ **macOS** 12+ (with Homebrew)
- ✅ **Windows** 10+ (WSL2 recommended)

---

## 🚀 Quick Start (Automatic Installation)

The easiest method is our automatic setup script:

### **1. Download and run script:**

```bash
# Download script (if not already present)
curl -O https://your-domain.com/secstore_setup.sh

# Make executable
chmod +x secstore_setup.sh

# Start installation
./secstore_setup.sh
```

### **2. What the script does automatically:**

- 🔍 **System detection**: Automatic detection of Debian/Ubuntu/Fedora
- 📦 **PHP installation**: Latest LTS version (8.3+) with all modules
- 🎼 **Composer**: Global installation and configuration
- 🛠️ **Dev tools**: PHP CodeSniffer and PHP-CS-Fixer
- 📂 **Project setup**: Install dependencies with `composer install`

### **3. After automatic installation:**

Jump to [🗄️ Database Setup](#️-database-setup) and [📁 Configuration](#-configuration).

---

## ⚙️ Manual Installation

### **Step 1: Install PHP 8.3+**

#### **Ubuntu/Debian:**
```bash
# Add PPA for latest PHP versions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and required extensions
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql \
    php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip \
    php8.3-bcmath php8.3-gd php8.3-intl php8.3-soap \
    php8.3-redis php8.3-imagick php8.3-ldap

# Install Apache/Nginx (choose one)
sudo apt install apache2 libapache2-mod-php8.3
# OR
sudo apt install nginx php8.3-fpm
```

#### **Fedora/CentOS/Rocky Linux:**
```bash
# Install Remi repository
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/fedora/remi-release-$(rpm -E %fedora).rpm

# Install PHP and extensions
sudo dnf module reset php
sudo dnf module enable php:remi-8.3 -y
sudo dnf install -y php php-cli php-fpm php-mysql php-xml \
    php-curl php-mbstring php-zip php-bcmath php-gd \
    php-intl php-soap php-redis php-imagick php-ldap

# Install web server
sudo dnf install -y httpd
# OR
sudo dnf install -y nginx
```

#### **macOS (with Homebrew):**
```bash
# Install PHP via Homebrew
brew install php@8.3
brew install composer

# Link PHP version
brew unlink php && brew link php@8.3 --force

# Install additional extensions
brew install php-redis php-imagick
```

### **Step 2: Install Composer**

```bash
# Download and install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

### **Step 3: Install MariaDB/MySQL**

#### **Ubuntu/Debian:**
```bash
# Install MariaDB
sudo apt install -y mariadb-server mariadb-client

# Secure installation
sudo mysql_secure_installation
```

#### **Fedora/CentOS/Rocky Linux:**
```bash
# Install MariaDB
sudo dnf install -y mariadb-server mariadb

# Start and enable service
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Secure installation
sudo mysql_secure_installation
```

### **Step 4: Download SecStore**

```bash
# Download via Git
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# OR download ZIP
wget https://github.com/madcoda9000/SecStore/archive/main.zip
unzip main.zip && cd SecStore-main
```

### **Step 5: Install Dependencies**

```bash
# Install project dependencies
composer install --no-dev --optimize-autoloader

# For development (with dev tools)
composer install
```

---

## 🗄️ Database Setup

### **Step 1: Create Database and User**

```sql
-- Connect to MySQL/MariaDB as root
mysql -u root -p

-- Create database
CREATE DATABASE secstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user and grant permissions
CREATE USER 'secstore_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON secstore.* TO 'secstore_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
exit;
```

### **Step 2: Import Database Schema**

```bash
# Import the schema
mysql -u secstore_user -p secstore < database/schema.sql

# Verify tables were created
mysql -u secstore_user -p secstore -e "SHOW TABLES;"
```

---

## 📁 Configuration

### **Step 1: Create Configuration File**

```bash
# Copy template
cp config.php_TEMPLATE config.php

# Make writable for web server
chmod 664 config.php
chown www-data:www-data config.php  # Ubuntu/Debian
# OR
chown apache:apache config.php      # RHEL/CentOS/Fedora
```

### **Step 2: Configure Database Connection**

Edit `config.php` and update database settings:

```php
// Database Configuration
$config = [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'secstore',
        'user' => 'secstore_user',
        'pass' => 'secure_password_here',
        'charset' => 'utf8mb4'
    ],
    
    // Application Settings
    'app' => [
        'url' => 'https://yourdomain.com',
        'session_timeout' => 3600,  // 1 hour
        'timezone' => 'Europe/Berlin'
    ],
    
    // Email Configuration (optional)
    'email' => [
        'enabled' => true,
        'smtp_host' => 'smtp.yourmailserver.com',
        'smtp_port' => 587,
        'smtp_username' => 'your@email.com',
        'smtp_password' => 'your_email_password',
        'from_email' => 'noreply@yourdomain.com',
        'from_name' => 'SecStore System'
    ]
];
```

### **Step 3: Generate Encryption Key**

```bash
# Generate secure encryption key
php generate_key.php

# The key is automatically written to config.php
```

### **Step 4: Set Directory Permissions**

```bash
# Cache directory
mkdir -p cache
chmod 755 cache
chown www-data:www-data cache  # Ubuntu/Debian
# OR
chown apache:apache cache      # RHEL/CentOS/Fedora

# Config file (readable by web server)
chmod 664 config.php
```

---

## 🌐 Webserver Configuration

### **Apache Configuration**

#### **Virtual Host Example:**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/secstore/public
    
    # Redirect to HTTPS
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/secstore/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/your.crt
    SSLCertificateKeyFile /path/to/your.key
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # PHP Configuration
    <Directory /var/www/secstore/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        
        # Enable mod_rewrite
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Protect sensitive directories
    <Directory /var/www/secstore/app>
        Require all denied
    </Directory>
    
    <Directory /var/www/secstore/cache>
        Require all denied
    </Directory>
    
    # Error and Access Logs
    ErrorLog ${APACHE_LOG_DIR}/secstore_error.log
    CustomLog ${APACHE_LOG_DIR}/secstore_access.log combined
</VirtualHost>
```

#### **Enable Apache Modules:**
```bash
# Enable required modules
sudo a2enmod rewrite ssl headers

# Restart Apache
sudo systemctl restart apache2
```

### **Nginx Configuration**

#### **Server Block Example:**
```nginx
# HTTP (redirect to HTTPS)
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/secstore/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /path/to/your.crt;
    ssl_certificate_key /path/to/your.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Pretty URLs
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Protect sensitive directories
    location ~ /(app|cache|config\.php) {
        deny all;
        return 404;
    }
    
    # Logs
    access_log /var/log/nginx/secstore_access.log;
    error_log /var/log/nginx/secstore_error.log;
}
```

#### **Test and Restart Nginx:**
```bash
# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## ✅ Verify Installation

### **1. First Login:**

- **URL:** `http://your-domain.com` (or `http://localhost:8000`)
- **Username:** `super.admin`
- **Password:** `Test1000!`

> **⚠️ Important:** Change the default password immediately after first login!

### **2. Check System Status:**

```bash
# Check PHP version
php --version

# Check required modules
php -m | grep -E "(curl|mysql|zip|xml|mbstring|bcmath|gd|intl|soap|redis|imagick|ldap)"

# Check Composer version
composer --version

# Check project dependencies
composer show
```

### **3. Function Tests:**

- ✅ Login with default account
- ✅ User dashboard accessible
- ✅ Admin area accessible
- ✅ Email sending (test in settings)
- ✅ Registration (if enabled)
- ✅ Password reset (if enabled)

---

## 🔒 Post-Installation Security

### **1. Immediately after installation:**

```bash
# Change default admin password
# -> In web interface: Profile -> Change Password

# Secure config file (but keep writable for webserver)
chmod 664 config.php
chown www-data:www-data config.php  # or appropriate webserver user

# Additional security: prevent access to config outside public/
# (Automatically handled by .htaccess/webserver configuration)
```

### **2. Production environment:**

```bash
# Disable debug mode
# In config.php or index.php:
ini_set('display_errors', '0');

# Force HTTPS (in webserver configuration)
# Apache: Redirect 301 / https://your-domain.com/
# Nginx: return 301 https://$server_name$request_uri;

# Configure firewall
sudo ufw enable
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
```

### **3. Regular maintenance:**

```bash
# Check for updates
composer update --no-dev

# Rotate logs (e.g., with logrotate)
sudo logrotate -f /etc/logrotate.d/secstore

# Implement backup strategy
mysqldump -u root -p secstore > backup_$(date +%Y%m%d).sql
```

---

## 🛠️ Development Environment

### **Code Quality Tools:**

```bash
# Activate Git pre-commit hook (prevents sensitive file commits)
cp preCommitHook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Test hook
echo "test" > config-test.php && git add config-test.php
git commit -m "Security test"  # Should be blocked
rm config-test.php  # Remove test file

**🔒 Security Notice:** The pre-commit hook automatically blocks commits of:
- `config*.php` (except templates)
- `.env*` files (except examples)  
- `*.key`, `*.credentials`, `*copy*`, `*backup*` files

**💡 Allowed Template Files:** `config.php_TEMPLATE`, `config.php.example`, `.env.example`

# Check PHP syntax
vendor/bin/phpcs app/

# Auto-format code
vendor/bin/php-cs-fixer fix

# Development server with debugging
php -S localhost:8000 -t public -d xdebug.mode=debug
```

### **Useful Developer Commands:**

```bash
# Dependencies with dev packages
composer install

# Update autoloader
composer dump-autoload

# Generate new secret key
php generate_key.php

# Check database schema
mysql -u secstore_user -p secstore -e "SHOW TABLES;"
```

---

## 🔧 Troubleshooting

### **Common Problems and Solutions:**

#### **Problem: "Cache directory is not writable"**
```bash
# Solution:
mkdir -p cache
chmod 755 cache
chown www-data:www-data cache
```

#### **Problem: "Database connection failed"**
```bash
# Test connection:
mysql -u secstore_user -p secstore

# Solution: Check config.php database settings
```

#### **Problem: "Config file not found"**
```bash
# Solution:
cp config.example.php config.php
# Then adjust config.php accordingly
```

#### **Problem: Email sending doesn't work**
```bash
# Test SMTP settings:
telnet smtp.yourmailserver.com 587

# Solution: Check mail configuration in config.php
```

#### **Problem: 500 Internal Server Error**
```bash
# Check error log:
tail -f /var/log/apache2/error.log
# or
tail -f public/error.log

# Enable PHP errors for debugging:
ini_set('display_errors', '1');
```

#### **Problem: Missing PHP modules**
```bash
# Install missing modules:
sudo apt install php8.3-MODULENAME
# or
sudo dnf install php-MODULENAME

# Restart PHP:
sudo systemctl restart php8.3-fpm
```

### **Check Log Files:**

```bash
# SecStore logs (in admin panel)
# System logs
tail -f /var/log/syslog

# Apache logs
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

---

## 📞 Support

### **Documentation:**
- 📖 [README.md](../README.md) - Project overview
- 📝 [CHANGELOG.md](CHANGELOG.md) - Version history and updates
- 🔒 [SECURITY.md](SECURITY.md) - Security policies (if available)

### **Community & Help:**
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/madcoda9000/SecStore/issues)
- 💡 **Feature Requests:** [GitHub Discussions](https://github.com/madcoda9000/SecStore/discussions)

### **Useful Links:**
- 🔗 [Flight PHP Documentation](https://docs.flightphp.com/)
- 🔗 [Latte Template Engine](https://latte.nette.org/en/)
- 🔗 [Composer Documentation](https://getcomposer.org/doc/)

---

## 🎉 Successfully Installed!

Congratulations! SecStore is now ready to use. 

### **Next Steps:**

1. 🔐 **Change admin password** (immediately!)
2. 📧 **Configure email settings**
3. 👥 **Create first users**
4. 🛡️ **Enable 2FA for admin accounts**
5. 📊 **Set up system monitoring**

---

> **💡 Tip:** Bookmark this guide for future updates and maintenance tasks!

**SecStore Team** ❤️ *Thank you for using SecStore!*
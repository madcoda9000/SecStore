# 🔐 SecStore - Complete Installation Guide

![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-blue?logo=php)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-orange)
![License](https://img.shields.io/github/license/madcoda9000/SecStore?color=green)

**SecStore** is a modern, secure user management and authentication platform. This guide walks you through the complete installation and configuration.

---

## 📋 Table of Contents

- [🔧 System Requirements](#-system-requirements)
- [🚀 Quick Start (Automatic Installation)](#-quick-start-automatic-installation)
- [🎯 Web-Based Setup (Recommended)](#-web-based-setup-recommended)
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

Continue with [🎯 Web-Based Setup](#-web-based-setup-recommended) for the easiest configuration experience.

---

## 🎯 Web-Based Setup (Recommended)

**✨ NEW:** SecStore now features an intuitive web-based setup wizard that guides you through the entire configuration process!

### **🚀 Getting Started**

1. **Complete the Quick Start** or Manual Installation above
2. **Start a web server:**
   ```bash
   # For development
   php -S localhost:8000 -t public
   
   # OR configure Apache/Nginx (see Webserver Configuration)
   ```
3. **Open your browser** and navigate to your SecStore installation
4. **Follow the setup wizard** - it will automatically detect missing configuration and guide you through each step

### **📸 Setup Process Screenshots**

#### **Step 1: Configuration File Check**
![Setup Step 1](Documentation/Screenshots/setup-step-1-config.png)
*The setup wizard checks if `config.php` exists and has proper permissions*

#### **Step 2: File Permissions**
![Setup Step 2](Documentation/Screenshots/setup-step-2-permissions.png)
*Verification that all files have correct write permissions for the web server*

#### **Step 3: Database Configuration**
![Setup Step 3](Documentation/Screenshots/setup-step-3-database.png)
*Interactive database configuration with connection testing*

#### **Step 4: Email Configuration (Optional)**
![Setup Step 4](Documentation/Screenshots/setup-step-4-email.png)
*SMTP configuration for email features - can be skipped and configured later*

#### **Setup Complete**
![Setup Complete](Documentation/Screenshots/setup-complete.png)
*Final screen with login credentials and next steps*

### **🔧 Setup Wizard Features**

| Feature | Description |
|---------|-------------|
| **🎯 Visual Progress** | Clear step-by-step indicator showing current progress |
| **✅ Automatic Validation** | Real-time validation of database connections and SMTP settings |
| **⚡ Smart Skip Options** | Skip optional steps (like email) and configure them later |
| **🔄 Error Recovery** | Helpful error messages with specific instructions to fix issues |
| **🌍 Multi-Language** | Available in German and English |
| **📱 Responsive Design** | Works perfectly on desktop, tablet, and mobile devices |

### **🛠️ What the Setup Wizard Configures**

1. **📁 Configuration File**: Creates and validates `config.php` from template
2. **🗄️ Database Connection**: Tests and saves database credentials
3. **🏗️ Database Schema**: Automatically creates all required tables and indexes
4. **👤 Admin User**: Creates default administrator account with secure credentials
5. **📧 Email Settings**: Configures SMTP for email features (optional)
6. **🔐 Security Settings**: Applies secure defaults for all security features

### **⚙️ Before Starting the Web Setup**

**Required:** Complete one of these preparation steps:

#### **Option A: Copy Configuration Template**
```bash
cd /path/to/secstore
cp config.php_TEMPLATE config.php
chmod 664 config.php
chown www-data:www-data config.php  # Ubuntu/Debian
# OR
chown apache:apache config.php      # RHEL/CentOS/Fedora
```

#### **Option B: Let the Setup Guide You**
If you haven't copied the configuration file, the setup wizard will:
- Detect the missing `config.php`
- Show you exactly which commands to run
- Wait for you to complete the step
- Continue automatically once the file is ready

### **🎯 Setup Wizard URLs**

| URL Pattern | Purpose |
|-------------|---------|
| `http://your-domain.com/` | Main entry point - automatically redirects to setup if needed |
| `http://your-domain.com/setup` | Direct access to setup wizard |
| `http://localhost:8000/` | Local development server |

> **💡 Tip:** The setup wizard is automatically activated when SecStore detects missing or incomplete configuration. Once setup is complete, the wizard is automatically disabled for security.

---

## ⚙️ Manual Installation

For advanced users who prefer manual configuration or custom environments:

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

### **📝 For Web-Based Setup Users:**
> **Skip this section** - the setup wizard handles database creation automatically!

### **⚙️ For Manual Installation:**

#### **Step 1: Create Database and User**

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

#### **Step 2: Import Database Schema**

```bash
# Import the database structure
mysql -u secstore_user -p secstore < database/schema.sql

# Import default data (admin user and roles)
mysql -u secstore_user -p secstore < database/default_data.sql

# Verify tables were created
mysql -u secstore_user -p secstore -e "SHOW TABLES;"

# Verify admin user was created
mysql -u secstore_user -p secstore -e "SELECT username, email, roles FROM users;"
```

> **📝 Note:** The database files are included in the SecStore repository:
> - `database/schema.sql` - Complete database structure
> - `database/default_data.sql` - Default admin user and roles

---

## 📁 Configuration

### **🎯 For Web-Based Setup Users:**
> **Skip this section** - the setup wizard creates the configuration automatically!

### **⚙️ For Manual Installation:**

#### **Step 1: Create Configuration File**

```bash
# Copy template
cp config.php_TEMPLATE config.php

# Make writable for web server
chmod 664 config.php
chown www-data:www-data config.php  # Ubuntu/Debian
# OR
chown apache:apache config.php      # RHEL/CentOS/Fedora
```

#### **Step 2: Configure Database Connection**

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

#### **Step 3: Generate Encryption Key**

```bash
# Generate secure encryption key
php generate_key.php

# The key is automatically written to config.php
```

#### **Step 4: Set Directory Permissions**

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

### **🎯 Web-Based Setup:**
If you used the web-based setup, verification is automatic! The setup wizard:
- ✅ Tests database connection during configuration
- ✅ Verifies email settings (if configured)
- ✅ Creates the default admin account
- ✅ Provides login credentials on completion

### **⚙️ Manual Installation:**

#### **1. First Login:**

- **URL:** `http://your-domain.com` (or `http://localhost:8000`)
- **Username:** `super.admin`
- **Password:** `Test1000!`

> **⚠️ Important:** Change the default password immediately after first login!

#### **2. Check System Status:**

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

#### **3. Function Tests:**

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
```

**🔒 Security Notice:** The pre-commit hook automatically blocks commits of:
- `config*.php` (except templates)
- `.env*` files (except examples)  
- `*.key`, `*.credentials`, `*copy*`, `*backup*` files

**💡 Allowed Template Files:** `config.php_TEMPLATE`, `config.php.example`, `.env.example`

```bash
# Check PHP syntax
vendor/bin/phpcs app/

# Auto-format code
vendor/bin/php-cs-fixer fix

# Development server with debugging
php -S localhost:8000 -t public -d xdebug.mode=debug
```

### **Useful Developer Commands:**

```bash
# Clear Latte template cache
rm -rf cache/*.php

# Regenerate composer autoloader
composer dump-autoload

# Export current database schema (for developers)
php generate_schema.php

# Check database connection
php -r "
$config = include 'config.php';
try {
    $pdo = new PDO('mysql:host='.\$config['db']['host'].';dbname='.\$config['db']['name'], 
                   \$config['db']['user'], \$config['db']['pass']);
    echo 'Database connection: OK\n';
} catch(Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

### **🛠️ Schema Management Tools:**

SecStore includes helpful tools for database schema management:

```bash
# Export current schema from database
php generate_schema.php

# This creates:
# - database/schema.sql (table structures)
# - database/default_data.sql (default admin & roles)
```

**When to use:**
- After database structure changes
- Before major updates
- For backup purposes
- When contributing to SecStore development

---

## 🔧 Troubleshooting

### **🎯 Web-Based Setup Issues**

#### **Setup wizard not loading:**
```bash
# Check file permissions
ls -la config.php*
chmod 664 config.php_TEMPLATE config.php

# Check web server logs
sudo tail -f /var/log/apache2/error.log
# OR
sudo tail -f /var/log/nginx/error.log
```

#### **Database connection fails in setup:**
- ✅ Verify database server is running: `sudo systemctl status mysql`
- ✅ Check database credentials are correct
- ✅ Ensure database exists: `SHOW DATABASES;`
- ✅ Verify user permissions: `SHOW GRANTS FOR 'username'@'localhost';`

#### **Email setup fails:**
- ✅ Test SMTP connection manually: `telnet smtp.server.com 587`
- ✅ Check firewall allows outbound SMTP: `sudo ufw status`
- ✅ Verify authentication credentials with email provider
- ✅ **Remember:** Email setup can be skipped and configured later!

### **⚙️ General Issues**

#### **Permission Errors:**
```bash
# Fix file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 664 config.php
chmod 755 cache/

# Fix ownership
sudo chown -R www-data:www-data .  # Ubuntu/Debian
sudo chown -R apache:apache .      # RHEL/CentOS
```

#### **Composer Issues:**
```bash
# Clear composer cache
composer clear-cache

# Update composer itself
composer self-update

# Reinstall dependencies
rm -rf vendor/ composer.lock
composer install
```

#### **Database Issues:**
```bash
# Check MySQL/MariaDB status
sudo systemctl status mysql

# Restart database service
sudo systemctl restart mysql

# Check error logs
sudo tail -f /var/log/mysql/error.log
```

#### **PHP Issues:**
```bash
# Check PHP configuration
php --ini
php -m  # List loaded modules

# Check PHP error logs
tail -f /var/log/php_errors.log

# Test PHP syntax
php -l index.php
```

### **🚨 Common Error Messages**

| Error | Solution |
|-------|----------|
| `config.php not found` | Copy `config.php_TEMPLATE` to `config.php` |
| `Permission denied` | Fix file permissions with `chmod 664 config.php` |
| `Database connection failed` | Check database credentials and server status |
| `SMTP connection failed` | Verify SMTP settings or skip email setup |
| `Cache directory not writable` | Set permissions: `chmod 755 cache/` |
| `Class not found` | Run `composer install` to install dependencies |

---

## 📞 Support

### **📖 Documentation**
- **[📖 Installation Guide](Documentation/INSTALL.md)** - This document
- **[📝 Changelog](Documentation/CHANGELOG.md)** - Version history
- **[🔒 Security](Documentation/SECURITY.md)** - Security policies

### **💬 Community Support**
- **[🐛 Bug Reports](https://github.com/madcoda9000/SecStore/issues)** - Report issues
- **[💡 Feature Requests](https://github.com/madcoda9000/SecStore/discussions)** - Suggest improvements
- **[❓ Q&A](https://github.com/madcoda9000/SecStore/discussions/categories/q-a)** - Get help from community

### **🔍 Before Asking for Help**

1. **✅ Check this troubleshooting section**
2. **✅ Search existing issues:** [GitHub Issues](https://github.com/madcoda9000/SecStore/issues)
3. **✅ Check the logs:** `tail -f /var/log/apache2/error.log`
4. **✅ Verify system requirements** are met

### **📝 When Reporting Issues**

Include this information:
- **Operating System:** (Ubuntu 22.04, etc.)
- **PHP Version:** `php --version`
- **Database:** MySQL/MariaDB version
- **Webserver:** Apache/Nginx version
- **Error Messages:** Full error text
- **Steps to Reproduce:** What you did before the error occurred

---

## 🎉 Conclusion

**Congratulations!** You've successfully installed SecStore. The platform is now ready to provide secure user management for your applications.

### **🚀 Next Steps:**

1. **🔐 Change the default admin password** (very important!)
2. **⚙️ Configure additional settings** in the admin panel
3. **👥 Create user accounts** or enable registration
4. **📧 Set up email notifications** (if skipped during setup)
5. **🔒 Review security settings** and enable 2FA
6. **📊 Explore the admin dashboard** and logging features

### **🛡️ Security Reminders:**

- ✅ **Use HTTPS** in production environments
- ✅ **Regular backups** of database and configuration
- ✅ **Keep SecStore updated** with latest releases
- ✅ **Monitor logs** for suspicious activity
- ✅ **Enable 2FA** for all admin accounts

**Welcome to SecStore!** 🎊
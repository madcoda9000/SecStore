# 🐳 SecStore - Docker Installation Guide

![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)

**The easiest way to run SecStore** - A complete containerized setup with automated configuration and persistent storage.

---

## 📋 Table of Contents

- [🎯 Quick Start](#-quick-start)
- [📦 What's Included](#-whats-included)
- [⚙️ Prerequisites](#️-prerequisites)
- [🚀 Installation](#-installation)
- [🔧 Configuration](#-configuration)
- [🌐 Accessing Services](#-accessing-services)
- [📁 Project Structure](#-project-structure)
- [🔄 Managing Containers](#-managing-containers)
- [🔄 Manageing Containers with make file](#-managing-containers-with-makefile-optional)
- [💾 Data Persistence](#-data-persistence)
- [🛠️ Troubleshooting](#️-troubleshooting)
- [🔒 Security Best Practices](#-security-best-practices)
- [📚 Advanced Usage](#-advanced-usage)

---

## 🎯 Quick Start

**Get SecStore running in 3 commands:**

```bash
# 1. Copy environment template
cp .env.example .env

# 2. Start containers
docker-compose up -d

# 3. Open browser
open http://localhost:8000
```

The Setup Wizard will guide you through the initial configuration! 🎉

---

## 📦 What's Included

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| **SecStore** | `secstore_app` | 8000 | Main application (PHP 8.3 + Apache) |
| **MySQL** | `secstore_db` | 3306 | Database server (MySQL 8.0) |
| **phpMyAdmin** | `secstore_phpmyadmin` | 8080 | Database management UI |

### 🔧 Installed Components

- ✅ **PHP 8.3** with all required extensions:
  - Core: `pdo`, `pdo_mysql`, `curl`, `json`, `xml`, `zip`
  - Image: `gd`, `imagick`
  - Localization: `mbstring`, `intl`
  - Security: `openssl`, `bcmath`
  - Services: `soap`, `ldap`, `redis`
- ✅ **Apache 2.4** with mod_rewrite enabled
- ✅ **Composer** for dependency management
- ✅ **MySQL 8.0** with persistent storage

---

## ⚙️ Prerequisites

### **Required Software:**

| Software | Minimum Version | Check Command |
|----------|----------------|---------------|
| **Docker** | 20.10+ | `docker --version` |
| **Docker Compose** | 1.29+ | `docker-compose --version` |

### **System Requirements:**

- **OS**: Linux, macOS, Windows 10/11 (with WSL2)
- **RAM**: 2 GB minimum, 4 GB recommended
- **Disk**: 1 GB free space
- **Ports**: 8000, 8080, 3306 must be available

### **Installing Docker:**

<details>
<summary><b>Ubuntu/Debian</b></summary>

```bash
# Update package index
sudo apt update

# Install Docker
sudo apt install -y docker.io docker-compose

# Add user to docker group
sudo usermod -aG docker $USER

# Restart session
newgrp docker
```
</details>

<details>
<summary><b>Fedora/RHEL/CentOS</b></summary>

```bash
# Install Docker
sudo dnf install -y docker docker-compose

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group
sudo usermod -aG docker $USER
```
</details>

<details>
<summary><b>macOS</b></summary>

```bash
# Install Docker Desktop
brew install --cask docker

# Start Docker Desktop from Applications
# Or run: open /Applications/Docker.app
```
</details>

<details>
<summary><b>Windows</b></summary>

1. Install **WSL2**: [Microsoft Guide](https://docs.microsoft.com/en-us/windows/wsl/install)
2. Install **Docker Desktop**: [Download](https://www.docker.com/products/docker-desktop)
3. Enable WSL2 integration in Docker Desktop settings
</details>

---

## 🚀 Installation

### **Step 1: Clone Repository**

```bash
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore
```

### **Step 2: Configure Environment**

```bash
# Copy environment template
cp .env.example .env

# Edit environment file
nano .env  # or your favorite editor
```

**Important: Configure data storage location in `.env`:**

```env
# Where to store persistent data (config, cache, logs)
DATA_PATH=./docker-data

# Change passwords
MYSQL_ROOT_PASSWORD=YourSecureRootPassword123!
MYSQL_PASSWORD=YourSecureAppPassword123!
```

**DATA_PATH Examples:**

| Environment | Recommended Path | Example |
|-------------|------------------|---------|
| **Local Development** | `./docker-data` | Current directory |
| **Proxmox LXC** | `/mnt/appdata/secstore` | Dedicated storage |
| **Synology NAS** | `/volume1/docker/secstore` | Docker volume |
| **Unraid** | `/mnt/user/appdata/secstore` | App data |
| **Generic Linux** | `/opt/secstore-data` | System location |

> **💡 Tip:** Choose a path on a volume with sufficient space and regular backups!

### **Step 3: Build and Start Containers**

```bash
# Build images and start containers
docker-compose up -d

# Watch startup logs (optional)
docker-compose logs -f
```

**Expected output:**
```
✓ Database connection established
✓ config.php created from template
✓ Composer dependencies installed
✓ Cache directory configured
✓ SecStore is ready!
```

### **Step 4: Complete Setup**

**Option A: Web Setup Wizard (Recommended)**

1. Open browser: `http://localhost:8000`
2. Setup wizard starts automatically
3. Follow the 4-step configuration:

   **Step 1:** Verify `config.php` was created ✓  
   **Step 2:** Check file permissions ✓  
   **Step 3:** Configure database:
   ```
   Host:     db
   Database: secstore
   User:     secstore
   Password: <from .env file>
   ```
   **Step 4:** Configure email (optional - can skip)

4. Login with default credentials:
   - **Username**: `super.admin`
   - **Password**: `Test1000!`
   - ⚠️ **Change password immediately after first login!**

**Option B: Manual Configuration**

```bash
# Edit config.php directly
nano config.php

# Update database settings
$db = [
    'host' => 'db',
    'name' => 'secstore',
    'user' => 'secstore',
    'pass' => 'YourSecureAppPassword123!',
];

# Restart container
docker-compose restart app
```

---

## 🔧 Configuration

### **Environment Variables (.env)**

| Variable | Default | Description |
|----------|---------|-------------|
| `DATA_PATH` | `./docker-data` | Storage location for persistent data |
| `MYSQL_ROOT_PASSWORD` | `SecureRootPass123!` | MySQL root password |
| `MYSQL_PASSWORD` | `SecureDBPass123!` | Application database password |

**DATA_PATH Configuration:**

This variable determines where SecStore stores all persistent data:
- `config.php` - Application configuration
- `cache/` - Latte template cache
- `logs/` - Application and error logs

Choose a location that:
- ✅ Has sufficient disk space (minimum 500 MB recommended)
- ✅ Is regularly backed up
- ✅ Has good I/O performance for database operations
- ✅ Is accessible by the Docker user

### **Database Connection**

When using the Setup Wizard, enter these values:

| Field | Value |
|-------|-------|
| **Host** | `db` (Docker container name) |
| **Database** | `secstore` |
| **User** | `secstore` |
| **Password** | Value from `.env` → `MYSQL_PASSWORD` |

### **Persistent Configuration**

All configuration changes are stored in volumes and persist across container restarts:

- ✅ `config.php` - Application configuration
- ✅ `cache/` - Template cache
- ✅ `logs/` - Application logs
- ✅ MySQL data - Database content

---

## 🔧 Configuration

### **Environment Variables (.env)**

| Variable | Default | Description |
|----------|---------|-------------|
| `DATA_PATH` | `./docker-data` | Storage location for persistent data |
| `MYSQL_ROOT_PASSWORD` | `SecureRootPass123!` | MySQL root password |
| `MYSQL_PASSWORD` | `SecureDBPass123!` | Application database password |

### **DATA_PATH Configuration**

**Why is DATA_PATH configurable?**

Different hosting environments have different storage requirements:

| Environment | Challenge | Solution with DATA_PATH |
|-------------|-----------|------------------------|
| **Proxmox LXC** | Data should persist on Proxmox storage, not container | Set to `/mnt/appdata/secstore` |
| **Synology NAS** | Store on specific volume with RAID/backups | Set to `/volume1/docker/secstore` |
| **Unraid** | Keep app data on array, not cache | Set to `/mnt/user/appdata/secstore` |
| **Cloud VPS** | Mount external block storage | Set to mounted volume path |
| **Development** | Keep data in project folder | Use default `./docker-data` |

**What gets stored in DATA_PATH:**
- `config.php` - Application configuration (passwords, SMTP, LDAP settings)
- `cache/` - Latte template cache (can be regenerated)
- `logs/` - Application and PHP error logs (important for debugging)

**Storage Requirements:**
- ✅ **Minimum:** 100 MB (just the application)
- ✅ **Recommended:** 500 MB - 1 GB (with logs and growth)
- ✅ **Permissions:** Docker user must have read/write access
- ✅ **Backup:** Should be part of your backup strategy
- ✅ **Performance:** SSD recommended for cache performance

**Setting DATA_PATH:**

```bash
# Edit .env file
nano .env

# Set your path
DATA_PATH=/your/preferred/location

# Ensure directory exists and has correct permissions
mkdir -p /your/preferred/location
chmod 755 /your/preferred/location

# Start containers
docker-compose up -d
```

**💡 Pro Tip:** Choose a path that:
1. Is on a volume with regular backups
2. Has good I/O performance (SSD if possible)
3. Is easy to remember and access
4. Won't fill up (has enough free space)

---

## 🌐 Accessing Services

### **Main Application**

```
URL:      http://localhost:8000
Username: super.admin
Password: Test1000!
```

### **phpMyAdmin**

```
URL:      http://localhost:8080
Server:   db
Username: root
Password: <MYSQL_ROOT_PASSWORD from .env>
```

### **Database Direct Access**

```bash
# From host machine
mysql -h 127.0.0.1 -P 3306 -u secstore -p
# Password: <MYSQL_PASSWORD from .env>

# From inside app container
docker-compose exec app bash
mysql -h db -u secstore -p
```

---

## 📁 Project Structure

```
SecStore/
├── docker-compose.yml      # Container orchestration
├── Dockerfile              # App container definition
├── docker-entrypoint.sh    # Startup script
├── .dockerignore          # Exclude from image
├── .env                   # Environment variables (create from .env.example)
├── .env.example           # Environment template
└── vendor/                # Composer dependencies (auto-installed)

Persistent Data Location (configured via DATA_PATH in .env):
${DATA_PATH}/              # Default: ./docker-data/
├── config.php             # Application configuration
├── cache/                 # Latte template cache
└── logs/                  # Application logs
    └── error.log          # PHP error log
```

---

## 🔄 Managing Containers

### **Common Commands**

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# View logs
docker-compose logs -f

# View logs for specific service
docker-compose logs -f app

# Check container status
docker-compose ps

# Execute commands in container
docker-compose exec app bash

# Update images
docker-compose pull
docker-compose up -d --build
```

### **Maintenance Commands**

```bash
# Clear cache
docker-compose exec app rm -rf /var/www/html/cache/*

# Run Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# Database backup
docker-compose exec db mysqldump -u secstore -p secstore > backup.sql

# Database restore
docker-compose exec -T db mysql -u secstore -p secstore < backup.sql

# View Apache error logs
docker-compose exec app tail -f /var/log/apache2/secstore_error.log

# View PHP error log (from public/index.php)
# Get DATA_PATH from .env
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)
tail -f ${DATA_PATH}/logs/error.log

# View application logs
ls -la ${DATA_PATH}/logs/
```

---

## 🎮 Managing Containers with Makefile (Optional)

**For developers and power users** - The included `Makefile` provides convenient shortcuts for common Docker operations.

### **Prerequisites**

```bash
# Check if make is installed
make --version

# Install if needed (Ubuntu/Debian)
sudo apt install make

# Install if needed (Fedora/RHEL)
sudo dnf install make

# macOS usually has make pre-installed
```

### **Quick Reference**

| Makefile Command | Equivalent Docker Command | Description |
|-----------------|---------------------------|-------------|
| `make help` | - | Show all available commands |
| `make install` | `cp .env.example .env && docker-compose up -d` | Complete initial setup |
| `make start` | `docker-compose up -d` | Start containers |
| `make stop` | `docker-compose down` | Stop containers |
| `make restart` | `docker-compose restart` | Restart containers |
| `make logs` | `docker-compose logs -f` | View all logs |
| `make status` | `docker-compose ps` | Show container status |
| `make shell` | `docker-compose exec app bash` | Open shell in app container |
| `make db` | `docker-compose exec db mysql -u secstore -p` | Open MySQL client |
| `make backup` | Multiple commands | Create database + config backup |
| `make clean` | `docker-compose down -v` | Remove everything (⚠️ destructive) |

### **Installation & Setup Commands**

```bash
# Complete first-time setup (recommended)
make install
# → Creates .env from template
# → Builds and starts containers
# → Shows connection information

# View all available commands
make help
```

### **Daily Operations**

```bash
# Start/Stop containers
make start
make stop
make restart

# View logs
make logs              # All container logs
make logs-app          # App container only
make logs-db           # Database container only
make logs-error        # PHP error.log file

# Check status
make status
```

### **Development & Debugging**

```bash
# Access containers
make shell             # Bash in app container
make db                # MySQL client

# Maintenance
make clear-cache       # Clear Latte template cache
make permissions       # Fix file permissions

# Composer operations
make composer CMD="install"
make composer CMD="update"
```

### **Backup & Restore**

```bash
# Create backup (database + config + logs)
make backup
# → Creates timestamped files in ./backups/

# Restore from latest backup
make restore
```

### **Information & Monitoring**

```bash
# Show connection details
make info
# → Displays URLs, credentials, database info

# Open services in browser
make phpmyadmin        # Opens phpMyAdmin
```

### **Advanced Operations**

```bash
# Update application
make update
# → Git pull
# → Rebuild containers
# → Install dependencies

# Run tests
make test

# Clean everything (⚠️ WARNING: Deletes all data!)
make clean
# → Asks for confirmation
# → Removes containers, volumes, config.php
```

### **Why Use Makefile?**

**Advantages:**
- ✅ **Shorter commands**: `make start` vs `docker-compose up -d`
- ✅ **Less error-prone**: No typos in long commands
- ✅ **Self-documenting**: `make help` shows all options
- ✅ **Consistent**: Same commands across teams
- ✅ **Time-saving**: Combines multiple steps

**When to use:**
- 👨‍💻 You're a developer working frequently with the project
- 🔧 You perform regular maintenance tasks
- 👥 Working in a team with standardized workflows
- ⚡ You value speed and convenience

**When NOT needed:**
- 👤 You're an end-user who just wants to run the app
- 🎯 You only start/stop containers occasionally
- 📚 You're more comfortable with docker-compose

### **Makefile vs. Docker Compose**

Both work perfectly fine - choose what you prefer:

```bash
# Traditional approach (always works)
docker-compose up -d
docker-compose logs -f
docker-compose exec app bash

# Makefile approach (shortcut)
make start
make logs
make shell
```

**Note:** All Makefile commands ultimately run docker-compose commands - it's just a convenience layer!

---

## 💾 Data Persistence

### **Persistent Volumes**

All important data persists across container restarts:

| Volume | Location | Purpose |
|--------|----------|---------|
| `${DATA_PATH}` | Configured in `.env` (default: `./docker-data`) | All persistent application data |
| `${DATA_PATH}/config.php` | | Application settings |
| `${DATA_PATH}/cache/` | | Latte template cache |
| `${DATA_PATH}/logs/` | | Application logs including error.log |
| `db_data` | Docker volume | MySQL database |

### **Backup Strategy**

**Application Configuration:**
```bash
# Get DATA_PATH from .env
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)

# Backup config
cp ${DATA_PATH}/config.php ${DATA_PATH}/config.php.backup

# Restore config
cp ${DATA_PATH}/config.php.backup ${DATA_PATH}/config.php
docker-compose restart app
```

**Database:**
```bash
# Create backup
docker-compose exec db mysqldump -u root -p secstore > secstore_backup_$(date +%Y%m%d).sql

# Restore from backup
docker-compose exec -T db mysql -u root -p secstore < secstore_backup_20250101.sql
```

**Complete Backup:**
```bash
# Stop containers
docker-compose down

# Get DATA_PATH from .env
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)

# Backup everything
tar -czf secstore_complete_backup.tar.gz ${DATA_PATH} .env

# Backup database volume
docker run --rm \
    -v secstore_db_data:/volume \
    -v $(pwd):/backup \
    alpine tar -czf /backup/db_data_backup.tar.gz -C /volume .

# Restart containers
docker-compose up -d
```

---

## 🛠️ Troubleshooting

### **Container Issues**

**Problem: Containers won't start**
```bash
# Check logs
docker-compose logs

# Check if ports are in use
sudo netstat -tulpn | grep -E '8000|8080|3306'

# Remove containers and retry
docker-compose down -v
docker-compose up -d
```

**Problem: Database connection failed**
```bash
# Check if database is ready
docker-compose exec db mysqladmin ping -h localhost -u root -p

# Check database logs
docker-compose logs db

# Verify credentials in .env match config.php
cat .env
cat config.php
```

**Problem: Permission errors**
```bash
# Fix cache permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/docker-data/cache
docker-compose exec app chmod -R 775 /var/www/html/docker-data/cache

# Fix config permissions on host
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)
sudo chown $USER:$USER ${DATA_PATH}/config.php
chmod 664 ${DATA_PATH}/config.php
```

### **Application Issues**

**Problem: Setup wizard not starting**
```bash
# Get DATA_PATH from .env
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)

# Check if config.php exists and is valid
cat ${DATA_PATH}/config.php

# Delete config to restart setup
rm ${DATA_PATH}/config.php
docker-compose restart app
```

**Problem: White screen / 500 error**
```bash
# Get DATA_PATH from .env
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)

# Check PHP error logs
tail -f ${DATA_PATH}/logs/error.log

# Check Apache error logs
docker-compose exec app tail -f /var/log/apache2/secstore_error.log

# Check all application logs
ls -la ${DATA_PATH}/logs/

# Clear cache
docker-compose exec app rm -rf /var/www/html/docker-data/cache/*
```

**Problem: Composer dependencies missing**
```bash
# Reinstall dependencies
docker-compose exec app composer install --no-dev --optimize-autoloader

# Clear composer cache
docker-compose exec app composer clear-cache
```

### **Performance Issues**

**Problem: Slow performance**
```bash
# Check container resources
docker stats

# Increase memory limit
# In docker-compose.yml under 'app' service:
# deploy:
#   resources:
#     limits:
#       memory: 512M

# Enable OPcache (already configured in Dockerfile)
docker-compose exec app php -i | grep opcache
```

### **Common Error Messages**

| Error | Solution |
|-------|----------|
| `Port 8000 already in use` | Change port in `docker-compose.yml` or stop conflicting service |
| `Permission denied: config.php` | Run: `chmod 664 docker-data/config.php` |
| `Database 'secstore' doesn't exist` | Run setup wizard or create manually via phpMyAdmin |
| `Class not found` | Run: `docker-compose exec app composer install` |
| `Cannot write to cache` | Run: `docker-compose exec app chmod -R 775 docker-data/cache` |

---

## 🔒 Security Best Practices

### **Production Deployment**

**1. Change All Default Passwords**
```bash
# Generate secure passwords
openssl rand -base64 24

# Update .env file with new passwords
nano .env

# Recreate database with new passwords
docker-compose down -v
docker-compose up -d
```

**2. Use SSL/TLS**
```yaml
# In docker-compose.yml
app:
  ports:
    - "443:443"
  volumes:
    - ./ssl/cert.pem:/etc/ssl/certs/secstore.pem
    - ./ssl/key.pem:/etc/ssl/private/secstore.key
```

**3. Restrict Network Access**
```yaml
# In docker-compose.yml - remove external ports
db:
  # Comment out port exposure for production
  # ports:
  #   - "3306:3306"
```

**4. Enable Firewall**
```bash
# Allow only necessary ports
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

**5. Regular Updates**
```bash
# Update base images
docker-compose pull
docker-compose up -d --build

# Update application
git pull
docker-compose restart app
```

### **Security Checklist**

- ✅ Change default `super.admin` password
- ✅ Update `.env` with strong passwords
- ✅ Remove phpMyAdmin in production (or restrict access)
- ✅ Enable HTTPS with valid certificates
- ✅ Restrict database port (don't expose 3306)
- ✅ Regular backups of config.php and database
- ✅ Keep Docker and images up to date
- ✅ Monitor logs for suspicious activity
- ✅ Use Docker secrets for sensitive data in production

---

## 📚 Advanced Usage

### **Custom PHP Configuration**

Create `docker/php/custom.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
```

Mount in `docker-compose.yml`:
```yaml
app:
  volumes:
    - ./docker/php/custom.ini:/usr/local/etc/php/conf.d/custom.ini
```

### **Running Tests**

```bash
# Install dev dependencies
docker-compose exec app composer install

# Run tests
docker-compose exec app vendor/bin/phpunit

# Code style check
docker-compose exec app vendor/bin/phpcs

# Fix code style
docker-compose exec app vendor/bin/php-cs-fixer fix
```

### **Development Mode**

```bash
# Enable PHP errors for debugging
docker-compose exec app bash -c "echo 'display_errors = On' > /usr/local/etc/php/conf.d/dev.ini"
docker-compose restart app

# Mount source code for live changes
# Add to docker-compose.yml:
volumes:
  - ./app:/var/www/html/app
  - ./public:/var/www/html/public
```

### **Using Different Database**

**PostgreSQL instead of MySQL:**
```yaml
# In docker-compose.yml
db:
  image: postgres:15
  environment:
    POSTGRES_DB: secstore
    POSTGRES_USER: secstore
    POSTGRES_PASSWORD: ${MYSQL_PASSWORD}
```

Update `config.php` PDO connection accordingly.

### **Multi-Container Scaling**

```bash
# Scale application containers
docker-compose up -d --scale app=3

# Use nginx as load balancer
# Create nginx.conf and add nginx service to docker-compose.yml
```

---

## 🆘 Getting Help

### **Documentation**

- 📖 [Main Installation Guide](Documentation/INSTALL.md)
- 👨‍💻 [Developer Documentation](Documentation/DEVDOC.md)
- 📝 [Changelog](Documentation/CHANGELOG.md)
- 🔒 [Security Policy](Documentation/SECURITY.md)

### **Community**

- 🐛 [Report Issues](https://github.com/madcoda9000/SecStore/issues)
- 💡 [Request Features](https://github.com/madcoda9000/SecStore/discussions)
- ❓ [Ask Questions](https://github.com/madcoda9000/SecStore/discussions/categories/q-a)

### **Before Asking for Help**

1. Check this troubleshooting section
2. Review container logs: `docker-compose logs`
3. Verify system requirements are met
4. Search existing GitHub issues

### **When Reporting Issues**

Include:
- Docker version: `docker --version`
- Docker Compose version: `docker-compose --version`
- Operating system and version
- Contents of `docker-compose logs`
- Steps to reproduce the issue

---

## 🎉 Success!

**Your SecStore instance is now running!**

Next steps:
1. ✅ Login at `http://localhost:8000`
2. ✅ Change the default admin password
3. ✅ Configure additional settings in admin panel
4. ✅ Create user accounts or enable registration
5. ✅ Explore features: 2FA, LDAP, Rate Limiting, Audit Logs

---

## 📄 License

SecStore is open-source software licensed under the [MIT License](LICENSE).

---

**Made with ❤️ by the SecStore Team**

For more information, visit: [GitHub Repository](https://github.com/madcoda9000/SecStore)
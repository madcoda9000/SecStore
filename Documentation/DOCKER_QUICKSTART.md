# 🐳 SecStore Docker - Quick Reference

**Ultra-quick installation guide for experienced users.**

---

## ⚡ 30-Second Install

```bash
cp .env.example .env
# Edit .env: Set DATA_PATH to your preferred storage location
docker-compose up -d
```

Open `http://localhost:8000` → Follow Setup Wizard → Done! 🎉

---

## 📍 Data Storage

Configure in `.env`:

```env
# Local development
DATA_PATH=./docker-data

# Proxmox
DATA_PATH=/mnt/appdata/secstore

# Synology
DATA_PATH=/volume1/docker/secstore

# Unraid
DATA_PATH=/mnt/user/appdata/secstore
```

---

## 📦 What You Get

| Service | URL | Credentials |
|---------|-----|-------------|
| **SecStore** | http://localhost:8000 | `super.admin` / `Test1000!` |
| **phpMyAdmin** | http://localhost:8080 | `root` / (from .env) |
| **MySQL** | localhost:3306 | `secstore` / (from .env) |

---

## 🔧 Setup Wizard Values

| Field | Value |
|-------|-------|
| Host | `db` |
| Database | `secstore` |
| User | `secstore` |
| Password | Check `.env` → `MYSQL_PASSWORD` |

---

## 🎮 Common Commands

### Basic Operations
```bash
docker-compose up -d        # Start
docker-compose down         # Stop
docker-compose restart      # Restart
docker-compose logs -f      # View logs
docker-compose ps           # Status
```

### With Makefile
```bash
make install      # Initial setup
make start        # Start containers
make stop         # Stop containers
make restart      # Restart
make logs         # View logs
make status       # Container status
make shell        # Open bash in app
make db           # Open MySQL client
make backup       # Backup DB + config
make clean        # Remove everything (⚠️)
make info         # Show credentials
```

---

## 🔄 Maintenance

```bash
# Clear cache
docker-compose exec app rm -rf /var/www/html/docker-data/cache/*

# View logs
docker-compose logs -f                    # All container logs
tail -f docker-data/logs/error.log        # PHP error log
docker-compose exec app tail -f /var/log/apache2/secstore_error.log  # Apache logs

# Update application
git pull && docker-compose up -d --build

# Database backup
docker-compose exec db mysqldump -u secstore -p secstore > backup.sql

# Database restore
docker-compose exec -T db mysql -u secstore -p secstore < backup.sql

# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/docker-data
```

---

## 🛠️ Troubleshooting

### Container won't start
```bash
docker-compose logs
docker-compose down -v
docker-compose up -d
```

### Database connection failed
```bash
# Check database status
docker-compose exec db mysqladmin ping -h localhost -u root -p

# Verify credentials
cat .env
cat config.php
```

### Permission errors
```bash
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)
docker-compose exec app chmod -R 775 /var/www/html/docker-data/cache
chmod 664 ${DATA_PATH}/config.php
```

### Reset everything
```bash
DATA_PATH=$(grep DATA_PATH .env | cut -d '=' -f2)
docker-compose down -v
rm -rf ${DATA_PATH}
docker-compose up -d
# Setup wizard will start again
```

---

## 📁 Persistent Data

All stored in volumes - survives container restarts:

- ✅ `docker-data/config.php` - Configuration
- ✅ `docker-data/cache/` - Template cache  
- ✅ `docker-data/logs/` - Application logs
- ✅ `db_data` - MySQL database

---

## 🔒 Security

```bash
# 1. Change passwords in .env
nano .env

# 2. Change admin password after first login

# 3. For production: Remove phpMyAdmin
# Comment out in docker-compose.yml

# 4. Don't expose MySQL port in production
# Remove ports: - "3306:3306" in docker-compose.yml
```

---

## 🆘 Need Help?

**Full Documentation:** [README_DOCKER.md](README_DOCKER.md)

**Issues:** https://github.com/madcoda9000/SecStore/issues

**Quick Tests:**
```bash
# Test web server
curl http://localhost:8000

# Test database
docker-compose exec db mysql -u secstore -p -e "SHOW DATABASES;"

# Check PHP modules
docker-compose exec app php -m
```

---

**Pro Tip:** Use `make help` to see all available commands!
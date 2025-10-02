#!/bin/bash
set -e

echo "====================================="
echo "🚀 SecStore Docker Setup"
echo "====================================="

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Wait for database to be ready
log_info "Waiting for database connection..."
until mysqladmin ping -h"db" --silent; do
    log_warn "Database not ready yet, waiting..."
    sleep 2
done
log_info "✓ Database connection established"

# Check if config.php exists
if [ ! -f /var/www/html/config.php ]; then
    log_info "config.php not found - copying template..."
    
    if [ -f /var/www/html/config.php_TEMPLATE ]; then
        cp /var/www/html/config.php_TEMPLATE /var/www/html/config.php
        chmod 664 /var/www/html/config.php
        chown www-data:www-data /var/www/html/config.php
        log_info "✓ config.php created from template"
        
        echo ""
        log_warn "======================================"
        log_warn "⚠️  INITIAL SETUP REQUIRED"
        log_warn "======================================"
        echo ""
        log_info "Next steps:"
        echo "  1. Open: http://localhost:8000"
        echo "  2. The Setup Wizard will start automatically"
        echo "  3. Configure database connection:"
        echo "     - Host: db"
        echo "     - Database: secstore"
        echo "     - User: secstore"
        echo "     - Password: (from .env or default)"
        echo ""
        log_info "Alternative: Edit config.php manually in your project folder"
        echo ""
    else
        log_error "config.php_TEMPLATE not found!"
        exit 1
    fi
else
    log_info "✓ config.php already exists (setup completed)"
fi

# Install Composer dependencies
if [ ! -d /var/www/html/vendor ]; then
    log_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    log_info "✓ Composer dependencies installed"
else
    log_info "✓ Vendor directory exists, skipping composer install"
fi

# Ensure cache directory exists and has correct permissions
log_info "Setting up cache directory..."
mkdir -p /var/www/html/cache
chmod -R 775 /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache
log_info "✓ Cache directory configured"

# Ensure logs directory exists
mkdir -p /var/www/html/logs
chmod -R 775 /var/www/html/logs
chown -R www-data:www-data /var/www/html/logs

# Create error.log if it doesn't exist (from public/index.php)
touch /var/www/html/public/error.log
chmod 664 /var/www/html/public/error.log
chown www-data:www-data /var/www/html/public/error.log
log_info "✓ Log files configured"

# Set ownership for all application files
log_info "Setting file permissions..."
chown -R www-data:www-data /var/www/html
log_info "✓ File permissions set"

echo ""
log_info "======================================"
log_info "✓ SecStore is ready!"
log_info "======================================"
echo ""
log_info "Access points:"
echo "  • Application:  http://localhost:8000"
echo "  • phpMyAdmin:   http://localhost:8080"
echo ""
log_info "Default database credentials:"
echo "  • Host:     db"
echo "  • Database: secstore"
echo "  • User:     secstore"
echo "  • Password: (check your .env file)"
echo ""

# Execute the main command (start Apache)
exec "$@"
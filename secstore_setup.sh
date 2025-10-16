#!/bin/bash

# SecStore PHP Development Environment Setup Script
# Supports: Debian, Ubuntu, and Fedora-based distributions
# Author: Generated for SecStore Project
# Version: 1.1

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if script is run as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "Dieses Script sollte NICHT als root ausgeführt werden!"
        log_info "Bitte führen Sie es als normaler Benutzer aus (sudo wird bei Bedarf verwendet)"
        exit 1
    fi
}

# Detect Linux distribution
detect_distro() {
    log_info "Erkenne Linux-Distribution..."
    
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        DISTRO=$ID
        VERSION=$VERSION_ID
    else
        log_error "Kann Distribution nicht erkennen"
        exit 1
    fi
    
    case $DISTRO in
        "debian")
            PACKAGE_MANAGER="apt"
            PHP_VERSION="8.3"
            DISTRO_TYPE="debian"
            log_success "Debian erkannt: $PRETTY_NAME"
            ;;
        "ubuntu"|"pop"|"linuxmint")
            PACKAGE_MANAGER="apt"
            PHP_VERSION="8.3"
            DISTRO_TYPE="ubuntu"
            log_success "Ubuntu-basierte Distribution erkannt: $PRETTY_NAME"
            ;;
        "fedora"|"centos"|"rhel"|"rocky"|"almalinux")
            PACKAGE_MANAGER="dnf"
            PHP_VERSION="8.3"
            DISTRO_TYPE="fedora"
            log_success "Fedora-basierte Distribution erkannt: $PRETTY_NAME"
            ;;
        *)
            log_error "Nicht unterstützte Distribution: $DISTRO"
            log_info "Unterstützte Distributionen: Ubuntu, Debian, Fedora, CentOS, RHEL"
            exit 1
            ;;
    esac
}

# Update package lists
update_packages() {
    log_info "Aktualisiere Paketlisten..."
    
    case $PACKAGE_MANAGER in
        "apt")
            sudo apt update
            ;;
        "dnf")
            sudo dnf makecache
            ;;
    esac
    
    log_success "Paketlisten aktualisiert"
}

# Check if PHP is installed and get version
check_php_version() {
    log_info "Überprüfe PHP-Installation..."
    
    if command -v php &> /dev/null; then
        CURRENT_PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        log_info "Aktuelle PHP-Version: $CURRENT_PHP_VERSION"
        
        # Check if version is >= 8.3
        if [ "$(printf '%s\n' "8.3" "$CURRENT_PHP_VERSION" | sort -V | head -n1)" = "8.3" ]; then
            log_success "PHP-Version ist kompatibel (>= 8.3)"
            return 0
        else
            log_warning "PHP-Version ist zu alt (< 8.3)"
            return 1
        fi
    else
        log_warning "PHP ist nicht installiert"
        return 1
    fi
}

# Install PHP
install_php() {
    log_info "Installiere PHP $PHP_VERSION..."
    
    case $DISTRO_TYPE in
        "debian")
            # Install prerequisites
            sudo apt install -y ca-certificates apt-transport-https lsb-release wget
            
            # Add Sury repository for Debian
            sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
            echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
            
            sudo apt update
            sudo apt install -y php$PHP_VERSION-cli
            ;;
        "ubuntu")
            # Add Ondrej PHP repository for Ubuntu
            sudo apt install -y software-properties-common
            sudo add-apt-repository -y ppa:ondrej/php
            sudo apt update
            sudo apt install -y php$PHP_VERSION-cli
            ;;
        "fedora")
            # Enable Remi repository for latest PHP versions
            sudo dnf install -y https://rpms.remirepo.net/fedora/remi-release-$(rpm -E %fedora).rpm
            sudo dnf module enable -y php:remi-$PHP_VERSION
            sudo dnf install -y php-cli
            ;;
    esac
    
    log_success "PHP $PHP_VERSION CLI installiert"
}

# Check if PHP module is installed
check_php_module() {
    local module=$1
    php -m | grep -i "^$module$" &> /dev/null
}

# Install PHP modules
install_php_modules() {
    log_info "Überprüfe und installiere PHP-Module..."
    
    # Required modules based on SecStore's composer.json
    local modules=("curl" "json" "openssl" "pdo" "pdo_mysql" "xml" "zip" "bcmath" "gd" "mbstring" "soap" "intl" "redis" "imagick" "ldap" "xdebug")
    
    local missing_modules=()
    
    # Check which modules are missing
    for module in "${modules[@]}"; do
        if ! check_php_module "$module"; then
            missing_modules+=("$module")
            log_warning "PHP-Modul '$module' ist nicht installiert"
        else
            log_success "PHP-Modul '$module' ist bereits installiert"
        fi
    done
    
    # Install missing modules
    if [ ${#missing_modules[@]} -gt 0 ]; then
        log_info "Installiere fehlende PHP-Module..."
        
        case $PACKAGE_MANAGER in
            "apt")
                local packages=""
                for module in "${missing_modules[@]}"; do
                    case $module in
                        "pdo_mysql") packages="$packages php$PHP_VERSION-mysql" ;;
                        "imagick") packages="$packages php$PHP_VERSION-imagick" ;;
                        "xdebug") packages="$packages php$PHP_VERSION-xdebug" ;;
                        "redis") packages="$packages php$PHP_VERSION-redis" ;;
                        "ldap") packages="$packages php$PHP_VERSION-ldap" ;;
                        *) packages="$packages php$PHP_VERSION-$module" ;;
                    esac
                done
                sudo apt install -y $packages
                ;;
            "dnf")
                local packages=""
                for module in "${missing_modules[@]}"; do
                    case $module in
                        "pdo_mysql") packages="$packages php-mysqlnd" ;;
                        "imagick") packages="$packages php-imagick" ;;
                        "xdebug") packages="$packages php-xdebug" ;;
                        "redis") packages="$packages php-redis" ;;
                        "ldap") packages="$packages php-ldap" ;;
                        "json") continue ;; # json is built-in in recent PHP versions
                        *) packages="$packages php-$module" ;;
                    esac
                done
                if [ -n "$packages" ]; then
                    sudo dnf install -y $packages
                fi
                ;;
        esac
        
        log_success "PHP-Module installiert"
    else
        log_success "Alle erforderlichen PHP-Module sind bereits installiert"
    fi
}

# Check if Composer is installed
check_composer() {
    log_info "Überprüfe Composer-Installation..."
    
    if command -v composer &> /dev/null; then
        COMPOSER_VERSION=$(composer --version | cut -d' ' -f3)
        log_success "Composer ist installiert (Version: $COMPOSER_VERSION)"
        return 0
    else
        log_warning "Composer ist nicht installiert"
        return 1
    fi
}

# Install Composer
install_composer() {
    log_info "Installiere Composer..."
    
    # Download and install Composer
    cd /tmp
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    
    # Verify installer (optional, but recommended)
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_error "Composer installer checksum mismatch"
        rm composer-setup.php
        exit 1
    fi
    
    # Install Composer globally
    php composer-setup.php --quiet
    sudo mv composer.phar /usr/local/bin/composer
    rm composer-setup.php
    
    # Make it executable
    sudo chmod +x /usr/local/bin/composer
    
    log_success "Composer installiert"
}

# Check if a Composer package is installed globally
check_global_package() {
    local package=$1
    composer global show "$package" &> /dev/null
}

# Install development tools
install_dev_tools() {
    log_info "Überprüfe und installiere Entwicklungstools..."
    
    # Check and install PHP CodeSniffer
    if ! check_global_package "squizlabs/php_codesniffer"; then
        log_info "Installiere PHP CodeSniffer..."
        composer global require squizlabs/php_codesniffer=*
        log_success "PHP CodeSniffer installiert"
    else
        log_success "PHP CodeSniffer ist bereits installiert"
    fi
    
    # Check and install PHP-CS-Fixer
    if ! check_global_package "friendsofphp/php-cs-fixer"; then
        log_info "Installiere PHP-CS-Fixer..."
        composer global require friendsofphp/php-cs-fixer
        log_success "PHP-CS-Fixer installiert"
    else
        log_success "PHP-CS-Fixer ist bereits installiert"
    fi
    
    # Add Composer global bin to PATH if not already there
    COMPOSER_BIN_DIR="$HOME/.composer/vendor/bin"
    if [ -d "$COMPOSER_BIN_DIR" ] && [[ ":$PATH:" != *":$COMPOSER_BIN_DIR:"* ]]; then
        log_info "Füge Composer bin-Verzeichnis zu PATH hinzu..."
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
        export PATH="$HOME/.composer/vendor/bin:$PATH"
        log_success "PATH aktualisiert"
    fi
}

# Check if user is in SecStore directory
check_secstore_directory() {
    log_info "Überprüfe aktuelles Verzeichnis..."
    
    CURRENT_DIR=$(basename "$(pwd)")
    
    if [ "$CURRENT_DIR" = "SecStore" ]; then
        log_success "Sie befinden sich bereits im SecStore-Verzeichnis"
        return 0
    else
        log_warning "Sie befinden sich nicht im SecStore-Verzeichnis (aktuell: $CURRENT_DIR)"
        return 1
    fi
}

# Navigate to SecStore directory and run composer install
setup_project() {
    if ! check_secstore_directory; then
        log_info "Bitte geben Sie den Pfad zum SecStore-Projektordner ein:"
        read -r PROJECT_PATH
        
        if [ ! -d "$PROJECT_PATH" ]; then
            log_error "Verzeichnis '$PROJECT_PATH' existiert nicht"
            exit 1
        fi
        
        if [ ! -f "$PROJECT_PATH/composer.json" ]; then
            log_error "Keine composer.json in '$PROJECT_PATH' gefunden"
            log_info "Stellen Sie sicher, dass Sie den korrekten SecStore-Projektpfad angegeben haben"
            exit 1
        fi
        
        log_info "Wechsle zu Projektverzeichnis: $PROJECT_PATH"
        cd "$PROJECT_PATH"
    fi
    
    log_info "Installiere Projektabhängigkeiten mit Composer..."
    composer install --no-dev --optimize-autoloader
    
    log_success "Projektabhängigkeiten installiert"
}

# Print final status and instructions
print_summary() {
    echo
    log_success "=== SETUP ABGESCHLOSSEN ==="
    echo
    log_info "Installierte Komponenten:"
    echo "  ✓ PHP $(php -r "echo PHP_VERSION;")"
    echo "  ✓ Alle erforderlichen PHP-Module"
    echo "  ✓ Composer $(composer --version | cut -d' ' -f3)"
    echo "  ✓ PHP CodeSniffer"
    echo "  ✓ PHP-CS-Fixer"
    echo "  ✓ SecStore Projektabhängigkeiten"
    echo
    log_info "Nützliche Befehle für die Entwicklung:"
    echo "  - PHP-Entwicklungsserver starten: php -S localhost:8000 -t public"
    echo "  - Code-Stil prüfen: vendor/bin/phpcs"
    echo "  - Code automatisch formatieren: vendor/bin/php-cs-fixer fix"
    echo
    log_success "Ihr SecStore-Entwicklungsumgebung ist bereit!"
}

# Main execution
main() {
    echo "=== SecStore PHP Development Environment Setup ==="
    echo
    
    check_root
    detect_distro
    update_packages
    
    # PHP installation and setup
    if ! check_php_version; then
        install_php
        if ! check_php_version; then
            log_error "PHP-Installation fehlgeschlagen"
            exit 1
        fi
    fi
    
    install_php_modules
    
    # Composer installation
    if ! check_composer; then
        install_composer
        if ! check_composer; then
            log_error "Composer-Installation fehlgeschlagen"
            exit 1
        fi
    fi
    
    install_dev_tools
    setup_project
    print_summary
}

# Run main function
main "$@"
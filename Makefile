# SecStore Docker Management
# Usage: make [command]

.PHONY: help install start stop restart logs status clean backup shell db test update

.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)SecStore Docker Management$(NC)"
	@echo ""
	@echo "$(GREEN)Available commands:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

install: ## Initial setup - copy .env and start containers
	@echo "$(BLUE)🚀 Setting up SecStore...$(NC)"
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN)✓ Created .env file$(NC)"; \
		echo "$(YELLOW)⚠️  Please edit .env and update passwords!$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  .env already exists, skipping$(NC)"; \
	fi
	@echo "$(BLUE)Building and starting containers...$(NC)"
	@docker-compose up -d --build
	@echo ""
	@echo "$(GREEN)✓ Setup complete!$(NC)"
	@echo "$(BLUE)Access SecStore:$(NC)"
	@echo "  Application:  http://localhost:8000"
	@echo "  phpMyAdmin:   http://localhost:8080"
	@echo ""
	@echo "$(YELLOW)Next step: Open http://localhost:8000 and follow the setup wizard$(NC)"

start: ## Start all containers
	@echo "$(BLUE)Starting SecStore containers...$(NC)"
	@docker-compose up -d
	@echo "$(GREEN)✓ Containers started$(NC)"

stop: ## Stop all containers
	@echo "$(BLUE)Stopping SecStore containers...$(NC)"
	@docker-compose down
	@echo "$(GREEN)✓ Containers stopped$(NC)"

restart: ## Restart all containers
	@echo "$(BLUE)Restarting SecStore containers...$(NC)"
	@docker-compose restart
	@echo "$(GREEN)✓ Containers restarted$(NC)"

logs: ## Show logs from all containers
	@docker-compose logs -f

logs-app: ## Show logs from app container only
	@docker-compose logs -f app

logs-db: ## Show logs from database container only
	@docker-compose logs -f db

logs-error: ## Show PHP error log
	@tail -f logs/error.log

logs-app-files: ## Show application log files
	@ls -la logs/

status: ## Show status of all containers
	@echo "$(BLUE)Container Status:$(NC)"
	@docker-compose ps

shell: ## Open bash shell in app container
	@echo "$(BLUE)Opening shell in app container...$(NC)"
	@docker-compose exec app bash

db: ## Open MySQL client in database container
	@echo "$(BLUE)Opening MySQL client...$(NC)"
	@docker-compose exec db mysql -u secstore -p secstore

phpmyadmin: ## Open phpMyAdmin in browser
	@echo "$(BLUE)Opening phpMyAdmin...$(NC)"
	@echo "URL: http://localhost:8080"
	@which xdg-open > /dev/null && xdg-open http://localhost:8080 || \
	which open > /dev/null && open http://localhost:8080 || \
	echo "Please open http://localhost:8080 in your browser"

clean: ## Remove all containers and volumes (DESTRUCTIVE!)
	@echo "$(RED)⚠️  WARNING: This will delete all data!$(NC)"
	@echo -n "Are you sure? [y/N] " && read ans && [ $${ans:-N} = y ]
	@docker-compose down -v
	@rm -f config.php
	@echo "$(GREEN)✓ All containers and data removed$(NC)"

backup: ## Create backup of database and config
	@echo "$(BLUE)Creating backup...$(NC)"
	@mkdir -p backups
	@docker-compose exec -T db mysqldump -u secstore -p$(grep MYSQL_PASSWORD .env | cut -d '=' -f2) secstore > backups/secstore_$(shell date +%Y%m%d_%H%M%S).sql
	@cp config.php backups/config_$(shell date +%Y%m%d_%H%M%S).php 2>/dev/null || true
	@cp logs/error.log backups/error_$(shell date +%Y%m%d_%H%M%S).log 2>/dev/null || true
	@echo "$(GREEN)✓ Backup created in ./backups/$(NC)"

restore: ## Restore database from latest backup
	@echo "$(BLUE)Restoring from latest backup...$(NC)"
	@LATEST=$$(ls -t backups/secstore_*.sql | head -1); \
	if [ -z "$$LATEST" ]; then \
		echo "$(RED)✗ No backup found$(NC)"; \
		exit 1; \
	fi; \
	echo "Restoring from $$LATEST"; \
	docker-compose exec -T db mysql -u secstore -p$$(grep MYSQL_PASSWORD .env | cut -d '=' -f2) secstore < $$LATEST
	@echo "$(GREEN)✓ Database restored$(NC)"

update: ## Update application (git pull + restart)
	@echo "$(BLUE)Updating SecStore...$(NC)"
	@git pull
	@docker-compose pull
	@docker-compose up -d --build
	@docker-compose exec app composer install --no-dev --optimize-autoloader
	@echo "$(GREEN)✓ Update complete$(NC)"

test: ## Run tests in container
	@echo "$(BLUE)Running tests...$(NC)"
	@docker-compose exec app composer install
	@docker-compose exec app vendor/bin/phpunit

composer: ## Run composer command (usage: make composer CMD="install")
	@docker-compose exec app composer $(CMD)

clear-cache: ## Clear application cache
	@echo "$(BLUE)Clearing cache...$(NC)"
	@docker-compose exec app rm -rf /var/www/html/cache/*
	@echo "$(GREEN)✓ Cache cleared$(NC)"

permissions: ## Fix file permissions
	@echo "$(BLUE)Fixing permissions...$(NC)"
	@docker-compose exec app chown -R www-data:www-data /var/www/html
	@docker-compose exec app chmod -R 775 /var/www/html/cache
	@echo "$(GREEN)✓ Permissions fixed$(NC)"

info: ## Show connection information
	@echo "$(BLUE)SecStore Connection Info:$(NC)"
	@echo ""
	@echo "$(GREEN)Web Application:$(NC)"
	@echo "  URL:      http://localhost:8000"
	@echo "  Username: super.admin"
	@echo "  Password: Test1000! $(YELLOW)(change immediately!)$(NC)"
	@echo ""
	@echo "$(GREEN)phpMyAdmin:$(NC)"
	@echo "  URL:      http://localhost:8080"
	@echo "  Username: root"
	@echo "  Password: $$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2)"
	@echo ""
	@echo "$(GREEN)Database:$(NC)"
	@echo "  Host:     localhost:3306"
	@echo "  Database: secstore"
	@echo "  Username: secstore"
	@echo "  Password: $$(grep MYSQL_PASSWORD .env | cut -d '=' -f2)"
	@echo ""
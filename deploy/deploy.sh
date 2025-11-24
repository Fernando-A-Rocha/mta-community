#!/bin/bash

# mtacomm2 Deployment Script
# This script handles git pull → rsync → Laravel optimization workflow
# with persistent storage preservation using symlinks
# Usage: ./deploy.sh [environment]

set -e

RED='\033[1;91m'
GREEN='\033[1;92m'
YELLOW='\033[1;93m'
BLUE='\033[1;94m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
HOME_DIR="/home/$(whoami)"
PROJECT_DIR="$HOME_DIR/mta/mtacomm2"
WEB_ROOT="/var/www/mtacomm2.frocha.net"
LARAVEL_DIR="$WEB_ROOT/platform"
PERSISTENT_STORAGE="$WEB_ROOT/storage"

# Logging function
log() {
    echo -e "$1"
}

log "${GREEN}🚀 Starting mtacomm2 deployment...${NC}"
log "${BLUE}Environment: $ENVIRONMENT${NC}"
log "${BLUE}Project directory: $PROJECT_DIR${NC}"
log "${BLUE}Web root: $WEB_ROOT${NC}"

# Check if running as correct user (not root for git operations)
if [ "$EUID" -eq 0 ]; then
    log "${RED}❌ Don't run this script as root. Use your regular user account.${NC}"
    exit 1
fi

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    log "${RED}❌ Project directory $PROJECT_DIR not found${NC}"
    exit 1
fi

# Navigate to project directory
cd "$PROJECT_DIR"

# Check if it's a git repository
if [ ! -d ".git" ]; then
    log "${RED}❌ Not a git repository. Please clone your repository first.${NC}"
    exit 1
fi


# Git operations
log "${GREEN}📥 Pulling latest changes from git...${NC}"
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    log "${RED}❌ Git pull failed${NC}"
    exit 1
fi

log "${GREEN}✅ Git pull successful${NC}"

# Setup persistent storage
log "${GREEN}💾 Setting up persistent storage...${NC}"

# Create persistent storage directory if it doesn't exist
if [ ! -d "$PERSISTENT_STORAGE" ]; then
    log "${BLUE}📁 Creating persistent storage directory...${NC}"
    sudo mkdir -p "$PERSISTENT_STORAGE"
    sudo chown www-data:www-data "$PERSISTENT_STORAGE"
    sudo chmod 755 "$PERSISTENT_STORAGE"
fi

# Backup current storage if it exists and is not already a symlink
if [ -d "$LARAVEL_DIR/storage" ] && [ ! -L "$LARAVEL_DIR/storage" ]; then
    log "${BLUE}💾 Backing up existing storage to persistent location...${NC}"
    sudo cp -r "$LARAVEL_DIR/storage"/* "$PERSISTENT_STORAGE/" 2>/dev/null || true
fi

# Create persistent storage subdirectories
sudo mkdir -p "$PERSISTENT_STORAGE/app/public"
sudo mkdir -p "$PERSISTENT_STORAGE/app/private"
sudo mkdir -p "$PERSISTENT_STORAGE/app/private/invoices"
sudo mkdir -p "$PERSISTENT_STORAGE/framework/cache"
sudo mkdir -p "$PERSISTENT_STORAGE/framework/sessions"
sudo mkdir -p "$PERSISTENT_STORAGE/framework/views"
sudo mkdir -p "$PERSISTENT_STORAGE/logs"
sudo chown -R www-data:www-data "$PERSISTENT_STORAGE"
sudo chmod -R 755 "$PERSISTENT_STORAGE"

log "${GREEN}🧹 Removing old files completely${NC}"
sudo rm -rf "$LARAVEL_DIR"/*
sudo rm -rf "$LARAVEL_DIR"/.[!.]*

# Rsync deployment
log "${GREEN}🔄 Syncing files to web directory...${NC}"

# Exclude files that shouldn't be deployed
# Safely deploy without ownership or permission errors
sudo rsync -av --delete \
    --no-o --no-g --no-perms \
    --chown=www-data:www-data \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage' \
    --exclude='bootstrap/cache' \
    --exclude='.gitignore' \
    --exclude='README.md' \
    --exclude='tests' \
    --exclude='.phpunit.xml' \
    --exclude='phpunit.xml' \
    --exclude='.editorconfig' \
    --exclude='.styleci.yml' \
    "$PROJECT_DIR/" "$LARAVEL_DIR/"

if [ $? -ne 0 ]; then
    log "${RED}❌ Rsync failed${NC}"
    exit 1
fi

# Create storage symlinks to persistent storage
log "${GREEN}🔗 Creating storage symlinks...${NC}"
sudo ln -sf "$PERSISTENT_STORAGE" "$LARAVEL_DIR/storage"

# Create bootstrap/cache directory and set permissions
sudo mkdir -p "$LARAVEL_DIR/bootstrap/cache"
sudo chown -R www-data:www-data "$LARAVEL_DIR/bootstrap/cache"
sudo chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

log "${GREEN}✅ Files synced successfully${NC}"

# Copy environment file from home directory
log "${GREEN}📋 Copying .env file...${NC}"
if [ -f "$PROJECT_DIR/comm2/.env" ]; then
    sudo cp "$PROJECT_DIR/comm2/.env" "$LARAVEL_DIR/.env"
    sudo chown www-data:www-data "$LARAVEL_DIR/.env"
    log "${GREEN}✅ .env file copied successfully${NC}"
else
    log "${YELLOW}⚠️  No .env file found in $PROJECT_DIR/comm2/.env${NC}"
fi

# Set proper permissions
log "${GREEN}🔐 Setting proper permissions...${NC}"
sudo chown -R www-data:www-data "$LARAVEL_DIR"
sudo chmod -R 755 "$LARAVEL_DIR"
sudo chmod -R 775 "$LARAVEL_DIR/storage"
sudo chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

# Install/update Composer dependencies
log "${GREEN}📦 Installing Composer dependencies...${NC}"
cd "$LARAVEL_DIR"
sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction

if [ $? -ne 0 ]; then
    log "${RED}❌ Composer install failed${NC}"
    exit 1
fi

# Install/update NPM dependencies and build assets
log "${GREEN}📦 Installing NPM dependencies and building assets...${NC}"
sudo -u www-data npm ci --production --cache "$LARAVEL_DIR/.npm-cache"
sudo -u www-data npm run build

if [ $? -ne 0 ]; then
    log "${RED}❌ NPM build failed${NC}"
    exit 1
fi

# Laravel optimization
log "${GREEN}⚡ Optimizing Laravel application...${NC}"

# Clear and cache configurations
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache

sudo -u www-data php artisan route:clear
sudo -u www-data php artisan route:cache

sudo -u www-data php artisan view:clear
sudo -u www-data php artisan view:cache

# Create storage link if it doesn't exist
if [ ! -L "$LARAVEL_DIR/public/storage" ]; then
    log "${GREEN}🔗 Creating storage link...${NC}"
    sudo -u www-data php artisan storage:link
fi

# Restart services
log "${GREEN}🔄 Restarting services...${NC}"
sudo systemctl reload nginx
sudo systemctl restart php8.4-fpm

# Check if services are running
if systemctl is-active --quiet nginx; then
    log "${GREEN}✅ Nginx is running${NC}"
else
    log "${RED}❌ Nginx is not running${NC}"
fi

if systemctl is-active --quiet php8.4-fpm; then
    log "${GREEN}✅ PHP 8.4 FPM is running${NC}"
else
    log "${RED}❌ PHP 8.4 FPM is not running${NC}"
fi

# Test the deployment
log "${GREEN}🧪 Testing deployment...${NC}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://mtacomm2.frocha.net || echo "000")

if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    log "${GREEN}✅ Deployment test successful (HTTP $HTTP_STATUS)${NC}"
else
    log "${YELLOW}⚠️  Deployment test returned HTTP $HTTP_STATUS${NC}"
fi


# Final status
log "${GREEN}🎉 Deployment completed successfully!${NC}"
log "${GREEN}🌐 The mtacomm2 is available at: https://mtacomm2.frocha.net${NC}"
log "${BLUE}📊 Deployment Summary:${NC}"
log "   - Files synced from: $PROJECT_DIR/comm2/"
log "   - Files deployed to: $LARAVEL_DIR"
log "   - Persistent storage: $PERSISTENT_STORAGE"
log "   - Storage symlinked: $LARAVEL_DIR/storage -> $PERSISTENT_STORAGE"
log "   - HTTP Status: $HTTP_STATUS"

log "${GREEN}✅ mtacomm2 Deployment completed!${NC}"

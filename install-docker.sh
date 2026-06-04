#!/usr/bin/env bash
set -euo pipefail

PATCH_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${1:-$(pwd)}"
PHP_SERVICE="${2:-php}"
CONTAINER_ROOT="${3:-/var/www/html}"
MOUNTED_PATCH_DIR=".nexusphp-custom-menu-patch"

cd "$APP_ROOT"

if [ ! -f artisan ] || [ ! -f composer.json ] || [ ! -f docker-compose.yml ]; then
    echo "ERROR: $APP_ROOT is not a Docker-based NexusPHP project root." >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: docker command not found." >&2
    exit 1
fi

SUDO=""
if [ "$(id -u)" != "0" ] && command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
fi

$SUDO rm -rf "$MOUNTED_PATCH_DIR"
$SUDO mkdir -p "$MOUNTED_PATCH_DIR"
$SUDO cp -R "$PATCH_ROOT"/. "$MOUNTED_PATCH_DIR"/

docker compose exec -T "$PHP_SERVICE" sh -lc "
set -eu
cd '$CONTAINER_ROOT'
PATCH_ROOT='$CONTAINER_ROOT/$MOUNTED_PATCH_DIR'

mkdir -p packages
rm -rf packages/nexus-custom-menu
cp -R \"\$PATCH_ROOT/files/packages/nexus-custom-menu\" packages/

mkdir -p app/Filament/Resources/System/CustomMenuItemResource/Pages
cp \"\$PATCH_ROOT/files/app/Filament/Resources/System/CustomMenuItemResource.php\" \
    app/Filament/Resources/System/CustomMenuItemResource.php
cp \"\$PATCH_ROOT/files/app/Filament/Resources/System/CustomMenuItemResource/Pages/ManageCustomMenuItems.php\" \
    app/Filament/Resources/System/CustomMenuItemResource/Pages/ManageCustomMenuItems.php

php \"\$PATCH_ROOT/tools/configure-composer.php\" '$CONTAINER_ROOT'
php \"\$PATCH_ROOT/tools/update-labels.php\" '$CONTAINER_ROOT'

composer update xiaomlove/nexusphp-menu -W --no-interaction
php artisan package:discover --ansi
php artisan plugin install xiaomlove/nexusphp-menu
php artisan optimize:clear
"

echo "Custom menu plugin installed in Docker service: $PHP_SERVICE."

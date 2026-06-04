#!/usr/bin/env bash
set -euo pipefail

PATCH_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${1:-$(pwd)}"

if [ "$(id -u)" = "0" ]; then
    export COMPOSER_ALLOW_SUPERUSER=1
fi

cd "$APP_ROOT"

if [ ! -f artisan ] || [ ! -f composer.json ]; then
    echo "ERROR: $APP_ROOT is not a NexusPHP project root." >&2
    exit 1
fi

mkdir -p packages
rm -rf packages/nexus-custom-menu
cp -R "$PATCH_ROOT/files/packages/nexus-custom-menu" packages/

mkdir -p app/Filament/Resources/System/CustomMenuItemResource/Pages
cp "$PATCH_ROOT/files/app/Filament/Resources/System/CustomMenuItemResource.php" \
    app/Filament/Resources/System/CustomMenuItemResource.php
cp "$PATCH_ROOT/files/app/Filament/Resources/System/CustomMenuItemResource/Pages/ManageCustomMenuItems.php" \
    app/Filament/Resources/System/CustomMenuItemResource/Pages/ManageCustomMenuItems.php

php "$PATCH_ROOT/tools/configure-composer.php" "$APP_ROOT"
php "$PATCH_ROOT/tools/update-labels.php" "$APP_ROOT"

composer update xiaomlove/nexusphp-menu -W --no-interaction
php artisan package:discover --ansi
php artisan plugin install xiaomlove/nexusphp-menu
php artisan optimize:clear

echo "Custom menu plugin installed."

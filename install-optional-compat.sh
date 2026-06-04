#!/usr/bin/env bash
set -euo pipefail

PATCH_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${1:-$(pwd)}"

cd "$APP_ROOT"

if [ ! -f artisan ] || [ ! -f composer.json ]; then
    echo "ERROR: $APP_ROOT is not a NexusPHP project root." >&2
    exit 1
fi

mkdir -p app/Auth app/Providers/Filament
cp "$PATCH_ROOT/optional-compat/app/Auth/NexusWebGuard.php" app/Auth/NexusWebGuard.php
cp "$PATCH_ROOT/optional-compat/app/Providers/Filament/AppPanelProvider.php" app/Providers/Filament/AppPanelProvider.php

php artisan optimize:clear

echo "Optional backend compatibility files installed."

#!/bin/sh
set -e

# APP_KEY debe existir antes de arrancar; en Railway se define como variable
# de entorno persistente (no se regenera en cada deploy).
if [ -z "$APP_KEY" ]; then
    echo "ERROR: la variable de entorno APP_KEY no está definida." >&2
    exit 1
fi

php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"

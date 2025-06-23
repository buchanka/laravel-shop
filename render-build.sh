#!/usr/bin/env bash
set -euo pipefail

# Установка зависимостей
composer install --no-interaction --optimize-autoloader --no-dev

# Генерация ключа приложения
php artisan key:generate

# Миграции и сиды (если нужно)
php artisan migrate --force
php artisan db:seed --force

# Очистка кэша
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
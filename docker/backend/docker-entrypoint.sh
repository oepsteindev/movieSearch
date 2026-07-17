#!/usr/bin/env bash
set -e

if [ ! -f composer.json ]; then
    echo "No Symfony app found in /app, scaffolding a new one..."
    composer create-project symfony/skeleton . --no-interaction
    composer require symfony/orm-pack symfony/http-client symfony/serializer-pack symfony/validator symfony/test-pack --no-interaction
fi

if [ ! -d vendor ]; then
    composer install --no-interaction
fi

echo "Waiting for MySQL to accept connections..."
until (exec 3<>/dev/tcp/mysql/3306) 2>/dev/null; do
    sleep 1
done
exec 3>&-

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

exec "$@"

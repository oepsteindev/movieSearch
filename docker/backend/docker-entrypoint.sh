#!/usr/bin/env bash
set -e

if [ ! -f composer.json ]; then
    echo "No Symfony app found in /app, scaffolding a new one..."
    composer create-project symfony/skeleton . --no-interaction
    composer require symfony/orm-pack symfony/http-client symfony/serializer-pack symfony/validator symfony/test-pack --no-interaction
fi

exec "$@"

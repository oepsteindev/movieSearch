#!/usr/bin/env sh
set -e

if [ ! -f package.json ]; then
    echo "No Vue app found in /app, scaffolding a new one..."
    npm exec --yes create-vite@latest . -- --template vue
    npm install
    npm install pinia
fi

if [ ! -d node_modules ]; then
    npm install
fi

exec "$@"

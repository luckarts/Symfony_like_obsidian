#!/bin/sh
set -e
cd /app

echo "⏳ Attente de la base de données..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do sleep 1; done

echo "🗄️  Migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🔑 OAuth2 client..."
php bin/console app:oauth:setup-client --no-interaction
echo "🚀 Démarrage Symfony sur 0.0.0.0:8000..."
exec php -S 0.0.0.0:8000 -t public/

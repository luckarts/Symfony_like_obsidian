#!/bin/sh
set -e

echo "🗄️  Migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🔑 OAuth2 client..."
php bin/console app:oauth:setup-client --no-interaction

echo "🚀 Démarrage des services..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf

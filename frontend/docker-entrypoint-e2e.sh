#!/bin/sh
set -e

echo "🚀 Démarrage de Nuxt E2E..."

# Vérifier si node_modules est vide ou inexistant
if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules)" ]; then
    echo "📦 Installation des dépendances (node_modules vide)..."
    pnpm install --no-frozen-lockfile
    echo "✅ Dépendances installées"
else
    echo "✅ Dépendances déjà présentes"
fi

echo "🌐 Démarrage de Nuxt sur 0.0.0.0:3000..."
exec pnpm dev --host 0.0.0.0
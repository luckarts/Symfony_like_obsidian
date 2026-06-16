.PHONY: up down build restart logs backend frontend shell-backend shell-frontend routes install-hooks

# Démarrer tous les services
up:
	docker compose up -d

# Arrêter tous les services
down:
	docker compose down

# Build et démarrer
restart: down up

# Voir les logs en temps réel
logs:
	docker compose logs -f

# Logs d'un service spécifique
logs-backend:
	docker compose logs -f backend

logs-frontend:
	docker compose logs -f frontend

# Shell dans un conteneur
shell-backend:
	docker exec -it app-backend sh

shell-frontend:
	docker exec -it app-frontend sh

# Commandes Symfony
console:
	docker exec app-backend php bin/console $(cmd)

routes:
	docker exec app-backend php bin/console debug:router

cc:
	docker exec app-backend php bin/console cache:clear

# Statut
ps:
	docker compose ps

# Tests backend
test:
	docker exec app-backend php bin/phpunit

# Migrations
migrate:
	docker exec app-backend php bin/console doctrine:migrations:migrate --no-interaction

# Installer les git hooks locaux (pre-push: validation schema Doctrine)
install-hooks:
	cp scripts/pre-push .git/hooks/pre-push
	chmod +x .git/hooks/pre-push

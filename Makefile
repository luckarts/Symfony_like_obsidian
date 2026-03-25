.PHONY: up down build restart logs backend frontend shell-backend shell-frontend routes install-hooks
.PHONY: dev-up dev-down dev-logs test-e2e-signup test-e2e install-browsers

DOCKER_LOCAL = docker compose --env-file backend/.env.docker.local -f backend/docker-compose.local.yml

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

# ─── Backend Docker (dev local) ──────────────────────────
dev-up:
	$(DOCKER_LOCAL) up -d
	@echo "✅ Backend disponible sur http://localhost:8000"

dev-down:
	$(DOCKER_LOCAL) down

dev-logs:
	$(DOCKER_LOCAL) logs -f symfony

# ─── Frontend ────────────────────────────────────────────
install-browsers:
	cd frontend && pnpm exec playwright install --with-deps chromium

# ─── Tests E2E ───────────────────────────────────────────
test-e2e-signup:
	cd frontend && PLAYWRIGHT_START_SERVER=false pnpm test:e2e:smoke --grep @smoke

test-e2e:
	cd frontend && PLAYWRIGHT_START_SERVER=false pnpm test:e2e:smoke

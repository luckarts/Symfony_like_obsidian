DOCKER_COMPOSE = docker compose --env-file .env.docker.test -f docker-compose.test.yml
PHP_RUN = $(DOCKER_COMPOSE) run --rm php

.PHONY: test test-build test-up test-install test-keys test-db test-db-reset test-e2e test-all test-down test-clean migration migrate

## Build les containers
test-build:
	$(DOCKER_COMPOSE) build

## Démarre PostgreSQL (attente healthcheck)
test-up:
	$(DOCKER_COMPOSE) up -d database

## Installe les dépendances Composer
test-install:
	$(PHP_RUN) composer install --no-interaction --prefer-dist

## Génère les clés OAuth2 pour les tests
test-keys:
	$(PHP_RUN) sh -c '\
		mkdir -p var/oauth && \
		openssl genrsa -out var/oauth/private.pem 2048 && \
		openssl rsa -in var/oauth/private.pem -pubout -out var/oauth/public.pem && \
		chmod 644 var/oauth/private.pem var/oauth/public.pem'

## Crée le schéma de base de données
test-db:
	$(PHP_RUN) bin/console doctrine:database:create --if-not-exists --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:schema:drop --force --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:migrations:migrate --env=test --no-interaction

## Réinitialise le schéma de base de données (drop + create)
test-db-reset:
	$(PHP_RUN) bin/console doctrine:database:drop --force --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:database:create --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:migrations:migrate --env=test --no-interaction

## Lance les tests E2E
test-e2e:
	$(PHP_RUN) bin/phpunit --testsuite E2E

## Lance tous les tests
test-all:
	$(PHP_RUN) bin/phpunit

## Tout d'un coup : build + up + install + db + tests E2E
test: test-build test-up test-install test-keys test-db-reset test-e2e

## Génère une migration Doctrine (reset DB → migrate → diff)
migration: test-up
	$(PHP_RUN) bin/console doctrine:database:drop --force --if-exists --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:database:create --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:migrations:migrate --allow-no-migration --env=test --no-interaction
	$(PHP_RUN) bin/console doctrine:migrations:diff --env=test --no-interaction

## Applique les migrations Doctrine en attente
migrate: test-up
	$(PHP_RUN) bin/console doctrine:migrations:migrate --env=test --no-interaction

## Stop et supprime les containers
test-down:
	$(DOCKER_COMPOSE) down

## Stop, supprime les containers et les volumes
test-clean:
	$(DOCKER_COMPOSE) down -v
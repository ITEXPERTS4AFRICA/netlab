# Makefile pour faciliter les commandes Docker

.PHONY: help build up down restart logs shell artisan migrate seed test clean

help: ## Afficher l'aide
	@echo "Commandes Docker disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construire les images Docker
	docker-compose build --no-cache

up: ## Démarrer tous les services
	docker-compose up -d

down: ## Arrêter tous les services
	docker-compose down

restart: ## Redémarrer tous les services
	docker-compose restart

logs: ## Voir les logs de tous les services
	docker-compose logs -f

shell: ## Ouvrir un shell dans le conteneur app
	docker-compose exec app bash

artisan: ## Exécuter une commande Artisan (usage: make artisan CMD="migrate")
	docker-compose exec app php artisan $(CMD)

migrate: ## Exécuter les migrations
	docker-compose exec app php artisan migrate

seed: ## Exécuter les seeders
	docker-compose exec app php artisan db:seed

test: ## Exécuter les tests
	docker-compose exec app php artisan test

clean: ## Nettoyer les volumes et images
	docker-compose down -v
	docker system prune -a

setup: ## Configuration initiale complète
	docker-compose up -d
	sleep 5
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate
	docker-compose exec app php artisan db:seed --class=AdminUserSeeder
	docker-compose exec app php artisan storage:link
	@echo "✅ Configuration terminée! Accédez à http://localhost:8000"



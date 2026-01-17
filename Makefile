.PHONY: setup up down logs reload monitor bash test migrate tinker queue

setup:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@docker compose up -d --build
	@docker compose exec app composer install
	@docker compose exec app php artisan key:generate
	@docker compose exec app php artisan migrate --seed
	@echo "Ambiente configurado e rodando!"

up:
	@docker compose up -d

down:
	@docker compose down

logs:
	@docker compose logs -f app

reload:
	@docker compose exec app php artisan octane:reload

monitor:
	@docker compose exec app php artisan octane:status

bash:
	@docker compose exec app bash

test:
	@docker compose exec app php artisan test

migrate:
	@docker compose exec app php artisan migrate

tinker:
	@docker compose exec app php artisan tinker

queue:
	@docker compose exec app php artisan queue:work

.PHONY: setup up down logs reload monitor bash test migrate tinker queue lint analyse check

setup:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@docker compose up -d --build
	@docker compose exec app composer install
	@docker compose exec app php artisan app:setup
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

lint:
	@docker compose exec app ./vendor/bin/pint

analyse:
	@docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=2G

phpmd:
	@docker compose exec app php -d error_reporting="E_ALL & ~E_DEPRECATED" ./vendor/bin/phpmd app text phpmd.xml

check: lint analyse phpmd test

# Simplified Platform - Backend

Este projeto é uma implementação do desafio técnico.

## Stack Tecnológica

- **PHP 8.5**
- **Laravel 12**
- **Laravel Octane (Swoole)**
- **PostgreSQL**
- **Redis**
- **Docker & Docker Compose**

## Pré-requisitos

- Docker
- Docker Compose

## Setup do Projeto

Siga os passos abaixo para configurar o ambiente de desenvolvimento:

1. **Clonar o repositório:**
   ```bash
   git clone <repo-url>
   cd simplified-platform
   ```

2. **Configurar o ambiente:**
   O arquivo `.env` já foi pré-configurado para funcionar com o Docker. Caso precise de ajustes:
   ```bash
   cp .env.example .env
   ```

3. **Subir os containers:**
   ```bash
   docker compose up -d --build
   ```

4. **Instalar dependências (caso não tenha sido feito automaticamente):**
   ```bash
   docker compose exec app composer install
   ```

5. **Gerar a chave da aplicação:**
   ```bash
   docker compose exec app php artisan key:generate
   ```

6. **Executar as migrations:**
   ```bash
   docker compose exec app php artisan migrate
   ```

## Acessando a Aplicação

A aplicação estará disponível em `http://localhost:8000`.

## Comandos Úteis

- **Logs do Octane:**
  ```bash
  docker compose logs -f app
  ```

- **Reiniciar Octane:**
  ```bash
  docker compose exec app php artisan octane:reload
  ```

- **Executar Testes:**
  ```bash
  docker compose exec app php artisan test
  ```

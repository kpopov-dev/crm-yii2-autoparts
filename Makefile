.DEFAULT_GOAL := help
COMPOSE := docker compose
PHP := $(COMPOSE) exec php

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

init: env build up install migrate rbac seed ## Полная установка проекта с нуля

env: ## Создать файлы окружения из примеров
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	@test -f frontend/.env || cp frontend/.env.example frontend/.env

build: ## Собрать образы
	$(COMPOSE) build

up: ## Поднять контейнеры
	$(COMPOSE) up -d

down: ## Остановить контейнеры
	$(COMPOSE) down

destroy: ## Остановить контейнеры и удалить данные
	$(COMPOSE) down -v

logs: ## Логи всех сервисов
	$(COMPOSE) logs -f --tail=100

install: ## Установить зависимости backend
	$(PHP) composer install

migrate: ## Применить миграции
	$(PHP) php yii migrate --interactive=0
	$(PHP) php yii migrate --migrationPath=@yii/rbac/migrations --interactive=0

rbac: ## Создать роли и разрешения
	$(PHP) php yii rbac/init

seed: ## Наполнить базу демо-данными
	$(PHP) php yii seed/init

test: ## Юнит-тесты
	$(PHP) ./vendor/bin/phpunit

stan: ## Статический анализ
	$(PHP) ./vendor/bin/phpstan analyse

cs: ## Проверка стиля кода PSR-12
	$(PHP) ./vendor/bin/phpcs --standard=PSR12 --ignore=vendor,runtime,web/assets .

shell: ## Консоль внутри контейнера php
	$(PHP) sh

outbox-status: ## Количество неопубликованных событий
	$(PHP) php yii outbox/status

.PHONY: help init env build up down destroy logs install migrate rbac seed test stan cs shell outbox-status

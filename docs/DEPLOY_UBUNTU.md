# Развёртывание на Ubuntu 22.04 / 24.04

Инструкция для боевого сервера без Docker: системный Nginx, PHP-FPM, MySQL,
RabbitMQ и воркеры под systemd. Все команды выполняются от пользователя с `sudo`.

## 1. Пакеты

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server rabbitmq-server git unzip \
  php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-intl \
  php8.2-xml php8.2-zip php8.2-bcmath php8.2-sockets php8.2-opcache
```

Composer:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Node.js для сборки фронтенда:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Пользователь и код

Не запускайте приложение от root.

```bash
sudo adduser --system --group --home /var/www/crm deploy
sudo -u deploy git clone https://github.com/<логин>/crm-yii2-autoparts.git /var/www/crm/app
cd /var/www/crm/app
```

## 3. База данных

```bash
sudo mysql
```

```sql
CREATE DATABASE crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm'@'localhost' IDENTIFIED BY 'ЗАМЕНИТЕ_НА_СЛОЖНЫЙ_ПАРОЛЬ';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON crm.* TO 'crm'@'localhost';
FLUSH PRIVILEGES;
```

## 4. RabbitMQ

```bash
sudo rabbitmq-plugins enable rabbitmq_management
sudo rabbitmqctl add_user crm 'ЗАМЕНИТЕ_НА_СЛОЖНЫЙ_ПАРОЛЬ'
sudo rabbitmqctl set_user_tags crm administrator
sudo rabbitmqctl set_permissions -p / crm '.*' '.*' '.*'
sudo rabbitmqctl delete_user guest
```

Панель управления слушает порт 15672 — наружу его не открывайте, ходите через
SSH-туннель: `ssh -L 15672:localhost:15672 user@server`.

## 5. Конфигурация приложения

```bash
cd /var/www/crm/app/backend
sudo -u deploy cp .env.example .env
sudo -u deploy nano .env
```

Обязательно измените:

```
APP_ENV=prod
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_PASSWORD=пароль_из_шага_3
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PASSWORD=пароль_из_шага_4
JWT_SECRET=<вывод команды openssl rand -hex 32>
COOKIE_VALIDATION_KEY=<вывод команды openssl rand -hex 32>
CORS_ORIGINS=https://crm.example.com
```

Установка и миграции:

```bash
sudo -u deploy composer install --no-dev --optimize-autoloader
sudo -u deploy php yii migrate --interactive=0
sudo -u deploy php yii migrate --migrationPath=@yii/rbac/migrations --interactive=0
sudo -u deploy php yii rbac/init
sudo -u deploy php yii user/create admin@example.com 'СложныйПароль1!' 'Администратор' admin
```

Права на каталог логов и кеша:

```bash
sudo chown -R deploy:www-data /var/www/crm/app/backend/runtime
sudo chmod -R 775 /var/www/crm/app/backend/runtime
```

## 6. Сборка фронтенда

```bash
cd /var/www/crm/app/frontend
sudo -u deploy cp .env.example .env
echo 'VITE_API_BASE_URL=https://crm.example.com' | sudo -u deploy tee .env
sudo -u deploy npm ci
sudo -u deploy npm run build
```

Готовая статика окажется в `frontend/dist`.

## 7. Nginx

```bash
sudo nano /etc/nginx/sites-available/crm
```

```nginx
server {
    listen 80;
    server_name crm.example.com;

    root /var/www/crm/app/frontend/dist;
    index index.html;

    charset utf-8;
    client_max_body_size 20m;

    gzip on;
    gzip_types text/css application/javascript application/json;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        alias /var/www/crm/app/backend/web;
        try_files $uri /index.php$is_args$args;

        location ~ ^/api/index\.php$ {
            fastcgi_pass unix:/run/php/php8.2-fpm.sock;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME /var/www/crm/app/backend/web/index.php;
        }
    }

    location ~* /\.(?!well-known) {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/crm
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

HTTPS через Let's Encrypt:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.example.com
```

## 8. PHP-FPM

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Для сервера с 4 ГБ памяти разумная отправная точка:

```
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500
```

Опкеш в production:

```bash
sudo nano /etc/php/8.2/fpm/conf.d/99-app.ini
```

```
opcache.enable=1
opcache.memory_consumption=192
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
display_errors=Off
expose_php=Off
```

`validate_timestamps=0` требует перезапуска PHP-FPM после каждого деплоя.

```bash
sudo systemctl restart php8.2-fpm
```

## 9. Воркеры под systemd

```bash
sudo nano /etc/systemd/system/crm-outbox.service
```

```ini
[Unit]
Description=CRM outbox relay
After=network.target mysql.service rabbitmq-server.service

[Service]
Type=simple
User=deploy
WorkingDirectory=/var/www/crm/app/backend
ExecStart=/usr/bin/php yii outbox/relay
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Аналогичные юниты для консьюмеров — отличается только строка `ExecStart`:

```
ExecStart=/usr/bin/php yii consume/notifications
ExecStart=/usr/bin/php yii consume/analytics
```

Запуск:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now crm-outbox crm-notifications crm-analytics
sudo systemctl status crm-outbox
journalctl -u crm-notifications -f
```

## 10. Файрвол

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Порты MySQL (3306) и RabbitMQ (5672, 15672) наружу не открываются — сервисы слушают
localhost.

## 11. Резервное копирование

```bash
sudo nano /usr/local/bin/crm-backup.sh
```

```bash
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR=/var/backups/crm
mkdir -p "$BACKUP_DIR"
mysqldump --single-transaction --routines crm | gzip > "$BACKUP_DIR/crm-$(date +%F).sql.gz"
find "$BACKUP_DIR" -name 'crm-*.sql.gz' -mtime +14 -delete
```

```bash
sudo chmod +x /usr/local/bin/crm-backup.sh
sudo crontab -e
# 0 3 * * * /usr/local/bin/crm-backup.sh
```

Флаг `--single-transaction` снимает консистентный дамп InnoDB без блокировки таблиц.

## 12. Обновление версии

```bash
cd /var/www/crm/app
sudo -u deploy git pull
cd backend
sudo -u deploy composer install --no-dev --optimize-autoloader
sudo -u deploy php yii migrate --interactive=0
cd ../frontend
sudo -u deploy npm ci && sudo -u deploy npm run build
sudo systemctl restart php8.2-fpm crm-outbox crm-notifications crm-analytics
```

## Проверка после установки

```bash
curl -s https://crm.example.com/api/health | jq
systemctl is-active crm-outbox crm-notifications crm-analytics
tail -n 50 /var/www/crm/app/backend/runtime/logs/app.log
```

Ответ `{"status":"ok","database":"up","outboxPending":0}` означает, что API,
база и очередь событий работают.

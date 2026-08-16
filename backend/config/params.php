<?php

declare(strict_types=1);

use app\components\Env;

return [
    'jwtSecret' => Env::get('JWT_SECRET', ''),
    'jwtTtl' => Env::int('JWT_TTL', 43200),
    'corsOrigins' => array_map('trim', explode(',', (string)Env::get('CORS_ORIGINS', '*'))),
    'rabbitmq' => [
        'host' => Env::get('RABBITMQ_HOST', 'rabbitmq'),
        'port' => Env::int('RABBITMQ_PORT', 5672),
        'user' => Env::get('RABBITMQ_USER', 'crm'),
        'password' => Env::get('RABBITMQ_PASSWORD', 'crm'),
        'vhost' => Env::get('RABBITMQ_VHOST', '/'),
    ],
    'outbox' => [
        'batchSize' => Env::int('OUTBOX_BATCH_SIZE', 100),
        'sleepSeconds' => Env::int('OUTBOX_SLEEP_SECONDS', 2),
    ],
];

<?php

declare(strict_types=1);

use app\components\Env;

return [
    'class' => yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%d;dbname=%s',
        Env::get('DB_HOST', 'mysql'),
        Env::int('DB_PORT', 3306),
        Env::get('DB_NAME', 'crm')
    ),
    'username' => Env::get('DB_USER', 'crm'),
    'password' => Env::get('DB_PASSWORD', 'crm'),
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
    'attributes' => [
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

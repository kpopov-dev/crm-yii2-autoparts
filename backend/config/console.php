<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$container = require __DIR__ . '/container.php';

return [
    'id' => 'crm-console',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'container' => $container,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                ],
            ],
        ],
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationPath' => '@app/migrations',
            'interactive' => false,
        ],
    ],
    'params' => $params,
];

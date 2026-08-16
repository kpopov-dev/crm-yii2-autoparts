<?php

declare(strict_types=1);

use app\components\ApiExceptionFormatter;
use app\components\Env;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$routes = require __DIR__ . '/routes.php';
$container = require __DIR__ . '/container.php';

return [
    'id' => 'crm-api',
    'name' => 'CRM API',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log', ApiExceptionFormatter::class],
    'container' => $container,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
            'cache' => 'cache',
        ],
        'request' => [
            'cookieValidationKey' => Env::get('COOKIE_VALIDATION_KEY', 'change-me'),
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'user' => [
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'errorAction' => null,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => $routes,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                ],
            ],
        ],
    ],
    'modules' => [
        'crm' => [
            'class' => app\modules\crm\Module::class,
        ],
    ],
    'params' => $params,
];

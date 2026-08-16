<?php

declare(strict_types=1);

use app\components\Env;

require __DIR__ . '/../vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

defined('YII_DEBUG') or define('YII_DEBUG', Env::bool('APP_DEBUG', false));
defined('YII_ENV') or define('YII_ENV', Env::get('APP_ENV', 'prod'));

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@app', dirname(__DIR__));

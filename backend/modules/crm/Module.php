<?php

declare(strict_types=1);

namespace app\modules\crm;

use yii\base\Module as BaseModule;

final class Module extends BaseModule
{
    public $controllerNamespace = 'app\modules\crm\controllers';

    public $defaultRoute = 'deal';
}

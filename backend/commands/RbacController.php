<?php

declare(strict_types=1);

namespace app\commands;

use app\domain\Enum\UserRole;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

final class RbacController extends Controller
{
    private const PERMISSIONS = [
        'client.view' => 'Просмотр контрагентов',
        'client.manage' => 'Управление контрагентами',
        'deal.view' => 'Просмотр заказов',
        'deal.manage' => 'Управление заказами',
        'task.view' => 'Просмотр задач',
        'task.manage' => 'Управление задачами',
        'report.view' => 'Просмотр отчётов',
        'user.manage' => 'Управление пользователями',
    ];

    private const MANAGER_PERMISSIONS = [
        'client.view',
        'client.manage',
        'deal.view',
        'deal.manage',
        'task.view',
        'task.manage',
        'report.view',
    ];

    public function actionInit(): int
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $permissions = [];

        foreach (self::PERMISSIONS as $name => $description) {
            $permission = $auth->createPermission($name);
            $permission->description = $description;
            $auth->add($permission);
            $permissions[$name] = $permission;
        }

        $manager = $auth->createRole(UserRole::MANAGER);
        $manager->description = 'Менеджер по продажам';
        $auth->add($manager);

        foreach (self::MANAGER_PERMISSIONS as $name) {
            $auth->addChild($manager, $permissions[$name]);
        }

        $admin = $auth->createRole(UserRole::ADMIN);
        $admin->description = 'Администратор системы';
        $auth->add($admin);
        $auth->addChild($admin, $manager);
        $auth->addChild($admin, $permissions['user.manage']);

        $this->assignAll();

        $this->stdout("Роли и разрешения созданы\n");

        return ExitCode::OK;
    }

    public function actionAssignAll(): int
    {
        $this->assignAll();
        $this->stdout("Роли назначены пользователям\n");

        return ExitCode::OK;
    }

    private function assignAll(): void
    {
        $auth = Yii::$app->authManager;

        foreach (User::find()->each() as $user) {
            $auth->revokeAll($user->getId());
            $role = $auth->getRole((string)$user->role);

            if ($role !== null) {
                $auth->assign($role, $user->getId());
            }
        }
    }
}

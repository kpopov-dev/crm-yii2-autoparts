<?php

declare(strict_types=1);

namespace app\commands;

use app\domain\Contract\PasswordHasherInterface;
use app\domain\Enum\UserRole;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

final class UserController extends Controller
{
    private PasswordHasherInterface $hasher;

    public function __construct($id, $module, PasswordHasherInterface $hasher, array $config = [])
    {
        $this->hasher = $hasher;

        parent::__construct($id, $module, $config);
    }

    public function actionCreate(string $email, string $password, string $fullName, string $role = UserRole::MANAGER): int
    {
        if (!UserRole::exists($role)) {
            $this->stderr('Недопустимая роль: ' . $role . "\n");

            return ExitCode::USAGE;
        }

        if (User::findByEmail($email) !== null) {
            $this->stderr('Пользователь уже существует' . "\n");

            return ExitCode::DATAERR;
        }

        $user = new User([
            'email' => mb_strtolower($email),
            'password_hash' => $this->hasher->hash($password),
            'full_name' => $fullName,
            'role' => $role,
            'is_active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        if (!$user->validate()) {
            $this->stderr(json_encode($user->getErrors(), JSON_UNESCAPED_UNICODE) . "\n");

            return ExitCode::DATAERR;
        }

        $user->save(false);

        $authRole = Yii::$app->authManager->getRole($role);

        if ($authRole !== null) {
            Yii::$app->authManager->assign($authRole, $user->getId());
        }

        $this->stdout('Пользователь создан, id=' . $user->getId() . "\n");

        return ExitCode::OK;
    }

    public function actionResetPassword(string $email, string $password): int
    {
        $user = User::findByEmail($email);

        if ($user === null) {
            $this->stderr("Пользователь не найден\n");

            return ExitCode::DATAERR;
        }

        $user->password_hash = $this->hasher->hash($password);
        $user->updated_at = time();
        $user->save(false);

        $this->stdout("Пароль обновлён\n");

        return ExitCode::OK;
    }
}

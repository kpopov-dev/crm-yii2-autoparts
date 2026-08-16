<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use yii\db\Connection;

final class UserController extends BaseApiController
{
    private Connection $db;

    public function __construct($id, $module, Connection $db, array $config = [])
    {
        $this->db = $db;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex(): array
    {
        $rows = $this->db->createCommand(
            "SELECT id, full_name, email, role
               FROM {{%user}}
              WHERE is_active = 1
              ORDER BY full_name ASC"
        )->queryAll();

        $items = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'fullName' => (string)$row['full_name'],
                'email' => (string)$row['email'],
                'role' => (string)$row['role'],
            ];
        }, $rows);

        return ['items' => $items];
    }
}

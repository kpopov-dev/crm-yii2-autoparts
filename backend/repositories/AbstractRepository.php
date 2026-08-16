<?php

declare(strict_types=1);

namespace app\repositories;

use yii\db\Connection;

abstract class AbstractRepository
{
    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->createCommand($sql, $params)->queryAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->db->createCommand($sql, $params)->queryOne();

        return $row === false ? null : $row;
    }

    protected function fetchScalar(string $sql, array $params = [])
    {
        return $this->db->createCommand($sql, $params)->queryScalar();
    }

    protected function resolveSort(string $requested, array $whitelist, string $default): string
    {
        $direction = 'ASC';
        $column = $requested;

        if (str_starts_with($requested, '-')) {
            $direction = 'DESC';
            $column = substr($requested, 1);
        }

        if (!isset($whitelist[$column])) {
            return $default;
        }

        return $whitelist[$column] . ' ' . $direction;
    }

    protected function likePrefix(string $value): string
    {
        return addcslashes(trim($value), '%_\\') . '%';
    }
}

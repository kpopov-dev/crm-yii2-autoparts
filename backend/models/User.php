<?php

declare(strict_types=1);

namespace app\models;

use app\domain\Enum\UserRole;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

final class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public static function findIdentity($id): ?IdentityInterface
    {
        return static::findOne(['id' => (int)$id, 'is_active' => 1]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        return null;
    }

    public static function findByEmail(string $email): ?self
    {
        return static::findOne(['email' => mb_strtolower(trim($email))]);
    }

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            [['email', 'password_hash', 'full_name', 'role', 'created_at'], 'required'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 190],
            [['email'], 'unique'],
            [['full_name'], 'string', 'max' => 255],
            [['role'], 'in', 'range' => UserRole::all()],
            [['is_active'], 'boolean'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function fields(): array
    {
        return ['id', 'email', 'full_name', 'role', 'is_active'];
    }
}

<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Contract\PasswordHasherInterface;
use app\domain\Contract\TokenIssuerInterface;
use app\domain\Exception\AuthenticationException;
use app\models\User;

final class AuthService
{
    private PasswordHasherInterface $hasher;
    private TokenIssuerInterface $tokens;

    public function __construct(PasswordHasherInterface $hasher, TokenIssuerInterface $tokens)
    {
        $this->hasher = $hasher;
        $this->tokens = $tokens;
    }

    public function login(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        if ($user === null || !$this->hasher->verify($password, (string)$user->password_hash)) {
            throw AuthenticationException::invalidCredentials();
        }

        if (!(bool)$user->is_active) {
            throw AuthenticationException::accountDisabled();
        }

        $token = $this->tokens->issue($user->getId(), (string)$user->role);

        return [
            'token' => $token['token'],
            'expiresAt' => $token['expiresAt'],
            'user' => [
                'id' => $user->getId(),
                'email' => (string)$user->email,
                'fullName' => (string)$user->full_name,
                'role' => (string)$user->role,
            ],
        ];
    }

    public function profile(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => (string)$user->email,
            'fullName' => (string)$user->full_name,
            'role' => (string)$user->role,
        ];
    }
}

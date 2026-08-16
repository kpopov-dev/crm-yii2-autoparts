<?php

declare(strict_types=1);

namespace app\components;

use app\domain\Contract\TokenIssuerInterface;
use app\domain\Exception\DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtIssuer implements TokenIssuerInterface
{
    private const ALGORITHM = 'HS256';

    private string $secret;
    private int $ttl;
    private string $issuer;

    public function __construct(string $secret, int $ttl = 43200, string $issuer = 'crm-api')
    {
        if (mb_strlen($secret) < 16) {
            throw new DomainException('JWT_SECRET должен содержать не менее 16 символов');
        }

        $this->secret = $secret;
        $this->ttl = $ttl;
        $this->issuer = $issuer;
    }

    public function issue(int $userId, string $role): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttl;

        $payload = [
            'iss' => $this->issuer,
            'sub' => $userId,
            'role' => $role,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return [
            'token' => JWT::encode($payload, $this->secret, self::ALGORITHM),
            'expiresAt' => $expiresAt,
        ];
    }

    public function parse(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

        return (array)$decoded;
    }
}

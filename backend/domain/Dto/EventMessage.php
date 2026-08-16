<?php

declare(strict_types=1);

namespace app\domain\Dto;

final class EventMessage
{
    private string $id;
    private string $name;
    private array $payload;
    private int $occurredAt;

    public function __construct(string $id, string $name, array $payload, int $occurredAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public static function create(string $name, array $payload): self
    {
        return new self(self::uuid4(), $name, $payload, time());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['id'] ?? self::uuid4()),
            (string)($data['name'] ?? ''),
            (array)($data['payload'] ?? []),
            (int)($data['occurredAt'] ?? time())
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): int
    {
        return $this->occurredAt;
    }

    public function get(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'payload' => $this->payload,
            'occurredAt' => $this->occurredAt,
        ];
    }

    public function toJson(): string
    {
        return (string)json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Contract\ClientRepositoryInterface;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\domain\Exception\EntityNotFoundException;
use app\domain\Exception\ValidationException;
use app\models\Client;
use yii\db\Connection;

final class ClientService
{
    private Connection $db;
    private ClientRepositoryInterface $clients;

    public function __construct(Connection $db, ClientRepositoryInterface $clients)
    {
        $this->db = $db;
        $this->clients = $clients;
    }

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        return $this->clients->search($filter, $pagination);
    }

    public function view(int $id): array
    {
        $client = $this->clients->findByIdWithStats($id);

        if ($client === null) {
            throw EntityNotFoundException::for('Клиент', $id);
        }

        return $client;
    }

    public function create(array $attributes): array
    {
        $client = new Client();
        $client->setAttributes($this->normalize($attributes), false);
        $client->created_at = time();
        $client->updated_at = time();
        $client->is_active = (int)($attributes['isActive'] ?? 1);

        $this->guardEmail((string)$client->email, null);
        $this->persist($client);

        return $this->view((int)$client->id);
    }

    public function update(int $id, array $attributes): array
    {
        $client = Client::findOne(['id' => $id]);

        if ($client === null) {
            throw EntityNotFoundException::for('Клиент', $id);
        }

        $client->setAttributes($this->normalize($attributes), false);
        $client->updated_at = time();

        if (array_key_exists('isActive', $attributes)) {
            $client->is_active = (int)(bool)$attributes['isActive'];
        }

        $this->guardEmail((string)$client->email, $id);
        $this->persist($client);

        return $this->view($id);
    }

    public function archive(int $id): void
    {
        $affected = $this->db->createCommand()->update(
            '{{%client}}',
            ['is_active' => 0, 'updated_at' => time()],
            ['id' => $id]
        )->execute();

        if ($affected === 0) {
            throw EntityNotFoundException::for('Клиент', $id);
        }
    }

    private function normalize(array $attributes): array
    {
        return [
            'name' => trim((string)($attributes['name'] ?? '')),
            'email' => isset($attributes['email']) && $attributes['email'] !== ''
                ? mb_strtolower(trim((string)$attributes['email']))
                : null,
            'phone' => isset($attributes['phone']) ? trim((string)$attributes['phone']) : null,
            'inn' => isset($attributes['inn']) ? trim((string)$attributes['inn']) : null,
            'comment' => isset($attributes['comment']) ? trim((string)$attributes['comment']) : null,
            'manager_id' => (int)($attributes['managerId'] ?? 0),
        ];
    }

    private function guardEmail(string $email, ?int $exceptId): void
    {
        if ($email === '') {
            return;
        }

        if ($this->clients->existsByEmail($email, $exceptId)) {
            throw new ValidationException(['email' => ['Контрагент с таким e-mail уже существует']]);
        }
    }

    private function persist(Client $client): void
    {
        if (!$client->validate()) {
            throw new ValidationException($client->getErrors());
        }

        $client->save(false);
    }
}

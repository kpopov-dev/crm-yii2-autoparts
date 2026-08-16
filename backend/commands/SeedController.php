<?php

declare(strict_types=1);

namespace app\commands;

use app\domain\Contract\PasswordHasherInterface;
use app\domain\Enum\DealStage;
use app\domain\Enum\TaskStatus;
use app\domain\Enum\UserRole;
use app\models\Client;
use app\models\Deal;
use app\models\DealStageHistory;
use app\models\Task;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;

final class SeedController extends Controller
{
    private const CLIENTS = [
        ['ООО «Автотехцентр Гаражъ»', 'независимая СТО', 'garazh'],
        ['ООО «М-Сервис Кузьминки»', 'независимая СТО', 'm-service'],
        ['АО «Дилерский центр Восток-Моторс»', 'официальный дилер', 'vostok-motors'],
        ['ООО «Премиум Авто Люберцы»', 'официальный дилер', 'premium-avto'],
        ['ООО «Таксопарк Столица»', 'таксопарк', 'taxi-stolitsa'],
        ['ООО «Городское такси 495»', 'таксопарк', 'taxi495'],
        ['АО «Транслогистик Групп»', 'грузовой автопарк', 'translogistik'],
        ['ООО «Рефрижератор Плюс»', 'грузовой автопарк', 'refrigerator-plus'],
        ['ИП Соколов А. В., магазин «Деталь»', 'розничная точка', 'detal-shop'],
        ['ООО «Автозапчасти на Ленинском»', 'розничная точка', 'zapchasti-lenin'],
        ['ООО «Шинный двор»', 'шиномонтаж', 'shinny-dvor'],
        ['ООО «Мойка 24 Сеть»', 'сеть автомоек', 'moyka24'],
        ['ООО «Спецтехника Урал-Сервис»', 'спецтехника', 'ural-service'],
        ['АО «Автобаза №7»', 'муниципальный автопарк', 'avtobaza7'],
        ['ООО «Каршеринг Драйв-Ю»', 'каршеринг', 'drive-you'],
        ['ООО «Электрокар Сервис»', 'сервис электромобилей', 'electrocar'],
        ['ООО «Дизель Мастер»', 'дизельный сервис', 'dizel-master'],
        ['ООО «Кузовной цех Профи»', 'кузовной ремонт', 'kuzov-profi'],
    ];

    private const PART_GROUPS = [
        ['Тормозные колодки', ['TRW', 'Brembo', 'ATE', 'Ferodo'], 'компл.', 1400, 5200],
        ['Тормозные диски', ['Zimmermann', 'Brembo', 'ATE'], 'шт.', 2600, 9800],
        ['Масляные фильтры', ['MANN-FILTER', 'Knecht', 'Filtron'], 'шт.', 320, 1100],
        ['Воздушные фильтры', ['MANN-FILTER', 'Bosch', 'Filtron'], 'шт.', 420, 1600],
        ['Моторное масло 5W-30', ['Shell', 'Mobil', 'Motul', 'Lukoil'], 'л', 450, 1300],
        ['Аккумуляторы 60 А·ч', ['Varta', 'Bosch', 'Tyumen'], 'шт.', 5400, 12800],
        ['Амортизаторы передние', ['KYB', 'Sachs', 'Bilstein'], 'шт.', 3100, 11500],
        ['Ремни ГРМ с роликами', ['Gates', 'INA', 'Contitech'], 'компл.', 4200, 14600],
        ['Свечи зажигания', ['NGK', 'Denso', 'Bosch'], 'компл.', 900, 3800],
        ['Сцепление в сборе', ['LuK', 'Sachs', 'Valeo'], 'компл.', 12800, 42000],
        ['Радиаторы охлаждения', ['Nissens', 'Hella', 'Luzar'], 'шт.', 6400, 21000],
        ['Ступичные подшипники', ['SKF', 'FAG', 'Optimal'], 'шт.', 1800, 6900],
        ['Стойки стабилизатора', ['Lemforder', 'Febi', 'Moog'], 'шт.', 620, 2400],
        ['Щётки стеклоочистителя', ['Bosch', 'Denso', 'Valeo'], 'компл.', 780, 2900],
        ['Шины 205/55 R16', ['Michelin', 'Nokian', 'Continental'], 'шт.', 5900, 14200],
        ['Антифриз G12+', ['Felix', 'Sintec', 'Motul'], 'л', 210, 620],
    ];

    private const VEHICLE_MODELS = [
        'Lada Vesta', 'Hyundai Solaris', 'Kia Rio', 'Volkswagen Polo',
        'Skoda Octavia', 'Toyota Camry', 'Renault Logan', 'Ford Transit',
        'Mercedes-Benz Sprinter', 'ГАЗель Next', 'Chery Tiggo', 'Haval Jolion',
    ];

    private const DELIVERY_TERMS = [
        'Отгрузка со склада на Каширском шоссе, самовывоз',
        'Доставка транспортной компанией до склада клиента',
        'Позиция под заказ, срок поставки 5–7 рабочих дней',
        'Резерв на складе до конца недели, оплата по счёту',
        'Доставка собственным транспортом, ежедневный рейс',
        'Отгрузка двумя партиями по графику клиента',
    ];

    private const PAYMENT_TERMS = [
        'Отсрочка платежа 14 календарных дней',
        'Предоплата 100 %, дилерская скидка 12 %',
        'Оптовая колонка цен, отсрочка 30 дней',
        'Оплата по факту отгрузки, скидка за объём 8 %',
        'Розничная цена, оплата картой при получении',
    ];

    private const TASK_TITLES = [
        'Проверить остаток на складе и в филиалах',
        'Подобрать аналоги по VIN клиента',
        'Запросить сроки поставки у поставщика',
        'Согласовать оптовую цену с руководителем отдела',
        'Выставить счёт и передать в бухгалтерию',
        'Уточнить адрес и график приёмки товара',
        'Оформить возврат по рекламации',
        'Проверить дебиторскую задолженность перед отгрузкой',
        'Собрать заказ на складе и передать в отгрузку',
        'Отправить накладные и сертификаты соответствия',
    ];

    private Connection $db;
    private PasswordHasherInterface $hasher;

    public function __construct($id, $module, Connection $db, PasswordHasherInterface $hasher, array $config = [])
    {
        $this->db = $db;
        $this->hasher = $hasher;

        parent::__construct($id, $module, $config);
    }

    public function actionInit(int $clientsCount = 36, int $dealsCount = 140): int
    {
        $transaction = $this->db->beginTransaction();

        try {
            $users = $this->createUsers();
            $clients = $this->createClients($users, $clientsCount);
            $deals = $this->createDeals($users, $clients, $dealsCount);
            $this->createTasks($users, $deals);

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $this->stderr('Ошибка наполнения: ' . $exception->getMessage() . "\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Yii::$app->runAction('rbac/assign-all');

        $this->stdout("Демо-данные оптового склада автозапчастей созданы\n");
        $this->stdout('Клиентов: ' . count($clients) . ', заказов: ' . count($deals) . "\n");
        $this->stdout("Администратор: admin@crm.local / Admin123!\n");
        $this->stdout("Менеджер: manager@crm.local / Manager123!\n");

        return ExitCode::OK;
    }

    public function actionFlush(): int
    {
        $tables = [
            'daily_stat', 'notification', 'processed_message', 'outbox_message',
            'task', 'deal_stage_history', 'deal', 'client', 'user',
        ];

        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();

        foreach ($tables as $table) {
            $this->db->createCommand()->truncateTable('{{%' . $table . '}}')->execute();
        }

        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();

        $this->stdout("Таблицы очищены\n");

        return ExitCode::OK;
    }

    private function createUsers(): array
    {
        $definitions = [
            ['admin@crm.local', 'Admin123!', 'Кирилл Попов', UserRole::ADMIN],
            ['manager@crm.local', 'Manager123!', 'Анна Ковалёва', UserRole::MANAGER],
            ['petrov@crm.local', 'Manager123!', 'Сергей Петров', UserRole::MANAGER],
            ['orlova@crm.local', 'Manager123!', 'Мария Орлова', UserRole::MANAGER],
            ['gusev@crm.local', 'Manager123!', 'Дмитрий Гусев', UserRole::MANAGER],
        ];

        $users = [];

        foreach ($definitions as [$email, $password, $fullName, $role]) {
            $user = User::findByEmail($email) ?? new User();
            $user->email = $email;
            $user->password_hash = $this->hasher->hash($password);
            $user->full_name = $fullName;
            $user->role = $role;
            $user->is_active = 1;
            $user->created_at = $user->created_at ?? time();
            $user->updated_at = time();
            $user->save(false);

            $users[] = $user;
        }

        return $users;
    }

    private function createClients(array $users, int $count): array
    {
        $managers = array_values(array_filter($users, static fn (User $user): bool => !$user->isAdmin()));
        $clients = [];
        $total = count(self::CLIENTS);

        for ($i = 0; $i < $count; $i++) {
            $manager = $managers[array_rand($managers)];
            [$name, $segment, $slug] = self::CLIENTS[$i % $total];
            $branch = (int)floor($i / $total);

            $client = new Client([
                'name' => $branch === 0 ? $name : $name . ', филиал ' . $branch,
                'email' => $branch === 0
                    ? sprintf('zakaz@%s.ru', $slug)
                    : sprintf('zakaz.f%d@%s.ru', $branch, $slug),
                'phone' => sprintf(
                    '+7 (9%02d) %03d-%02d-%02d',
                    random_int(0, 99),
                    random_int(100, 999),
                    random_int(10, 99),
                    random_int(10, 99)
                ),
                'inn' => (string)random_int(1000000000, 9999999999),
                'comment' => sprintf(
                    'Сегмент: %s. %s',
                    $segment,
                    self::PAYMENT_TERMS[array_rand(self::PAYMENT_TERMS)]
                ),
                'manager_id' => $manager->getId(),
                'is_active' => 1,
                'created_at' => time() - random_int(0, 300) * 86400,
                'updated_at' => time(),
            ]);

            $client->save(false);
            $clients[] = $client;
        }

        return $clients;
    }

    private function createDeals(array $users, array $clients, int $count): array
    {
        $managers = array_values(array_filter($users, static fn (User $user): bool => !$user->isAdmin()));
        $stages = DealStage::all();
        $deals = [];

        for ($i = 0; $i < $count; $i++) {
            $client = $clients[array_rand($clients)];
            $manager = $managers[array_rand($managers)];
            $stage = $stages[array_rand($stages)];
            $createdAt = time() - random_int(1, 300) * 86400;
            $closedAt = DealStage::isClosed($stage) ? $createdAt + random_int(2, 30) * 86400 : null;

            [$group, $brands, $unit, $priceFrom, $priceTo] = self::PART_GROUPS[array_rand(self::PART_GROUPS)];
            $brand = $brands[array_rand($brands)];
            $quantity = random_int(4, 120);
            $price = random_int($priceFrom, $priceTo);

            $deal = new Deal([
                'number' => sprintf('ZP-%d-%05d', (int)date('Y', $createdAt), $i + 1),
                'title' => sprintf('%s %s — %d %s', $group, $brand, $quantity, $unit),
                'description' => sprintf(
                    "Подбор под %s. %s. %s",
                    self::VEHICLE_MODELS[array_rand(self::VEHICLE_MODELS)],
                    self::DELIVERY_TERMS[array_rand(self::DELIVERY_TERMS)],
                    self::PAYMENT_TERMS[array_rand(self::PAYMENT_TERMS)]
                ),
                'amount' => $quantity * $price,
                'currency' => 'RUB',
                'stage' => $stage,
                'client_id' => (int)$client->id,
                'responsible_id' => $manager->getId(),
                'created_at' => $createdAt,
                'updated_at' => $closedAt ?? $createdAt,
                'closed_at' => $closedAt !== null && $closedAt < time() ? $closedAt : null,
            ]);

            $deal->save(false);

            $history = new DealStageHistory([
                'deal_id' => (int)$deal->id,
                'stage_from' => null,
                'stage_to' => DealStage::NEW,
                'comment' => 'Заявка принята от клиента',
                'user_id' => $manager->getId(),
                'created_at' => $createdAt,
            ]);
            $history->save(false);

            if ($stage !== DealStage::NEW) {
                $transition = new DealStageHistory([
                    'deal_id' => (int)$deal->id,
                    'stage_from' => DealStage::NEW,
                    'stage_to' => $stage,
                    'comment' => $this->transitionComment($stage),
                    'user_id' => $manager->getId(),
                    'created_at' => $closedAt ?? $createdAt + 86400,
                ]);
                $transition->save(false);
            }

            $deals[] = $deal;
        }

        return $deals;
    }

    private function transitionComment(string $stage): string
    {
        $comments = [
            DealStage::QUALIFICATION => 'Позиции подобраны, наличие на складе подтверждено',
            DealStage::PROPOSAL => 'Отправлен прайс с оптовой скидкой',
            DealStage::NEGOTIATION => 'Обсуждаем сроки поставки и отсрочку платежа',
            DealStage::WON => 'Счёт оплачен, товар отгружен со склада',
            DealStage::LOST => 'Клиент нашёл позиции дешевле у другого поставщика',
        ];

        return $comments[$stage] ?? 'Стадия изменена';
    }

    private function createTasks(array $users, array $deals): void
    {
        $managers = array_values(array_filter($users, static fn (User $user): bool => !$user->isAdmin()));
        $statuses = TaskStatus::all();

        foreach ($deals as $index => $deal) {
            if ($index % 2 !== 0) {
                continue;
            }

            $assignee = $managers[array_rand($managers)];
            $status = $statuses[array_rand($statuses)];
            $dueAt = time() + random_int(-20, 20) * 86400;

            $task = new Task([
                'title' => self::TASK_TITLES[array_rand(self::TASK_TITLES)],
                'description' => sprintf('По заказу %s', (string)$deal->number),
                'status' => $status,
                'deal_id' => (int)$deal->id,
                'client_id' => (int)$deal->client_id,
                'assignee_id' => $assignee->getId(),
                'author_id' => $assignee->getId(),
                'due_at' => $dueAt,
                'created_at' => (int)$deal->created_at,
                'updated_at' => time(),
                'completed_at' => TaskStatus::isFinal($status) ? $dueAt : null,
            ]);

            $task->save(false);
        }
    }
}

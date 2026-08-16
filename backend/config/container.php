<?php

declare(strict_types=1);

use app\components\JwtIssuer;
use app\components\PasswordHasher;
use app\domain\Contract\ClientRepositoryInterface;
use app\domain\Contract\DealRepositoryInterface;
use app\domain\Contract\EventPublisherInterface;
use app\domain\Contract\PasswordHasherInterface;
use app\domain\Contract\ReportRepositoryInterface;
use app\domain\Contract\TaskRepositoryInterface;
use app\domain\Contract\TokenIssuerInterface;
use app\messaging\EventDispatcher;
use app\messaging\Handler\AnalyticsHandler;
use app\messaging\Handler\NotificationHandler;
use app\messaging\OutboxEventPublisher;
use app\messaging\RabbitMqConnection;
use app\repositories\ClientRepository;
use app\repositories\DealRepository;
use app\repositories\ReportRepository;
use app\repositories\TaskRepository;
use yii\db\Connection;

return [
    'definitions' => [
        ClientRepositoryInterface::class => ClientRepository::class,
        DealRepositoryInterface::class => DealRepository::class,
        TaskRepositoryInterface::class => TaskRepository::class,
        ReportRepositoryInterface::class => ReportRepository::class,
        EventPublisherInterface::class => OutboxEventPublisher::class,
        PasswordHasherInterface::class => PasswordHasher::class,
    ],
    'singletons' => [
        Connection::class => static function (): Connection {
            return Yii::$app->getDb();
        },
        TokenIssuerInterface::class => static function (): JwtIssuer {
            return new JwtIssuer(
                (string)Yii::$app->params['jwtSecret'],
                (int)Yii::$app->params['jwtTtl']
            );
        },
        RabbitMqConnection::class => static function (): RabbitMqConnection {
            $config = Yii::$app->params['rabbitmq'];

            return new RabbitMqConnection(
                (string)$config['host'],
                (int)$config['port'],
                (string)$config['user'],
                (string)$config['password'],
                (string)$config['vhost']
            );
        },
        EventDispatcher::class => static function (): EventDispatcher {
            return new EventDispatcher(Yii::$app->getDb(), [
                Yii::$container->get(NotificationHandler::class),
                Yii::$container->get(AnalyticsHandler::class),
            ]);
        },
    ],
];

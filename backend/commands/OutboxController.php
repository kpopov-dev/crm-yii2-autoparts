<?php

declare(strict_types=1);

namespace app\commands;

use app\messaging\OutboxRelay;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

final class OutboxController extends Controller
{
    private OutboxRelay $relay;

    public function __construct($id, $module, OutboxRelay $relay, array $config = [])
    {
        $this->relay = $relay;

        parent::__construct($id, $module, $config);
    }

    public function actionRelay(): int
    {
        $batchSize = (int)Yii::$app->params['outbox']['batchSize'];
        $sleepSeconds = (int)Yii::$app->params['outbox']['sleepSeconds'];

        $this->stdout("Outbox relay запущен\n");

        while (true) {
            try {
                $published = $this->relay->relayBatch($batchSize);

                if ($published > 0) {
                    $this->stdout(sprintf("[%s] опубликовано сообщений: %d\n", date('H:i:s'), $published));
                    continue;
                }
            } catch (\Throwable $exception) {
                Yii::error($exception->getMessage(), 'outbox');
                $this->stderr(sprintf("[%s] ошибка: %s\n", date('H:i:s'), $exception->getMessage()));
            }

            sleep($sleepSeconds);
        }

        return ExitCode::OK;
    }

    public function actionOnce(int $limit = 100): int
    {
        $published = $this->relay->relayBatch($limit);
        $this->stdout(sprintf("Опубликовано сообщений: %d\n", $published));

        return ExitCode::OK;
    }

    public function actionStatus(): int
    {
        $this->stdout(sprintf("В очереди на публикацию: %d\n", $this->relay->pendingCount()));

        return ExitCode::OK;
    }
}

<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\messaging\OutboxRelay;
use Yii;

final class HealthController extends BaseApiController
{
    private OutboxRelay $relay;

    public function __construct($id, $module, OutboxRelay $relay, array $config = [])
    {
        $this->relay = $relay;

        parent::__construct($id, $module, $config);
    }

    protected function publicActions(): array
    {
        return ['options', 'index'];
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex(): array
    {
        $database = 'down';
        $outboxPending = null;

        try {
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            $database = 'up';
            $outboxPending = $this->relay->pendingCount();
        } catch (\Throwable $exception) {
            Yii::error($exception->getMessage(), 'health');
        }

        return [
            'status' => $database === 'up' ? 'ok' : 'degraded',
            'database' => $database,
            'outboxPending' => $outboxPending,
            'time' => date('c'),
        ];
    }
}

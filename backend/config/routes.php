<?php

declare(strict_types=1);

return [
    'GET api/health' => 'crm/health/index',

    'POST api/auth/login' => 'crm/auth/login',
    'GET api/auth/me' => 'crm/auth/me',

    'GET api/users' => 'crm/user/index',

    'GET api/clients' => 'crm/client/index',
    'POST api/clients' => 'crm/client/create',
    'GET api/clients/<id:\d+>' => 'crm/client/view',
    'PUT api/clients/<id:\d+>' => 'crm/client/update',
    'PATCH api/clients/<id:\d+>' => 'crm/client/update',
    'DELETE api/clients/<id:\d+>' => 'crm/client/archive',

    'GET api/deals' => 'crm/deal/index',
    'GET api/deals/board' => 'crm/deal/board',
    'GET api/deals/stages' => 'crm/deal/stages',
    'POST api/deals' => 'crm/deal/create',
    'GET api/deals/<id:\d+>' => 'crm/deal/view',
    'PUT api/deals/<id:\d+>' => 'crm/deal/update',
    'PATCH api/deals/<id:\d+>' => 'crm/deal/update',
    'POST api/deals/<id:\d+>/stage' => 'crm/deal/change-stage',

    'GET api/tasks' => 'crm/task/index',
    'GET api/tasks/statuses' => 'crm/task/statuses',
    'POST api/tasks' => 'crm/task/create',
    'GET api/tasks/<id:\d+>' => 'crm/task/view',
    'POST api/tasks/<id:\d+>/status' => 'crm/task/change-status',
    'POST api/tasks/<id:\d+>/assignee' => 'crm/task/reassign',

    'GET api/reports/dashboard' => 'crm/report/dashboard',
    'GET api/reports/funnel' => 'crm/report/funnel',
    'GET api/reports/managers' => 'crm/report/managers',
    'GET api/reports/daily' => 'crm/report/daily',

    'GET api/notifications' => 'crm/notification/index',
    'GET api/notifications/unread-count' => 'crm/notification/unread-count',
    'POST api/notifications/read' => 'crm/notification/read',
    'POST api/notifications/read-all' => 'crm/notification/read-all',

    'OPTIONS api/<path:.*>' => 'crm/auth/options',
];

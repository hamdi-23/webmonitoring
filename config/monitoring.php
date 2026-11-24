<?php

return [
    // How many days of check history to keep before `model:prune` deletes it.
    'retention_days' => (int) env('MONITORING_RETENTION_DAYS', 30),

    // User-Agent sent with every check request.
    'user_agent' => env('MONITORING_USER_AGENT', 'WebMonitoring/1.0 (+uptime check)'),

    'notify' => [
        // Email every registered user when a monitor goes down or recovers.
        'mail' => (bool) env('MONITORING_NOTIFY_MAIL', true),
    ],
];

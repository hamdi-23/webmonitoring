<?php

use Illuminate\Support\Facades\Schedule;

// Dispatch checks for every monitor whose interval has elapsed.
Schedule::command('monitors:check')->everyMinute()->withoutOverlapping();

// Remove check history older than MONITORING_RETENTION_DAYS.
Schedule::command('model:prune')->daily();

<?php

use Illuminate\Support\Facades\Schedule;

// Poll insurers for orders waiting on payment confirmation or policy issue.
// Needs the scheduler: `* * * * * php artisan schedule:run` (cron) or `php artisan schedule:work` locally.
Schedule::command('orders:poll-policies')->everyFiveMinutes()->withoutOverlapping();

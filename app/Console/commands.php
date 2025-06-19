<?php

use Illuminate\Foundation\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->command('reservations:generate-weekly')->dailyAt('00:05');
};

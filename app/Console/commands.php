<?php

use Illuminate\Foundation\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->command('reservations:generate-weekly')->weeklyOn(1, '01:00');
};

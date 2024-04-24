<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('retry:consume')->everyMinute()->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');
Schedule::command('command:master')->everyFiveMinutes()->sendOutputTo('/proc/1/fd/1');
// Schedule::command('command:master')->dailyAt/('16:40')->withoutOverlapping();

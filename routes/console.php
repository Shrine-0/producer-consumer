<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('retry:consume')->everyMinute()->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');
Schedule::command('command:master')->dailyAt('12:45')->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');
// Schedule::command('command:master')->dailyAt('01:00')->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');

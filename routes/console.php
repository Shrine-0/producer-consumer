<?php

use Illuminate\Support\Facades\Schedule;

// Schedule::command('retry:consume')->everyMinute()->withoutOverlapping()->sendOutputTo('/dev/stdout');
Schedule::command('command:master')->dailyAt('01:00')->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');

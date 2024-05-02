<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('retry:consume')->everyMinute()->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');
Schedule::command('command:master')->dailyAt('15:30')->withoutOverlapping()->sendOutputTo('/proc/1/fd/1');

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('retry:consume')->everyMinute()->withoutOverlapping();

Schedule::command('command:master')->dailyAt('16:50')->withoutOverlapping();
// Schedule::command('command:master')->everyThirtyMinutes()->withoutOverlapping();
// Schedule::command('command:sync-support-zone-customers', ['137', 'DHIKURE'])->everySecond()->withoutOverlapping();

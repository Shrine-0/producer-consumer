<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('retry:consume')->everyMinute()->withoutOverlapping();

// Schedule::command('command:master')->dailyAt('23:00')->withoutOverlapping();
Schedule::command('command:master')->everyThirtyMinutes()->withoutOverlapping();
// Schedule::command('command:sync-support-zone-customers', ['137', 'DHIKURE'])->everyFifteenSeconds()->withoutOverlapping();

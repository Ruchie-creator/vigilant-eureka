<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reports:weekly')->weeklyOn(1, '08:00');
Schedule::command('agents:run-scheduled')->everyTenMinutes()->withoutOverlapping(15);

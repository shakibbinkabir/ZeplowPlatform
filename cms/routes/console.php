<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:check-failed')->weekly()->mondays()->at('09:00');

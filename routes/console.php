<?php
use Illuminate\Support\Facades\Schedule;

// Run `php artisan schedule:work` in a separate terminal on localhost;
// use cron calling `schedule:run` each minute on hosting.
Schedule::command('kriuk:reconcile')->everyTenMinutes()->withoutOverlapping();

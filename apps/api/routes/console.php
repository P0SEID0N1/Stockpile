<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('portfolio:demo-account', function () {
    $this->info('Use /setup in the browser to create the first account.');
})->purpose('Display the Stockpile first-run setup hint');

Schedule::command('portfolio:refresh-quotes')
    ->weekdays()
    ->everyFiveMinutes()
    ->between('09:30', '16:30');

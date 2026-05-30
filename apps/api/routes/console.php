<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('portfolio:demo-account', function () {
    $this->info('Use /setup in the browser to create the first account.');
})->purpose('Display the Stockpile first-run setup hint');

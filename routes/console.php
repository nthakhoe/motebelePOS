<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use app\Jobs\RetryFailedReceiptsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//Schedule::job(new RetryFailedReceiptsJob)
//    ->everyMinute();

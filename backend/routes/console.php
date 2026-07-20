<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CreateInvoiceJob;
use App\Jobs\SendPaymentReminderJob;
use App\Jobs\AutoCancelUnpaidSubscriptions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    CreateInvoiceJob::dispatch();
})->dailyAt('00:00');

Schedule::job(new SendPaymentReminderJob())
    ->dailyAt('08:00')
    ->name('payment-reminder')
    ->withoutOverlapping();

Schedule::job(new AutoCancelUnpaidSubscriptions())
    ->dailyAt('10:00')
    ->name('subscription-cancellation')
    ->withoutOverlapping();


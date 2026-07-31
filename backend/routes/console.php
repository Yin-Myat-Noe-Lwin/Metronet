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
})->everyFiveMinutes()
  ->name('create-invoices')
  ->withoutOverlapping();

Schedule::job(new SendPaymentReminderJob())
    ->everyFiveMinutes()
    ->name('payment-reminder')
    ->withoutOverlapping();

Schedule::job(new AutoCancelUnpaidSubscriptions())
    ->everyFiveMinutes()
    ->name('subscription-cancellation')
    ->withoutOverlapping();


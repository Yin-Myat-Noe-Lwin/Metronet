<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CustomerRepository;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Services\AuthService;
use App\Contracts\Services\AuthServiceInterface;
use App\Repositories\InvoiceRepository;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Repositories\PlanRepository;
use App\Contracts\Repositories\PlanRepositoryInterface;
use App\Repositories\PaymentRepository;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Repositories\SubscriptionRepository;
use App\Contracts\Repositories\SubscriptionRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );

        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );

        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        $this->app->bind(
            PlanRepositoryInterface::class,
            PlanRepository::class
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );

        $this->app->bind(
            SubscriptionRepositoryInterface::class,
            SubscriptionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\CustomerRepository;
use App\Contracts\Repositories\CustomerRepositoryInterface;

use App\Services\AuthService;
use App\Contracts\Services\AuthServiceInterface;

use App\Repositories\InvoiceRepository;
use App\Contracts\Repositories\InvoiceRepositoryInterface;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

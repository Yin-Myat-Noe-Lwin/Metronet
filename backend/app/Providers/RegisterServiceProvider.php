<?php

    namespace App\Providers;

    use Illuminate\Support\ServiceProvider;

    use App\Repositories\CustomerRepository;
    use App\Repositories\Interfaces\CustomerRepositoryInterface;

    use App\Services\AuthService;
    use App\Services\Interfaces\AuthServiceInterface;

    class RepositoryServiceProvider extends ServiceProvider
    {
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
        }
    }
?>

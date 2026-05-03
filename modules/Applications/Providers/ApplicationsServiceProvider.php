<?php

namespace Modules\Applications\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Applications\Repositories\ApplicationRepositoryInterface;
use Modules\Applications\Repositories\EloquentApplicationRepository;

class ApplicationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApplicationRepositoryInterface::class, EloquentApplicationRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}

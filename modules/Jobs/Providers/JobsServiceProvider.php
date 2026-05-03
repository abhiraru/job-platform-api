<?php

namespace Modules\Jobs\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Jobs\Repositories\EloquentJobRepository;
use Modules\Jobs\Repositories\JobRepositoryInterface;

class JobsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JobRepositoryInterface::class, EloquentJobRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}

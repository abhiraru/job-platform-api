<?php

use App\Providers\AppServiceProvider;
use Modules\AI\Providers\AIServiceProvider;
use Modules\Applications\Providers\ApplicationsServiceProvider;
use Modules\Jobs\Providers\JobsServiceProvider;
use Modules\Profile\Providers\ProfileServiceProvider;

return [
    AppServiceProvider::class,
    AIServiceProvider::class,
    ApplicationsServiceProvider::class,
    JobsServiceProvider::class,
    ProfileServiceProvider::class,
];

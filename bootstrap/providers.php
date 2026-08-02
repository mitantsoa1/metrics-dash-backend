<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Domains\IAM\IAMServiceProvider::class,
    App\Domains\Instance\InstanceServiceProvider::class,
];

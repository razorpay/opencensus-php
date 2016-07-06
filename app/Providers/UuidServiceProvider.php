<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Uuid\Generator;

class UuidServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['uuid.generator'] = $this->app->share(function ($app) {
            return new Generator;
        });
    }
}

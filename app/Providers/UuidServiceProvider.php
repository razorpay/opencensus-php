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
        $this->app->singleton('uuid.generator', function ($app) {
            return new Generator;
        });
    }
}

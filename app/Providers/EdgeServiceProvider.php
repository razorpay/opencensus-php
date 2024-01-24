<?php

namespace App\Providers;

use App\Edge\ApiResponseForwarder;
use Illuminate\Support\ServiceProvider;

class EdgeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('edgeResponseForwarder', function ($app) {
            return new ApiResponseForwarder();
        });
    }
}

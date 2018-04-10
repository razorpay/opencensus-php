<?php

namespace RZP\Services\Metrics;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function register()
    {
        App::singleton('metrics', function()
        {
            return new Metrics(Config::get('metrics'));
        });
    }
}

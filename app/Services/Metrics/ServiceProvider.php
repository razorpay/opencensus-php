<?php

namespace RZP\Services\Metrics;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function register()
    {
        $this->app
             ->singleton(
                'metrics',
                function()
                {
                    return new MetricsManager($this->app);
                });
    }
}

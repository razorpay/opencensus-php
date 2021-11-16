<?php

namespace RZP\Models\User\RateLimitLoginSignup;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('rate_limit_login_signup', function ($app)
        {
            return new Service($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return array('rate_limit_login_signup');
    }
}


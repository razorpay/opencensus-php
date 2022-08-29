<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Http\RequestContext;

class RequestOauthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('request.ctx', RequestContext::class);
    }
}

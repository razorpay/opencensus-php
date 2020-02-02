<?php


namespace RZP\Http\Middleware;

use App;
use Config;
use Closure;
use Illuminate\Support\Str;

class SameSiteSession
{
    public function handle($request, Closure $next)
    {
        $app = App::getFacadeRoot();

        $userAgent = $app['request']->userAgent();

        $containsSafari = Str::contains($userAgent, ['Safari']);

        $containsSafariVersion = Str::contains($userAgent, ['Version']);

        if (($containsSafari and $containsSafariVersion) === false)
        {
            Config::set('session.same_site', 'none');
        }

        return $next($request);
    }
}

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

        $regex = "/(iPhone; CPU iPhone OS 1[0-2]|iPad; CPU OS 1[0-2]|iPod touch; CPU iPhone OS 1[0-2]|Macintosh; Intel Mac OS X.*Version\\x2F1[0-2].*Safari)/";

        if (preg_match($regex, $userAgent, $matches, PREG_OFFSET_CAPTURE, 0) === 0)
        {
            Config::set('session.same_site', 'none');
        }

        return $next($request);
    }
}

<?php

namespace RZP\Http\Middleware;

use App;
use Closure;
use ApiResponse;

class VerifyHttps
{
    protected function getProductionHosts()
    {
        $app = App::getFacadeRoot();

        $config = $app['config'];

        $productionHosts = $config->get('url.api_hosts');

        return $productionHosts;
    }

    public function handle($request, Closure $next)
    {
        $host = $request->getHttpHost();

        $productionHosts = $this->getProductionHosts();

        if ((in_array($host, $productionHosts, true)) and
            ($request->secure() === false))
        {
            return ApiResponse::onlyHttpsAllowed();
        }

        return $next($request);
    }
}

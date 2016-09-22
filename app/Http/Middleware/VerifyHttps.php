<?php

namespace RZP\Http\Middleware;

use App;
use Closure;

use RZP\Http\ApiResponse;


class VerifyHttps
{
    const PRODUCTION_ENVS = [
        'alpha',
        'beta',
        'production',
    ];

    protected function getProductionHosts()
    {
        $app = App::getFacadeRoot();

        $config = $app['config'];

        $productionHosts = [];

        $productionUrls = $config->get('url.api');

        foreach (self::PRODUCTION_ENVS as $env)
        {
            $productionHosts[] = parse_url($productionUrls[$env], PHP_URL_HOST);
        }

        return $productionHosts;
    }

    public function handle($request, Closure $next)
    {
        $host = $request->getHttpHost();

        $productionHosts = $this->getProductionHosts();

        if ((in_array($host, $productionHosts)) and
            ($request->secure() === false))
        {
            return ApiResponse::onlyHttpsAllowed();
        }

        return $next($request);
    }
}

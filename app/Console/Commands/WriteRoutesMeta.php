<?php

namespace RZP\Console\Commands;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;

use RZP\Http\P2pRoute;
use RZP\Http\Route;

class WriteRoutesMeta extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'rzp:write_routes_meta';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Writes routes meta to specified output handler function';

    /**
     * Once routes meta is prepared this function will called with same.
     * @var string
     */
    protected $writerFunc;

    protected $writerFuncJSON;

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->writerFunc = 'writeToNginxLuaFile';

        $this->writerFuncJSON = 'writeToJSONFile';
    }

    public function handle()
    {
        $this->info('Reading routes meta');
        // Format: [['methods', 'uri_regex', 'name', auth']].
        $routesMeta = [];
        $jsonRoutesMeta = [];
        $v2PrefixRoutesMap = [];

        for ($i = 0; $i < count(Route::$routesWithV2Prefix); $i++) {
            $v2PrefixRoutesMap[Route::$routesWithV2Prefix[$i]] = true;
        }


        foreach (Route::getApiRoutes() as $name => $meta)
        {
            $methods = $meta[0] === 'any' ? Router::$verbs : array_merge(explode(',', $meta[0]), ['HEAD']);
            $methods = array_map(function($v) { return strtoupper($v); }, $methods);

            $routesMeta[] = [
                'methods'   => $methods,
                'uri_regex' => laravelPatternToNonPosixRegex($meta[1]),
                'name'      => $name,
                'auth'      => routeNameToAuth($name),
            ];
        }


        foreach (Route::getApiRoutes() as $name => $meta)
        {
            $methods = $meta[0] === 'any' ? Router::$verbs : explode(',', $meta[0]);
            $methods = array_map(function($v) { return strtoupper($v); }, $methods);

            $apps = [];

            foreach (Route::$internalApps as $appName => $routes) {

                foreach ( $routes as $route )
                {
                    if ($route == $name)
                    {
                        $apps[] = $appName;
                    }
                }

            }

            $prefix = "/v1" ;

            if (isset($v2PrefixRoutesMap[$name])) {
                $prefix = "/v2";
            }

            $jsonRoutesMeta[] = [
                'methods'   => $methods,
                'uri_regex' => laravelPatternToEdgeRoute($meta[1],$prefix),
                'name'      => $name,
                'auth'      => routeNameToEdgeAuth($name),
                'apps' => $apps
            ];


        }

        $v2PrefixP2pRoutesMap = [];
        for ($i = 0; $i < count(P2pRoute::$routesWithV2Prefix); $i++) {
            $v2PrefixP2pRoutesMap[P2pRoute::$routesWithV2Prefix[$i]] = true;
        }

        foreach (P2pRoute::getP2PRoutes() as $name => $meta)
        {
            $methods = $meta[0] === 'any' ? Router::$verbs : explode(',', $meta[0]);
            $methods = array_map(function($v) { return strtoupper($v); }, $methods);

            $prefix = "/v1/upi" ;
            if (isset($v2PrefixP2pRoutesMap[$name])) {
                $prefix = "/v2/upi";
            }

            $jsonRoutesMeta[] = [
                'methods'   => $methods,
                'uri_regex' => laravelPatternToEdgeRoute($meta[1], $prefix),
                'name'      => $name,
                'auth'      => routeNameToEdgeAuthP2P($name),
            ];
        }


        $this->info("Writing routes meta using writer func: {$this->writerFunc}");
        $this->{$this->writerFunc}($routesMeta);

        $this->info(sprintf('Wrote total %d routes', count($routesMeta)));

        $this->info("Writing routes meta using writer func: {$this->writerFuncJSON}");
        $this->{$this->writerFuncJSON}($jsonRoutesMeta);

        $this->info(sprintf('Wrote total %d routes in JSON file', count($jsonRoutesMeta)));
    }

    protected function writeToNginxLuaFile(array $routesMeta)
    {
        $content = '-- routes_meta.lua'.PHP_EOL;
        $content .= '-- Auto generated. Do not edit.'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'local M = {}'.PHP_EOL;
        $content .= 'M.routes_meta = {'.PHP_EOL;
        foreach ($routesMeta as $index => $meta)
        {
            $content .= '   ['.$index.'] = {'.PHP_EOL;
            $content .= '       methods = {["'.implode('"] = true, ["', $meta['methods']).'"] = true},'.PHP_EOL;
            $content .= '       uri_regex = "'.$meta['uri_regex'].'",'.PHP_EOL;
            $content .= '       name = "'.$meta['name'].'",'.PHP_EOL;
            $content .= '       auth = "'.$meta['auth'].'",'.PHP_EOL;
            $content .= '   },'.PHP_EOL;
        }
        $content .= '}'.PHP_EOL;
        $content .= 'M.routes_meta_count = #M.routes_meta'.PHP_EOL;
        $content .= 'return M'.PHP_EOL;

        file_put_contents(app_path().'/../dockerconf/openresty/routes_meta.lua', $content);
    }

    protected function writeToJSONFile(array $routesMeta)
    {
        $content = ' ['.PHP_EOL;
        foreach ($routesMeta as $index => $meta)
        {

            $content .= '{'.PHP_EOL;
            $content .= '       "methods" : ["'.implode("\",\"", $meta['methods']).'"],'.PHP_EOL;
            $content .= '       "paths" : ["'.$meta['uri_regex'].'"],'.PHP_EOL;
            $content .= '       "name" : "'.$meta['name'].'",'.PHP_EOL;

            if (array_key_exists("apps", $meta)) {
                $content .= '       "auth" : ["'.implode("\",\"", $meta['auth']).'"],'.PHP_EOL;
                $content .= '       "apps" : ["'.implode("\",\"", $meta['apps']).'"]'.PHP_EOL;
            }
            else {
                $content .= '       "auth" : ["'.implode("\",\"", $meta['auth']).'"]'.PHP_EOL;
            }

            if ($index < sizeof($routesMeta)-1)
            {
                $content .= '   },'.PHP_EOL;
            } else {
                $content .= '   }'.PHP_EOL;
            }

        }

        $content .= ']'.PHP_EOL;

        file_put_contents('./routes_meta.json', $content);

    }
}

function laravelPatternToNonPosixRegex(string $pattern): string
{
    $pattern = preg_replace('/{path\?}/', '%/?(.*)', $pattern);
    $pattern = preg_replace('/{[A-Za-z0-9_]+\?}/', '%/?([^/]*)', $pattern);
    $pattern = preg_replace('/{[A-Za-z0-9_]+}/', '([^/]+)', $pattern);
    // Because everything is /v1/ is api service.
    // If not so someone please fix it here.
    return '^/v1/'.$pattern.'$';
}

function laravelPatternToEdgeRoute(string $pattern, string $prefix): string
{
    return '~' .$prefix . '/'.$pattern.'/?$';
}

function routeNameToAuth(string $name): string
{
    if(in_array($name, Route::$public) ||
        in_array($name, Route::$publicCallback))
    {
        return 'public';
    }
    else if(in_array($name, Route::$device))
    {
        return 'device';
    }
    else if(in_array($name, Route::$private))
    {
        return 'private';
    }
    else if(in_array($name, Route::$internal))
    {
        return 'privilege';
    }
    else if(in_array($name, Route::$proxy))
    {
        return 'private';
    }
    else if(in_array($name, Route::$admin))
    {
        return 'admin';
    }
    else if(in_array($name, Route::$direct))
    {
        return 'direct';
    }
    else
    {
        return 'unknown';
    }
}

function isInInternalApps(string $name) : bool {
    foreach (Route::$internalApps as $appName => $routes) {

        foreach ( $routes as $route )
        {
            if ($route == $name)
            {
                return true ;
            }
        }

    }

    return false ;
}

function isInInternalAppsWithAppName(string $name, array $apps) : bool {
    foreach (Route::$internalApps as $appName => $routes) {

        foreach ( $routes as $route )
        {
            if ($route == $name && in_array($appName, $apps))
            {
                return true ;
            }
        }

    }

    return false ;
}

function routeNameToEdgeAuth(string $name): array
{

    $authModes = [];

    if (in_array($name, Route::$private) || in_array($name, Route::getOAuthSpecificRoutes()) ) {
        $authModes[] = 'OAUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, Route::$internal) && isInInternalApps($name) ) {
        $authModes[] = 'INTERNAL_AUTH';
    }

    if (in_array($name, Route::$admin) && isInInternalAppsWithAppName($name, ['admin_dashboard']) ) {
        $authModes[] = 'ADMIN_AUTH';
    }

    if (in_array($name, Route::$private) ) {
        $authModes[] = 'MERCHANT_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, Route::$private) ) {
        $authModes[] = 'MERCHANT_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, Route::$private) ) {
        $authModes[] = 'PARTNER_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, Route::$private) && in_array($name, Route::$partnerCredentialsWithoutSubmerchantIdWhitelist)  ) {
        $authModes[] = 'PARTNER_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, Route::$public) || in_array($name, Route::$publicCallback) || in_array($name, Route::$direct) )  {
        $authModes[] = 'PUBLIC_OAUTH';
    }

    if (in_array($name, Route::$public) || in_array($name, Route::$publicCallback) || in_array($name, Route::$direct) )  {
        $authModes[] = 'PUBLIC_MERCHANT_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, Route::$public) || in_array($name, Route::$publicCallback) || in_array($name, Route::$direct) )  {
        $authModes[] = 'PUBLIC_MERCHANT_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, Route::$public) || in_array($name, Route::$publicCallback) || in_array($name, Route::$direct) )  {
        $authModes[] = 'PUBLIC_PARTNER_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, Route::$public) )  {
        $authModes[] = 'LEGACY_KEYLESS_AUTH';
    }

    if ( (in_array($name, Route::$private) || in_array($name, Route::$proxy) )  && (isInInternalApps($name)) ) {
        $authModes[] = 'PROXY_AUTH';
    }

    if ( in_array($name, Route::$device) ) {
        $authModes[] = 'DEVICE_AUTH';
    }

    if ( in_array($name, Route::$direct) ) {
        $authModes[] = 'DIRECT_AUTH';
    }

    return $authModes;


}

function routeNameToEdgeAuthP2P(string $name): array
{

    $authModes = [];

    if (in_array($name, P2pRoute::$private) ) {
        $authModes[] = 'MERCHANT_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$private) ) {
        $authModes[] = 'MERCHANT_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$private) ) {
        $authModes[] = 'PARTNER_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$private)  ) {
        $authModes[] = 'PARTNER_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$public) || in_array($name, P2pRoute::$direct) )  {
        $authModes[] = 'PUBLIC_OAUTH';
    }

    if (in_array($name, P2pRoute::$public) || in_array($name, P2pRoute::$direct) )  {
        $authModes[] = 'PUBLIC_MERCHANT_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$public)  || in_array($name, P2pRoute::$direct) )  {
        $authModes[] = 'PUBLIC_MERCHANT_AUTH_WITHOUT_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$public) || in_array($name, P2pRoute::$direct) )  {
        $authModes[] = 'PUBLIC_PARTNER_AUTH_WITH_IMPERSONATION';
    }

    if (in_array($name, P2pRoute::$public) )  {
        $authModes[] = 'LEGACY_KEYLESS_AUTH';
    }

    if ( (in_array($name, P2pRoute::$private) )  && (isInInternalApps($name)) ) {
        $authModes[] = 'PROXY_AUTH';
    }

    if ( in_array($name, P2pRoute::$device) ) {
        $authModes[] = 'DEVICE_AUTH';
    }

    if ( in_array($name, P2pRoute::$direct) ) {
        $authModes[] = 'DIRECT_AUTH';
    }

    return $authModes;


}

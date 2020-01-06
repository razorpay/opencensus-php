<?php

namespace RZP\Console\Commands;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;

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

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->writerFunc = 'writeToNginxLuaFile';
    }

    public function handle()
    {
        $this->info('Reading routes meta');
        // Format: [['methods', 'uri_regex', 'name', auth']].
        $routesMeta = [];
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

        $this->info("Writing routes meta using writer func: {$this->writerFunc}");
        $this->{$this->writerFunc}($routesMeta);

        $this->info(sprintf('Wrote total %d routes', count($routesMeta)));
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

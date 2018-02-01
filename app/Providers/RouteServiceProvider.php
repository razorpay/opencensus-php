<?php

namespace RZP\Providers;

use RZP\Http\Route;
use RZP\Http\Response\Response;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'RZP\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        $this->route = $this->app['api.route'];

        parent::boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('api.route', function($app)
        {
            return new Route($app);
        });

        $this->app->singleton('api.response', function ($app)
        {
            return new Response($app);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        $this->route->defineRootApiRoute();

        /**
         * Following params are as explained:
         * - prefix: v1 - All the routes defined have prefix v1
         * - namespace - All the routes defined have a controller and action.
         *     We only define the class name of the controller, the namespace
         *     is derived from this parameter.
         * - middleware:auth - All routes have Throttle and Authenticate
         *     middleware applied to them
         */
        $routeGroupGlobalParams = array(
            'prefix'        => 'v1',
            'namespace'     => $this->namespace,
            'middleware'    => ['throttle', 'auth', 'admin_access', 'workflow', 'event_tracker']);

        $router->group(
            $routeGroupGlobalParams,
            function ($router)
            {
                $this->mapApiRoutes($router);
            });

        $this->route->defineAllExtraRoutes();
    }

    protected function mapApiRoutes(Router $router)
    {
        $router->group(
            [],
            function ($router)
            {
                $this->route->addRouteGroups(['public',
                    'publicCallback',
                    'direct',
                    'admin',
                    'internal',
                    'private',
                    'proxy',
                    'device']);
            }
        );
    }
}

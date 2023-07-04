<?php

namespace RZP\Providers;

use RZP\Http\Route;
use RZP\Http\P2pRoute;
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
     * Route groups which are to be added for api
     *
     * @var array
     */
    protected $apiRoutes = [
        'public',
        'publicCallback',
        'direct',
        'admin',
        'internal',
        'private',
        'proxy',
        'device'
    ];

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        $this->route = $this->app['api.route'];

        $this->p2pRoute = $this->app['api.p2p.route'];

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

        $this->app->singleton('api.p2p.route', function($app)
        {
            return new P2pRoute($app);
        });

        $this->app->singleton('api.response', function ($app)
        {
            return new Response($app);
        });
        parent::register();
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

        $this->route->defineStatusApiRoute();

        $this->route->defineRobotsSubRoutes();

        /**
         * Following params are as explained:
         * - prefix: v1 - All the routes defined have prefix v1
         * - namespace - All the routes defined have a controller and action.
         *     We only define the class name of the controller, the namespace
         *     is derived from this parameter.
         * - middleware:auth - All routes have Throttle and Authenticate
         *     middleware applied to them
         */
        $routeGroupGlobalParams = [
            'prefix'        => 'v1',
            'namespace'     => $this->namespace,
            'middleware'    => [
                'product_identifier',
                'proxysql',
                'auth',
                'sdk_metric',
                'admin_access',
                'user_access',
                'workflow',
                'merchant_ip_filter',
                'request_context',
                'event_tracker',
            ],
        ];

        $router->group(
            $routeGroupGlobalParams,
            function ($router)
            {
                $this->mapApiRoutes($router);
            });

        $routeGroupGlobalParams['prefix'] = 'v2';
        $router->group(
            $routeGroupGlobalParams,
            function ($router)
            {
                $this->mapApiV2Routes($router);
            });

        $routeGroupP2pParams = [
            'prefix'        => 'v1/upi',
            'namespace'     => $this->namespace . '\\P2p',
            'middleware'    => [
                'product_identifier',
                'throttle',
                'auth',
                'p2p',
            ],
        ];

        $router->group(
            $routeGroupP2pParams,
            function ($router)
            {
                $this->mapP2pRoutes($router);
            });

        $this->route->defineAllExtraRoutes();
    }

    protected function mapApiRoutes(Router $router)
    {
        $router->group(
            [],
            function($router) {
                $this->route->addRouteGroups($this->apiRoutes);
            }
        );
    }

    protected function mapApiV2Routes(Router $router)
    {
        $router->group(
            [],
            function($router) {
                $this->route->addV2RouteGroups($this->apiRoutes);
            }
        );
    }

    protected function mapP2pRoutes(Router $router)
    {
        $router->group(
            [],
            function($router) {
                $this->p2pRoute->addRouteGroups([
                    'public',
                    'device',
                    'direct',
                    'private',
                ]);
            }
        );
    }
}

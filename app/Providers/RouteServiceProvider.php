<?php

namespace RZP\Providers;

use RZP\Http\Route;
use RZP\Http\Response\Response;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
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
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot(Router $router)
    {
        $this->route = $this->app['api.route'];

        parent::boot($router);
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
         * - namepsace - All the routes defined have a controller and action.
         *     We only define the class name of the controller, the namespace
         *     is derived from this parameter.
         * - middleware:auth - All routes have Authenticate middleware applied
         *     to them
         */
        $routeGroupGlobalParams = array(
            'prefix'        => 'v1',
            'namespace'     => $this->namespace,
            'middleware'    => 'auth');

        $router->group(
            $routeGroupGlobalParams,
            function ($router)
            {
                $this->mapWebRoutes($router);
                $this->mapApiRoutes($router);
            });

        $this->route->defineAllExtraRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function mapWebRoutes(Router $router)
    {
        $router->group(
            ['middleware' => 'web'],
            function ($router)
            {
                $this->route->addRouteGroups(['public', 'publicCallback', 'direct']);
            }
        );
    }

    protected function mapApiRoutes(Router $router)
    {
        $router->group(
            ['middleware' => 'api'],
            function ($router)
            {
                $this->route->addRouteGroups(['internal', 'private', 'proxy']);
            }
        );
    }
}
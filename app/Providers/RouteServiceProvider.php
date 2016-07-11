<?php

namespace RZP\Providers;

use RZP\Http\Route;
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
        parent::boot($router);
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        Route::setRouter($router);

        Route::defineRootApiRoute();

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

        Route::defineAllExtraRoutes();
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
                Route::addRoutes('public');
                Route::addRoutes('publicCallback');
                Route::addRoutes('direct');
            }
        );
    }

    protected function mapApiRoutes(Router $router)
    {
        $router->group(
            ['middleware' => 'web'],
            function ($router)
            {
                Route::addRoutes('internal');
                Route::addRoutes('private');
                Route::addRoutes('proxy');
            }
        );
    }
}
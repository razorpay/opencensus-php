<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use RZP\Http\Route;

class AdminAccess
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->repo = $app['repo'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {

        if ($this->ba->isAdminAuth())
        {
            // $adminAuthRoutes = Route::$admin;

            $routeName = $this->router->currentRouteName();

            $admin = $this->ba->getAdmin();

            $merchant = $this->getMerchant($request);

            $authorized = $this->policyChecker($routeName, $admin, $merchant);

            if (! $authorized)
            {
                return ApiResponse::routeNotFound();
            }
        }

        return $next($request);
    }

    private function getMerchant($request)
    {
        $params = $request->route()->parameters();

        $mid = $params['mid'];

        $repo = $this->app['repo'];

        $merchant = $repo->merchant->findOrFailPublic('10000000000000');

        return $merchant;
    }

    private function policyChecker($routeName, $admin, $merchant = null)
    {
        $adminAuthRoutes = Route::$adminPermission;

        $permissions = $adminAuthRoutes[$routeName];

        // We have the following:
        // - permission
        // - admin
        // - merchant (when available)

        // 1. Get all the roles of the first

        // $repo->admin->
        dd($admin->roles);

        return false;
    }
}

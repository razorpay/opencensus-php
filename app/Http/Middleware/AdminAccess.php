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

        // TODO: Move most of the logic to Admin/Admin/Repository

        // === Do a Role check

        // 1. Get all the permissions by all the roles first

        $roles = $admin->roles->toArray();

        $adminPermissions = $admin->getPermissionsList();

        // 2. Check if the specified permissions exist in our
        // generated white list

        $allowed = $this->checkPermissionsAllowed($permissions, $adminPermissions);

        // === Do a Group check.

        if ($allowed)
        {
            return true;
        }

        return false;
    }

    private function checkPermissionsAllowed($toCheck, $haystack)
    {
        foreach ($toCheck as $permission)
        {
            if (! in_array($permission, $haystack))
            {
                return false;
            }
        }

        return true;
    }
}

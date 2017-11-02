<?php

namespace App\Http\Middleware;

use Auth;
use App;
use Closure;
use Config;
use App\RZP\Permission;
use Illuminate\Contracts\Auth\Guard;
use App\Admin\Service as AdminService;

class AdminAccess {

    const WILDCARD_PERMISSION = '*';

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->app = App::getFacadeRoot();
        $this->router = $this->app['router'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        $routeName = $this->router->currentRouteName();

        if (isset($routeName) === false)
        {
            // No route name, proceed
            return $next($request);
        }

        $routePermissions = $this->getRoutePermissions($routeName);

        if (empty($routePermissions))
        {
            // No permission required, proceed
            return $next($request);
        }

        list($error, $adminData) = (new AdminService)->getAdminData($user);

        $authorized = $this->policyChecker($adminData, $routePermissions);

        if ($authorized === false)
        {
            return response()->json(array('success' => false, 'errors' => ['Unauthorised']), 401);
        }

        return $next($request);
    }

    private function policyChecker($admin, $routePermissions)
    {
        $adminPermissions = $admin['permissions'] ?? [];

        if (in_array(self::WILDCARD_PERMISSION, $routePermissions, true) === true)
        {
            $this->validateWildCardPermissionRules($routePermissions);

            return true;
        }

        foreach ($routePermissions as $permission)
        {
            if (in_array($permission, $adminPermissions, true) === false)
            {
                return false;
            }
        }

        return true;
    }

    private function getRoutePermissions(string $routeName)
    {
        $adminAuthRoutes = Config::get('admin-auth-routes');

        if (isset($adminAuthRoutes[$routeName]) === false)
        {
            return [];
        }

        return $adminAuthRoutes[$routeName];
    }

    private function validateWildCardPermissionRules(array $permissions)
    {
        // Check wildcard permission is the only one used in the list
        if ((in_array(self::WILDCARD_PERMISSION, $permissions) === true) and
            (count($permissions) > 1))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PERMISSIONS_USAGE);
        }
    }
}

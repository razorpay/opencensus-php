<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use Illuminate\Foundation\Application;
use RZP\Http\Route;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\UserRolesScope;
use RZP\Trace\TraceCode;

class UserAccess
{
    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * UserAccess constructor.
     *
     * @param \RZP\Http\Middleware\Application $app
     */
    public function __construct(Application $app)
    {
        $this->repo = $app['repo'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];

        $this->trace = $app['trace'];

        $this->userRoleScope = new UserRolesScope();
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request  $request
     * @param Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->currentRouteName();

        $this->ba->verifyAndSetUser();

        $routePolicyResponse = $this->validateUserRoutePolicy($route);

        if (empty($routePolicyResponse) === false)
        {
            return $routePolicyResponse;
        }

        /**
         * User Role to route validation will happen only in proxy auth.
         */
        if ($this->ba->isProxyAuth() === true)
        {
            $routeUserRolePolicy = $this->validateRouteUserRolesPolicy($route);

            if (empty($routeUserRolePolicy) === false)
            {
                return $routeUserRolePolicy;
            }
        }

        return $next($request);
    }

    private function validateUserRoutePolicy($route)
    {
        $user = $this->ba->getUser();

        if ((empty($user) === true) and
            (in_array($route, Route::$user, true) === true))
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }
    }

    private function validateRouteUserRolesPolicy($route)
    {
        $routeRoles = $this->userRoleScope->getRouteUserRoles($route);

        if (empty($routeRoles) === false)
        {
            $userRole = $this->getUserRole();

            if (empty($userRole) === true)
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING);
            }

            if (((is_array($routeRoles) === true) and (in_array($userRole, $routeRoles, true) === false)) or
                ($routeRoles !== $userRole))
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED);
            }
        }
    }

    private function getUserRole()
    {
        $dashboardHeaders = $this->ba->getDashboardHeaders();

        $userRole = $dashboardHeaders['user_role'] ?? null;

        return $userRole;
    }
}


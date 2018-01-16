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
     * Application instance
     *
     * @var Application
     */
    protected $app;

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
        $this->app = $app;

        $this->repo = $app['repo'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];

        $this->trace = $this->app['trace'];

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

        $routePolicyresponse = $this->validateUserRoutePolicy($route);

        if (empty($routePolicyresponse) === false)
        {
            return $routePolicyresponse;
        }

        $routeUserRolePolicy = $this->validateRouteUserRolesPolicy($route);

        if (empty($routeUserRolePolicy) === false)
        {
            return $routeUserRolePolicy;
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
        $routeRoles = $this->userRoleScope->getRouteRoles($route);

        if (empty($routeRoles) === false)
        {
            $userRole = $this->getUserRole();

            if (empty($userRole) === true)
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_USER_ROLE_NOT_PROVIDED);
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


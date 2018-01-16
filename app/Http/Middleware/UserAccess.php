<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use Illuminate\Foundation\Application;
use RZP\Http\Route;
use RZP\Exception;
use RZP\Error\ErrorCode;
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

        return;
    }

    private function validateRouteUserRolesPolicy($route)
    {
        if (in_array($route, Route::$user, true) === true)
        {
            $userRole = $this->getCurrentUserRole();

            if (empty($userRole) === true)
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_USER_ROLE_NOT_PROVIDED);
            }
        }
    }

    private function getCurrentUserRole()
    {
        $dashboardHeaders = $this->ba->getDashboardHeaders();

        $userRole = $dashboardHeaders['user_role'] ?? null;

        return $userRole;
    }
}


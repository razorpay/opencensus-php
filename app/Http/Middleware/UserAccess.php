<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Http\UserRolesScope;
use Illuminate\Foundation\Application;

class UserAccess
{
    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * \RZP\Base\RepositoryManager
     *
     * @var mixed
     */
    protected $repo;

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

        if ($routePolicyResponse !== null)
        {
            return $routePolicyResponse;
        }

        /**
         * User Role to route validation will happen only in proxy auth.
         */
        if ($this->ba->isProxyAuth() === true)
        {
            $routeUserRolePolicy = $this->validateRouteUserRolesPolicy($route);

            if ($routeUserRolePolicy !== null)
            {
                return $routeUserRolePolicy;
            }
        }

        return $next($request);
    }

    /**
     * Ensures that if a route needs user entity then this will check userwhitelist and validates.
     *
     * @param $route
     *
     * @return mixed
     */
    private function validateUserRoutePolicy($route)
    {
        $user = $this->ba->getUser();

        if ((empty($user) === true) and
            (in_array($route, Route::$userWhitelist, true) === true))
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }
    }

    private function validateRouteUserRolesPolicy($route)
    {
        $routeRoles = $this->userRoleScope->getRouteUserRoles($route);

        if ($routeRoles === null)
        {
            return;
        }

        $userRole = $this->getUserRole();

        if (empty($userRole) === true)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING);
        }

        if (in_array($userRole, $routeRoles, true) === false)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    private function getUserRole()
    {
        $dashboardHeaders = $this->ba->getDashboardHeaders();

        $userRole = $dashboardHeaders['user_role'] ?? null;

        return $userRole;
    }
}


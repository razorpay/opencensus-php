<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\UserRolesScope;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\Balance\Type as ProductType;

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
     * We'll verify the user basis the incoming (request) user_id
     * from dashboard headers and set that in BasicAuth context.
     * This will involve a DB call.
     *
     * Once the user has been verified, basis the route => [roles]
     * mapping we'll check whether the user is allowed to access the current
     * route basis his own role that comes in the dashboard header as well.
     *
     * This way we're able to implement ACL on dashboard for merchant users.
     *
     * Note: If the mapping doesn't contain role for the current route
     * then all the users of the merchant will get access to that specific route.
     * Also the entire logic is application on proxy auth (but not admin auth).
     *
     * @todo we should move from blacklisting to whitelisting.
     *
     * @param \Illuminate\Http\Request  $request
     * @param Closure  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->setRequestOriginProduct($request);

        if (($this->ba->isAdminAuth() === false) and
            ($this->ba->isStrictPrivateAuth() === false) and
            ($this->ba->isDashboardApp() === true))
        {
            $route = $this->router->currentRouteName();

            $this->ba->verifyAndSetUser();

            $routePolicyResponse = $this->validateUserRoutePolicy($route);

            // If there's an exception return that and fail
            if ($routePolicyResponse !== null)
            {
                return $routePolicyResponse;
            }

            // User role validation will only happen on proxy auth
            // when merchant (not admin) is hitting the route
            if ($this->ba->isProxyAuth() === true)
            {
                $routeUserRolePolicy = $this->validateRouteUserRolesPolicy($route);

                // If there's an exception then return and fail
                if ($routeUserRolePolicy !== null)
                {
                    return $routeUserRolePolicy;
                }
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

        // If the user is not set in the current BasicAuth context
        // and the route being hit exists in $userWhitelist then fail
        //
        // Effectively $userWhitelist becomes a list of routes that
        // makes user in current context mandatory.
        if ((empty($user) === true) and
            (in_array($route, Route::$userWhitelist, true) === true))
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }
    }

    /**
     * Check if the request origin is banking and set the banking product as banking in BA.
     * Don't need to add any other stricter checks becaues we have cors enabled for only BB domain and one request
     * uri on oauth app.
     *
     * @param $request
     */
    private function setRequestOriginProduct(Request $request)
    {
        $originDomain = $request->headers->get(RequestHeader::X_REQUEST_ORIGIN);

        $bankingOriginHost = parse_url(config('applications.banking_service_url'), PHP_URL_HOST);

        $requestOriginHost = parse_url($originDomain, PHP_URL_HOST);

        $product = ProductType::PRIMARY;

        if ($bankingOriginHost === $requestOriginHost)
        {
            $product = ProductType::BANKING;
        }

        $this->ba->setRequestOriginProduct($product);
    }

    private function validateRouteUserRolesPolicy($route)
    {
        $routeRoles = $this->userRoleScope->getRouteUserRoles($route);

        // Due to this we're effectively blacklisting and not whitelisting.
        // This means that if there's a route which doesn't have a role
        // mapping then all the users will get access to that by default.
        //
        // @todo change this to a whitelist instead of blacklist.
        if ($routeRoles === null)
        {
            return;
        }

        $userRole = $this->getUserRole();

        // If no role was sent in the headers
        if (empty($userRole) === true)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING);
        }

        // If the role sent in header is not allowed to hit the
        // route basis mapping fetched (above) from UserRolesScope
        if (in_array($userRole, $routeRoles, true) === false)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    private function getUserRole()
    {
        // @todo validate the user actually has the role sent
        // in headers since we don't want to trust dashboard

        $dashboardHeaders = $this->ba->getDashboardHeaders();

        if ($this->ba->isProductBanking() === true)
        {
            $userRole = $dashboardHeaders['user_banking_role'] ?? null;
        }
        else
        {
            $userRole = $dashboardHeaders['user_role'] ?? null;
        }

        return $userRole;
    }
}

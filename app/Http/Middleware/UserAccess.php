<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use RZP\Exception;
use RZP\Http\AxisCardsUser;
use RZP\Http\Route;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use RZP\Http\UserRolesScope;
use Illuminate\Http\Request;
use RZP\Http\RequestContext;
use Illuminate\Routing\Router;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\UserRolePermissionsMap;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\User\Metric as UserMetricCode;

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
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * @var RequestContext
     */
    protected $reqCtx;

    /**
     * @var Router
     */
    protected $router;

    const WILDCARD_PERMISSION = '*';

    private $razorx;

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

        $this->razorx = $app->razorx;

        $this->userRoleScope = new UserRolesScope();

        $this->trace = $app['trace'];

        $this->reqCtx = $app['request.ctx'];
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
        if (($this->ba->isAdminAuth() === false) and
            ($this->ba->isStrictPrivateAuth() === false) and
            ($this->ba->isInternalApp() === true))
        {
            $route = $this->router->currentRouteName();

            $this->ba->verifyAndSetUser();

            // Check for routes requiring 2FA validation
            if ((array_key_exists($route, Route::$twoFactorAuthRequiredRoutes) === true) and
                (in_array($this->ba->getMode(), Route::$twoFactorAuthRequiredRoutes[$route]) === true))
            {
                $this->validateUser2FaStatus();
            }

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
                $userAccessResponse = $this->validateUserAccess($route);

                // If there's an exception then return and fail
                if ($userAccessResponse !== null)
                {
                    return $userAccessResponse;
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

    private function validateUserAccess(string $route)
    {
        $userAccessResponse = $this->ba->isProductBanking() ? $this->validateBankingUserAccess($route) :
                                                              $this->validateRouteUserRolesPolicy($route);
        return $userAccessResponse;
    }

    private function validateRouteUserRolesPolicy($route)
    {
        $routeRoles = $this->userRoleScope->getRouteUserRoles($route);
        $userRole   = $this->ba->getUserRole();

        if ($routeRoles === null)
        {
            $this->trace->warning(TraceCode::USER_ACCESS_MISSING_ROUTE_ROLE_MAPPING,
                ['route' => $route, 'role' => $userRole]);
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        // If no role was sent in the headers
        if (empty($userRole) === true)
        {
            if ($this->userRoleScope->isRouteAccessibleWithoutRole($route))
            {
                return;
            }

            $this->trace->warning(TraceCode::USER_ACCESS_ROLE_MISSING, ['route' => $route]);
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING);
        }

        // If the role sent in header is not allowed to hit the
        // route basis mapping fetched (above) from UserRolesScope
        if (in_array($userRole, $routeRoles, true) === false)
        {
            $this->trace->warning(TraceCode::USER_ACCESS_AUTHZ_FAILED, ['route' => $route, 'role' => $userRole]);
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    private function validateBankingUserAccess(string $route)
    {
        try
        {
            $this->validateBankingUserRoutePolicy($route);
        }
        catch (\Throwable $e)
        {
            // TODO: This is added to identify impact on other clients if unauthorised requests are blocked
            $variant = $this->razorx->getTreatment($this->ba->getMerchant()->getId(),
                                                   RazorxTreatment::RAZORPAY_X_ACL_DENY_UNAUTHORISED,
                                                   $this->ba->getMode());

            $this->trace->traceException($e,
                Trace::INFO,
                TraceCode::BANKING_ACCOUNT_USER_PERMISSION_ERROR,
                [
                    'experiment' => $variant,
                    'user_role'  => $this->ba->getUserRole(),
                ]);

            if (strtolower($variant) === 'on')
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED);
            }
        }
    }

    private function validateUser2FaStatus()
    {
        $user = $this->ba->getUser();
        $userId = $user->getId();

        $merchantId = $this->ba->getMerchantId();

        // currently keeping this feature under razorx
        // keeping this razorx under merchantId for consistency with dashboard
        // dashboard sends all razorx request with merchant context
        $user2FaCheckExperimentVariant = $this->razorx->getTreatment(
            $merchantId,
            RazorxTreatment::VALIDATE_USER_2FA_STATUS,
            $this->ba->getMode());

        if (strtolower($user2FaCheckExperimentVariant) === 'on')
        {
            $user2FaVerified = $this->reqCtx->getUser2FAVerified();

            $this->trace->count(UserMetricCode::USER_ACCESS_CRITICAL_ROUTE, [
                'rote_name'             => $this->router->currentRouteName(),
                'user_2fa_verified'     => $user2FaVerified,
            ]);

            if ($user2FaVerified === false)
            {
                $errorData = [
                    'internal_error_code'       => ErrorCode::BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED,
                    'user'              => [
                        'id'                => $userId,
                        'contact_mobile'   => $user->getMaskedContactMobile(),
                    ]
                ];

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED,
                    null,
                    $errorData);
            }
        }

    }

    /**
     * Banking API request will be validated based on whitelisted permissions
     * Roles are assigned with set of permissions and route is also assigned with permission
     * If user role has current route permission then allow otherwise deny
     *
     * @param $route
     *
     * @throws BadRequestException
     */
    private function validateBankingUserRoutePolicy($route)
    {
        $userRole = $this->getUserRole();

        $routePermission = $this->getRoutePermission($route);

        // Allow route to all roles having wildcard permission
        if ($routePermission === self::WILDCARD_PERMISSION)
        {
            return;
        }
        //TODO Resolving Org
        //As of now we are hardcoding org ,in next phase when we are able to resolve the org from request
        //then that will be passed in the params itself

        $org = 'RAZORPAY_X';
        // If role doesn't have route permission then deny otherwise allow
        //Checking if its a Axis User or a X User below
        $isRoleValid = true;
        switch ($org)
        {
            case 'RAZORPAY_X':
            {
                if (UserRolePermissionsMap::isInvalidRolePermission($userRole, $routePermission))
                {
                    $isRoleValid = false;
                }
                break;
            }
            case 'AXIS_CORPORATE':
            {
                if (AxisCardsUser::isInvalidRolePermission($userRole, $routePermission))
                {
                    $isRoleValid = false;
                }
                break;
            }
        }
        if($isRoleValid !== true){
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    /**
     * @throws BadRequestException
     */
    private function getUserRole()
    {
        $userRole = $this->ba->getUserRole();

        if (empty($userRole) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING);
        }

        return $userRole;
    }

    /**
     * @param string $routeName
     *
     * @return mixed
     * @throws BadRequestException
     */
    private function getRoutePermission(string $routeName)
    {
        $routePermissionList = Route::$bankingRoutePermissions;

        if (isset($routePermissionList[$routeName]) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BANKING_ROUTE_PERMISSION_MISSING);
        }

        return $routePermissionList[$routeName];
    }
}

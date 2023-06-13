<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Http\AxisCardsUser;
use RZP\Http\Route;
use RZP\Http\LmsUserRolePermissionsMap;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Attribute\Group;
use RZP\Models\User\Entity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use RZP\Http\UserRolesScope;
use Illuminate\Http\Request;
use RZP\Http\RequestContext;
use Illuminate\Routing\Router;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\AccessAuthorizationService;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\UserRolePermissionsMap;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\User\Metric as UserMetricCode;
use RZP\Models\Merchant\Attribute;

class UserAccess
{
    //TODO: decide where to place these constants
    const MERCHANT_ALLOWED     = 'allowed';
    const MERCHANT_DENIED      = 'denied';
    const MERCHANT_NO_RULES    = 'no_rules';

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
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     *
     * @return mixed
     * @todo we should move from blacklisting to whitelisting.
     *
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
        if ($this->ba->isBankLms())
        {
            $userAccessResponse = $this->validateBankLmsUserAccess($route);
        }
        else if ($this->ba->getRequestOriginProduct() === Product::BANKING)
        {
            $userAccessResponse = $this->validateBankingUserAccess($route);
        }
        else
        {
            $userAccessResponse = $this->validateRouteUserRolesPolicy($route);
        }

        return $userAccessResponse;
    }

    private function validateRouteUserRolesPolicy($route)
    {
        $routeRoles = $this->userRoleScope->getRouteUserRoles($route);
        $userRole = $this->ba->getUserRole();


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

    private function isCACDisabledForGithubTestSuites() :bool
    {
        $isCACExperimentEnabled = $this->razorx->getTreatment($this->ba->getMerchant()->getId(),
            RazorxTreatment::DISABLE_CAC_FOR_GITHUB_TEST_SUITES,
            MODE::LIVE);

        return $isCACExperimentEnabled === RazorxTreatment::RAZORX_VARIANT_ON;
    }

    private function validateBankLmsUserAccess(string $route)
    {
        try
        {
            $this->validateBankLmsUserRoutePolicy($route);
        }
        catch (\Throwable $e)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    private function validateBankingUserAccess(string $route)
    {
        try
        {
            $isBankingDisabledRoute = $this->isBankingDisabledRoute($route);

            if ($isBankingDisabledRoute === true)
            {
                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING);
            }

            // check if cac is enabled
            $isCACEnabled = $this->ba->getMerchant()->isCACEnabled();

            // check if disable cac for gihub test suites is on. We are checking this in order to bypass the
            // authorization from authz when the test cases are running via github actions.
            $isCACDisabledForGithubTestSuites = $this->isCACDisabledForGithubTestSuites();

            $this->trace->info(TraceCode::CAC_EXPERIMENT_STATUS,
                [
                    'route' => $route,
                    'cac_status' => $isCACEnabled,
                    'merchant_id' => $this->ba->getMerchant()->getId()
                ]);

            if ($isCACEnabled === true && $isCACDisabledForGithubTestSuites === false)
            {
                $this->validateBankingUserRoutePolicyV2($route);
            }
            else {
                $this->validateBankingUserRoutePolicy($route);
            }
        }
        catch (\Throwable $e)
        {

           if ($this->hasMerchantRulesSupport($route))
           {
               return ApiResponse::unauthorized(
                   ErrorCode::BAD_REQUEST_UNAUTHORIZED);
           }

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

    private function isBankingDisabledRoute(string $route): bool
    {
        // if the feature is enabled, then only the routes will be blocked
        $variant = $this->razorx->getTreatment($this->ba->getMerchant()->getId(),
                                               RazorxTreatment::BLOCK_BANKING_REQUESTS,
                                               $this->ba->getMode());

        if (strtolower($variant) === 'on')
        {
            if (in_array($route, Route::$bankingDisabledRoutes) === true)
            {
                return true;
            }
        }

        return false;
    }

    private function validateUser2FaStatus()
    {
        $user = $this->ba->getUser();
        $userId = $user->getId();

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

    /**
     * Banking API request will be validated based on whitelisted permissions
     * Roles are assigned with set of permissions and route is also assigned with permission
     * If user role has current route permission then allow otherwise deny
     *
     * @param $route
     *
     * @throws BadRequestException
     */

    private function validateBankingUserRoutePolicyV2($route)
    {
        $userRole = $this->getUserRole();

        $routePermission = $this->getRoutePermission($route);

        // Allow route to all roles having wildcard permission
        if ($routePermission === self::WILDCARD_PERMISSION)
        {
            return;
        }

        $authzRoles = (new \RZP\Models\RoleAccessPolicyMap\Service())->getAuthzRolesForRoleId($userRole);

        $isRoleAllowedAccess = AccessAuthorizationService::hasAccessAllowedV2($routePermission, $authzRoles);

        if ($isRoleAllowedAccess !== true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    /**
     * @throws BadRequestException
     */
    private function validateBankLmsUserRoutePolicy($route)
    {
        $userRole = $this->getUserRole();

        $routePermission = $this->getBankLmsRoutePermission($route);

        // Allow route to all roles having wildcard permission
        if ($routePermission === self::WILDCARD_PERMISSION)
        {
            return;
        }

        $isRoleAllowedAccess = true;

        if (LmsUserRolePermissionsMap::isInvalidLmsRolePermission($userRole, $routePermission))
        {
            $isRoleAllowedAccess = false;
        }

        if ($isRoleAllowedAccess !== true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

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
        $isRoleAllowedAccess = true;

        //if custom permission not found, fall back to the system role
            switch ($org) {
                case 'RAZORPAY_X':
                {
                    $hasMerchantAllowedAccess = $this->verifyMerchantRules($userRole, $route, $routePermission);

                    if ($hasMerchantAllowedAccess == self::MERCHANT_ALLOWED)
                    {
                        $isRoleAllowedAccess = true;
                        break;
                    }
                    if ($hasMerchantAllowedAccess == self::MERCHANT_DENIED)
                    {
                        $isRoleAllowedAccess = false;
                        break;
                    }

                    $accessAuthViaAuthZEnabled = $this->ba->getMerchant()->isFeatureEnabled(Features::AUTHORIZE_VIA_AUTHZ);

                    if($hasMerchantAllowedAccess == self::MERCHANT_NO_RULES
                        && $accessAuthViaAuthZEnabled == true
                        && AccessAuthorizationService::isAuthorizationEnabled($route))
                    {
                            $isRoleAllowedAccess = AccessAuthorizationService::hasAccessAllowed($route, [$userRole]);
                            break;
                    }
                    if ($hasMerchantAllowedAccess == self::MERCHANT_NO_RULES
                        && UserRolePermissionsMap::isInvalidRolePermission($userRole, $routePermission))
                    {
                        $isRoleAllowedAccess = false;
                        break;
                    }
                    break;
                }
                case 'AXIS_CORPORATE':
                {
                    if (AxisCardsUser::isInvalidRolePermission($userRole, $routePermission)) {
                        $isRoleAllowedAccess = false;
                    }
                    break;
                }
            }


        if ($isRoleAllowedAccess !== true)
        {
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
    private function getBankLmsRoutePermission(string $routeName)
    {
        $routePermissionList = Route::$bankLmsRoutePermissions;

        if (isset($routePermissionList[$routeName]) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BANKING_ROUTE_PERMISSION_MISSING);
        }

        return $routePermissionList[$routeName];
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

    //TODO: decide if this list should be a part of Route.php
    private function hasMerchantRulesSupport(string $route)
    {
        //add routes to this array to allow merchant control access policies
        $routesWithMerchantRules = [
            'transaction_statement_fetch',
            'transaction_statement_fetch_multiple',
            'transaction_statement_fetch_multiple_for_banking',
        ];
        return in_array($route, $routesWithMerchantRules);
    }

    private function verifyMerchantRules(string $userRole, string $route, string $routePermission)
    {
        //merchant controlled access policies are at route permissions levels not at route levels
        $attributeGroupNameMap = [
            Permission::VIEW_TRANSACTION_STATEMENT => 'x_transaction_view',
        ];

        //merchant rules are not supported for this route
        if (!$this->hasMerchantRulesSupport($route))
        {
            return self::MERCHANT_NO_RULES;
        }

        try
        {
            $merchant = $this->ba->getMerchant();
            $merchantAttributes = (new Attribute\Core())->fetch(
                $merchant,
                $this->ba->getRequestOriginProduct(),
                $attributeGroupNameMap[$routePermission],
                $userRole);
        }
        catch (\Exception $e)
        {
            //zero entries in the table for this merchant for this route
            return self::MERCHANT_NO_RULES;
        }

        return $merchantAttributes['value'] === 'true' ? self::MERCHANT_ALLOWED : self::MERCHANT_DENIED;

    }
}

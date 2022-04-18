<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Application;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Http\RouteRoleScope;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;

class AdminAccess
{
    const WILDCARD_PERMISSION = '*';

    const ORG_HEADER_KEY = 'X-Org-Id';

    const CROSS_ORG_HEADER_KEY = 'X-Cross-Org-Id';

    const ORG_HOSTNAME_HEADER_KEY = 'X-Org-Hostname';

    protected $app;
    protected $repo;

    /** @var BasicAuth */
    protected $ba;

    /** @var Router */
    protected $router;

    /** @var Trace */
    protected $trace;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->repo = $app['repo'];

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    public function handle($request, Closure $next)
    {
        $orgId = $this->getOrgIdForRoute($request);

        // setting here so app auth also uses orgId.
        $this->ba->setOrgId($orgId);

        $this->setOrgType($orgId);

        if ($this->ba->isAdminAuth() === false)
        {
            return $next($request);
        }

        /** @var Admin\Admin\Entity $admin */
        $admin = $this->ba->getAdmin();

        if ($admin->isLocked() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED);
        }

        $routeName = $this->router->currentRouteName();

        if ($admin->isDisabled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED);
        }

        $this->validateAdminBelongsToSameOrg($routeName, $admin, $request);

        $merchant = $this->getMerchant($request);

        $authorized = $this->policyChecker($routeName, $admin, $merchant);

        if ($authorized === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return $next($request);
    }

    private function setOrgType(string $orgId = null)
    {
        if (empty($orgId) === false)
        {
            Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            /** @var Org\Entity $org */
            $org = $this->repo->org->findOrFailPublic($orgId);

            $this->ba->setOrgType($org->getType());
        }
    }

    private function getRoutePermission(string $routeName)
    {
        $routePermissionList = Route::$routePermission;

        if (isset($routePermissionList[$routeName]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PERMISSION_ERROR);
        }

        return $routePermissionList[$routeName];
    }

    private function validateAdminBelongsToSameOrg(string $routeName, Admin\Admin\Entity $admin, $request)
    {
        if (in_array($routeName, static::getExcludedRoutes(), true) === true)
        {
            return;
        }

        // Some orgs have global access to edit other org over specific routes
        if ((in_array($routeName, Route::$crossOrgRoutes, true) === true) and
            ($admin->org->isCrossOrgAccessEnabled() === true))
        {
            $crossOrgId = $this->getCrossOrgIdForRoute($request);

            $this->ba->setCrossOrgId($crossOrgId);

            return true;
        }

        // Fetch public org Id from uri
        $orgId = $this->ba->getOrgId();

        // $admin->getPublicOrgId cannot be null here because admin has to be associated with org.
        if ($orgId !== $admin->getPublicOrgId())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }
    }

    /**
     * Fetching crossOrgId from the headers of request.
     *
     * @param $request
     *
     * @return crossOrgId.
     */
    private function getCrossOrgIdForRoute(Request $request)
    {
        return $request->headers->get(self::CROSS_ORG_HEADER_KEY);
    }

    /**
     * Get the OrgId from different source and validate it.
     * Precedence of sources
     * 1. Route
     * 2. Params or PostData
     * 3. Headers
     *
     * @param request
     *
     * @return orgId
     */
    private function getOrgIdForRoute($request)
    {
        $orgId = $this->router->current()->parameter('orgId');

        if ($orgId === null)
        {
            $orgId = $request->input('org_id');
        }

        if ($orgId === null)
        {
            $orgId = $request->headers->get(self::ORG_HEADER_KEY);
        }

        $validateOrgId = $orgId;

        if(empty($validateOrgId) === false)
        {
            $this->repo->org->isValidOrg(Org\Entity::verifyIdAndStripSign($validateOrgId));
        }

        // Resolving OrgId from hostname.
        if ($orgId === null)
        {
            $orgHostname = $request->headers->get(self::ORG_HOSTNAME_HEADER_KEY);

            if (!empty($orgHostname))
            {
                /** @var Org\Entity $org */
                $org = $this->ba->fetchOrgByHostname($orgHostname);

                $orgId = $org->getPublicId();

                $this->ba->setOrgHostName($orgHostname);
            }
        }

        return $orgId;
    }

    /*
     * Routes excluded form orgId check
     */
    private static function getExcludedRoutes()
    {
        return [
            'org_create',
            'org_get_multiple',
            'org_fieldmap_create',
            'org_fieldmap_get_multiple',
            'org_fieldmap_get',
            'org_fieldmap_get_by_entity',
            'org_fieldmap_edit',
            'org_fieldmap_delete',
            // Permission API are not exposed and org agnostic
            'permission_get',
            'permission_create',
            'permission_get_by_type',
            'permission_delete',
            'permission_edit',
        ];
    }

    private function getMerchant($request)
    {
        $params = $request->route()->parameters();

        $merchant = null;

        if (empty($params['mid']) === false)
        {
            $mid = $params['mid'];

            $merchant = $this->repo->merchant->findOrFailPublic($mid);
        }
        else
        {
            // Getting from ba Merchant because X-Razorpay-Account will set Merchant.
            $merchant = $this->ba->getMerchant();
        }

        return $merchant;
    }

    private function policyChecker($routeName, $admin, $merchant = null)
    {
        $permission = $this->getRoutePermission($routeName);

        // We have the following:
        // - permission
        // - admin
        // - merchant (when available)

        // TODO: Move most of the logic to Admin/Admin/Repository

        // === Do a Role check

        // 1. Get all the permissions by all the roles first

        $adminRolesPermissions = $admin->getRolesAndPermissionsList();
        $adminRoles = $adminRolesPermissions['roles'];
        $adminPermissions = $adminRolesPermissions['permissions'];

        // set admin roles in passport
        $this->ba->setPassportRoles($adminRoles);

        // For Razorpay org admins, check if the admin has the roles required to access this routeName
        if (($admin->getOrgId() === Org\Entity::RAZORPAY_ORG_ID) and
            ($this->checkTenantRoleAllowed($routeName, $adminRoles) === false))
        {
            $this->trace->info(TraceCode::TENANT_ROUTE_ACCESS_DENIED, ['route' => $routeName, 'admin_roles' => $adminRoles]);
            return false;
        }

        // 2. Check if the specified permissions exist in our
        // generated white list

        $policyPassed = $this->checkPermissionAllowed($permission, $adminPermissions, $routeName);

        if ($policyPassed === true && $merchant)
        {
            //This skips the check where we validate admin has access to merchant or not. This was added to
            // allow a sales spoc to initiate CA onboarding for all merchants on LMS (even if they don’t have
            // access to them). For almost all admin routes below negative check will pass, because they won’t
            // be present in the skipMerchantAccessCheckOnSpecificAdminAuthRoutes
            if(in_array($routeName, Route::$skipMerchantAccessCheckOnSpecificAdminAuthRoutes, true) === false)
            {
                $policyPassed = (new Admin\Group\Core)->groupCheck($admin, $merchant);
            }
        }

        return $policyPassed;
    }

    /**
     * Certain routes are restricted to be accessed by defined tenant roles only.
     * This enforcement is in addition to existing permission-based checks, and comes prior.
     *
     * This allows us to do things like enforce RBAC for resources that need to be accessed by admins under
     * a certain legal entity. Ex - /payments/* routes to be access by payments BU admins alone.
     * @param string $routeName
     * @param array  $adminRoles
     *
     * @return bool
     */
    private function checkTenantRoleAllowed(string $routeName, array $adminRoles): bool
    {
        $routeRoles = RouteRoleScope::getRoles($routeName);

        // If no roles are defined for the route, we assume that no enforcement
        // is needed. i.e. this is currently an allowlist (while we're rolling it out)
        // TODO: ideally, move this to a denylist once this goes fully live.
        if ($routeRoles === null)
        {
            $this->trace->info(TraceCode::TENANT_ROUTE_ROLES_NOT_MAPPED, ['route' => $routeName]);
            return true;
        }

        $this->trace->info(TraceCode::TENANT_ROUTE_ROLES_RESOLVED, ['route' => $routeName, 'route_roles' => $routeRoles]);
        return count(array_intersect($routeRoles, $adminRoles)) > 0;
    }

    private function checkPermissionAllowed(string $toCheck, array $haystack, string $routeName)
    {
        if ($toCheck === self::WILDCARD_PERMISSION)
        {
           // Previously, admin routes could be accessed without permission if there '*'(wildcard_permission) entry
           // for that route in $routePermissionMap
           // Now we want every route that needs to be accessible via admin auth needs to have a specific permission
           // Therefore deny access to admin route if it has a wildcard permission
           return false;
        }

        // Check if the required permission is present in the
        // list of admin permissions
        if (in_array($toCheck, $haystack, true) === true)
        {
            return true;
        }

        // Access denied!
        return false;
    }
}

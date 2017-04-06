<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use Request;
use RZP\Http\Route;
use RZP\Models\Admin;
use RZP\Exception;
use RZP\Error\ErrorCode;

class AdminAccess
{
    const WILDCARD_PERMISSION = '*';

    const ORG_HEADER_KEY = 'X-Org-Id';

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
                return ApiResponse::routeNotFound();
            }
        }

        return $next($request);
    }

    private function getRoutePermissions(string $routeName)
    {
        $adminAuthRoutes = Route::$adminPermission;

        if (isset($adminAuthRoutes[$routeName]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PERMISSION_ERROR);
        }

        return $adminAuthRoutes[$routeName];
    }

    private function validateAdminBelongsToSameOrg($routeName, $admin, $request)
    {
        if (in_array($routeName, static::getExcludedRoutes(), true) === true)
        {
            return;
        }

        // Some orgs have global access to edit other org over specific routes
        if ((in_array($routeName, Route::$crossOrgRoutes, true) === true) and
            ($admin->org->isCrossOrgAccessEnabled() === true))
        {
            return true;
        }

        // Fetch public org Id from uri
        $orgId = $this->getOrgIdForRoute($request);

        if ($orgId !== $admin->getPublicOrgId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }
    }

    /**
     * Get the OrgId from different source.
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
        $orgId = $this->router->current()->getParameter('orgId');

        if ($orgId === null)
        {
            $orgId = $request->input('org_id');
        }

        if ($orgId === null)
        {
            $orgId = $request->headers->get(self::ORG_HEADER_KEY);
        }

        if ($orgId === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORG_ID_REQUIRED);
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

            // workflow
            'workflow_create',
            'workflow_get',
            'workflow_update',
            'workflow_delete',
            'workflow_step_get_multiple',
            'workflow_step_create',
            'workflow_step_get',
            'workflow_action_get_multiple',
            'action_checker_create',
            'workflow_action_details',
            'workflow_action_states',
            'action_checker_multiple',
            'action_checker_get',
            'action_diff_create',
            'action_diff_get',
            'action_request_execute',
            'action_comment_create',
            'action_comment_fetch',
            'workflow_get_actions_for_checker',
            'workflow_get_actions_by_maker',
        ];
    }

    private function getMerchant($request)
    {
        $params = $request->route()->parameters();

        $repo = $this->app['repo'];

        if (isset($params['mid']))
        {
            $mid = $params['mid'];

            $merchant = $repo->merchant->findOrFailPublic($mid);

            return $merchant;
        }

        return null;
    }

    private function policyChecker($routeName, $admin, $merchant = null)
    {
        $permissions = $this->getRoutePermissions($routeName);

        // We have the following:
        // - permission
        // - admin
        // - merchant (when available)

        // TODO: Move most of the logic to Admin/Admin/Repository

        // === Do a Role check

        // 1. Get all the permissions by all the roles first

        $adminPermissions = $admin->getPermissionsList();

        // 2. Check if the specified permissions exist in our
        // generated white list

        $policyPassed = $this->checkPermissionsAllowed(
            $permissions, $adminPermissions);

        if ($policyPassed === true)
        {
            if ($merchant)
            {
                $hasMerchantAccess = $this->groupCheck($admin, $merchant);

                if ($hasMerchantAccess)
                {
                    $policyPassed = true;
                }
                else
                {
                    $policyPassed = false;
                }
            }
        }

        return $policyPassed;
    }

    private function checkPermissionsAllowed($toCheck, $haystack)
    {
        if (in_array(self::WILDCARD_PERMISSION, $toCheck, true) === true)
        {
            $this->validateWildCardPermissionRules($toCheck);

            return true;
        }

        foreach ($toCheck as $permission)
        {
            if (in_array($permission, $haystack, true) === false)
            {
                return false;
            }
        }

        return true;
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

    private function groupCheck($admin, $merchant)
    {
        // TODO: Enforce there's no cycle in the graph (while creation/assigning)

        // 1. Get all the required groups and admins for the $admin

        // Get all groups and admins required to look into in case
        // there's a hierarchy (or actually a graph)
        $nodes = $this->getAllNodes($admin);

        // $nodes['groups'], $nodes['admins']

        // dd($nodes);

        $merchantIds = $this->getMerchantIdsOfNodes($nodes);

        // dd($merchantIds);

        if (in_array($merchant->id, $merchantIds))
        {
            return true;
        }

        return false;
    }

    private function getAllNodes($admin)
    {
        // 1. Get all the groups of the admin

        $groups = $admin->groups->toArray();

        $parentGroupIds = [];

        foreach ($groups as $group)
        {
            $parentGroupIds[] = $group['id'];
        }

        // We have all the parent group IDs now
        // 2. Get all the sub groups of the parent groups now

        // To get the sub/child groups, raw query would be something
        // like this:
        // SELECT entity_id FROM group_map WHERE group_id IN ($groupIds)
        // This gets all the groups that belong to (child/sub) $groupIds

        $allSubGroupIds = [];
        $allSubAdminIds = [];

        $groupIds = $parentGroupIds;

        $exit = false;

        while (!$exit)
        {
            $subGroups = \DB::table('group_map')
                            ->whereIn('group_id', $groupIds)
                            ->where('entity_type', 'group')
                            ->get();

            $groupIds = [];

            foreach ($subGroups as $subGroup)
            {
                $groupIds[] = $subGroup->entity_id;

                $allSubGroupIds[] = $subGroup->entity_id;
            }

            if (count($groupIds) === 0)
            {
                $exit = true;
            }

            // Also get all the admins

            $subAdmins = \DB::table('group_map')
                            ->whereIn('group_id', $groupIds)
                            ->where('entity_type', 'admin')
                            ->get();

            foreach ($subAdmins as $subAdmin)
            {
                $allSubAdminIds[] = $subAdmin->entity_id;
            }
        }

        // We have all the subgroups (recursively) in $allSubGroupIds now
        return [
            'groups' => $allSubGroupIds,
            'admins' => $allSubAdminIds
        ];
    }

    private function getMerchantIdsOfNodes($nodes)
    {
        $groups = $nodes['groups'];

        $admins = $nodes['admins'];

        $allMerchantIds = [];

        // TODO: I think we can merge these 2 queries
        // because ENTITY_IDs are unique across

        $merchants = \DB::table('merchant_map')
            ->whereIn('entity_id', $groups)
            ->where('entity_type', 'group')
            ->get();

        foreach ($merchants as $merchant)
        {
            $allMerchantIds[] = $merchant->merchant_id;
        }

        $merchants = \DB::table('merchant_map')
            ->whereIn('entity_id', $admins)
            ->where('entity_type', 'admin')
            ->get();

        foreach ($merchants as $merchant)
        {
            $allMerchantIds[] = $merchant->merchant_id;
        }

        return $allMerchantIds;
    }
}

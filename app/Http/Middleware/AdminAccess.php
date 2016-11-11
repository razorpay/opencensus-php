<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use RZP\Http\Route;
use RZP\Models\Admin;

class AdminAccess
{
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
            // $adminAuthRoutes = Route::$admin;

            $routeName = $this->router->currentRouteName();

            $admin = $this->ba->getAdmin();

            $merchant = $this->getMerchant($request);

            $authorized = $this->policyChecker($routeName, $admin, $merchant);

            if (! $authorized)
            {
                return ApiResponse::routeNotFound();
            }
        }

        return $next($request);
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
        $adminAuthRoutes = Route::$adminPermission;

        $permissions = $adminAuthRoutes[$routeName];

        // We have the following:
        // - permission
        // - admin
        // - merchant (when available)

        // TODO: Move most of the logic to Admin/Admin/Repository

        // === Do a Role check

        // 1. Get all the permissions by all the roles first

        $roles = $admin->roles->toArray();

        $adminPermissions = $admin->getPermissionsList();

        // 2. Check if the specified permissions exist in our
        // generated white list

        $allowed = $this->checkPermissionsAllowed($permissions, $adminPermissions);

        $hasMerchantAccess = $this->groupCheck($admin, $merchant);

        $policyPassed = false;

        if ($allowed)
        {
            if ($merchant)
            {
                if ($hasMerchantAccess)
                {
                    $policyPassed = true;
                }
            }
            else
            {
                $policyPassed = true;
            }
        }

        return $policyPassed;
    }

    private function checkPermissionsAllowed($toCheck, $haystack)
    {
        foreach ($toCheck as $permission)
        {
            if (! in_array($permission, $haystack))
            {
                return false;
            }
        }

        return true;
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

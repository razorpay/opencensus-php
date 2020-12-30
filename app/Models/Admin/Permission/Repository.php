<?php

namespace RZP\Models\Admin\Permission;

use DB;

use RZP\Models\Admin\Base;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;

class Repository extends Base\Repository
{
    protected $entity = 'permission';

    protected $adminFetchParamRules = [
        Entity::CATEGORY  => 'sometimes|string|max:255',
        Entity::NAME      => 'sometimes|string|max:255',
    ];

    protected $proxyFetchParamRules = [
        Entity::CATEGORY  => 'sometimes|string|max:255',
        Entity::NAME      => 'sometimes|string',
    ];

    protected $appFetchParamRules = [
        Entity::CATEGORY  => 'sometimes|string|max:255',
        Entity::NAME      => 'sometimes|string',
    ];

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function retrieveByIds(array $permIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $permIds)
                    ->get();
    }

    public function fetchAll()
    {
        return $this->newQuery()
                    ->get();
    }

    public function fetchAllByOrg(string $orgId, string $type = null)
    {
        $pid = $this->dbColumn(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        $query = $this->newQuery()
                      ->select(Table::PERMISSION . '.*')
                      ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                      ->where($pmTable . '.entity_id', '=', $orgId)
                      ->where($pmTable . '.entity_type', '=', 'org');

        if ((empty($type) === false) and
            ($type === 'workflow'))
        {
            $query->where($pmTable . '.enable_workflow', '=', 1);
        }

        return $query->get();
    }

    public function retrieveIdsByNames(array $permissionNames)
    {
        return $this->newQuery()
                    ->whereIn(Entity::NAME, $permissionNames)
                    ->get(['id']);
    }

    public function fetchAllAssignable()
    {
        return $this->newQuery()
                    ->where(Entity::ASSIGNABLE, 1)
                    ->get();
    }

    public function retrieveIdsByNamesAndOrg(string $permissionName, string $orgId)
    {
        $pid = $this->dbColumn(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
                    ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                    ->where($pmTable . '.entity_id', '=', $orgId)
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->where(Entity::NAME, $permissionName)
                    ->pluck('id');
    }

    public function retrieveIdsByNamesAndOrgWithPermissionList(array $permissionList, string $orgId)
    {
        $pid = $this->dbColumn(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
            ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
            ->where($pmTable . '.entity_id', '=', $orgId)
            ->where($pmTable . '.entity_type', '=', 'org')
            ->whereIn(Entity::NAME, $permissionList)
            ->pluck('id');
    }

    /**
     * Enable workflows for orgs which are assigned to a permission
     * Worklows can only be enabled for orgs if the permission is assigned to
     * it
     */
    public function toggleWorkflowOnPermissionForOrgs(
        string $permissionId,
        array $orgIds,
        bool $enabled)
    {
        DB::table(Table::PERMISSION_MAP)
                ->where('permission_id', '=', $permissionId)
                ->where('entity_type', '=', 'org')
                ->whereIn('entity_id', $orgIds)
                ->update(['enable_workflow' => $enabled]);
    }

    public function toggleWorkflowOnOrgForPermissions(
        string $orgId,
        array $permissionIds,
        bool $enabled)
    {
        DB::table(Table::PERMISSION_MAP)
                ->where('entity_id', '=', $orgId)
                ->where('entity_type', '=', 'org')
                ->whereIn('permission_id', $permissionIds)
                ->update(['enable_workflow' => $enabled]);
    }

    public function getPermissionsWithWorkflowEnabled(string $orgId)
    {
        $attributes = $this->dbColumn('*');

        $pid = $this->dbColumn(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
                    ->select($attributes)
                    ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->where($pmTable . '.entity_id', '=', $orgId)
                    ->where($pmTable . '.enable_workflow', '=', 1)
                    ->get();
    }

    public function findByOrgIdAndPermission($orgId, $permissionName)
    {
        $pid = $this->dbColumn(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
                    ->select(Table::PERMISSION . '.*')
                    ->with('roles.admins')
                    ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                    ->where($pmTable . '.entity_id', '=', $orgId)
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->where(Entity::NAME, $permissionName)
                    ->first();
    }
}

<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;
use RZP\Constants\Table;
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

    public function fetchAllByOrg(string $orgId)
    {
        $pid = $this->getAttributeWithTableName(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
                    ->select(Table::PERMISSION . '.*')
                    ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                    ->where($pmTable . '.entity_id', '=', $orgId)
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->get();
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

    public function retrieveIdsByNamesAndOrg(array $permissionNames, string $orgId)
    {
        $pid = $this->getAttributeWithTableName(Permission\Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        return $this->newQuery()
                    ->join($pmTable, $pid, '=', $pmTable . '.permission_id')
                    ->where($pmTable . '.entity_id', '=', $orgId)
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->get(['id']);
    }
}

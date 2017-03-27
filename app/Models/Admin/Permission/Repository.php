<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;
use RZP\Constants\Table;

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

    public function retrieveIdsByNames(array $permNames)
    {
        return $this->newQuery()
                    ->whereIn(Entity::NAME, $permNames)
                    ->get(['id']);
    }

    public function fetchAll($orgId)
    {
        return $this->newQuery()
                    ->select('*')
                    ->join(Table::PERMISSION_MAP, Table::PERMISSION.'.'.Entity::ID, '=', Table::PERMISSION_MAP.'.permission_id')
                    ->where([
                        [Table::PERMISSION_MAP.'.entity_id', '=', $orgId],
                        [Table::PERMISSION_MAP.'.entity_type', '=', 'org']
                    ])
                    ->get();
    }
}

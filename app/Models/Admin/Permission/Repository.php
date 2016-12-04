<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'permission';

    protected $adminFetchParamRules = [
        Entity::CATEGORY  => 'sometimes|string|max:255',
        Entity::NAME      => 'sometimes|string|max:255',
    ];

    protected $proxyFetchParamRules = [
        Entity::CATEGORY  => 'sometimes|string|max:255',
        Entity::NAME    => 'sometimes|string',
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

    public function fetchAll($input)
    {
        return $this->newQuery()
                    ->orderBy(Entity::CATEGORY)
                    ->get();
    }
}

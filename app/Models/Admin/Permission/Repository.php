<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'permission';

    protected $proxyFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    protected $appFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
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
}

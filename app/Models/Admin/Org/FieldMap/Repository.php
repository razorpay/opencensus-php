<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org_field_map';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::ENTITY_NAME => 'sometimes|string',
        Entity::ORG_ID      => 'sometimes|string|max:14',
    ];

    // These are admin allowed params to search on.
    protected $adminFetchParamRules = [
        Entity::ENTITY_NAME => 'sometimes|string',
        Entity::ORG_ID      => 'sometimes|string|max:14',
    ];

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function findByOrgIdAndEntity(
        string $orgId,
        string $entity)
    {
        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ENTITY_NAME, '=', $entity)
                    ->first();
    }
}

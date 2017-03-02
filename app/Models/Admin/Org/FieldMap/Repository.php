<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org_field_map';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::ENTITY_NAME           => 'sometimes|string',
        Entity::ORG_ID                => 'sometimes|string|max:20',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::ENTITY_NAME           => 'sometimes|string',
        Entity::ORG_ID                => 'sometimes|string|max:20',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function findByOrgIdAndEntity($orgId, $entity)
    {
        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ENTITY_NAME, '=', $entity)
                    ->first();
    }
}

<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::ENTITY                => 'sometimes|string',
        Entity::ORG_ID                => 'sometimes|string|max:20',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::ENTITY                => 'sometimes|string',
        Entity::ORG_ID                => 'sometimes|string|max:20',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}

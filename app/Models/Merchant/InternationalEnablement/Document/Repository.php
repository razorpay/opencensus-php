<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'international_enablement_document';

    protected $adminFetchParamRules = [
        Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID => 'sometimes|string|size:14',
    ];
}

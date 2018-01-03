<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'account';

    protected $expands = [
        Entity::SETTLEMENT_SCHEDULES
    ];

    protected $entityFetchParamRules = [
        Entity::PARENT_ID => 'sometimes|string|max:14',
    ];

    public function isMerchantIdRequiredForFetch()
    {
        // If private auth => yes
        // For admin, no
        return true;
    }
}

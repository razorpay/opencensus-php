<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID               => 'sometimes|string|max:14',
    ];

    public function isMerchantIdRequiredForFetch()
    {
        return true;
    }
}

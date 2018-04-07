<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Constants\Entity as ConstantEntity;

class Repository extends Base\Repository
{
    protected $entity = ConstantEntity::DISPUTE_FILE;

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::DISPUTE_ID   => 'sometimes|string|max:14',
    ];
}

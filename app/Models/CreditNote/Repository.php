<?php

namespace RZP\Models\CreditNote;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'creditnote';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
    ];
}

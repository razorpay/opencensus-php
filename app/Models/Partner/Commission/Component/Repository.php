<?php

namespace RZP\Models\Partner\Commission\Component;

use RZP\Models\Base\Repository as BaseRepository;

class Repository  extends BaseRepository
{
    protected $entity = 'commission_component';

    protected $proxyFetchParamRules = [
        Entity::ID              => 'sometimes|string|size:14',
        Entity::COMMISSION_ID   => 'sometimes|string|size:14',
        Entity::PRICING_TYPE    => 'sometimes|string|max:255',
        Entity::PRICING_FEATURE => 'sometimes|string|max:255',
    ];
}
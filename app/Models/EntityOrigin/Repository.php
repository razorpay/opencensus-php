<?php

namespace RZP\Models\EntityOrigin;

use RZP\Constants;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = Constants\Entity::ENTITY_ORIGIN;

    protected $appFetchParamRules = [
        Entity::ORIGIN_ID   => 'sometimes|string|size:14',
        Entity::ORIGIN_TYPE => 'sometimes|string|in:merchant,application',
        Entity::ENTITY_ID   => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string|in:payment',
    ];
}

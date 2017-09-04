<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_file';

    protected $entityFetchParamRules = [
        Entity::SOURCE => 'sometimes|string',
        Entity::TYPE   => 'sometimes|string',
        Entity::STATUS => 'sometimes|string',
    ];
}

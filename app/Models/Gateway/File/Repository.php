<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_file';

    protected $entityFetchParamRules = [
        Entity::TARGET => 'filled|string|max:50',
        Entity::TYPE   => 'filled|string|max:20',
        Entity::STATUS => 'filled|string|max:20',
    ];
}

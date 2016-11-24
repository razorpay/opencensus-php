<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{

    protected $appFetchParamRules = [
        Entity::ID                  => 'sometimes',
        Entity::MERCHANT_ID         => 'required|alpha_num',
        Entity::TYPE                => 'sometimes|alpha_num',
        Entity::ENTITY_ID           => 'sometimes|alpha_num',
    ];

    protected $entity = 'file_store';
}

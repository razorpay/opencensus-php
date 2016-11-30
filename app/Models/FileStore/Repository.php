<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{

    protected $appFetchParamRules = [
        Entity::ID                  => 'sometimes|size:14',
        Entity::MERCHANT_ID         => 'required|alpha_num|size:14',
        Entity::TYPE                => 'sometimes|alpha_num|max:100',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
    ];

    protected $entity = 'file_store';
}

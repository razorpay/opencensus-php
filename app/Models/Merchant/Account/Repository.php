<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID          => 'sometimes|string|size:14',
        EsRepository::SEARCH_HITS  => 'filled|boolean',
        EsRepository::QUERY        => 'filled|string|min:2|max:100',
    ];
}

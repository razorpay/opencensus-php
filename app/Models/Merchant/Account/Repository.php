<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID => 'sometimes|string|max:14',
    ];
}

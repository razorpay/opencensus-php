<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::ID                 => 'sometimes|string|min:14',
        Entity::EMAIL              => 'sometimes|email',
        Entity::PARENT_ID          => 'sometimes|string|size:14',
        EsRepository::SEARCH_HITS  => 'filled|boolean',
        EsRepository::QUERY        => 'filled|string|min:2|max:100',
        Entity::ACCOUNT_CODE       => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
    ];

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        Entity::stripSignWithoutValidation($id);

        $query->where(Entity::ID, '=', $id);
    }
}

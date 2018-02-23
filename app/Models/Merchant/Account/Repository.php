<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID => 'sometimes|string|size:14',
    ];

    public function getAccounts(string $parentId, array $input): Base\PublicCollection
    {
        // Send all the linked accounts. Dashboard applies a local filter.
        $limit = 500;

        $query = $this->newQuery()
                      ->where(Entity::PARENT_ID, $parentId);

        foreach ($input as $attribute => $value)
        {
            $query = $query->where($attribute, $value);
        }

        return $query->take($limit)
                     ->get();
    }
}

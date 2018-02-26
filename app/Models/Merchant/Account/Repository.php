<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    public function getAccounts(string $parentId, array $input): Base\PublicCollection
    {
        $skip  = 0;

        // Send all the linked accounts. Dashboard applies a local filter.
        $count = 1000;

        if (isset($input[Fetch::SKIP]) === true)
        {
            $skip = $input[Fetch::SKIP];

            unset($input[Fetch::SKIP]);
        }

        if (isset($input[Fetch::COUNT]) === true)
        {
            $count = $input[Fetch::COUNT];

            unset($input[Fetch::COUNT]);
        }

        $query = $this->newQuery()
                      ->whereNull(Entity::SUSPENDED_AT)
                      ->where(Entity::PARENT_ID, $parentId);

        foreach ($input as $attribute => $value)
        {
            $query = $query->where($attribute, $value);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->get();
    }
}

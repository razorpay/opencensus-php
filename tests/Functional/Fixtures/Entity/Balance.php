<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Models\Merchant\Balance\AccountType;

class Balance extends Base
{
    public function create(array $attributes = [])
    {
        $type = $attributes[Entity::TYPE] ?? Type::PRIMARY;

        $accountType = ($type === Type::BANKING) ? AccountType::SHARED : null;

        $defaultValues = [
            Entity::ACCOUNT_TYPE => $accountType,
            Entity::CHANNEL      => null,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $balance = parent::create($attributes);

        return $balance;
    }
}

<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Merchant\Balance\BalanceConfig\Entity;

class BalanceConfig extends Base
{
    public function create(array $attributes = [])
    {
        $type = $attributes[Entity::TYPE] ?? Type::PRIMARY;

        $defaultValues = [
            Entity::TYPE                                 => $type,
            Entity::NEGATIVE_LIMIT_AUTO                 => 0,
            Entity::NEGATIVE_LIMIT_MANUAL               => 0,
            Entity::NEGATIVE_TRANSACTION_FLOWS          => ['payment'],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $balanceConfig = parent::create($attributes);

        return $balanceConfig;
    }
}

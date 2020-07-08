<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Credits\Type;
use RZP\Models\Merchant\Credits\Balance\Entity;

class CreditBalance extends Base
{
    public function create(array $attributes = [])
    {
        $defaultValues = [
            Entity::PRODUCT         => 'banking',
            Entity::TYPE            => Type::REWARD_FEE,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $balance = parent::create($attributes);

        return $balance;
    }
}

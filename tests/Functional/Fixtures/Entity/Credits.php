<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Credits\Entity;
use RZP\Models\Merchant\Credits\Type;

class Credits extends Base
{
    public function create(array $attributes = [])
    {
        $balance = parent::create($attributes);

        return $balance;
    }
}

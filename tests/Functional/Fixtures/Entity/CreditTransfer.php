<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class CreditTransfer extends Base
{
    public function create(array $attributes = [])
    {
        $creditTransfer = parent::create($attributes);

        return $creditTransfer;
    }
}

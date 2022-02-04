<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class BankTransfer extends Base
{
    public function create(array $attributes = [])
    {
        $attributes = array_merge($attributes, ['status'=>'processed']);
        $bankTransfer = parent::create($attributes);

        return $bankTransfer;
    }
}

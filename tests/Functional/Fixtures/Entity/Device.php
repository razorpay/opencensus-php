<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Device extends Base
{
    public function createVerified(array $attributes = [])
    {
        $defaultAttributes = [
            'status'      => 'verified',
            'upi_token'   => 'random_upi_token',
            'verified_at' => time()
        ];

        $attributes = array_merge($attributes, $defaultAttributes);

        return parent::create($attributes);
    }
}

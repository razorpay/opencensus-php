<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Vpa extends Base
{
    public function createDefault()
    {
        $attributes = [
            'id' => 'RazorpayDftVpa',
            'username' => 'ceo'
        ];

        return parent::create($attributes);
    }
}

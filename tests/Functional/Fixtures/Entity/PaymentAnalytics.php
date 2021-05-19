<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class PaymentAnalytics extends Base
{
    public function create(array $attributes = array())
    {
        $defaultAttributes = array('ip' => '127.0.0.1');

        $attributes = array_merge($attributes, $defaultAttributes);
        
        return parent::create($attributes);
    }
}

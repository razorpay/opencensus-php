<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class PaymentMeta extends Base
{
    public function create(array $attributes = array())
    {
        $defaultAttributes = array('payment_id' => 'J4xTrMIbNo41ac');

        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);
    }
}

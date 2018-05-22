<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PaymentLink extends Base
{
    use DbEntityFetchTrait;

    public function create(array $attributes = [])
    {
        $paymentLink = $this->createEntity('payment_link', $attributes);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $paymentLink->merchant()->associate($merchant);

        return $paymentLink;
    }
}

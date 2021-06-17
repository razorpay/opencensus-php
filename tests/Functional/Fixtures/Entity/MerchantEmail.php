<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Constants\Entity as E;

class MerchantEmail extends Base
{
    public function create(array $attributes = array())
    {
        return $this->createEntityInTestAndLive(E::MERCHANT_EMAIL, $attributes);
    }
}

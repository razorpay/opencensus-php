<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Constants\Entity as E;

class MerchantBusinessDetail extends Base
{
    public function create(array $attributes = array())
    {
        $merchantBusinessDetail = $this->createEntityInTestAndLiveAndAsv('merchant_business_detail', $attributes);

        return $merchantBusinessDetail;
    }
}

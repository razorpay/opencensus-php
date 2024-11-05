<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Constants\Entity as E;
use RZP\Tests\Functional\Fixtures\Entity\Base;

class MerchantWebsite extends Base
{
    public function create(array $attributes = array())
    {

        $merchantWebsite = $this->createEntityInTestAndLiveAndAsv('merchant_website', $attributes);

        return $merchantWebsite;
    }
}

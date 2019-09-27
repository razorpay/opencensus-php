<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class MerchantDocument extends Base
{
    public function create(array $attributes = array())
    {
        $merchantDetail = $this->createEntityInTestAndLive('merchant_document', $attributes);

        return $merchantDetail;
    }
}

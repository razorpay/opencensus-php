<?php


namespace Functional\Fixtures\Entity;


use RZP\Tests\Functional\Fixtures\Entity\Base;

class MerchantAttribute extends Base
{
    public function create(array $attributes = [])
    {
        $merchantAttribute = $this->createEntityInTestAndLive('merchant_attribute', $attributes);

        return $merchantAttribute;
    }
}

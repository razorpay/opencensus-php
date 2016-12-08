<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class MerchantDetail extends Base
{
    public function create(array $attributes = array())
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $defaultValues =  [
                'merchant_id'   => $merchant['id'],
                'contact_email' => $merchant['email']
            ];

        $attributes = array_merge($defaultValues, $attributes);

        $merchantDetail = $this->createEntityInTestAndLive('merchant_detail', $attributes);

        return $merchantDetail;
    }
}

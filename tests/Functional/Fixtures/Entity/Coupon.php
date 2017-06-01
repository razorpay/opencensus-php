<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Coupon extends Base
{
    public function createCoupon(array $attributes = [])
    {
        $coupon = $this->fixtures->create('coupon', $attributes);

        return $coupon;
    }

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'coupon_code' => 'RANDOM',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $coupon = parent::create($attributes);

        return $coupon;
    }
}

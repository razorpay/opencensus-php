<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Invitation extends Base
{
    const DEFAULT_MERCHANT_ID = '1000InviteMerc';

    public function create(array $attributes = [])
    {
        $defaultValues = [
           'role'        => 'manager',
           'email'       => 'testteaminvite@razorpay.com',
           'merchant_id' => self::DEFAULT_MERCHANT_ID,
           'token'       => str_random(40),
           'is_draft'    => 0,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $invitation = parent::create($attributes);

        return $invitation;
    }
}

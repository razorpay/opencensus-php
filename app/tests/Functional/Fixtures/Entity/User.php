<?php

namespace Tests\Functional\Fixtures\Entity;

class User extends Base
{
    protected $user = array(
        'id'            => '1000000000user',
        'name'          => 'test',
        'contact'       => '1234567890',
        'email'         => 'test@razorpay.com',
        'merchant_id'   => '10000000000000'
    );

    public function createDefaultUsers()
    {
        $this->fixtures->create('user', $this->user);
    }
}
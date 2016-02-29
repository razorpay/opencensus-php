<?php

namespace Tests\Functional\Fixtures\Entity;

class UserMethod extends Base
{
    protected $userMethods = array(
        /*
        array(
            'id'            => '100000usercard',
            'user_id'       => '1000000000user',
            'method'        => 'card',
            'card_id'       => '',
        ),
        */
        array(
            'id'            => '1000userwallet',
            'user_id'       => '1000000000user',
            'method'        => 'wallet',
            'wallet'        => 'paytm',
        ),
        array(
            'id'            => '100000userbank',
            'user_id'       => '1000000000user',
            'method'        => 'netbanking',
            'bank'          => 'HDFC',
        )
    );

    public function createDefaultUserMethods()
    {
        $methods = [];

        foreach ($this->userMethods as $attributes)
        {
            $methods[] = $this->fixtures->create('user_method', $attributes);
        }

        return $methods;
    }
}
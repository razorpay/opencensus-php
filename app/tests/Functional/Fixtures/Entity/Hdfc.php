<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Hdfc extends Base
{
    public function createAuthorized(array $attributes = array())
    {
        $attributes['action'] = 4;
        $attributes['status'] = 'authorized';

        return $this->createEntity($attributes);
    }

    public function createCaptured(array $attributes = array())
    {
        $attributes['action'] = 5;
        $attributes['status'] = 'captured';

        return $this->createEntity($attributes);
    }

    public function createRefunded(array $attributes = array())
    {
        $attributes['action'] = 2;
        $attributes['status'] = 'refunded';

        return $this->createEntity($attributes);
    }

    public function createFromRefund($attributes)
    {
        $refund = $attributes['refund'];
        unset($attributes['refund']);

        $attributes = array(
            'refund_id' => $refund->getId(),
            'payment_id' => $refund->payment->getId(),
            'created_at' => $refund->created_at,
            'updated_at' => $refund->updated_at,
            'amount' => $refund->getAmount());

        $hdfcRefund = $this->fixtures->create('hdfc:refunded', $attributes);

        return $hdfcRefund;
    }
}
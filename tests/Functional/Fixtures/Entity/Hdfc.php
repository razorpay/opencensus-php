<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Account;

class Hdfc extends Base
{
    public function createPurchased(array $attributes = array())
    {
        $attributes['action'] = 1;
        $attributes['status'] = 'captured';

        return $this->create($attributes);
    }

    public function createAuthorized(array $attributes = array())
    {
        $attributes['action'] = 4;
        $attributes['status'] = 'authorized';

        return $this->create($attributes);
    }

    public function createCaptured(array $attributes = array())
    {
        $attributes['action'] = 5;
        $attributes['status'] = 'captured';

        return $this->create($attributes);
    }

    public function createRefunded(array $attributes = array())
    {
        $attributes['action'] = 2;
        $attributes['status'] = 'refunded';

        return $this->create($attributes);
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

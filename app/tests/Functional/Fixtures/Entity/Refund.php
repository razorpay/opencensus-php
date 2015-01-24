<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Refund extends Base
{
    public function createFromPayment($attributes)
    {
        $payment = $attributes['payment'];

        unset($attributes['payment']);

        $refund = $this->create(
            'refund',
            ['payment_id' => $payment->getId(),
             'merchant_id' => $payment->merchant->getId(),
             'amount' => $payment->getAmount()]);

        $txn = (new \Models\Transaction\Core)->createFromRefund($refund);

        $refund->transaction()->associate($txn);

        return $refund;
    }
}
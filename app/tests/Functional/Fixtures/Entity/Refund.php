<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Refund extends Base
{
    public function createFromPayment($attributes)
    {
        $payment = $attributes['payment'];

        unset($attributes['payment']);

        if (isset($attributes['amount']) === false)
            $attributes['amount'] = $payment->getAmount();

        $attributes['payment_id'] = $payment->getId();
        $attributes['merchant_id'] = $payment->merchant->getId();

        $refund = parent::create($attributes);

        $hdfcRefund = $this->fixtures->create('hdfc:from_refund', ['refund' => $refund]);

        $txn = (new \Models\Transaction\Core)->createFromRefund($refund);
        $txn->saveOrFail();

        $refund->transaction()->associate($txn);
        $refund->saveOrFail();

        return $refund;
    }
}
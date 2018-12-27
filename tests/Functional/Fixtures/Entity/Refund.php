<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Account;
use RZP\Models\Transaction;

class Refund extends Base
{
    use TransactionTrait;

    public function createFromPayment($attributes)
    {
        $payment = $attributes['payment'];

        unset($attributes['payment']);

        if (isset($attributes['amount']) === false)
            $attributes['amount'] = $payment->getAmount();

        $attributes['payment_id'] = $payment->getId();
        $attributes['merchant_id'] = $payment->merchant->getId();
        $attributes['base_amount'] = $attributes['amount'];

        if (isset($attributes['gateway']) === false)
        {
            $attributes['gateway']     = $payment->getGateway();
        }

        if (isset($attributes['status']) === false)
        {
            $attributes['status'] = 'processed';
        }

        $refund = parent::create($attributes);

        $hdfcRefund = $this->fixtures->create('hdfc:from_refund', ['refund' => $refund]);

        list($txn, $feesSplit) = $this->createTransactionOnRefund($refund);
        $txn->saveOrFail();

        $refund->transaction()->associate($txn);
        $refund->saveOrFail();

        return $refund;
    }

    public function createFromTransferPayment($attributes)
    {
        $payment = $attributes['payment'];

        unset($attributes['payment']);

        if (isset($attributes['amount']) === false)
        {
            $attributes['amount'] = $payment->getAmount();
        }

        $attributes['payment_id']  = $payment->getId();
        $attributes['merchant_id'] = $payment->merchant->getId();
        $attributes['base_amount'] = $attributes['amount'];
        $attributes['status']      = 'processed';

        $refund = $this->build('refund', $attributes);

        list($txn, $feesSplit) = $this->createTransactionOnRefund($refund);

        $txn->saveOrFail();

        $refund->saveOrFail();

        $payment->refundAmount($attributes['amount'], $attributes['base_amount']);

        $payment->saveOrFail();

        return $refund;
    }
}

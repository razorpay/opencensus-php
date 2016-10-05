<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

trait TransactionTrait
{
    protected function createTransactionForPaymentAuthorized($payment, $feesSplit)
    {
        return $this->transaction(function() use ($payment, $feesSplit)
        {
            return (new \RZP\Models\Transaction\Core)->createFromPaymentAuthorized($payment, $feesSplit);
        });
    }

    protected function updateTransactionOnCapture($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new \RZP\Models\Transaction\Core)->updateOnCapture($payment);
        });
    }

    protected function createTransactionOnRefund($refund)
    {
        return $this->transaction(function() use ($refund)
        {
            return  (new \RZP\Models\Transaction\Core)->createFromRefund($refund);
        });
    }
}

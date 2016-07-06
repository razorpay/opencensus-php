<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

trait TransactionTrait
{
    protected function createTransactionForPaymentAuthorized($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new \Models\Transaction\Core)->createFromPaymentAuthorized($payment);
        });
    }

    protected function updateTransactionOnCapture($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new \Models\Transaction\Core)->updateOnCapture($payment);
        });
    }

    protected function createTransactionOnRefund($refund)
    {
        return $this->transaction(function() use ($refund)
        {
            return  (new \Models\Transaction\Core)->createFromRefund($refund);
        });
    }
}

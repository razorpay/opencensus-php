<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

trait TransactionTrait
{
    protected function createTransactionForPaymentAuthorized($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new \RZP\Models\Transaction\Core)->createFromPaymentAuthorized($payment);
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

    protected function createTransactionFromPayout($payout)
    {
        return $this->transaction(function() use ($payout)
        {
            return  (new \RZP\Models\Transaction\Core)->createFromPayout($payout);
        });
    }
}

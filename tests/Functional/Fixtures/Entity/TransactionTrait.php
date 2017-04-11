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
            return (new \RZP\Models\Transaction\Core)->createFromPaymentCaptured($payment);
        });
    }

    protected function createTransactionOnPaymentMethodTransfer($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new \RZP\Models\Transaction\Core)->createFromPaymentTransferred($payment);
        });
    }

    protected function createTransactionOnRefund($refund)
    {
        return $this->transaction(function() use ($refund)
        {
            return (new \RZP\Models\Transaction\Core)->createFromRefund($refund);
        });
    }

    protected function createTransactionFromPayout($payout)
    {
        return $this->transaction(function() use ($payout)
        {
            return (new \RZP\Models\Transaction\Core)->createFromPayout($payout);
        });
    }

    protected function createTransactionOnTransfer($transfer)
    {
        return $this->transaction(function() use ($transfer)
        {
            return (new \RZP\Models\Transaction\Core)->createFromTransfer($transfer);
        });
    }

    protected function createTransactionOnReversal($reversal)
    {
        return $this->transaction(function() use ($reversal)
        {
            return (new \RZP\Models\Transaction\Core)->createFromReversal($reversal);
        });
    }
}

<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Transaction;
use RZP\Models\Transaction\Core as TransactionCore;

trait TransactionTrait
{
    protected function createTransactionForPaymentAuthorized($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new TransactionCore)->createFromPaymentAuthorized($payment);
        });
    }

    protected function updateTransactionOnCapture($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new TransactionCore)->createOrUpdateFromPaymentCaptured($payment);
        });
    }

    protected function createTransactionOnPaymentMethodTransfer($payment)
    {
        return $this->transaction(function() use ($payment)
        {
            return (new TransactionCore)->createFromPaymentTransferred($payment);
        });
    }

    protected function createTransactionOnRefund($refund)
    {
        return $this->transaction(function() use ($refund)
        {
            return (new TransactionCore)->createFromRefund($refund);
        });
    }

    protected function createTransactionFromPayout($payout)
    {
        return $this->transaction(function() use ($payout)
        {
            return (new Transaction\Processor\Payout($payout))->createTransaction()[0];
        });
    }

    protected function createTransactionOnTransfer($transfer)
    {
        return $this->transaction(function() use ($transfer)
        {
            return (new TransactionCore)->createFromTransfer($transfer);
        });
    }

    protected function createTransactionOnReversal($reversal)
    {
        return $this->transaction(function() use ($reversal)
        {
            return (new TransactionCore)->createFromTransferReversal($reversal);
        });
    }

    protected function createTransactionOnPayoutReversal($reversal)
    {
        return $this->transaction(function() use ($reversal)
        {
            return (new TransactionCore)->createFromPayoutReversal($reversal);
        });
    }

    protected function createTransactionOnDispute($dispute)
    {
        return $this->transaction(function() use ($dispute)
        {
            return (new TransactionCore)->createFromDispute($dispute);
        });
    }

    protected function createTransactionOnAdjustment($adjustment)
    {
        return $this->transaction(function() use ($adjustment)
        {
            return (new TransactionCore)->createFromAdjustment($adjustment);
        });
    }
}

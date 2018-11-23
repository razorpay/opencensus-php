<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Transaction;
use RZP\Models\Merchant\RefundSource;

class Refund extends Base
{
    protected function setTransactionForSource()
    {
        $txn = $this->createNewTransaction();

        $this->setTransaction($txn);
    }

    public function fillDetails()
    {
        $amount = $this->source->getBaseAmount();

        $this->txn->setAmount($amount);
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax = 0;
    }

    public function updateTransaction()
    {
        $settledAt = $this->getSettledAtTimestampForRefund();

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->repo->saveOrFail($this->txn);
    }

    protected function getSettledAtTimestampForRefund()
    {
        $payment = $this->source->payment;

        if ($payment->hasBeenCaptured())
        {
            $paymentTxn = $payment->transaction;

            return ($paymentTxn->isSettled() ? 1 : $paymentTxn->getSettledAt());
        }

        return null;
    }

    public function calculateFees()
    {
        $refund = $this->source;

        $payment = $refund->payment;

        if ($payment->isCaptured() === true)
        {
            $this->debit = $refund->getBaseAmount();

            $merchant = $refund->merchant;

            if ($merchant->getRefundSource() === RefundSource::CREDITS)
            {
                $this->debit = 0;

                $this->txn->setCredits($refund->getBaseAmount());

                $this->txn->setCreditType(Transaction\CreditType::REFUND);
            }
        }
    }
}

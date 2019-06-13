<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Models\Pricing;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;

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

            $nowTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            return ($paymentTxn->isSettled() ? $nowTimestamp : $paymentTxn->getSettledAt());
        }

        return null;
    }

    protected function shouldUpdateBalance()
    {
        $payment = $this->source->payment;

        if ($payment->isAuthorized() === true)
        {
            return false;
        }

        return true;
    }

    protected function getNetAmount()
    {
        $refund = $this->source;

        $settledBy = $refund->payment->getSettledBy();

        $netAmount = $refund->getBaseAmount() + $this->fees;

        if ((($settledBy !== 'Razorpay') or ($refund->isDirectSettlementRefund() === true)) and
            ($refund->isRefundSpeedInstant() === false))
        {
            $netAmount = 0;
        }

        if (in_array($refund->getSpeedRequested(), RefundSpeed::REFUND_INSTANT_SPEEDS))
        {
            list($this->fees, $this->tax, $this->feesSplit) = (new Pricing\Fee)->calculateMerchantFees($this->source);
        }

        return $netAmount;
    }

    public function calculateFees()
    {
        $refund = $this->source;

        $payment = $refund->payment;

        if ($payment->isCaptured() === true)
        {
            if ($refund->isRefundSpeedInstant() === true)
            {
                list($this->fees, $this->tax, $this->feesSplit) = (new Pricing\Fee)->calculateMerchantFees($this->source);
            }

            $netAmount = $this->getNetAmount();

            $this->debit = $netAmount;

            $merchant = $refund->merchant;

            if ($merchant->getRefundSource() === RefundSource::CREDITS)
            {
                $this->debit = 0;

                $this->txn->setCredits($netAmount);

                $this->txn->setCreditType(Transaction\CreditType::REFUND);
            }
        }
    }

    protected function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill refunds
        // with all existing refunds having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

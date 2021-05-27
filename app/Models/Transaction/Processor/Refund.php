<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Models\Pricing;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Transaction\ReconciledType;

class Refund extends Base
{
    protected function setTransactionForSource()
    {
        $txn = $this->createNewTransaction();

        $this->setTransaction($txn);
    }

    public function fillDetails()
    {
        if ($this->source->isRefundSpeedInstant() === true)
        {
            $this->txn->setFeeModel($this->txn->merchant->getFeeModel());
        }

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

        $this->checkAndSetTxnReconciliation();

        $this->repo->saveOrFail($this->txn);
    }

    protected function checkAndSetTxnReconciliation()
    {
        if ($this->source->getGateway() === Gateway::WALLET_OPENWALLET)
        {
            $this->txn->setReconciledAt(time());

            $this->txn->setReconciledType(ReconciledType::NA);
        }
    }

    protected function getSettledAtTimestampForRefund()
    {
        $payment = $this->source->payment;

        $refund  = $this->source;

        $nowTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        if ($payment->hasBeenCaptured())
        {
            $paymentTxn = $payment->transaction;

            return ($paymentTxn->isSettled() ? $nowTimestamp : $paymentTxn->getSettledAt());
        }

        if (($payment->isAuthorized() === true) and
            ($refund->isDirectSettlementWithoutRefund() === true))
        {
            return $nowTimestamp;
        }

        return null;
    }

    protected function shouldUpdateBalance()
    {
        $payment = $this->source->payment;
        $refund  = $this->source;

        if ($payment->isCaptured() === true)
        {
            return true;
        }

        if (($payment->isAuthorized() === true) and
            ($refund->isDirectSettlementWithoutRefund() === true))
        {
            return true;
        }

        return false;
    }

    protected function getNetAmount()
    {
        $refund = $this->source;

        $netAmount = $refund->getBaseAmount();

        if (($refund->isRefundSpeedInstant() === true) and
            ($this->txn->isPostpaid() !== true))
        {
            $netAmount += $this->fees;
        }

        // For cases like cred, here instead of using the whole base amount we deduct the coin burn for the transaction
        // from the base amount.
        $discount = $this->getDiscountIfApplicable($refund->payment);

        $netAmount -= $discount;

        //
        // Net amount is 0, only in a single case when payment's settledby is not razorpay and
        // direct settlement refund is true, provided speed of refund is not instant/optimum
        // in which case we will deduct the whole amount + fees as listed above
        //
        if (($refund->payment->getSettledBy() !== 'Razorpay') and
            ($refund->isDirectSettlementRefund() === true) and
            ($refund->isRefundSpeedInstant() === false))
        {
            $netAmount = 0;
        }

        return $netAmount;
    }

    public function calculateFees()
    {
        $refund = $this->source;

        $payment = $refund->payment;

        if (($payment->isCaptured() === true) or
            ($refund->isDirectSettlementWithoutRefund() === true))
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

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill refunds
        // with all existing refunds having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Transaction\ReconciledType;
use RZP\Trace\TraceCode;

class Refund extends Base
{
    protected function setTransactionForSource($txnId = null)
    {
        $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($this->source);

        if ($txn === null)
        {
            $txn = $this->createNewTransaction($txnId);
        }

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

            // Setting current timestamp to refund settled_at when $paymentTxn->getSettledAt() is null to support async_txn_fill_details feature
            // Slack ref - https://razorpay.slack.com/archives/CNXC0JHQF/p1649241605237939?thread_ts=1648804095.677009&cid=CNXC0JHQF
            return ($paymentTxn->isSettled() || $paymentTxn->getSettledAt() === null ? $nowTimestamp : $paymentTxn->getSettledAt());
        }

        if (($payment->hasBeenAuthorized() === true) and
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

        if ($payment->hasBeenCaptured() === true)
        {
            return true;
        }

        if (($payment->hasBeenAuthorized() === true) and
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
        // For walnut369, here instead of deducting whole base amount we subtract the discount for the payment
        // in case of partial refunds.
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

    public function calculateDiscount($payment)
    {
        $paymentClone = clone $payment;

        $paymentClone->base_amount = $this->txn->amount;

       list($fees, $tax, $feesplit) = (new Pricing\Fee)->calculateMerchantFees($paymentClone);

        return $fees;
    }

    public function getDiscountIfApplicable($payment)
    {
        // For the Bajaj finserv emi payments we have to calculate fee that we deducted while making
        // the payments
        if ($payment->gateway === Entity::BAJAJFINSERV and $payment->isMethod(Payment\Entity::EMI))
        {
            return $this->calculateDiscount($payment);
        }

        return parent::getDiscountIfApplicable($payment); // TODO: Change the autogenerated stub
    }

    public function calculateFees()
    {
        $refund = $this->source;

        $payment = $refund->payment;

        if (($payment->hasBeenCaptured() === true) or
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

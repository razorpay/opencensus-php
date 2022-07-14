<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Models\Merchant\RazorxTreatment;
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
use RZP\Models\Merchant\Credits;

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

        $this->setMerchantCredits();
    }

    // This is for setting a lock on the merchant's refund credits
    public function setMerchantCredits()
    {
        if(($this->merchant != null)&& ($this->isMerchantRefundCreditsRamped($this->merchant->getId()) === true))
        {
            $this->repo->credits->getMerchantCreditsForRefund($this->txn->merchant);
        }

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
        if (($this->source->getGateway() === Gateway::WALLET_OPENWALLET))
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

    /**
     * This function modifies the values of the transaction created during refund to ensure that if refund credits are enough then the refund is processed through refund credits
     * and in case the credits are not enough and the merchant has fallback mechanism enabled then the refund is processed through his balance.
     */
    public function decideBalanceSource()
    {
         if ($this->merchant == null || $this->merchant->getRefundSource() !== RefundSource::BALANCE)
        {
            return;
        }

        if ($this->isMerchantRefundFallbackEnabled($this->merchant->getId()) === false)
        {
            return;
        }

        $refundCredits = $this->getMerchantCreditsOfType(Credits\Type::REFUND);

        $netAmount = $this->debit;

        if ($refundCredits < $netAmount)
        {
            $this->txn->setDebit($netAmount);

            $this->txn->setCredits(0);

        } else {

            $this->txn->setDebit(0);

            $this->txn->setCredits($netAmount);

            $this->txn->setCreditType(Transaction\CreditType::REFUND);
        }
    }

    /**
     * This function determines whether the merchant has refund fallback mechanism enabled or not.
     *
     * @param $merchantId
     * @return bool
     */
    public function isMerchantRefundFallbackEnabled($merchantId): bool
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::REFUND_FALLBACK_ENABLED_ON_MERCHANT, $mode);

        $this->trace->info(
            TraceCode::SCROOGE_REFUND_SOURCE_FALLBACK_RAZORX,
            [
                'result' => $result,
                'mode' => $mode,
                'merchant_id' => $merchantId,
            ]);

        return (strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON);
    }

    /**
     * This function determines whether the merchant has Refund Credits ramped up to be fetched with a locking read mechanism
     *
     * @param $merchantId
     * @return bool
     */
    public function isMerchantRefundCreditsRamped($merchantId): bool
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::REFUND_CREDITS_WITH_LOCK, $mode);

        $this->trace->info(
            TraceCode::SCROOGE_FETCH_REFUND_CREDITS_WITH_LOCK,
            [
                'result' => $result,
                'mode' => $mode,
                'merchant_id' => $merchantId,
            ]);

        return (strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON);
    }

}

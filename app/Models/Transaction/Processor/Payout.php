<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Merchant\Balance\AccountType;

/**
 * NOTE: Before making any changes here, check Payout\Core
 * for handlePayoutProcessed, handlePayoutReversed, etc
 * We are doing some payout transaction related changes there.
 *
 * Class Payout
 *
 * @package RZP\Models\Transaction\Processor
 */
class Payout extends Base
{
    /** @var PayoutModel\Entity */
    protected $source;

    /**
     * We are overriding this because base function was written very badly. (`hasTransaction`)
     */
    protected function setTransactionForSource()
    {
        $this->setTransaction($this->createNewTransaction());
    }

    public function fillDetails()
    {
        // Overrides channel which is earlier set in parent's setSourceDefaults() method.
        $this->txn->setChannel($this->source->getChannel());
    }

    public function setFeeDefaults()
    {
        if ($this->source->balance->isAccountTypeDirect() === true)
        {
            // In case of CA payouts, fees and tax has already been calculated at time of payout creation.
            // Now we just update the transaction fees data from payout fees data.

            $this->fees = $this->source->getFees();

            $this->tax = $this->source->getTax();

            $pricingRuleId = $this->source->getPricingRuleId();

            $this->feesSplit = $this->getFeeSplitForDirectPayouts($this->fees, $this->tax, $pricingRuleId);
        }
        else
        {
            $this->setMerchantCredits();

            $this->setMerchantFeeDefaults();
        }
    }

    protected function adjustFeeSplitAccordingToCreditsIfApplicable()
    {
        if ($this->source->getFeeType() === CreditType::REWARD_FEE)
        {
            foreach ($this->feesSplit as $key => $feeSplit)
            {
                if ($feeSplit->getName() === Constants\Entity::TAX)
                {
                    $this->feesSplit->forget($key);
                }
            }
        }
    }

    protected function setMerchantCredits()
    {
        $merchantId = $this->txn->merchant->getId();

        $credits = (new Credits\Balance\Core)->getMerchantCreditBalanceAggregatedByProductForEveryType(
                                                                        $merchantId,
                                                                Product::BANKING);

        $rewardFeeCredits = $credits[CreditType::REWARD_FEE] ?? 0;

        $this->rewardFeeCredits = $rewardFeeCredits;
    }

    public function setMerchantFeeDefaults()
    {
        list($this->fees, $this->tax, $this->feesSplit) = (new Pricing\PayoutFee)->calculateMerchantFees($this->source);
    }

    public function calculateFees()
    {
        //
        // In case of on demand, the payout amount is modified. (this is done in the caller)
        // We deduct the fees from the payout and reset the payout amount to (actual_payout_amount - fees).
        // In case of normal payout, the payout amount remains as it is. We charge fees over and above this amount.
        //
        // Hence, in transaction, for on-demand, the transaction amount is (actual_payout_amount - fees)
        // and for normal, the amount is just the actual_payout_amount.
        // We set the fees for both types accordingly.
        //
        // For balance, we need to check whether merchant's balance
        // has enough balance for actual transaction amount plus the fees.
        //

        $amount = $this->source->getAmount();

        if ($this->source->getPayoutType() === PayoutModel\Entity::ON_DEMAND)
        {
            $payoutAmount = $amount - $this->fees;

            if ($payoutAmount < 100)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
                    null,
                    [
                        'amount' => $amount,
                        'fee'    => $this->fees
                    ]);
            }

            $this->debit = $amount;
        }
        else
        {
            if ($this->source->balance->isAccountTypeDirect() === true)
            {
                switch (true)
                {
                    // we are just copying the credits used from payouts to the txn.
                    case $this->source->getFeeType() === CreditType::REWARD_FEE:
                        $this->calculateFeeForRewardFeeCreditForSource();
                        break;

                    default:
                        $this->txn->setCreditType(CreditType::DEFAULT);
                }

                $payoutAmount = $amount;
            }
            else
            {
                switch (true)
                {
                    case (($this->rewardFeeCredits >= $this->fees - $this->tax) and ($this->fees > 0)):
                        $this->calculateFeeForRewardFeeCredit();
                        $payoutAmount = $amount;
                        break;

                    default:
                        $this->calculateFeeDefault();
                        $payoutAmount = $amount + $this->fees;
                }
            }

            $this->debit = $payoutAmount;
        }

        $this->txn->setAmount($payoutAmount);
    }

    public function setOtherDetails()
    {
        parent::setOtherDetails();

        $this->txn->setApiFee($this->fees);
    }

    public function updateTransaction()
    {
        $settledAt = $reconciledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);

        $this->txn->setReconciledAt($reconciledAt);

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setGatewayFee(0);

        $this->txn->setGatewayServiceTax(0);

        $this->updatePostedDate();

        // the transaction is saved in the caller
    }

    public function setMerchantBalanceLockForUpdate()
    {
        //
        // TODO: Remove the second condition later once we backfill payouts
        // with all existing payouts having primaryBalance filled in.
        // Already filled on prod-live. Need to backfill on prod-test.
        //
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    /**
     * This happens only when we are creating a dummy transaction to update an existing external type transaction
     * with the payout's transaction. The balance checks and updates would have been done and deducted already while
     * creating the external transaction. We don't want to do it here again and fail the dummy transaction creation.
     *
     * @return bool
     */
    public function shouldUpdateBalance()
    {
        return $this->source->shouldValidateAndUpdateBalances();
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->validateMerchantBalance();

        parent::updateBalances($negativeLimit);
    }

    protected function validateMerchantBalance()
    {
        $balanceType = $this->merchantBalance->getType();

        $accountType = $this->merchantBalance->getAccountType();

        if (($balanceType === Type::BANKING) and
            ($accountType === AccountType::DIRECT) and
            ($this->merchantBalance->getChannel() === Channel::RBL))
        {
            return ;
        }

        $debitAmount = $this->txn->getAmount();

        if ($this->source->getPayoutType() === PayoutModel\Entity::ON_DEMAND)
        {
            $debitAmount += $this->txn->getFee();
        }

        // TODO: Use locked balance here to throw the exception
        $hasBalance = ($this->merchantBalance->getBalance() >= $debitAmount);

        if ($hasBalance === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
                null,
                [
                    'payout_id'     => $this->source->getId(),
                    'txn_id'        => $this->txn->getId(),
                    'txn_amount'    => $this->txn->getAmount(),
                    'txn_fees'      => $this->txn->getFee(),
                    'payout_amount' => $this->source->getAmount(),
                    'debit_amount'  => $debitAmount,
                    'balance_amount'=> $this->merchantBalance->getBalance()
                ]);
        }
    }

    public function getFeeSplitForDirectPayouts($fees, $tax, $pricingRuleId)
    {
        $this->fees = $fees;

        $this->tax = $tax;

        $calculator = Calculator\Base::make($this->source, Product::BANKING);

        $this->feesSplit = $calculator->getFeeBreakupFromData($fees, $tax, $pricingRuleId);

        $this->adjustFeeSplitAccordingToCreditsIfApplicable();

        return $this->feesSplit;
    }

    public function updateCredits(int $negativeLimit = 0)
    {
        // If the balance is not sufficient we will not use
        // credits since the payout will fail. We don't
        // want a state where we debit the credits but not
        // the banking balance

        $this->validateMerchantBalance();
        // credits will be applied at the time of payout creation
        // for direct account payouts.
        if ($this->source->balance->isAccountTypeDirect() === true)
        {
            return;
        }

        if ($this->txn->isRewardFeeCredits() === true)
        {
            $this->updateFeeRewardCredits();
        }
    }

    protected function updateFeeRewardCredits()
    {
        if (($this->txn->isRewardFeeCredits() === false) or
            ($this->txn->getCredits() === 0))
        {
            return;
        }

        $merchantId = $this->txn->merchant->getId();

        $credits = (new Credits\Balance\Core)->getMerchantCreditBalanceAggregatedByProductForEveryType(
                                                                                $merchantId,
                                                                                Product::BANKING);

        $rewardFeeCredits = $credits[CreditType::REWARD_FEE] ?? 0;

        $fee = $this->txn->getFee();

        if ($rewardFeeCredits < $fee)
        {
            throw new LogicException(
                'RewardFeeCredits should be higher or equal to the fee',
                null,
                [
                    'transaction_id'            => $this->txn->getId(),
                    'merchant_id'               => $merchantId,
                    'reward_fee_credits'        => $rewardFeeCredits,
                    'fee'                       => $fee,
                ]);
        }

        $this->subtractRewardFeeCredits($fee);

        $this->txn->source->setFeeType(CreditType::REWARD_FEE);
    }

    /**
     * The reward fee are credits allotted to merchant by Razorpay
     * The tax component of such payouts will be 0 as these are
     * being given as an incentive to the merchant to use our
     * platform. The fee will be consumed by these credits
     */
    protected function calculateFeeForRewardFeeCredit()
    {
        $amount = $this->txn->getAmount();

        $source = $this->source;

        $this->trace->info(
            TraceCode::TRANSACTION_REWARD_FEE_CREDITS,
            [
                'source_type'    => $this->txn->getType(),
                'source_id'      => $source->getId(),
                'amount'         => $amount,
            ]);

        $this->fees -= $this->tax;

        $this->txn->setFee($this->fees);

        $this->txn->setTax($this->tax);

        $rewardFeeCredits = $this->txn->getFee();

        $this->tax = 0;

        $this->txn->setCreditType(CreditType::REWARD_FEE);

        $this->txn->setCredits($rewardFeeCredits);

        foreach ($this->feesSplit as $key => $feeSplit)
        {
            if ($feeSplit->getName() === Constants\Entity::TAX)
                {
                    $this->feesSplit->forget($key);
                }
        }
    }

    protected function subtractRewardFeeCredits($amount)
    {
        (new Credits\Transaction\Core)->subtractMerchantCreditBalanceAndCreateTransactions(
                                                    $this->txn->merchant,
                                                    CreditType::REWARD_FEE,
                                                    Product::BANKING,
                                                    $amount,
                                                    $this->txn->source);
    }

    protected function calculateFeeForRewardFeeCreditForSource()
    {
        $this->txn->setCreditType(CreditType::REWARD_FEE);

        $rewardFeeCredits = (new Credits\Transaction\Core)->getCreditsForSource($this->source);

        $this->txn->setCredits($rewardFeeCredits);
    }
}

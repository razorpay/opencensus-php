<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Transaction\Core;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Transaction\Entity;
use RZP\Constants as RzpConstants;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccount\Channel as BankingAccountChannel;

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

    public function createTransactionForLedger($txnId, $newBalance, $createdAt = null)
    {
        // Let us first check if a transaction is already created with this ID or not.
        // May be possible that at high TPS, multiple workers pick the same SQS job.
        $existingTxn = $this->repo->transaction->find($txnId);

        if ($existingTxn !== null)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_ALREADY_EXISTS
            );

            // returning fee split as null, as a fee breakup shall already be created
            return [$existingTxn, null];
        }

        $txn = new Entity;

        $txn->setId($txnId);
        $txn->setSettledAt(false);
        $txn->sourceAssociate($this->source);
        $txn->merchant()->associate($this->source->merchant);

        $this->setTransaction($txn);

        $this->setSourceDefaults();

        $channel = $this->source->getChannel();

        if (($this->txn->getType() === RzpConstants\Entity::PAYOUT) and
            (empty($channel) === false))
        {
            $this->txn->setChannel($channel);
        }

        $this->fillDetails();

        $this->setCreditDebitDetails($this);

        $this->updateTransaction();

        $merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $oldBalance = $merchantBalance->getBalance();

        $this->txn->accountBalance()->associate($merchantBalance);

        $merchantBalance->setAttribute(Balance\Entity::BALANCE, $newBalance);

        $this->repo->balance->updateBalance($merchantBalance);

        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_DATA,
            [
                'merchant_id' => $txn->getMerchantId(),
                'new_balance' => $newBalance,
                'old_balance' => $oldBalance,
                'method'      => __METHOD__,
            ]);

        $this->txn->setBalance($newBalance, 0, false);

        if (empty($createdAt) === false)
        {
            $this->txn->setCreatedAt($createdAt);
            $this->txn->setSettledAt($createdAt);
            $this->txn->setPostedDate($createdAt);
        }

        if (in_array($this->txn->getType(), Constants::DO_NOT_DISPATCH_FOR_SETTLEMENT, true) === false)
        {
            (new Core)->dispatchForSettlementBucketing($txn);
        }

        return [$this->txn, $this->feesSplit];
    }

    /**
     * We are overriding this because base function was written very badly. (`hasTransaction`)
     */
    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
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

            $this->feesSplit = $this->getFeeSplitForPayouts($this->fees, $this->tax, $pricingRuleId);
        }
        else
        {
            // If merchant is already migrated to new credits flow, the payout fees and tax
            // would have been already calculated by now, so we will be doing something
            // exactly same as what we do in CA payouts. We will be copying fees and tax
            // of payout to transaction.
            $merchant = $this->source->merchant;

            //We only assign the pricing rule Id for fund account payouts, so only consuming for fund account payouts. For other types pricing rule id might not be set.
            if($this->source->hasFundAccount() === true)
            {
                $this->fees = $this->source->getFees();

                $this->tax = $this->source->getTax();

                $pricingRuleId = $this->source->getPricingRuleId();

                $this->feesSplit = $this->getFeeSplitForPayouts($this->fees, $this->tax, $pricingRuleId);
            }
            else
            {
                $this->setMerchantFeeDefaults();
            }
        }
    }

    protected function adjustFeeSplitAccordingToCreditsIfApplicable()
    {
        if ($this->source->getFeeType() === CreditType::REWARD_FEE)
        {
            foreach ($this->feesSplit as $key => $feeSplit)
            {
                if ($feeSplit->getName() === RzpConstants\Entity::TAX)
                {
                    $this->feesSplit->forget($key);
                }
            }
        }
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
        // The below block handles cases for fund account payout
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
                $merchant = $this->source->merchant;

                // if the merchant has new credit flow feature enabled, in that case
                // merchant can have either credits applied or free payouts or neither
                // of them, in that case we will set the credit type of txn as default
                switch (true)
                {
                    case $this->source->getFeeType() === CreditType::REWARD_FEE:
                        $this->updateTransactionWithRewardFeeCreditsDetails();
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
        // We don't want to update the balance because in this scenario, we would have already deducted the balance
        if ($this->source->isBalancePreDeducted() === true)
        {
            return false;
        }

        return $this->source->shouldValidateAndUpdateBalances();
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->validateMerchantBalance();

        if ($this->source->isBalancePreDeducted() === true)
        {
            $this->updateMerchantBalance($negativeLimit);

            return;
        }

        parent::updateBalances($negativeLimit);
    }

    protected function validateMerchantBalance()
    {
        $balanceType = $this->merchantBalance->getType();

        $accountType = $this->merchantBalance->getAccountType();

        if (($balanceType === Type::BANKING) and
            ($accountType === AccountType::DIRECT) and
            (in_array($this->merchantBalance->getChannel(), BankingAccountChannel::$directTypeChannels, true) === true))
        {
            return ;
        }

        if ($this->source->isBalancePreDeducted() === true)
        {
            $debitAmount = $this->source->getAmount() + $this->source->getFees();
        }
        else
        {
            $debitAmount = $this->txn->getAmount();

            if ($this->source->getPayoutType() === PayoutModel\Entity::ON_DEMAND)
            {
                $debitAmount += $this->txn->getFee();
            }
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
                    'txn_id'        => optional($this->txn)->getId(),
                    'txn_amount'    => optional($this->txn)->getAmount(),
                    'txn_fees'      => optional($this->txn)->getFee(),
                    'payout_amount' => $this->source->getAmount(),
                    'debit_amount'  => $debitAmount,
                    'balance_amount'=> $this->merchantBalance->getBalance()
                ]);
        }
    }

    public function getFeeSplitForPayouts($fees, $tax, $pricingRuleId)
    {
        $this->fees = $fees;

        $this->tax = $tax;

        $calculator = Calculator\Base::make($this->source, Product::BANKING);

        $this->feesSplit = $calculator->getFeeBreakupFromData($fees, $tax, $pricingRuleId);

        $this->adjustFeeSplitAccordingToCreditsIfApplicable();

        return $this->feesSplit;
    }

    protected function updateTransactionWithRewardFeeCreditsDetails()
    {
        $this->txn->setCreditType(CreditType::REWARD_FEE);

        $this->txn->setCredits($this->fees);

        // since tax is 0 for reward_fee payouts, we are
        // just ensuring no row gets created for tax in
        // feesplit for transaction of this payout
        foreach ($this->feesSplit as $key => $feeSplit)
        {
            if ($feeSplit->getName() === RzpConstants\Entity::TAX)
            {
                $this->feesSplit->forget($key);
            }
        }
    }

    protected function calculateFeeForRewardFeeCreditForSource()
    {
        $this->txn->setCreditType(CreditType::REWARD_FEE);

        $rewardFeeCredits = (new Credits\Transaction\Core)->getCreditsForSource($this->source);

        $this->txn->setCredits($rewardFeeCredits);
    }

    public function preDeductBalanceForPayout($fees, $tax)
    {
        $startTime = microtime(true);

        try
        {
            $this->fees = $fees;

            $this->tax = $tax;

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_INIT);

            $lockStartTime = microtime(true);

            // update merchant credits an balances
            $this->setMerchantBalanceLockForUpdate();

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_TIME_TAKEN,
                               [
                                   'lock_start_time'   => (microtime(true) - $lockStartTime) * 1000
                               ]
            );

            $this->updateBalances();
        }
        finally
        {
            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_TIME_TAKEN,
                               [
                                   'txn_type'              => $this->txn ? $this->txn->getType() : '',
                                   'async_update'          => false,
                                   'balance_update_time'   => (microtime(true) - $startTime) * 1000
                               ]
            );
        }
    }

    public function updateMerchantBalance(int $negativeLimit = 0)
    {
        if ($this->source->isBalancePreDeducted() === true)
        {
            $merchantBalance = $this->merchantBalance;

            $oldBalance = $merchantBalance->getBalance();

            $netAmount = -1 * ($this->source->getAmount() + $this->fees);

            $merchantBalance->updateBalance(null, $negativeLimit, $netAmount);

            $newBalance = $this->merchantBalance->getBalance();

            $this->trace->info(TraceCode::MERCHANT_BALANCE_DATA,
                               [
                                   'merchant_id' => $this->source->getMerchantId(),
                                   'new_balance' => $newBalance,
                                   'old_balance' => $oldBalance,
                                   'method'      => 'updateMerchantBalance',
                               ]);

            $this->repo->balance->updateBalance($this->merchantBalance);
        }
        else
        {
            parent::updateMerchantBalance($negativeLimit);
        }
    }

    public function setFeeDefaultsWithoutBalanceDeduction()
    {
        $this->fees = $this->source->getFee();
        $this->tax  = $this->source->getTax();
    }

    public function createTransactionWithoutBalanceDeduction()
    {
        // Creates new or fetches existing transaction entity for the source entity
        $this->setTransactionForSource();

        // set transaction attributes from the source entity
        $this->setSourceDefaults();

        // fills the transaction attributes from the merchant attributes
        $this->fillDetails();

        $merchant = $this->source->merchant;

        $this->txn->setFeeModel($merchant->getFeeModel());

        $this->txn->setFeeBearer($merchant->getFeeBearer());

        // fetches credits, balance and calculates fees and taxes
        $this->setFeeDefaultsWithoutBalanceDeduction();

        // calculates fee sources and calculates credit and debit amounts
        $this->calculateFees();

        // update credit and debit amounts, fees and taxes in transaction
        $this->setOtherDetails();

        // updates entity specific attributes in transaction
        $this->updateTransaction();

        return $this->txn;
    }

        public function incrementBalanceForPayout($netAmount)
    {
        $startTime = microtime(true);

        try
        {
            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_INIT);

            $lockStartTime = microtime(true);

            // update merchant credits an balances
            $this->setMerchantBalanceLockForUpdate();

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_TIME_TAKEN,
                               [
                                   'lock_start_time'   => (microtime(true) - $lockStartTime) * 1000
                               ]
            );

            $this->incrementMerchantBalance($netAmount);
        }
        finally
        {
            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_TIME_TAKEN,
                               [
                                   'txn_type'              => $this->txn ? $this->txn->getType() : '',
                                   'async_update'          => false,
                                   'balance_update_time'   => (microtime(true) - $startTime) * 1000
                               ]
            );
        }
    }

    public function incrementMerchantBalance($netAmount)
    {
        if ($this->source->isBalancePreDeducted() === true)
        {
            $merchantBalance = $this->merchantBalance;

            $oldBalance = $merchantBalance->getBalance();

            $merchantBalance->updateBalance(null, 0, $netAmount);

            $newBalance = $this->merchantBalance->getBalance();

            $this->trace->info(TraceCode::MERCHANT_BALANCE_DATA,
                               [
                                   'merchant_id' => $this->source->getMerchantId(),
                                   'new_balance' => $newBalance,
                                   'old_balance' => $oldBalance,
                                   'method'      => 'incrementMerchantBalance',
                               ]);

            $this->repo->balance->updateBalance($this->merchantBalance);
        }
    }
}

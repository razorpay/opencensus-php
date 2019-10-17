<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Payout as PayoutModel;
use RZP\Exception\BadRequestException;
use RZP\Models\Transaction\ReconciledType;

class Payout extends Base
{
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
        $this->setMerchantFeeDefaults();
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
            $payoutAmount = $amount + $this->fees;

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

        // the transaction is saved in the caller
    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill payouts
        // with all existing payouts having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    public function updateBalances(bool $updateNodalBalance = true)
    {
        $this->validateMerchantBalance();

        parent::updateBalances($updateNodalBalance);
    }

    protected function validateMerchantBalance()
    {
        $debitAmount = $this->txn->getAmount();

        if ($this->source->getPayoutType() === PayoutModel\Entity::ON_DEMAND)
        {
            $debitAmount += $this->txn->getFee();
        }

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
                ]);
        }
    }
}

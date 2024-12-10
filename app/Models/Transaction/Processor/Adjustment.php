<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;

class Adjustment extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount(abs($this->source->getAmount()));

        $this->txn->setChannel($this->source->getChannel());
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function setFeeDefaultsForDualWrite($fees, $tax)
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $amount = $this->source->getAmount();

        if ($amount > 0)
        {
            $this->credit = $amount;
        }

        if ($amount < 0)
        {
            $this->debit = abs($amount);
        }
    }

    public function calculateFeesForDualWrite($fees, $tax, $feeCreditsUsed, $amountCreditsUsed, $refundCreditsUed)
    {
        $amount = $this->source->getAmount();

        if ($amount > 0)
        {
            $this->credit = $amount;
        }

        if ($amount < 0)
        {
            $this->debit = abs($amount);
        }
    }

    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(Transaction\ReconciledType::NA);

        $this->updatePostedDate();

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we back fill adjustment balance_id
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    /**
     * This function is responsible to update the merchant balance, set fee breakup entity
     * @param $newBalance
     * @return PublicCollection feeSplit
     * @throws Exception\LogicException
     */
    public function updateBalanceForLedger($newBalance)
    {
        // define fee split entity
        $this->setFeeDefaults();

        $merchantBalance = $this->source->balance ?? $this->source->merchant->primaryBalance;

        $oldBalance = $merchantBalance->getBalance();

        $merchantBalance->setAttribute(Merchant\Balance\Entity::BALANCE, $newBalance);

        $this->repo->balance->updateBalance($merchantBalance);

        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_DATA,
            [
                'merchant_id' => $this->source->merchant->getMerchantId(),
                'new_balance' => $newBalance,
                'old_balance' => $oldBalance,
                'method'      => __METHOD__,
            ]);

        return $this->feesSplit;
    }

    /**
     * This function will update the merchant balance, save fee breakup entity
     * @param $entityId
     * @param $txnId
     * @param $newBalance
     * @throws Exception\LogicException
     */
    public function updateBalanceForLedgerReverseShadow($entityId, $txnId, $newBalance)
    {
        $this->trace->info(
            TraceCode::BALANCE_UPDATE_FOR_LEDGER_REVERSE_SHADOW_BEGINS,
            [
                'entity_id' => $entityId,
            ]
        );

        $feeSplit = $this->updateBalanceForLedger($newBalance);

        if ($feeSplit !== null)
        {
            (new Transaction\Core)->saveFeeDetailsWithoutTransactionAssociation($txnId, $entityId, $feeSplit);
        }

        $this->trace->info(
            TraceCode::BALANCE_FOR_LEDGER_REVERSE_SHADOW_UPDATED,
            [
                'entity_id' => $entityId,
            ]
        );
    }
}

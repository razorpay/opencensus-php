<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Pricing\Feature;
use RZP\Models\Reversal as ReversalModel;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

/**
 * Class Reversal
 *
 * This cannot be used as it is for other types of reversals.
 * Mainly due to setting of settlement time and such.
 *
 * @package RZP\Models\Transaction\Processor
 *
 * @property Transaction\Entity   $txn
 * @property ReversalModel\Entity $source
 */
class Reversal extends Base
{
    public function createTransactionForLedger($txnId, $newBalance)
    {
        // Let us first check if a transaction is already created with this ID or not.
        // May be possible that at high TPS, multiple workers pick the same SQS job.
        $existingTxn = $this->repo->transaction->find($txnId);

        if ($existingTxn !== null)
        {
            // returning fee split as null, as a fee breakup shall already be created
            return [$existingTxn, null];
        }

        $txn = new Transaction\Entity;

        $txn->setId($txnId);
        $txn->setSettledAt(false);
        $txn->sourceAssociate($this->source);
        $txn->merchant()->associate($this->source->merchant);

        $this->setTransaction($txn);

        $this->setSourceDefaults();

        $channel = $this->source->getChannel();

        if (($this->txn->getType() === E::REVERSAL) and
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

        $merchantBalance->setAttribute(Merchant\Balance\Entity::BALANCE, $newBalance);

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

        if (in_array($this->txn->getType(), Constants::DO_NOT_DISPATCH_FOR_SETTLEMENT, true) === false)
        {
            (new Transaction\Core)->dispatchForSettlementBucketing($txn);
        }

        return [$this->txn, $this->feesSplit];
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

        // update fee split entity
        $this->updateFeesSplitForLedgerReverseShadow();

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

    /**
     * {@inheritdoc}
     *
     * @see Base::setTransactionForSource()
     */
    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::fillDetails()
     */
    public function fillDetails()
    {
        $amount = $this->source->getAmount();

        $this->txn->setAmount($amount);

        // We are only filling reversal channel for payout types.
        // For transfers, we are not. Ideally, we should. But, later.
        $channel = $this->source->getChannel() ?? $this->source->merchant->getChannel();

        $this->txn->setChannel($channel);
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::setFeeDefaults()
     */
    public function setFeeDefaults()
    {
        $this->fees = 0;

        $this->tax = 0;
    }

    public function setFeeDefaultsForDualWrite($fees, $tax)
    {
        $this->fees = 0;
        $this->tax  = 0;
    }


    /**
     * {@inheritdoc}
     *
     * @see Base::calculateFees()
     */
    public function calculateFees()
    {
        $creditAmount = $this->source->getAmount() + $this->source->getFee();
        $debitAmount = $this->source->entity->getAmount() + $this->source->entity->getFee();

        if ($this->source->entity->transaction->isPostpaid() === true)
        {
            $creditAmount -= $this->source->getFee();
            $debitAmount -= $this->source->entity->getFee();

            $this->txn->setFeeModel(Merchant\FeeModel::POSTPAID);
        }

        $this->credit = $creditAmount;

        // These checks are specifically used for refunds
        if ($this->source->getEntityType() === Entity::REFUND)
        {
            // Checking if refund source is credits
            if (($this->source->entity->transaction->getDebit() === 0) and
                ($this->source->entity->transaction->getCredits() > 0))
            {
                $this->txn->setCredits(-1 * $this->credit);

                $this->txn->setCreditType(Transaction\CreditType::REFUND);

                $this->credit = 0;
            }
        }

        if ($this->source->getFee() > 0)
        {
            $feeParams = [
                Transaction\FeeBreakup\Entity::NAME       => Feature::REFUND,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * ($this->source->getFee() - $this->source->getTax()),
            ];

            $taxParams = [
                Transaction\FeeBreakup\Entity::NAME       => FeeBreakupName::TAX,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * $this->source->getTax(),
            ];

            $fee = (new Transaction\FeeBreakup\Entity)->build($feeParams);
            $tax = (new Transaction\FeeBreakup\Entity)->build($taxParams);

            $this->feesSplit->push($fee);
            $this->feesSplit->push($tax);
        }
    }

    public function calculateFeesForDualWrite($fees, $tax, $feeCreditsUsed, $amountCreditsUsed, $refundCreditsUed)
    {
        $creditAmount = $this->source->getAmount() + $this->source->getFee();
        $debitAmount = $this->source->entity->getAmount() + $this->source->entity->getFee();

        if ($this->source->entity->transaction->isPostpaid() === true)
        {
            $creditAmount -= $this->source->getFee();
            $debitAmount -= $this->source->entity->getFee();

            $this->txn->setFeeModel(Merchant\FeeModel::POSTPAID);
        }

        $this->credit = $creditAmount;

        // These checks are specifically used for refunds
        if ($this->source->getEntityType() === Entity::REFUND)
        {
            // Checking if refund source is credits
            if (($this->source->entity->transaction->getDebit() === 0) and
                ($this->source->entity->transaction->getCredits() > 0))
            {
                $this->txn->setCredits(-1 * $this->credit);

                $this->txn->setCreditType(Transaction\CreditType::REFUND);

                $this->credit = 0;
            }
        }

        if ($this->source->getFee() > 0)
        {
            $feeParams = [
                Transaction\FeeBreakup\Entity::NAME       => Feature::REFUND,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * ($this->source->getFee() - $this->source->getTax()),
            ];

            $taxParams = [
                Transaction\FeeBreakup\Entity::NAME       => FeeBreakupName::TAX,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * $this->source->getTax(),
            ];

            $fee = (new Transaction\FeeBreakup\Entity)->build($feeParams);
            $tax = (new Transaction\FeeBreakup\Entity)->build($taxParams);

            $this->feesSplit->push($fee);
            $this->feesSplit->push($tax);
        }
    }

    // similar to calculateFees but transaction is not being updated
    public function updateFeesSplitForLedgerReverseShadow()
    {
        if ($this->source->getFee() > 0)
        {
            $feeParams = [
                Transaction\FeeBreakup\Entity::NAME       => Feature::REFUND,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * ($this->source->getFee() - $this->source->getTax()),
            ];

            $taxParams = [
                Transaction\FeeBreakup\Entity::NAME       => FeeBreakupName::TAX,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * $this->source->getTax(),
            ];

            $fee = (new Transaction\FeeBreakup\Entity)->build($feeParams);
            $tax = (new Transaction\FeeBreakup\Entity)->build($taxParams);

            $this->feesSplit->push($fee);
            $this->feesSplit->push($tax);
        }
    }

    public function setOtherDetails()
    {
        parent::setOtherDetails();

        $this->txn->setApiFee($this->fees);

        $this->txn->setFee(-1 * $this->source->getFee());

        $this->txn->setTax(-1 * $this->source->getTax());
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::updateTransaction()
     */
    public function updateTransaction()
    {
        $settledAt = $reconciledAt = Carbon::now(Timezone::IST)->getTimestamp();

        // In case of payout reversal, reversal settlement
        // will be instant since payout settlement is.
        $this->txn->setSettledAt($settledAt);

        $this->txn->setReconciledAt($reconciledAt);

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setGatewayFee(0);

        $this->txn->setGatewayServiceTax(0);

        $reversalType = $this->source->getEntityType();

        switch ($reversalType)
        {
            case E::TRANSFER:
                // TODO: Call settled_at calculation method here.
                break;
        }

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updatePostedDate();

        // Save is necessary here to create CreditReversalTransaction
        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill reversals
        // with all existing reversals having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

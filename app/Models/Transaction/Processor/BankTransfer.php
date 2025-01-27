<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

/**
 * For business banking we directly create transaction against bank transfer,
 * and the merchant's banking balance is credited. There is no other use for it
 * right now in normal pg flow.
 */
class BankTransfer extends Base
{
    /**
     * {@inheritDoc}
     */
    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
    }

    /**
     * {@inheritDoc}
     */
    public function fillDetails()
    {
        $this->txn->setAmount($this->source->getAmount());

        // Overrides channel which is earlier set in parent's setSourceDefaults() method.
        $this->txn->setChannel(Settlement\Channel::YESBANK);
    }

    /**
     * {@inheritDoc}
     */
    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance;

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

    /**
     * {@inheritDoc}
     */
    public function updateTransaction()
    {
        $this->updatePostedDate();
    }

    /**
     * {@inheritDoc}
     */
    public function calculateFees()
    {
        $this->credit = $this->source->getAmount();
    }

}

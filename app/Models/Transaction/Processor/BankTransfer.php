<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Settlement;

/**
 * Use case: For business banking we directly create transaction against bank
 * transfer, merchant's banking balance is credited.
 * There is no other use for it right now in normal pg flow.
 */
class BankTransfer extends Base
{
    /**
     * {@inheritDoc}
     */
    protected function setTransactionForSource()
    {
        $this->setTransaction($this->createNewTransaction());
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
    protected function setMerchantBalance()
    {
        $this->merchantBalance = $this->txn->merchant->bankingBalance();
    }

    /**
     * {@inheritDoc}
     */
    protected function setMerchantBalanceLockForUpdate()
    {
        $this->setMerchantBalance();
        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    /**
     * {@inheritDoc}
     */
    public function updateTransaction()
    {
        $this->repo->saveOrFail($this->txn);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateFees()
    {
        $this->credit = $this->source->getAmount();
    }
}

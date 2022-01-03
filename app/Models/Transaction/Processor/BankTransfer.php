<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Settlement;
use RZP\Trace\TraceCode;

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

<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\External as ExternalModel;
use RZP\Models\BankingAccountStatement\Type;

class External extends Base
{
    /** @var ExternalModel\Entity */
    protected $source;

    /**
     * We are overriding this because base function was written very badly. (`hasTransaction`)
     */
    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
    }

    public function fillDetails()
    {
        $this->txn->setChannel($this->source->getChannel());

        $this->txn->setAmount($this->source->getAmount());
    }

    public function setFeeDefaults()
    {
        $this->fees = $this->tax = $this->feesSplit = 0;
    }

    public function calculateFees()
    {
        $type = $this->source->getType();

        $amount = $this->source->getBaseAmount();

        if ($type === Type::CREDIT)
        {
            $this->credit = $amount;
        }
        else
        {
            $this->debit = $amount;
        }
    }

    public function updateTransaction()
    {
        $this->updatePostedDate();
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

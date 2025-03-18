<?php
namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

class BundleFee extends Base
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

    public function setFeeDefaults()
    {
        $this->fees = $this->source->getAmount();
        $this->tax  = $this->source->getTax();
    }

    function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(Transaction\ReconciledType::NA);

        $this->updatePostedDate();

        $this->repo->saveOrFail($this->txn);
    }

    public function calculateFees()
    {
        $amount = $this->source->getAmount();
        $isReversal = $this->source->isReversal();

        if ($isReversal === false)
        {
            $this->debit = $amount;
            $this->credit = 0;
        }
        else
        {
            $this->debit = 0;
            $this->credit = $amount;
        }
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

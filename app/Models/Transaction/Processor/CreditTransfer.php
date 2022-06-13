<?php


namespace RZP\Models\Transaction\Processor;


use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transaction\Core;
use RZP\Models\Transaction\Entity;
use RZP\Models\Transaction\ReconciledType;
use RZP\Constants\Entity as EntityConstants;

class CreditTransfer extends Base
{
    public function fillDetails()
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

        $this->txn->setAmount(abs($this->source->getAmount()));

        $this->txn->setChannel($this->source->getChannel());
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

        $this->repo->saveOrFail($this->txn);
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {

    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we back fill creditTransfer
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    public function createTransactionForLedger($txnId, $newBalance)
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

        if (($this->txn->getType() === EntityConstants::CREDIT_TRANSFER) and
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

        if (in_array($this->txn->getType(), Constants::DO_NOT_DISPATCH_FOR_SETTLEMENT, true) === false)
        {
            (new Core)->dispatchForSettlementBucketing($txn);
        }

        return [$this->txn, $this->feesSplit];
    }
}

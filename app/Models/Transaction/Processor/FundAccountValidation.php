<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Transaction\Processor\Base as BaseProcessor;

class FundAccountValidation extends BaseProcessor
{
    public function createTransactionForLedger($txnId, $newBalance)
    {
        // Let us first check if a transaction is already created with this ID or not.
        // May be possible that at high TPS, multiple workers pick the same SQS job.
        $existingTxn = $this->repo->transaction->find($txnId);

        if ($existingTxn !== null)
        {
            return $existingTxn;
        }

        $txn = new Transaction\Entity;

        $txn->setId($txnId);
        $txn->setSettledAt(false);
        $txn->sourceAssociate($this->source);
        $txn->merchant()->associate($this->source->merchant);

        $this->setTransaction($txn);

        $this->setSourceDefaults();

        $this->fillDetails();

        $this->setFeeDefaultsForLedger();

        $this->calculateFees();

        $this->setOtherDetails();

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
            (new Transaction\Core)->dispatchForSettlementBucketing($txn);
        }

        return $this->txn;
    }

    public function setFeeDefaultsForLedger()
    {
        $this->amountCredits = 0;
        $this->feeCredits    = 0;
        $this->fees          = $this->source->getFees();
        $this->tax           = $this->source->getTax();
    }

    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
    }

    public function updateTransaction()
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_VALIDATION_CREATE_TRANSACTION,
            [
                'fund_account_id' => $this->source->getId()
            ]);

        $this->txn->setSettled(false);

        $nowTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $nowTimestamp);

        $this->updatePostedDate();

        $this->repo->saveOrFail($this->txn);
    }

    public function fillDetails()
    {
        $merchant = $this->source->merchant;

        $this->txn->setFeeModel($merchant->getFeeModel());

        $this->txn->setFeeBearer(FeeBearer::PLATFORM);

        $amount = $this->source->getBaseAmount();

        $this->txn->setAmount($amount);
    }

    public function calculateFees()
    {
        switch (true)
        {
            case (($this->feeCredits >= $this->fees) and
                  ($this->source->balance['type'] === Balance\Type::PRIMARY)):
                $this->calculateFeeForFeeCredit();
                break;

            default:
                $this->calculateFeeDefault();
        }

        $this->credit = 0;

        // We should get an Exception if balance is going negative while updating
        $this->debit  = $this->getNetAmount();
    }

    public function getNetAmount()
    {
        switch (true)
        {
            case ($this->txn->isPostpaid() === true):
            case ($this->txn->getCreditType() === Transaction\CreditType::FEE):
                return 0;
            default:
                return $this->fees;
        }
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    protected function setMerchantBalance()
    {
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;
    }

    public function setFeeDefaults()
    {
        if ($this->source->balance['type'] === Balance\TYPE::BANKING)
        {
            $this->amountCredits = 0;
            $this->feeCredits    = 0;

            $this->setMerchantFeeDefaults();
        }
        else
        {
            parent::setFeeDefaults();
        }
    }
}

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
    protected function setTransactionForSource()
    {
        $this->setTransaction($this->createNewTransaction());
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

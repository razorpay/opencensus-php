<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Currency;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Models\Payment as PaymentEntity;
use RZP\Models\Base as BaseCollection;
use RZP\Models\Schedule\Library as ScheduleLibrary;
use RZP\Models\Schedule\Task as ScheduleTask;

class Payment extends Base
{
    public function updateTransaction()
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id' => $this->source->getId()
            ]);

        $this->repo->saveOrFail($this->txn);

        $settledAt = $this->getSettledAtTimestamp();

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);
    }

    public function createTransaction()
    {
        if ($this->source->isAuthorized() === true)
        {
             $this->trace->info(
                TraceCode::PAYMENT_AUTHORIZE_CREATE_TRANSACTION,
                [
                    'payment_id' => $this->source->getId()
                ]);
            // Creates new or fetches existing transaction entity for the source entity
            $this->setTransactionForSource();

            // set transaction attributes from the source entity
            $this->setSourceDefaults();

            return $this->fillEmptyTxnFeesAndAmount();
        }

        return parent::createTransaction();
    }

    protected function shouldUpdateBalance()
    {
        if ($this->source->isAuthorized() === true)
        {
            return false;
        }

        return true;
    }

    protected function fillEmptyTxnFeesAndAmount()
    {
        $amount = $this->source->getBaseAmount();

        $values = [
            Transaction\Entity::DEBIT               => 0,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::FEE                 => 0,
            Transaction\Entity::TAX                 => 0,
            Transaction\Entity::AMOUNT              => $amount,
        ];

        $this->txn->fill($values);

        return [$this->txn, new BaseCollection\PublicCollection];

    }

    public function calculateFees()
    {
        switch (true)
        {
            case ($this->txn->isFeeBearerCustomer()):
                $this->calculateFeeDefault();
                break;

            // @todo: Need to rethink this.
            case (($this->amountCredits > 0) and ($this->source->getAmount() !== 0)):
                $this->calculateFeeForAmountCredit();
                break;

            case ($this->feeCredits >= $this->fees):
                $this->calculateFeeForFeeCredit();
                break;

            default:
                $this->calculateFeeDefault();
        }

        $amount = $this->getNetAmount();

        $this->credit = 0;
        $this->debit  = 0;

        if ($amount > 0)
        {
            $this->credit = $amount;
        }
        else
        {
            $this->debit = -1 * $amount;
        }
    }

    public function getNetAmount()
    {
        $amount = $this->txn->getAmount();

        if ($this->source->isDirectSettlement() === true)
        {
            $amount = 0;
        }

        $netAmount = 0;

        switch (true)
        {
            case ($this->txn->isPostpaid() === true):
            case ($this->txn->getCreditType() === Transaction\CreditType::FEE):
            case ($this->txn->getCreditType() === Transaction\CreditType::AMOUNT):
                $netAmount = $amount;
                break;

            default:
                $netAmount = $amount - $this->fees;
        }

        return $netAmount;
    }

    protected function getSettledAtTimestamp()
    {
        $payment = $this->source;

        $capturedAt = $payment->getAttribute(PaymentEntity\Entity::CAPTURED_AT);

        $merchant = $payment->merchant;

        $scheduleTask = (new ScheduleTask\Core)->getMerchantSettlementSchedule(
            $merchant,
            $payment->getMethod(),
            $payment->isInternational());

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime($capturedAt, $schedule, $nextRunAt);
        }
        else
        {
            $addDays = $payment->isInternational() === true ?
                        Merchant\Entity::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY :
                        Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }

        return $returnTime;
    }
}

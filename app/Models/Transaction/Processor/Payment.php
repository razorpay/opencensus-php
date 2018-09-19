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
    public function setSourceDefaults()
    {
        $txnData = [
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::CHANNEL         => $this->source->merchant->getChannel(),
        ];

        $this->txn->fill($txnData);
    }

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

        $this->setCredit();

        $this->setDebit();
    }


    public function setCredit()
    {
        $credit = 0;

        switch (true)
        {
            case ($this->txn->isPostpaid() === true):
            case ($this->txn->getCreditType() === Transaction\CreditType::FEE):
            case ($this->txn->getCreditType() === Transaction\CreditType::AMOUNT):

                $credit = $this->txn->getAmount();
                break;

            default:
                $credit = $this->txn->getAmount() - $this->fees;
        }

        $this->credit = $credit;
    }

    public function setDebit()
    {
        $debit = 0;

        if ($this->credit < 0)
        {
            $debit = -1 * $this->credit;
        }

        $this->debit = $debit;
    }

    protected function getSettledAtTimestamp()
    {
        $payment = $this->source;

        $capturedAt = $payment->getAttribute(PaymentEntity\Entity::CAPTURED_AT);

        $merchant = $payment->merchant;

        $scheduleTask = (new ScheduleTask\Core)->getMerchantSettlementSchedule($merchant, $payment->getMethod());

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime($capturedAt, $schedule, $nextRunAt);
        }
        else
        {
            $addDays = Merchant\Entity::SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }

        return $returnTime;
    }
}

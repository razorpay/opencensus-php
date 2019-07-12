<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base as BaseCollection;
use RZP\Models\Payment as PaymentEntity;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Schedule\Library as ScheduleLibrary;

class Payment extends Base
{
    public function updateTransaction()
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id' => $this->source->getId()
            ]);

        $this->checkAndSetTxnReconciliation();

        $this->repo->saveOrFail($this->txn);

        $settledAt = $this->getSettledAtTimestamp();

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);
    }

    private function checkAndSetTxnReconciliation()
    {
        if ($this->source->getGateway() === Gateway::WALLET_OPENWALLET)
        {
            $this->txn->setReconciledAt(time());

            $this->txn->setReconciledType(ReconciledType::NA);
        }
    }

    public function createTransaction()
    {
        //
        // We have the check on captured_at because of the following reason:
        // - A payment happens on an auth&capture supported gateway. This means
        //   that the transaction will get created on `capture` in the normal flow.
        // - Capture completes and then API/DB goes down, due to which the transaction
        //   is not created. Also, payment is not marked as `captured`.
        // - This payment is refunded.
        // - Now we find out that this payment was actually successful from the bank side and that the capture is
        //   complete from the bank side. So we try to create a nodal transaction for this (not merchant transaction
        //   since it's not captured by the merchant). Note that the payment status is `refunded` currently.
        // - When we call `createTransaction`, the payment can either be ONLY gateway_captured or merchant_captured.
        //   If it's NOT merchant_captured, we create a nodal transaction. If it's merchant_captured, we create
        //   merchant transaction + nodal transaction (taken care by the parent::createTransaction).
        // - NOTE: `merchant_captured` is signified by `captured_at` value being set or not.
        //

        if ($this->source->hasBeenCaptured() === false)
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
        //
        // ======================================================================================
        // NOTE: DO READ THIS COMPLETELY AND UNDERSTAND WHAT IS BEING DONE BEFORE MAKING CHANGES!
        // ======================================================================================
        //
        // In some flows, we would have taken a transaction and closed it after a lot
        // of things. This causes an issue since in this flow, we take a lock on balance
        // and we don't release the lock until the transaction is complete. So, we might
        // want to take the lock on balance and do the necessary updates during the
        // transaction closure time instead of doing it here.
        //
        //
        // When we are doing this, ensure that the source handles `shouldUpdateBalance` also.
        // In case of payments, we are setting this flag only on capture. Hence, not an issue.
        // Needs to be done source-by-source basis (see what I did there?)
        //
        // Any changes being done in this block, we should do it in the other places also
        // where we are updating the balance at the end of the transaction.
        //

        //
        // If authorized is false, it means that the payment is captured since
        // we create payment transaction only in two flows - authorized and captured.
        // If it's captured, we decide whether to do lateBalanceUpdate or not based
        // on some conditions.
        //
        if ($this->source->isAuthorized() === true)
        {
            // in authorize transaction we set to balance updated as true since there is no actual balance update.
            $this->txn->setBalanceUpdated(true);

            return false;
        }
        else if ($this->source->merchant->isFeatureEnabled(Feature\Constants::ASYNC_BALANCE_UPDATE) === true)
        {
            return false;
        }
        else if ($this->source->isLateBalanceUpdate() === true)
        {
            // in late balance update we do it on the fly and setting it to true for backward compatiablility
            $this->txn->setBalanceUpdated(true);

            return false;
        }


        $this->txn->setBalanceUpdated(true);

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

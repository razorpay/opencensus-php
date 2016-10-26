<?php

namespace RZP\Models\Transaction;

use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Schedule\Entity as Schedule;
use RZP\Models\Schedule\Repository as ScheduleRepo;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Exception;
use RZP\Gateway\Billdesk;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Transaction';

    protected $appFetchParamRules = array(
        Entity::SETTLED         => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_num',
        Entity::ENTITY_ID       => 'sometimes|string|min:14',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::RECONCILED      => 'sometimes|in:0,1',
    );

    public function fetchTxnsExpectedToSettle($timestamp)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLED_AT, '=', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchUnsettledTransactions($timestamp)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->with('merchant')
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchUnsettledTxnsAndSchedules($timestamp)
    {
        $schedules = (new ScheduleRepo)->fetchSchedulesWithDueRun($timestamp);

        $merchants = (new MerchantRepo)->fetchBySettlementScheduleId($schedules->getIds());

        $txns = $this->newQuery()
                     ->whereIn(Entity::MERCHANT_ID, $merchants->getIds())
                     ->where(Entity::SETTLED_AT, '<', $timestamp)
                     ->with('merchant')
                     ->orderBy(Entity::MERCHANT_ID)
                     ->orderBy(Entity::ID)
                     ->get();

        $this->trace->info(TraceCode::SCHEDULE_UNSETTLED_TXNS_FETCH, [
            'transactions' => $txns->getIds(),
            'schedules'    => $schedules->getIds(),
            'merchants'    => $merchants->getIds(),
        ]);

        return array($txns, $schedules);
    }

    public function fetchUnsettledTransactionsForMerchant($timestamp, $merchant)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->merchantId($merchant->getId())
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        $setls = (new Settlement\Repository)->fetchBetweenTimestamp($merchantId, $from, $to);

        $setlIds = $setls->modelKeys();

        $query = $this->newQuery();

        $txns = $query->merchantId($merchantId)
                      ->where(function($query) use ($from, $to, $setlIds)
                      {
                        $query->betweenTime($from, $to);

                        if (count($setlIds) !== 0)
                        {
                            $query->orWhereIn(Entity::SETTLEMENT_ID, $setlIds);
                        }
                      })
                      ->latest()
                      ->get();

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        $txns = $this->fetchAssociatedRelations($txns, 'source');

        return $txns;
    }

    public function fetchDataForInvoice($merchantId, $from, $to)
    {
        $fee = $this->newQuery()
                    ->where('transactions.merchant_id', $merchantId)
                    ->where('type', 'payment')
                    ->join('payments', 'transactions.entity_id', '=', 'payments.id')
                    ->whereNotNull('payments.captured_at')
                    ->betweenTime($from, $to)
                    ->sum('transactions.fee');

        $serviceTax = $this->newQuery()
                           ->where('transactions.merchant_id', $merchantId)
                           ->where('type', 'payment')
                           ->join('payments', 'transactions.entity_id', '=', 'payments.id')
                           ->whereNotNull('payments.captured_at')
                           ->betweenTime($from, $to)
                           ->sum('transactions.service_tax');

        // Total fee includes our cut + service tax
        return [
            'total_fee'         =>  $fee,
            // This is a combined tax column
            // and includes more than just service_tax (sb cess, kk cess)
            'tax'               =>  $serviceTax
        ];
    }

    public function fetchTransactionsForAuthorizedRefundedPayments()
    {
        $txns = $this->newQuery()
                     ->where(Transaction\Entity::TYPE, '=', Type::REFUND)
                     ->where(Transaction\Entity::SETTLED, '=', 1)
                     ->whereNull(Transaction\Entity::BALANCE)
                     ->get();

        //
        // Transactions with only refunded authorized payments
        // The previous txns can contain those refunds where balance went to 0
        // after the refund.
        //
        $txns2 = new Base\PublicCollection;

        foreach ($txns as $txn)
        {
            $refund = $txn->source;
            $payment = $refund->payment;

            if ($payment->hasBeenCaptured() === false)
            {
                $txns2->push($txn);
            }
        }

        return $txns2;
    }

    public function settled($txns, $settledAt)
    {
        if ($txns->count() === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $values = array(
            Transaction\Entity::SETTLED_AT  => $settledAt,
            Transaction\Entity::SETTLED     => true);

        $count = $this->newQuery()
                      ->whereIn(Transaction\Entity::ID, $ids)
                      ->update($values);

        $expected = count($ids);

        if ($count !== $expected)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of rows. \n' .
                'Expected: ' . $expected . ' Updated: ' . $count);
        }

        return $count;
    }

    public function updateSettlementId($txns, $settlementId)
    {
        if ($txns->count() === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $values = [Transaction\Entity::SETTLEMENT_ID  => $settlementId];

        $count = $this->newQuery()
                      ->whereIn(Transaction\Entity::ID, $ids)
                      ->update($values);

        $expected = count($ids);

        if ($count !== $expected)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of rows. \n' .
                'Expected: ' . $expected . ' Updated: ' . $count);
        }

        return $count;
    }

    public function findByEntityId($entityId, $fail = false)
    {
        $txn = $this->newQuery()
                    ->where(Transaction\Entity::ENTITY_ID, '=', $entityId)
                    ->first();

        if (($txn === null) and
            ($fail))
        {
            throw new Exception\LogicException(
                'Failed to find transaction with entity_id: ' . $entityId);
        }

        return $txn;
    }

    public function fetchBySettlementId($setlId)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLEMENT_ID, '=', $setlId)
                    ->get();
    }

    public function getCancelledBilldeskTransactions()
    {
        $billdeskPaymentId = Billdesk\Entity::getAttributeWithTableName(Billdesk\Entity::PAYMENT_ID);
        $billdeskRefStatus = Billdesk\Entity::getAttributeWithTableName('RefStatus');

        $paymentId = Payment\Entity::getAttributeWithTableName(Payment\Entity::ID);
        $paymentStatus = Payment\Entity::getAttributeWithTableName(Payment\Entity::STATUS);

        $transactionEntityId = Entity::getAttributeWithTableName(Entity::ENTITY_ID);
        $transactionReconciledAt = Entity::getAttributeWithTableName(Entity::RECONCILED_AT);

        $transactionData = Entity::getAttributeWithTableName('*');

        return $this->newQuery()
                    ->select($transactionData)
                    ->join(Table::PAYMENT, $paymentId, '=', $transactionEntityId)
                    ->join(Table::BILLDESK, $billdeskPaymentId, '=', $paymentId)
                    ->where($billdeskRefStatus, '=', Billdesk\RefundStatus::CANCELLED)
                    ->where($paymentStatus, '=', Payment\Status::REFUNDED)
                    ->whereNull($transactionReconciledAt)
                    ->get();
    }

    protected function addQueryParamEntityId($query, $params)
    {
        $entityId = $params[Entity::ENTITY_ID];

        Entity::stripSignWithoutValidation($entityId);

        $query->where(Entity::ENTITY_ID, '=', $entityId);
    }

    protected function addQueryParamReconciled($query, $params)
    {
        $reconciled = $params[Entity::RECONCILED];

        if ($reconciled === '0')
        {
            $query->whereNull(Entity::RECONCILED_AT);
        }
        else if ($reconciled === '1')
        {
            $query->whereNotNull(Entity::RECONCILED_AT);
        }
    }
}

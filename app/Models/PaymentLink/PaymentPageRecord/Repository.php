<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use Illuminate\Support\Facades\DB;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\PaymentLink\Entity as PaymentLink;
use RZP\Models\PaymentLink\PaymentPageRecord\Status as STATUS;

class Repository extends Base\Repository
{
    protected $entity = 'payment_page_record';

    public function findByPaymentPageAndPrimaryRefIdOrFail(
        string $payment_page_id,
        string $primary_ref_id): Entity
    {

        PaymentLink::silentlyStripSign($payment_page_id);

        return $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->where(Entity::PRIMARY_REFERENCE_ID, $primary_ref_id)
            ->firstOrFail();
    }

    public function findByPaymentPageIdAndStatus(
        string $payment_page_id
    )
    {
        PaymentLink::silentlyStripSign($payment_page_id);

        $query = $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->where(Entity::STATUS, STATUS::UNPAID);

        $total_pending_revenue = $query->sum(Entity::AMOUNT);
        $total_pending_payments = $query->count();

        return [
            Entity::TOTAL_PENDING_PAYMENTS => $total_pending_payments,
            Entity::TOTAL_PENDING_REVENUE => $total_pending_revenue,
        ];
    }

    public function totalPendingPaymentsWithLateFee(
        String $PaymentPageId,
        String $lateFeeType,
        String $lateFeeDueDateTitle
    ){
        // process in batches of 1k records as this query could be expensive
        $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::PAYMENT_LINK_ID, $PaymentPageId)
            ->where(Entity::STATUS, STATUS::UNPAID)
            ->chunk(1000, function ($records) use (&$total_pending_revenue, &$total_pending_payments, &$total_pending_late_fee, $lateFeeType, $lateFeeDueDateTitle)
            {
                foreach ($records as $record)
                {
                    $total_pending_revenue += $record->amount;
                    $total_pending_payments++;

                    $lateFee = (new Core())->getTotalLateFeeForRecord($lateFeeType, $lateFeeDueDateTitle, $record->toArray());

                    if ($lateFee !== null)
                    {
                        $total_pending_late_fee += $lateFee;
                    }
                }
            });

        return [
            Entity::TOTAL_PENDING_PAYMENTS => $total_pending_payments,
            Entity::TOTAL_PENDING_REVENUE => $total_pending_revenue,
            Entity::TOTAL_PENDING_LATE_FEE => $total_pending_late_fee,
        ];
    }

    // query is executed on replica as it could get expensive
    public function getMatchingRecordsCount(
        string $payment_page_id,
        string $secondaryRefId): int
    {
        $startTime = millitime();

        $res =  $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->whereRaw('JSON_EXTRACT(other_details,  \'$."sec__ref__id_1"\') = ?', [$secondaryRefId])
            ->count();

        $this->trace->histogram(Merchant\Metric::FETCH_PAYMENT_PAGE_RECORDS_WITH_SEC_REF_ID, millitime()-$startTime);

        return $res;
    }

    public function findByPaymentPageIdAndBatchIdorFail(
        string $payment_page_id,
        string $batch_id
    )
    {
        PaymentLink::silentlyStripSign($payment_page_id);
        Batch::silentlyStripSign($batch_id);

        return $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->where(Entity::BATCH_ID, $batch_id)
            ->where(Entity::STATUS,STATUS::UNPAID)
            ->get()
            ->toArray();
    }


    public function getBatchesByPaymentPageId(
        string $payment_page_id,
        int $skip = 0,
        int $count = 25
    )
    {
        $records =  $this->newQuery()
            ->select(Entity::BATCH_ID)
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->distinct()
            ->skip($skip)
            ->limit($count)
            ->get()
            ->toArray();

        $totalCount =  $this->newQuery()
            ->select(Entity::BATCH_ID)
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->distinct()
            ->count();

        return [
            'records' => $records,
            'totalCount' => $totalCount
        ];
    }

    public function getAllBatchesByPaymentPageId(
        string $payment_page_id
    )
    {
        return $this->newQuery()
            ->select(Entity::BATCH_ID)
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->distinct()
            ->limit(1000)
            ->get()
            ->toArray();
    }
}

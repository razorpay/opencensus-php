<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
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

        return $this->newQuery()
            ->select(Entity::AMOUNT)
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->where(Entity::STATUS, STATUS::UNPAID)
            ->get()
            ->toArray();
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

<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use RZP\Models\Base;
use RZP\Models\PaymentLink\Entity as PaymentLink;

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

    public function findByPaymentPageIdorFail(
        string $payment_page_id
    )
    {
        PaymentLink::silentlyStripSign($payment_page_id);

        return $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->get()
            ->toArray();
    }


    public function getBatchesByPaymentPageId(
        string $payment_page_id,
        int $skip = 0,
        int $count = 25
    )
    {
        return $this->newQuery()
            ->select(Entity::BATCH_ID)
            ->where(Entity::PAYMENT_LINK_ID, $payment_page_id)
            ->skip($skip)
            ->limit($count)
            ->get()
            ->toArray();
    }
}

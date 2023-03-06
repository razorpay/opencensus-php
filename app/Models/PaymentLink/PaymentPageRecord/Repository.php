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
}

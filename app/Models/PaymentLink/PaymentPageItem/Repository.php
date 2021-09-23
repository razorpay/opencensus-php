<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Base;
use RZP\Models\PaymentLink;
use RZP\Trace\Tracer;

class Repository extends Base\Repository
{
    protected $entity = 'payment_page_item';

    protected $expands = [
        Entity::ITEM,
    ];

    public function findByIdAndPaymentLinkEntityOrFail(
        string $id,
        PaymentLink\Entity $paymentLink): Entity
    {
        return Tracer::inSpan(['name' =>  'payment_page.find_by_id_and_payment_link'], function() use($id, $paymentLink)
        {
            return $this->newQuery()
                    ->where(Entity::PAYMENT_LINK_ID, $paymentLink->getId())
                    ->findOrFailPublic($id);
        });
    }
}

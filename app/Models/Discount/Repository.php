<?php

namespace RZP\Models\Discount;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::DISCOUNT;

    public function fetchForPayment($payment)
    {
        $paymentID = $payment->getId();

        $discount = $this->newQuery()
                         ->where(Entity::PAYMENT_ID, '=', $paymentID)
                         ->first();

        return $discount;
    }

    public function fetchDiscountWithOffer($paymentID)
    {
        $discount = $this->newQuery()
            ->where(Entity::PAYMENT_ID, '=', $paymentID)
            ->whereNotNull(Entity::OFFER_ID)
            ->first();

        return $discount;
    }

    public function fetchDiscountWithoutOffer($paymentID)
    {
        $discount = $this->newQuery()
            ->where(Entity::PAYMENT_ID, '=', $paymentID)
            ->whereNull(Entity::OFFER_ID)
            ->first();

        return $discount;
    }
}

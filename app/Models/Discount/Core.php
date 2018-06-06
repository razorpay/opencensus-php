<?php

namespace RZP\Models\Discount;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Offer;

class Core extends Base\Core
{
    public function create(array $input, Payment\Entity $payment, Offer\Entity $offer)
    {
        $discount = (new Entity)->build($input);

        $discount->payment()->associate($payment);

        $discount->order()->associate($payment->order);

        $discount->offer()->associate($offer);

        $this->repo->saveOrFail($discount);

        return $discount;
    }
}

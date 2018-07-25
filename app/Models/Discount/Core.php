<?php

namespace RZP\Models\Discount;

use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Payment\Entity $payment, Offer\Entity $offer)
    {
        $discount = (new Entity)->build($input);

        $discount->payment()->associate($payment);

        $discount->order()->associate($payment->order);

        $discount->offer()->associate($offer);

        $this->repo->saveOrFail($discount);

        $this->trace->info(TraceCode::OFFER_DISCOUNT_CREATED, [
            'discount'   => $discount->toArrayPublic(),
        ]);

        return $discount;
    }
}

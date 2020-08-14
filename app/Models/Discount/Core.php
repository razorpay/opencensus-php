<?php

namespace RZP\Models\Discount;

use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Payment\Entity $payment, $offer)
    {
        $discount = (new Entity)->build($input);

        $discount->payment()->associate($payment);

        if ($this->checkIfOrderValid($payment->order) === true)
        {
            $discount->order()->associate($payment->order);
        }

        if ($this->checkIfOfferValid($offer) === true)
        {
            $discount->offer()->associate($offer);
        }

        $this->repo->saveOrFail($discount);

        $this->trace->info(TraceCode::OFFER_DISCOUNT_CREATED, [
            'discount'   => $discount->toArrayPublic(),
        ]);

        return $discount;
    }

    protected function checkIfOrderValid($order)
    {
        if (empty($order) === true)
        {
            return false;
        }
        return true;
    }

    protected function checkIfOfferValid($offer)
    {
        if (empty($offer) === true)
        {
            return false;
        }

        if ($offer instanceof Offer\Entity)
        {
            return true;
        }

        return false;
    }
}

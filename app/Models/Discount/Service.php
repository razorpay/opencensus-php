<?php

namespace RZP\Models\Discount;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Offer;

class Service extends Base\Service
{
    public function create(array $input, Payment\Entity $payment, $offer)
    {
        $discount = $this->core()->create($input, $payment, $offer);

        return $discount->toArrayPublic();
    }
}

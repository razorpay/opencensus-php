<?php

namespace RZP\Models\Discount;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Offer;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input, Payment\Entity $payment, Offer\Entity $offer)
    {
        $discount = $this->core->create($input, $payment, $offer);

        return $discount->toArrayPublic();
    }
}

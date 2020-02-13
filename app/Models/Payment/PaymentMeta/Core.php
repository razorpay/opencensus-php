<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;
use RZP\Models\Payment\PaymentMeta;

class Core extends Base\Core
{
    protected $paymentMeta = null;

    public function create($input, $payment)
    {
        $paymentMeta = (new PaymentMeta\Entity)->build($input);

        $paymentMeta->payment()->associate($payment);

        $this->$paymentMeta = $paymentMeta;

        //$paymentMeta->saveOrFail();

        return $paymentMeta;
    }
}

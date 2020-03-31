<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;
use RZP\Models\Payment\PaymentMeta;

class Core extends Base\Core
{
    protected $paymentMeta = null;

    public function create($input)
    {
        $paymentMeta = (new PaymentMeta\Entity)->build($input);

        $this->$paymentMeta = $paymentMeta;

        $this->repo->saveOrFail($paymentMeta);

        return $paymentMeta;
    }
}

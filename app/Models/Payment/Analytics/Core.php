<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment)
    {
        $pa = new Entity;

        $parser = new Parser;

        // parse, and set data in $paymentAnalytics object
        $parser->recordPaymentRequestData($pa, $payment);

        $this->repo->saveOrFail($pa);

        $paArray = $pa->toArrayPublic();

        $parser->traceInconsistentData($paArray);

        $parser->traceUnrecognizedData($paArray);

        return $pa;
    }
}
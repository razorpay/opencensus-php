<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment\Analytics;

class Core extends Base\Core
{
    public function create($payment)
    {
        $input = [];

        $parser = new Analytics\Parser;

        // parse, and set data in $paymentAnalytics object
        $parser->recordPaymentRequestData($input, $payment);

        $paymentAnalytics = (new Analytics\Entity)->build($input);

        $this->repo->saveOrFail($paymentAnalytics);

        // trace unrecognized data in $paymentAnalytics object
        $parser->traceUnrecognizedData($paymentAnalytics);

        return $paymentAnalytics;
    }
}
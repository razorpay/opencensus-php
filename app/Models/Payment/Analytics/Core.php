<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, $deviceId = null)
    {
        $pa = new Entity;

        $parser = new Parser;

        $pa->setVirtualDeviceId($deviceId);

        // parse, and set data in $paymentAnalytics object
        $parser->recordPaymentRequestData($pa, $payment);

        $paArray = $pa->toArrayPublic();

        $parser->traceInconsistentData($paArray);

        $parser->traceUnrecognizedData($paArray);

        return $pa;
    }
}

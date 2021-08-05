<?php

namespace RZP\Services\Mock;

use RZP\Services\Shield as BaseShield;
use RZP\Models\Payment\Entity as Payment;

class Shield extends BaseShield
{
    public function __construct($app)
    {
    }

    public function getRiskAssessment(Payment $payment, $input = [])
    {
        return null;
    }
}

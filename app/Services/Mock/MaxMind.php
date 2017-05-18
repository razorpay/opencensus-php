<?php

namespace RZP\Services\Mock;

use RZP\Services\MaxMind as BaseMaxmind;
use RZP\Models\Payment\Entity as Payment;

class MaxMind extends BaseMaxmind
{
    const LICENSE_KEY = 'license_key';

    public function __construct($app)
    {
        ;
    }

    public function query(Payment $payment)
    {
        return null;
    }
}

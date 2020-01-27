<?php

namespace RZP\Gateway\GooglePay\Mock;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Gateway\GooglePay;

class Gateway extends GooglePay\Gateway
{
    use Base\Mock\GatewayTrait;

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;

        $this->mozartClass = 'RZP\Gateway\Mozart\Mock\Gateway';
    }
}


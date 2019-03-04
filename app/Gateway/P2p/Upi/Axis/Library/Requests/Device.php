<?php

namespace RZP\Gateway\P2p\Upi\Axis\Library\Requests;

use RZP\Gateway\P2p\Upi\Axis\Library\Action;
use RZP\Gateway\P2p\Upi\Axis\Library\Fields;

class Device
{
    const MAP = [
        Action::BIND_DEVICE => [
            'validator' => [
                Fields::SIM_ID  => 'required'
            ],
            'signature' => false,
            'mapper' => [
                Fields::SIM_ID,
            ],
        ],
        Action::ACTIVATE_DEVICE_BINDING => [
            'validator' => [
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_MOBILE_NUMBER  => 'required'
            ],
            'signature' => [
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::CUSTOMER_MOBILE_NUMBER
            ],
            'mapper' => [

            ],
        ]
    ];
}

<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\S2sDirect;

class DeviceAction extends Action
{
    const BIND_DEVICE = 'BIND_DEVICE';

    const ACTIVATE_DEVICE_BINDING = 'ACTIVATE_DEVICE_BINDING';

    const GET_SESSION_TOKEN = 'GET_SESSION_TOKEN';

    const IS_DEVICE_FINGERPRINT_VALID = 'IS_DEVICE_FINGERPRINT_VALID';

    const DEREGISTER = 'DEREGISTER';

    const MAP = [

        self::BIND_DEVICE => [
            self::VALIDATOR => [
                Fields::SIM_ID          => 'required',
            ],
            self::SIGNATURE => false,
        ],

        self::ACTIVATE_DEVICE_BINDING => [
            self::VALIDATOR => [
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_MOBILE_NUMBER  => 'required',
                Fields::SHOULD_ACTIVATE         => 'required',
                Fields::TIME_STAMP              => 'required',
            ],
            self::SIGNATURE => [
                Fields::CUSTOMER_MOBILE_NUMBER,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::SHOULD_ACTIVATE,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
            ],
        ],

        self::GET_SESSION_TOKEN => [
            self::VALIDATOR => [
                Fields::MERCHANT_ID             => 'required',
                Fields::MERCHANT_CHANNEL_ID     => 'required',
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::MCC                     => 'required',
                Fields::TIMESTAMP               => 'required',
                Fields::CURRENCY                => 'required',
                Fields::SIM_ID                  => 'required',
            ],
            self::SIGNATURE => [
                Fields::CURRENCY,
                Fields::MCC,
                Fields::MERCHANT_CHANNEL_ID,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_ID,
                Fields::TIMESTAMP,
                Fields::UDF_PARAMETERS,
            ],
        ],

        self::DEREGISTER    => [
            self::SOURCE    => self::DIRECT,
            self::DIRECT    => [
                S2sDirect::METHOD => 'post'
            ],
        ]
    ];
}

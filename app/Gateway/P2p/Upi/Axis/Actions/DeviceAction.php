<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Request;

class DeviceAction extends Action
{
    const BIND_DEVICE = 'bind_device';

    const ACTIVATE_DEVICE_BINDING = 'activate_device_binding';

    const GET_SESSION_TOKEN = 'get_session_token';

    const MAP = [

        self::BIND_DEVICE => [
            self::VALIDATOR => [
                Fields::SIM_ID          => 'required',
                Fields::UDF_PARAMETERS  => 'sometimes'
            ],
            self::SIGNATURE => false,
            self::SDK_VALIDATE => false,
        ],

        self::ACTIVATE_DEVICE_BINDING => [
            self::VALIDATOR => [
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_MOBILE_NUMBER  => 'required',
                Fields::SHOULD_ACTIVATE         => 'required',
                Fields::TIME_STAMP              => 'required',
                Fields::UDF_PARAMETERS  => 'sometimes'
            ],
            self::SIGNATURE => [
                Fields::CUSTOMER_MOBILE_NUMBER,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::SHOULD_ACTIVATE,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
            ],
            self::SDK_VALIDATE => false,
        ],

        self::GET_SESSION_TOKEN => [
            self::VALIDATOR => [
                Fields::MERCHANT_ID             => 'required',
                Fields::MERCHANT_CHANNEL_ID     => 'required',
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::MCC                     => 'required',
                Fields::SIM_ID                  => 'required',
                Fields::TIMESTAMP               => 'required',
                Fields::CURRENCY                => 'required',
                Fields::UDF_PARAMETERS  => 'sometimes'
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
            self::SDK_VALIDATE => false,
        ]
    ];
}
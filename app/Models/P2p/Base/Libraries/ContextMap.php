<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Models\P2p\Device;
use Illuminate\Http\Request;
use RZP\Http\Controllers\P2p\Requests;

class ContextMap
{
    const X_RAZORPAY_REQUEST_ID       = 'X-Razorpay-P2p-Request-Id';
    const X_RAZORPAY_VPA_HANDLE       = 'X-Razorpay-Vpa-Handle';
    const X_RAZORPAY_DEVICE_IP        = 'X-Razorpay-Device-Ip';
    const X_RAZORPAY_DEVICE_GEOCODE   = 'X-Razorpay-Device-Geocode';
    const X_RAZORPAY_P2P_SESSION_ID   = 'X-Razorpay-P2p-Session-Id';

    const X_RAZORPAY_PSP_OS               = 'X-Razorpay-Psp-Os';
    const X_RAZORPAY_PSP_OS_VERSION       = 'X-Razorpay-Psp-Os-Version';
    const X_RAZORPAY_PSP_SDK_VERSION      = 'X-Razorpay-Psp-Sdk-Version';
    const X_RAZORPAY_PSP_NETWORK_TYPE     = 'X-Razorpay-Psp-Network-Type';
    const X_RAZORPAY_PSP_SDK_SESSION_ID   = 'X-Razorpay-Psp-Sdk-Session-Id';

    const REQUEST_OPTIONS = [
        Context::HANDLE             => self::X_RAZORPAY_VPA_HANDLE,
        Context::REQUEST_ID         => self::X_RAZORPAY_REQUEST_ID,
        Context::META => [
            Device\Entity::IP         => self::X_RAZORPAY_DEVICE_IP,
            Device\Entity::GEOCODE    => self::X_RAZORPAY_DEVICE_GEOCODE,
            Context::OS               => self::X_RAZORPAY_PSP_OS,
            Context::OS_VERSION       => self::X_RAZORPAY_PSP_OS_VERSION,
            Context::SDK_SESSION_ID   => self::X_RAZORPAY_P2P_SESSION_ID,
            Context::SDK_VERSION      => self::X_RAZORPAY_PSP_SDK_VERSION,
            Context::NETWORK_TYPE     => self::X_RAZORPAY_PSP_NETWORK_TYPE,
        ],
        Context::DEVICE        => [
            Device\Entity::IP        => self::X_RAZORPAY_DEVICE_IP,
            Device\Entity::GEOCODE   => self::X_RAZORPAY_DEVICE_GEOCODE,
        ]
    ];

    const SKIP_TOKEN_VALIDATION_ROUTES = [
        Requests::P2P_CUSTOMER_INITIATE_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION,
        Requests::P2P_CUSTOMER_INITIATE_GET_TOKEN,
        Requests::P2P_CUSTOMER_GET_TOKEN,
    ];

    public static function resolveRequestHeaders(Request $request, array $map = self::REQUEST_OPTIONS)
    {
        $options = [];

        foreach ($map as $item => $value)
        {
            if (is_array($value) === true)
            {
                $options[$item] = self::resolveRequestHeaders($request, $value);
            }
            else
            {
                $options[$item] = $request->header($value);
            }
        }

        return (new ArrayBag($options));
    }
}

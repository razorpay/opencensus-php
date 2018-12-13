<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Models\P2p\Device;
use Illuminate\Http\Request;

class ContextMap
{
    const X_RAZORPAY_REQUEST_ID     = 'X-Razorpay-Request-Id';
    const X_RAZORPAY_VPA_HANDLE     = 'X-Razorpay-Vpa-Handle';
    const X_RAZORPAY_DEVICE_IP      = 'X-Razorpay-Device-Ip';
    const X_RAZORPAY_DEVICE_GEOCODE = 'X-Razorpay-Device-Geocode';

    const REQUEST_OPTIONS = [
        Context::HANDLE        => self::X_RAZORPAY_VPA_HANDLE,
        Context::REQUEST_ID    => self::X_RAZORPAY_REQUEST_ID,
        Context::DEVICE        => [
            Device\Entity::IP        => self::X_RAZORPAY_DEVICE_IP,
            Device\Entity::GEOCODE   => self::X_RAZORPAY_DEVICE_GEOCODE,
        ]
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

        return $options;
    }
}

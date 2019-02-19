<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Exception;
use RZP\Error\ErrorCode;

class RefundStatus
{
    const REFUNDED         = 'refunded';
    const CANCELLED        = 'cancelled';
    const REQUESTED        = 'requested';
    const PROCESSING       = 'processing';
    const DUPLICATE_REFUND = 'duplicate refund';
    const COMPLETED        = 'completed';

    public static function isVerifySuccess($status)
    {
        $status = strtolower($status);

        if (($status === self::REQUESTED) or ($status === self::PROCESSING))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED,
                '',
                '',
                [
                    'message' => 'refund status is in requested or processing stage'
                ]
            );
        }
        elseif ($status === self::DUPLICATE_REFUND)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_MULTIPLE_REFUNDS_FOUND);
        }

        return (($status === self::REFUNDED) or ($status === self::COMPLETED));
    }
}

<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Mode as PayoutMode;

class Mode extends PayoutMode
{
    public static function validateMode(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MODE,
                null,
                [
                    'mode provided in requested' => $mode,
                    'supported modes'            => self::$allSupportedModes,
                ]);
        }
    }
}

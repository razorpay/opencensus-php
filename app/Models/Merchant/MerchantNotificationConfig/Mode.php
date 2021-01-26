<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Exception\BadRequestValidationFailureException;

class Mode extends PayoutMode
{
    const ALL = 'ALL';

    public static function validateMode(string $mode)
    {
        if ((self::isValid($mode) === false) and
            ($mode !== self::ALL))
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MODE,
                null,
                [
                    'mode provided in request' => $mode,
                    'supported modes'            => array_merge([self::ALL], self::$allSupportedModes),
                ]);
        }
    }
}

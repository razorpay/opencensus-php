<?php

namespace RZP\Gateway\Netbanking\Indusind;

use RZP\Gateway\Base\Action;

class Constants
{
    // Mode
    const PAY               = 'P';
    const VERIFY            = 'V';

    // Confirmation
    const YES               = 'Y';
    const NO                = 'N';

    // State flag for verification
    const HOT_PAYMENT       = 'H';
    const SCHEDULED_PAYMENT = 'S';

    const ACTION_MODE_MAPPING = [
        Action::AUTHORIZE => self::PAY,
        Action::VERIFY    => self::VERIFY,
    ];

    public static function getModeForAction(string $action)
    {
        return self::ACTION_MODE_MAPPING[$action];
    }
}

<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Gateway\AxisMigs;
use RZP\Models\Payment\TwoFaStatus;

class ThreeDSecureStatus
{
    /**
     * 3DSstatus field can have follow values with associated meaning
     *
     * 'Y' - 3d secure auth succeeded
     * 'N' - 3d secure auth failed
     * 'A' - Attempted authentication
     * 'U' - Unavailable for checking
     */

    const Y = 'Y';
    const N = 'N';
    const U = 'U';
    const A = 'A';

    public static function isThreeDSsuccess($status)
    {
        $twoFaStatus = self::getThreeDSstatus($status);

        return ($twoFaStatus === TwoFaStatus::PASSED);
    }

    public static function getThreeDSstatus($status)
    {
        switch ($status) {
            case self::Y:
                return TwoFaStatus::PASSED;
            case self::N:
                return TwoFaStatus::FAILED;
            default:
                return TwoFaStatus::UNKNOWN;
        }
    }
}
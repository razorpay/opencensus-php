<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Gateway\AxisMigs;

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

    const SUCCESS = 'success';
    const FAILURE = 'failure';
    const SKIPPED = 'skipped';

	protected static $vpc3DSstatusMap = array(
        self::SUCCESS => array('Y'),
        self::FAILURE => array('N'),
        self::SKIPPED => array('U', 'A'));

    public static function is3DSecureSuccess($status)
    {
        return ($status === self::Y);
    }
}

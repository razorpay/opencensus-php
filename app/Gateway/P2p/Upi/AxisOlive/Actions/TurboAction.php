<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Actions;

use RZP\Gateway\P2p\Upi\AxisOlive\Fields;

/**
 * Class TurboAction
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Actions
 *
 * Constants of this class are used in RZP\Http\Controllers\TurboController for redirecting turbo callbacks to correct
 * controller based on callback types
 */
class TurboAction extends Action
{
    const REQUEST_COMPLAINT_CALLBACK        = 'REQUEST_COMPLAINT_CALLBACK';
    const NOTIFICATION_COMPLAINT_CALLBACK   = 'NOTIFICATION_COMPLAINT_CALLBACK';
}

<?php

namespace RZP\Models\P2p\Turbo;

use RZP\Models\P2p\Base;

/**
 * Class Action for Turbo
 *
 * @package RZP\Models\P2p\Turbo
 */
class Action extends Base\Action
{
    const INITIATE_TURBO_CALLBACK            = 'initiateTurboCallback';
    const INITIATE_TURBO_CALLBACK_SUCCESS    = 'initiateTurboCallbackSuccess';
    const TURBO_CALLBACK                     = 'turboCallback';
    const TURBO_CALLBACK_SUCCESS             = 'turboCallbackSuccess';
}

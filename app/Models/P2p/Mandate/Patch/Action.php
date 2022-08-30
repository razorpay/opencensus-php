<?php

namespace RZP\Models\P2p\Mandate\Patch;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Mandate;

/**
 * Class Action
 *
 * @package RZP\Models\P2p\Mandate\Patch
 */
class Action extends Base\Action
{
    const CREATE                                    = 'create';
    const UPDATE                                    = 'update';
    const AUTHORIZE                                 = 'authorize';
    const REJECT                                    = 'reject';
    const REVOKE                                    = 'revoke';
    const PAUSE                                     = 'pause';
    const UNPAUSE                                   = 'unpause';

    protected static $map = [
        Mandate\Action::INCOMING_COLLECT            => Self::CREATE,
        Mandate\Action::INCOMING_UPDATE             => Self::UPDATE,
        Mandate\Action::MANDATE_STATUS_UPDATE       => Self::UPDATE,
        Mandate\Action::AUTHORIZE_MANDATE_SUCCESS   => Self::AUTHORIZE,
        Mandate\Action::PAUSE_SUCCESS               => Self::PAUSE,
        Mandate\Action::INCOMING_PAUSE              => Self::AUTHORIZE,
        Mandate\Action::UNPAUSE_SUCCESS             => Self::UNPAUSE,
        Mandate\Action::REVOKE_SUCCESS              => Self::REVOKE,
    ];

    public function getAction($action)
    {
        return self::$map[$action];
    }
}

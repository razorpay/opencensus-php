<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Http\Controllers\P2p\Requests;

class Action extends Base\Action
{
    const FETCH_ALL                                 = 'fetchAll';
    const FETCH_ALL_SUCCESS                         = 'fetchAllSuccess';

    const FETCH                                     = 'fetch';
    const FETCH_SUCCESS                             = 'fetchSuccess';

    const INCOMING_COLLECT                          = 'incomingCollect';
    const INCOMING_COLLECT_SUCCESS                  = 'incomingCollectSuccess';

    const INITIATE_AUTHORIZE                        = 'initiateAuthorize';
    const INITIATE_AUTHORIZE_SUCCESS                = 'initiateAuthorizeSuccess';

    const AUTHORIZE_MANDATE                         = 'authorizeMandate';
    const AUTHORIZE_MANDATE_SUCCESS                 = 'authorizeSuccess';

    const INITIATE_REJECT                           = 'initiateReject';
    const INITIATE_REJECT_SUCCESS                   = 'initiateRejectSuccess';

    const REJECT                                    = 'reject';
    const REJECT_SUCCESS                            = 'rejectSuccess';

    const INITIATE_PAUSE                            = 'initiatePause';
    const INITIATE_PAUSE_SUCCESS                    = 'initiatePauseSuccess';

    const PAUSE                                     = 'pause';
    const PAUSE_SUCCESS                             = 'pauseSuccess';

    const INITIATE_UNPAUSE                          = 'initiateUnPause';
    const INITIATE_UNPAUSE_SUCCESS                  = 'initiateUnPauseSuccess';

    const UNPAUSE                                   = 'unpause';
    const UNPAUSE_SUCCESS                           = 'unpauseSuccess';

    const INITIATE_REVOKE                           = 'initiateRevoke';
    const INITIATE_REVOKE_SUCCESS                   = 'initiateRevokesSuccess';

    const REVOKE                                    = 'revoke';
    const REVOKE_SUCCESS                            = 'revokeSuccess';


    const INCOMING_UPDATE                           = 'incomingUpdate';
    const INCOMING_PAUSE                            = 'incomingPause';
    const MANDATE_STATUS_UPDATE                     = 'mandateStatusUpdate';

    protected $actionToRoute = [
        self::INITIATE_AUTHORIZE        => Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE,
        self::INITIATE_PAUSE            => Requests::P2P_CUSTOMER_MANDATE_PAUSE,
        self::INITIATE_UNPAUSE          => Requests::P2P_CUSTOMER_MANDATE_UNPAUSE,
        self::INITIATE_REVOKE           => Requests::P2P_CUSTOMER_MANDATE_REVOKE,
        self::INITIATE_REJECT           => Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE,
    ];
}

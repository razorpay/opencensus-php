<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const START_VERIFICATION                = 'startVerification';
    const START_VERIFICATION_SUCCESS        = 'startVerificationSuccess';

    const GET_VERIFICATION_STATUS           = 'getVerificationStatus';
    const GET_VERIFICATION_STATUS_SUCCESS   = 'getVerificationStatusSuccess';

    const REFRESH_CL_TOKEN                  = 'refreshClToken';
    const REFRESH_CL_TOKEN_SUCCESS          = 'refreshClTokenSuccess';

    const DEREGISTER                        = 'deregister';
    const DEREGISTER_SUCCESS                = 'deregisterSuccess';
}

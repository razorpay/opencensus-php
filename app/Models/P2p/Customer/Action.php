<?php

namespace RZP\Models\P2p\Customer;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const START_VERIFICATION       = 'startVerification';

    const GET_VERIFICATION_STATUS  = 'getVerificationStatus';

    const CREATE                   = 'create';

    const DELETE                   = 'delete';
}

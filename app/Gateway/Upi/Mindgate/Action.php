<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const COLLECT        = 'collect';

    const VALIDATE_VPA   = 'validate_vpa';

    const VALIDATE_PUSH  = 'validate_push';

    const AUTHORIZE_PUSH = 'authorize_push';

    const INTENT_TPV     = 'intent_tpv';
}

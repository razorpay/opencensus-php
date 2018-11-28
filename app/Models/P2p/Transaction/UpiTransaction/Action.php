<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const INITIATE_PAY         = 'initiatePay';

    const INITIATE_COLLECT     = 'initiateCollect';

    const FETCH_ALL            = 'fetchAll';

    const FETCH                = 'fetch';

    const INITIATE_AUTHORIZE   = 'initiateAuthorize';

    const AUTHORIZE            = 'authorize';

    const REJECT               = 'reject';
}

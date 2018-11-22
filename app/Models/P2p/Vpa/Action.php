<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const FETCH_HANDLES        = 'fetchHandles';

    const CREATE               = 'create';

    const FETCH_ALL            = 'fetchAll';

    const FETCH                = 'fetch';

    const ASSIGN_BANK_ACCOUNT  = 'assignBankAccount';

    const CHECK_AVAILABILITY   = 'checkAvailability';

    const DELETE               = 'delete';
}

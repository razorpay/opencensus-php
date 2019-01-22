<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const FETCH_HANDLES                 = 'fetchHandles';
    const FETCH_HANDLES_SUCCESS         = 'fetchHandlesSuccess';

    const ADD                           = 'add';
    const ADD_SUCCESS                   = 'addSuccess';

    const ASSIGN_BANK_ACCOUNT           = 'assignBankAccount';
    const ASSIGN_BANK_ACCOUNT_SUCCESS   = 'assignBankAccountSuccess';

    const CHECK_AVAILABILITY            = 'checkAvailability';
    const CHECK_AVAILABILITY_SUCCESS    = 'checkAvailabilitySuccess';

    const DELETE                        = 'delete';
    const DELETE_SUCCESS                = 'deleteSuccess';
}

<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const ADD                           = 'add';

    const VALIDATE                      = 'validate';
    const VALIDATE_SUCCESS              = 'validateSuccess';

    const FETCH_ALL                     = 'fetchAll';
    const FETCH_ALL_SUCCESS             = 'fetchAllSuccess';

    const HANDLE_BENEFICIARY            = 'handleBeneficiary';
    const HANDLE_BENEFICIARY_SUCCESS    = 'handleBeneficiarySuccess';
}

<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const CREATE       = 'create';

    const VALIDATE     = 'validate';

    const FETCH_ALL    = 'fetchAll';
}

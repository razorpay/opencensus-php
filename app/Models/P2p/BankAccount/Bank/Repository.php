<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_bank';

    protected $merchantIdRequiredForMultipleFetch = false;
}

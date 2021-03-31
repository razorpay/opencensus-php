<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const FETCH_ALL                 = 'fetchAll';

    const RETRIEVE_BANKS            = 'retrieveBanks';
    const RETRIEVE_BANKS_SUCCESS    = 'retrieveBanksSuccess';
}

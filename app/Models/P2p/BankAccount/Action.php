<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const FETCH_BANKS              = 'fetchBanks';

    const RETRIEVE                 = 'retrieve';

    const FETCH_ALL                = 'fetchAll';

    const FETCH                    = 'fetch';

    const INITIATE_SET_UPI_PIN     = 'initiateSetUpiPin';

    const SET_UPI_PIN              = 'setUpiPin';

    const INITIATE_FETCH_BALANCE   = 'initiateFetchBalance';

    const FETCH_BALANCE            = 'fetchBalance';
}

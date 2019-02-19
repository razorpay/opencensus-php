<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const FETCH_BANKS                       = 'fetchBanks';

    const RETRIEVE                          = 'retrieve';
    const RETRIEVE_SUCCESS                  = 'retrieveSuccess';

    const FETCH_ALL                         = 'fetchAll';

    const FETCH                             = 'fetch';

    const INITIATE_SET_UPI_PIN              = 'initiateSetUpiPin';
    const INITIATE_SET_UPI_PIN_SUCCESS      = 'initiateSetUpiPinSuccess';

    const SET_UPI_PIN                       = 'setUpiPin';
    const SET_UPI_PIN_SUCCESS               = 'setUpiPinSuccess';

    const INITIATE_FETCH_BALANCE            = 'initiateFetchBalance';
    const INITIATE_FETCH_BALANCE_SUCCESS    = 'initiateFetchBalanceSuccess';

    const FETCH_BALANCE                     = 'fetchBalance';
    const FETCH_BALANCE_SUCCESS             = 'fetchBalanceSuccess';
}

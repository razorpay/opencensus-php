<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const INITIATE_PAY                  = 'initiatePay';
    const INITIATE_PAY_SUCCESS          = 'initiatePaySuccess';

    const INITIATE_COLLECT              = 'initiateCollect';
    const INITIATE_COLLECT_SUCCESS      = 'initiateCollectSuccess';

    const FETCH_ALL                     = 'fetchAll';
    const FETCH_ALL_SUCCESS             = 'fetchAllSuccess';

    const FETCH                         = 'fetch';
    const FETCH_SUCCESS                 = 'fetchSuccess';

    const INITIATE_AUTHORIZE            = 'initiateAuthorize';
    const INITIATE_AUTHORIZE_SUCCESS    = 'initiateAuthorizeSuccess';

    const AUTHORIZE_TRANSACTION         = 'authorizeTransaction';
    const AUTHORIZE_TRANSACTION_SUCCESS = 'authorizeTransactionSuccess';

    const REJECT                        = 'reject';
    const REJECT_SUCCESS                = 'rejectSuccess';
}

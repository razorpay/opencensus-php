<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Http\Controllers\P2p\Requests;

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

    const INITIATE_REJECT               = 'initiateReject';
    const INITIATE_REJECT_SUCCESS       = 'initiateRejectSuccess';

    const REJECT                        = 'reject';
    const REJECT_SUCCESS                = 'rejectSuccess';

    const INCOMING_COLLECT              = 'incomingCollect';
    const INCOMING_COLLECT_SUCCESS      = 'incomingCollectSuccess';

    const INCOMING_PAY                  = 'incomingPay';
    const INCOMING_PAY_SUCCESS          = 'incomingPaySuccess';

    const RAISE_CONCERN                 = 'raiseConcern';
    const RAISE_CONCERN_SUCCESS         = 'raiseConcernSuccess';

    const CONCERN_STATUS                = 'concernStatus';
    const CONCERN_STATUS_SUCCESS        = 'concernStatusSuccess';

    const FETCH_ALL_CONCERNS            = 'fetchAllConcerns';

    protected $actionToRoute = [
        self::INITIATE_PAY              => Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        self::INITIATE_COLLECT          => Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        self::INITIATE_AUTHORIZE        => Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        self::INITIATE_REJECT           => Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
    ];

    protected $redactRules = [
        self::AUTHORIZE_TRANSACTION     => [
            'sdk'                       => [
                'customerMobileNumber'  => 'phone',
            ]
        ]
    ];
}

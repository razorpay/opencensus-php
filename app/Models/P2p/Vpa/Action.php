<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Http\Controllers\P2p\Requests;

class Action extends Base\Action
{
    const FETCH_HANDLES                 = 'fetchHandles';
    const FETCH_HANDLES_SUCCESS         = 'fetchHandlesSuccess';

    const INITIATE_ADD                  = 'initiateAdd';
    const INITIATE_ADD_SUCCESS          = 'initiateAddSuccess';

    const ADD                           = 'add';
    const ADD_SUCCESS                   = 'addSuccess';

    const ASSIGN_BANK_ACCOUNT           = 'assignBankAccount';
    const ASSIGN_BANK_ACCOUNT_SUCCESS   = 'assignBankAccountSuccess';

    const SET_DEFAULT                   = 'setDefault';
    const SET_DEFAULT_SUCCESS           = 'setDefaultSuccess';

    const INITIATE_CHECK_AVAILABILITY   = 'initiateCheckAvailability';

    const CHECK_AVAILABILITY            = 'checkAvailability';
    const CHECK_AVAILABILITY_SUCCESS    = 'checkAvailabilitySuccess';

    const DELETE                        = 'delete';
    const DELETE_SUCCESS                = 'deleteSuccess';

    protected $actionToRoute = [
        self::INITIATE_ADD                      => Requests::P2P_CUSTOMER_VPA_CREATE,
        self::INITIATE_ADD_SUCCESS              => Requests::P2P_CUSTOMER_VPA_CREATE,
        self::ADD                               => Requests::P2P_CUSTOMER_VPA_CREATE,
        self::ADD_SUCCESS                       => Requests::P2P_CUSTOMER_VPA_CREATE,
        self::INITIATE_CHECK_AVAILABILITY       => Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY,
        self::ASSIGN_BANK_ACCOUNT               => Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT,
    ];

    protected $redactRules = [
        self::ADD                       => [
            'sdk'                             => [
                'customerMobileNumber'        => 'phone',
            ],
        ],
    ];
}

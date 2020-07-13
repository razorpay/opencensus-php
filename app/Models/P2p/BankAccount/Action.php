<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Http\Controllers\P2p\Requests;

class Action extends Base\Action
{
    const FETCH_BANKS                       = 'fetchBanks';

    const INITIATE_RETRIEVE                 = 'initiateRetrieve';
    const INITIATE_RETRIEVE_SUCCESS         = 'initiateRetrieveSuccess';
    const INITIATE_RETRIEVE_FAILURE         = 'initiateRetrieveFailure';

    const RETRIEVE                          = 'retrieve';
    const RETRIEVE_SUCCESS                  = 'retrieveSuccess';
    const RETRIEVE_FAILURE                  = 'retrieveFailure';

    const INITIATE_SET_UPI_PIN              = 'initiateSetUpiPin';
    const INITIATE_SET_UPI_PIN_SUCCESS      = 'initiateSetUpiPinSuccess';
    const INITIATE_SET_UPI_PIN_FAILURE      = 'initiateSetUpiPinFailure';

    const SET_UPI_PIN                       = 'setUpiPin';
    const SET_UPI_PIN_SUCCESS               = 'setUpiPinSuccess';
    const SET_UPI_PIN_FAILURE               = 'setUpiPinFailure';

    const INITIATE_FETCH_BALANCE            = 'initiateFetchBalance';
    const INITIATE_FETCH_BALANCE_SUCCESS    = 'initiateFetchBalanceSuccess';
    const INITIATE_FETCH_BALANCE_FAILURE    = 'initiateFetchBalanceFailure';

    const FETCH_BALANCE                     = 'fetchBalance';
    const FETCH_BALANCE_SUCCESS             = 'fetchBalanceSuccess';
    const FETCH_BALANCE_FAILURE             = 'fetchBalanceFailure';

    protected $actionToRoute = [
        self::INITIATE_RETRIEVE            => Requests::P2P_CUSTOMER_BA_RETRIEVE,
        self::INITIATE_SET_UPI_PIN         => Requests::P2P_CUSTOMER_BA_SET_UPI_PIN,
        self::INITIATE_FETCH_BALANCE       => Requests::P2P_CUSTOMER_BA_FETCH_BALANCE,
    ];

    protected $redactRules = [
        self::INITIATE_SET_UPI_PIN                  => [
            // Request with card details
            Entity::CARD                            => [
                Base\Libraries\Card::LAST6          => 'verbose',
                Base\Libraries\Card::EXPIRY_MONTH   => 'verbose',
                Base\Libraries\Card::EXPIRY_YEAR    => 'verbose',
            ],

            // Response to Axis SDK
            Base\Entity::REQUEST                    => [
                'content'                           => [
                    'card'                          => 'default',
                    'expiry'                        => 'default',
                ]
            ],
        ],

        self::FETCH_BALANCE                         => [
            'sdk'                                   => [
                'customerMobileNumber'              => 'phone',
                'balance'                           => 'default',
            ],
        ],

        self::SET_UPI_PIN                         => [
            'sdk'                                   => [
                'customerMobileNumber'              => 'phone',
            ],
        ],

        self::FETCH_BALANCE_SUCCESS                 => [
            Entity::BALANCE                         => 'default',
        ],
    ];
}

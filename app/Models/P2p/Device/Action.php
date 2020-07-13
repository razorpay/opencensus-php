<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Http\Controllers\P2p\Requests;

class Action extends Base\Action
{
    const INITIATE_VERIFICATION            = 'initiateVerification';
    const INITIATE_VERIFICATION_SUCCESS    = 'initiateVerificationSuccess';
    const INITIATE_VERIFICATION_FAILURE    = 'initiateVerificationFailure';

    const VERIFICATION                     = 'verification';
    const VERIFICATION_SUCCESS             = 'verificationSuccess';
    const VERIFICATION_FAILURE             = 'verificationFailure';

    const INITIATE_GET_TOKEN               = 'initiateGetToken';
    const INITIATE_GET_TOKEN_SUCCESS       = 'initiateGetTokenSuccess';
    const INITIATE_GET_TOKEN_FAILURE       = 'initiateGetTokenFailure';

    const GET_TOKEN                        = 'getToken';
    const GET_TOKEN_SUCCESS                = 'getTokenSuccess';
    const GET_TOKEN_FAILURE                = 'getTokenFailure';

    const DEREGISTER                       = 'deregister';
    const DEREGISTER_SUCCESS               = 'deregisterSuccess';
    const DEREGISTER_FAILURE               = 'deregisterFailure';

    const DEVICE_COOLDOWN_COMPLETED        = 'deviceCooldownCompleted';

    const UPDATE_WITH_ACTION               = 'update_with_action';

    const RESTORE_DEVICE                   = 'restore_device';
    const REASSIGN_CUSTOMER                = 'reassign_customer';

    protected $actionToRoute = [
        self::INITIATE_VERIFICATION            => Requests::P2P_CUSTOMER_VERIFICATION,
        self::INITIATE_VERIFICATION_SUCCESS    => Requests::P2P_CUSTOMER_VERIFICATION,

        self::VERIFICATION                     => Requests::P2P_CUSTOMER_VERIFICATION,
        self::VERIFICATION_SUCCESS             => Requests::P2P_CUSTOMER_VERIFICATION,

        self::INITIATE_GET_TOKEN               => Requests::P2P_CUSTOMER_GET_TOKEN,
        self::INITIATE_GET_TOKEN_SUCCESS       => Requests::P2P_CUSTOMER_GET_TOKEN,

        self::GET_TOKEN                        => Requests::P2P_CUSTOMER_GET_TOKEN,
        self::GET_TOKEN_SUCCESS                => Requests::P2P_CUSTOMER_GET_TOKEN,
    ];

    protected $redactRules = [
        self::VERIFICATION_SUCCESS            => [
            Entity::AUTH_TOKEN                => 'default',
        ],
        self::INITIATE_GET_TOKEN_SUCCESS      => [
            Entity::AUTH_TOKEN                => 'default',
        ],
        self::GET_TOKEN_SUCCESS               => [
            Entity::AUTH_TOKEN                => 'default',
        ],
        self::VERIFICATION                    => [
            'sdk'                             => [
                'customerMobileNumber'        => 'phone',
            ],
        ],
        self::GET_TOKEN                       => [
            'sdk'                             => [
                'customerMobileNumber'        => 'phone',
            ],
        ],
        self::DEREGISTER                      => [
            'payload'                         => [
                'customerMobileNumber'        => 'phone',
            ]
        ],
    ];

    protected static $updateAllowedActions = [
        self::RESTORE_DEVICE,
        self::REASSIGN_CUSTOMER,
    ];

    public static function getUpdateAllowedActions()
    {
        return self::$updateAllowedActions;
    }
}

<?php

use RZP\Gateway\HdfcGateway\HdfcGatewayErrorCode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFetchCardDetails' => [
       'request' => [
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'card',
                'name'          => 'Harshil',
                'network'       => 'Visa',
                'last4'         => '3335',
                'international' => false,
            ],
        ],
    ],

    'testFetchCardRecurring' => [
       'request' => [
            'url' => '/cards/recurring',
            'method' => 'get',
            'content' => [
                'iin' => '411111'
            ],
        ],
        'response' => [
            'content' => [
                'recurring' => true
            ],
        ],
    ],

    'testBlockedCard' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        ],
    ],



    'testUnsupportedCardNetworks' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        ],
    ],

    'testCardWhenNotEnabledOnLive' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT,
        ],
    ],

    'testCreditCardNotEnabledOnLive' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Credit card transactions are not allowed',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateSavedCard' => [
        'request' => [
            'method'  => 'put',
            'url'     => '/cards/saved',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];

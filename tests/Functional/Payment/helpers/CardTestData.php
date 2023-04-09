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
                'name'          => '',
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

    'testFetchCardRecurringForDebit' => [
        'request' => [
            'url' => '/cards/recurring',
            'method'    => 'get',
                'content' => [
                'iin' => '478893'
            ]
        ],
        'response' => [
            'content' => [
                'recurring' => true
            ],
        ],
    ],

    'testFetchCardRecurringForNonSupportedDebitBank' => [
        'request' => [
            'url' => '/cards/recurring',
            'method'    => 'get',
            'content' => [
                'iin' => '469386'
            ]
        ],
        'response' => [
            'content' => [
                'recurring' => false
            ],
        ],
    ],

    'testFetchCardRecurringForDebitWithNullIssuer' => [
        'request' => [
            'url' => '/cards/recurring',
            'method'    => 'get',
            'content' => [
                'iin' => '424512'
            ]
        ],
        'response' => [
            'content' => [
                'recurring' => false
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

    'testBinValidationWithFeature' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/cards/validate',
            'content' => [
                'number' => '401200',
            ],
        ],
        'response' => [
            'content' => [
                'result' => true
            ],
        ],
    ],

    'testBinValidationWithOutFeature' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/cards/validate',
            'content' => [
                'number' => '401200',
            ],
        ],
        'response' => [
           'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testBinValidation' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/cards/validate',
            'content' => [
                'number' => '101200122',
            ],
        ],
        'response' => [
            'content' => [
                'result' => false
            ],
        ],
    ],

    'testFetchCardDetailsForRearchPayment' => [
        'request' => [
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'card',
                'name'          =>  'test',
                'network'       =>  'RuPay',
                'last4'         =>  '1111',
            ],
        ],
    ],
];

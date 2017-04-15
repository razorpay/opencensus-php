<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGatewayCreateDowntimeNetbanking' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'comment' => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'reason_code' => 'LOW_SUCCESS_RATE'
            ]
        ]
    ],
    'testGatewayCreateDowntimeNetbankingPartial' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'partial' => true,
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'partial' => true,
                'method' => 'netbanking'
            ]
        ]
    ],
    'testCreateNBGeneral' => [
        'request' => [
            'content' => [
                'gateway' => 'ALL',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'partial' => true,
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'partial' => true,
                'method' => 'netbanking'
            ]
        ]
    ],
    'testCreateNBAllIssuers' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'partial' => true,
                'method' => 'netbanking',
                'issuer' => 'ALL',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'partial' => true,
                'method' => 'netbanking'
            ]
        ]
    ],
    'testCreateDowntimeNBEmptyIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testCreateDowntimeInvalidGateway' => [
        'request' => [
            'content' => [
                'gateway' => 'UNKNOWN_GATEWAY',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    //'description' => 'Gateway [UNKNOWN_GATEWAY] does not exist',
                ]
            ],
            'status_code'   => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ]
    ],
    'testCreateDowntimeNBInvalidIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'SOME BANK',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    //'description' => 'Bank: SOME BANK is not a valid Bank Name',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreateDowntimeCardInvalidIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'SOME BANK',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreateDowntimeNBNonSupportedIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'ICIC',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    //'description' => 'Bank: ICIC is not supported for Gateway: netbanking_hdfc',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayInvalidTo' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayInvalidReasonCode' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'SOME CODE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testGatewayCreateDowntimeInvalidSource' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'DUMMY'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testGatewayCreateDowntimeInvalidFrom' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testGatewayCreateDowntimeInvalidTo' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testGatewayCreateNullTo' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'comment' => 'Test Reason'
            ]
        ]
    ],
    'testGatewayDowntimeForCard' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC'
            ]
        ]
    ],
    'testGatewayDowntimeForCardWithoutIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
            ]
        ]
    ],
    'testGatewayDowntimeForCardUnsupportedNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'DICL',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayDowntimeForCardInvalidNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'XYZ',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayDowntimeForCardInvalidCardType' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'MC',
                'card_type' => 'xyz',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayDowntimeCardSpecificIssuerCardType' => [
        'request' => [
            'content' => [
                'gateway' => 'ALL',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'card_type' => 'debit',
                'network' => 'visa',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC'
            ]
        ]
    ],
    'testGatewayDowntimeCardSpecificIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'ALL',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC'
            ]
        ]
    ],
    'testGatewayDowntimeCardSpecificIssuerNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'ALL',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'network' => 'visa',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC'
            ]
        ]
    ],
    'testGatewayDowntimeCardCompleteGateway' => [
        'request' => [
            'content' => [
                'gateway' => 'ALL',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'UNKNOWN'
            ]
        ]
    ],
    'testGatewayDowntimeCardWithTypeIssuerNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'card_type' => 'credit',
                'network' => 'VISA',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC',
                'card_type' => 'credit',
                'network' => 'VISA'
            ]
        ]
    ],
    'testGatewayDowntimeWithWallet' => [
        'request' => [
            'content' => [
                'gateway' => 'wallet_olamoney',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'wallet',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'gateway' => 'wallet_olamoney'
            ]
        ]
    ],
    'testGatewayDowntimeWithInvalidWallet' => [
        'request' => [
            'content' => [
                'gateway' => 'wallet_dummywallet',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'wallet',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ]
            ],
            'status_code'   => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ]
    ],
    'testGatewayDowntimeWithInvalidWalletIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'wallet_olamoney',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'wallet',
                'issuer' => 'wallet_somewallet',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testStatusCakeWebHookNB' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "netbanking", "issuer":"hdfc"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'issuer' => 'HDFC',
                'method' => 'netbanking',
                'reason_code' => 'ISSUER_DOWN',
                'gateway' => 'ALL'
            ]
        ]
    ],
    'testStatusCakeWebHookCard' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "card", "gateway":"HDFC"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'reason_code' => 'ISSUER_DOWN',
                'gateway' => 'hdfc'
            ]
        ]
    ],

    'testStatusCakeWebHookWallet' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "wallet", "gateway": "wallet_airtelmoney", "issuer": "airtelmoney"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'method' => 'wallet',
                'reason_code' => 'ISSUER_DOWN',
                'gateway' => 'wallet_airtelmoney'
            ]
        ]
    ],

    'testStatusCakeWebHookUPI' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "upi", "gateway": "upi_icici"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'method' => 'upi',
                'reason_code' => 'ISSUER_DOWN',
                'gateway' => 'upi_icici'
            ]
        ]
    ],

    'testStatusCakeInvalidNB' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "netbanking", "issuer": "xyz"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testStatusCakeInvalidCard' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "card", "issuer": "xyz"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testStatusCakeInvalidWallet' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "wallet", "issuer": "xyz"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testStatusCakeInvalidUPI' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"method": "upi", "issuer": "xyz"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    // data specified without any method
    'testStatusCakeInvalidFormat' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"issuer": "HDFC"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testStatusCakeWebHookMissingToken' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"issuer": "HDFC"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testStatusCakeWebHookInvalidToken' => [
        'request' => [
            'content' => [
                'URL' => 'http://www.example.com',
                'Method' => 'Website',
                'Token' => 'Some Token',
                'Name' => 'Test',
                'StatusCode' => 400,
                'Status' => 'Down',
                'Tags' => '{"issuer": "HDFC"}'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/status_cake/webhook'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
];
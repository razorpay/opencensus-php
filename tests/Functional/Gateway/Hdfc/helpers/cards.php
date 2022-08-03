<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;

//
// Hdfc debit cards
//
// 4012001037141112
// 4005559876540
// 4012001037167778
// 4012001037490014
// 4012001037141112
//

return [
    'testCardTimeout' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayTimeoutException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::RP00013,
        ],
    ],

    'testCreditCardSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001038443335',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
                'two_factor_auth' => TwoFactorAuth::NOT_APPLICABLE,
                'captured' => false,
                'fee' => null,
                'tax' => null,
            ],
        ],
    ],

    'testCreditCardAuthNotAvailable1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001038488884',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::FSS0001,
        ],
    ],

    'testCreditCardAuthNotAvailable2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036298889',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::FSS0001,
        ],
    ],

    'testAcsFailure' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],

    'testCreditCardAuthNotAvailable3' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::FSS0001,
        ],
    ],

    'testCreditCardAuthNotApproved' => [
        'request' => [
            'content' => [
                'card' => [
                    // This IIN is of a credit card, to ensure that the enroll
                    // response is auth_not_enrolled.
                    'number' => '4628481036290001',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
            'gateway_error_code' => Hdfc\ErrorCodes\ErrorCodes::RP00006,
        ],
    ],

    'testDebitCardAuthNotApproved' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037141112'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
            'gateway_error_code' => Hdfc\ErrorCodes\ErrorCodes::RP00006,
        ],
    ],

    'testSignatureFailure1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036853337',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::GV00007,
        ],
    ],

    'testSignatureFailure2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036983332',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::GV00008,
        ],
    ],

    'testParesNotSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037461114',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESSFUL,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::GV00004,
        ],
    ],

    'testDebitCardAuthNotAvailable1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037484447',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::FSS0001,
        ],
    ],

    'testDebitCardAuthNotAvailable2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037490006',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::FSS0001,
        ],
    ],

    'testRupayFailedPayment' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '6080757792005576',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => Hdfc\ErrorCodes\ErrorCodes::PY20007,
        ],
    ],

    'testMockOnLiveMode' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testJsonpPaymentReturnFields' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
            ]
        ],
        'jsonp' => true
    ],

    'testAuthNotEnrolledDeniedByRisk' => [
        'action' => 4,
        'received' => true,
        'amount' => '500',
        'enroll_result' => '2',
        'status' => 'auth_not_enroll_failed',
        'result' => 'DENIED BY RISK',
        'eci' => '6',
        'auth' => null,
        'ref' => null,
        'avr' => null,
        'postdate' => null,
        'error_code2' => 'RP00005',
        'error_text' => 'Denied by risk. Response result code is "DENIED BY RISK"',
        'entity' => 'hdfc',
    ],

    'testAuthEnrolledDeniedByRisk' => [
        'action' => 4,
        'received' => true,
        'amount' => '500',
        'enroll_result' => '1',
        'status' => 'auth_enroll_failed',
        'result' => 'DENIED BY RISK',
        'eci' => null,
        'auth' => null,
        'ref' => null,
        'avr' => null,
        'postdate' => null,
        'error_code2' => 'RP00005',
        'error_text' => 'Denied by risk. Response result code is "DENIED BY RISK"',
        'entity' => 'hdfc',
    ],

    'testEnrollResponseWithOnlyErrorText' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
            'gateway_error_code'  => 'GW00555',
        ],
    ],

    'testMetadataErrorResponseWithFeatureFlag' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
            'gateway_error_code'  => 'RP00005',
        ],
    ],

    'testDetailedErrorResponseForCard' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
                    'reason' => 'payment_risk_check_failed',
                    'source' => 'issuer_bank',
                    'step'   => 'payment_authorization'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
            'gateway_error_code'  => 'RP00005',
        ],
    ],
];

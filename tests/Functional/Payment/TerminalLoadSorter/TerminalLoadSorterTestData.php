<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateGatewayRuleForCard' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'axis_migs',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'axis_migs',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'HDFC',
                'international'    => false,
                'gateway_acquirer' => 'axis',
                'load'             => 50,
                'admin'            => true
            ],
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidGateway' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'xyz',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid gateway',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidCardGateway' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'netbanking_hdfc',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Gateway netbanking_hdfc does not support card method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleWithInvalidMethod' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'hdfc',
                'method'           => 'xyz',
                'network'          => 'VISA',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid payment method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidNetwork' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'hdfc',
                'method'           => 'card',
                'network'          => 'xyz',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'XYZ is not a valid network',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidNetworkForGateway' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'axis_migs',
                'method'           => 'card',
                'network'          => 'DICL',
                'issuer'           => 'HDFC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'DICL is not a valid network for gateway axis_migs',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'hdfc',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'XYZ',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'XYZ is not a valid bank code',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidCardType' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'hdfc',
                'method'           => 'card',
                'method_type'      => 'xyz',
                'network'          => 'VISA',
                'issuer'           => 'ICIC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card Type: xyz is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForCardWithInvalidGatewayAcquirer' => [
        'request' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'gateway'          => 'hdfc',
                'method'           => 'card',
                'network'          => 'VISA',
                'issuer'           => 'ICIC',
                'international'    => 0,
                'gateway_acquirer' => 'axis',
                'load'             => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'axis is not a valid gateway acquirer for hdfc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForNetbanking' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'billdesk',
                'method'      => 'netbanking',
                'issuer'      => 'SBIN',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'billdesk',
                'method'      => 'netbanking',
                'issuer'      => 'SBIN',
                'load'        => 50,
                'admin'       => true,
            ],
        ],
    ],

    'testCreateGatewayRuleForNetbankingWithInvalidGatewayForNetbanking' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'method'      => 'netbanking',
                'issuer'      => 'SBIN',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Gateway hdfc does not support netbanking method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForNetbankingWithDirectNetbankingGatewayAndNullIssuer' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'netbanking_hdfc',
                'method'      => 'netbanking',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'issuer can be null only for shared netbanking gateways',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForNetbankingWithIssuerNotSupportedByGateway' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'ebs',
                'issuer'      => 'ALLA',
                'method'      => 'netbanking',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'ALLA is not a supported bank for gateway ebs',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayRuleForWallet' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'wallet_jiomoney',
                'method'      => 'wallet',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'wallet_jiomoney',
                'method'      => 'wallet',
                'load'        => 50,
                'admin'       => true,
            ],
        ],
    ],

    'testCreateGatewayRuleWithAlreadyExistingRule' => [
        'request' => [
            'content' => [
                'method'      => 'card',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_GATEWAY_RULE_EXISTS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_GATEWAY_RULE_EXISTS,
        ],
    ],

    'testCreateGatewayRuleWithConflictingRulesButTotalLoadNotExceedingMaxLoad' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'axis_migs',
                'method'      => 'card',
                'network'     => 'VISA',
                'load'        => 40
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'axis_migs',
                'method'      => 'card',
                'network'     => 'VISA',
                'load'        => 40,
                'admin'       => true
            ],
        ],
    ],

    'testCreateGatewayRuleWithConflictingRulesButTotalLoadExceedsMaxLoad' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway'     => 'axis_migs',
                'method'      => 'card',
                'network'     => 'VISA',
                'load'        => 50
            ],
            'url' => '/gateway/rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Load across all gateway rules must be less than 100 percent',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdadateGatewayRuleLoad' => [
        'request' => [
            'content' => [
                'load' => 70,
            ],
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'method'      => 'card',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'load'        => 70
            ],
        ],
    ],

    'testUpdateGatewayRuleLoadButWithTotalLoadExceedingMaxLoad' => [
        'request' => [
            'content' => [
                'load' => 70
            ],
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Load across all gateway rules must be less than 100 percent',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteGatewayRule' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
        ],
    ],
];

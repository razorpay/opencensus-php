<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateGatewayLoadRuleForCard' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'HDFC',
                'international' => false,
                'gateway_acquirer' => 'axis',
                'load' => 5000,
                'admin' => true
            ],
        ],
    ],

    'testCreateGatewayLoadRuleForCardWithInvalidGateway' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'xyz',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForCardWithInvalidCardGateway' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'netbanking_hdfc',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleWithInvalidMethod' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'xyz',
                'network' => 'VISA',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForCardWithInvalidNetwork' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'card',
                'network' => 'xyz',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid network',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayLoadRuleForCardWithInvalidNetworkForGateway' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'DICL',
                'issuer' => 'HDFC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForCardWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'XYZ',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForCardWithInvalidCardType' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'card',
                'card_type' => 'xyz',
                'network' => 'VISA',
                'issuer' => 'ICIC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForCardWithInvalidGatewayAcquirer' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'card',
                'network' => 'VISA',
                'issuer' => 'ICIC',
                'international' => 0,
                'gateway_acquirer' => 'axis',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForNetbanking' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'billdesk',
                'method'  => 'netbanking',
                'issuer' => 'SBIN',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'billdesk',
                'method'  => 'netbanking',
                'issuer' => 'SBIN',
                'load' => 5000,
                'admin' => true,
            ],
        ],
    ],

    'testCreateGatewayLoadRuleForNetbankingWithInvalidGatewayForNetbanking' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'method'  => 'netbanking',
                'issuer' => 'SBIN',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForNetbankingWithDirectNetbankingGatewayAndNullIssuer' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'netbanking_hdfc',
                'method'  => 'netbanking',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForNetbankingWithIssuerNotSupportedByGateway' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'ebs',
                'issuer' => 'ALLA',
                'method'  => 'netbanking',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
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

    'testCreateGatewayLoadRuleForWallet' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'wallet_jiomoney',
                'method'  => 'wallet',
                'issuer' => 'jiomoney',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'wallet_jiomoney',
                'method'  => 'wallet',
                'issuer' => 'jiomoney',
                'load' => 5000,
                'admin' => true,
            ],
        ],
    ],

    'testCreateGatewayLoadRuleForWalletWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'wallet_jiomoney',
                'method'  => 'wallet',
                'issuer' => 'xyz',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid wallet',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateGatewayLoadRuleWithAlreadyExistingRule' => [
        'request' => [
            'content' => [
                'method' => 'card',
                'merchant_id' => '10000000000000',
                'gateway' => 'hdfc',
                'network' => 'VISA',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_GATEWAY_LOAD_RULE_EXISTS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_GATEWAY_LOAD_RULE_EXISTS,
        ],
    ],

    'testCreateGatewayLoadRuleWithConflictingRulesButTotalLoadNotExceedingMaxLoad' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'VISA',
                'load' => 4000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'VISA',
                'load' => 4000,
                'admin' => true
            ],
        ],
    ],

    'testCreateGatewayLoadRuleWithConflictingRulesButTotalLoadExceedsMaxLoad' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'gateway' => 'axis_migs',
                'method'  => 'card',
                'network' => 'VISA',
                'load' => 5000
            ],
            'url' => '/gateway/load_rules',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD,
        ],
    ],

    'testUpdadateGatewayLoadRuleLoad' => [
        'request' => [
            'content' => [
                'load' => 7000,
            ],
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'method'      => 'card',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'load'        => 7000
            ],
        ],
    ],

    'testUpdateGatewayLoadRuleLoadButWithTotalLoadExceedingMaxLoad' => [
        'request' => [
            'content' => [
                'load' => 7000
            ],
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD,
        ],
    ],

    'testDeleteGatewayLoadRule' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'method'      => 'card',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'load'        => 5000
            ],
        ],
    ],
];

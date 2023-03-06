<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateToken' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143660000123456',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                    'name' => 'Gaurav Kumar',
                ],
                'notes' => [
                    'test' => 'test',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testParApiWithCardNumber' => [
        'request' => [
            'url' => '/cards/fingerprints',
            'method' => 'post',
            'content' => [
                'number'=> '4854980604708430'
            ],
        ],
        'response' => [
            'content' => [
                'network' => 'Visa',
                'network_reference_id' => null,
                'payment_account_reference' => '50014EES0F4P295H2FQG7Q37823B9'
            ],
        ],
    ],

    'testParApiWithCardNumberWithTokenisedFalseTestData' => [
        'request' => [
            'url' => '/cards/fingerprints',
            'method' => 'post',
            'content' => [
                "number" => "4143660026123456",
                "tokenised" => false,
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testParApiWithTokenPanWithTokenisedTrueTestData' => [
        'request' => [
            'url' => '/cards/fingerprints',
            'method' => 'post',
            'content' => [
                'number' => '4610151724696781',
                'tokenised' => true
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testParApiWithEncryptedCardNumber' => [
        'request' => [
            'url' => '/cards/fingerprints',
            'method' => 'post',
            'content' => [
                'encrypted_number'=> 'QIfoeA8AR7vkw0Rq9gs0btihYQ6wL9ONUNQ9cjaqAeI='
            ],
        ],
        'response' => [
            'content' => [
                'network' => 'Visa',
                'network_reference_id' => null,
                'payment_account_reference' => '50014EES0F4P295H2FQG7Q37823B9'
            ],
        ],
    ],

    'testParApiWithTokenIdTestData' => [
        'request' => [
            'url' => '/cards/fingerprints',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'provider' => 'Visa',
                'network_reference_id' => null,
                'payment_account_reference' => '=ETdmZ3MvlmMtF2QsJTS'
            ],
        ],
    ],

    'testCreateTokenEncrypted' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                "customer_id"=> "cust_1Aa00000000001",
                "method"=> "card",
                "card"=> [
                    "encrypted_number"=> "FNDLK39VNguRh52WutDOTErz0LjpoYIG2foMEa//yPE=",
                    "cvv"=> "123",
                    "expiry_month"=> "12",
                    "expiry_year"=> "25",
                    "name"=> "Gaurav Kumar"
                ],
                "authentication"=> [
                    "provider"=> "razorpay",
                    "provider_reference_id"=> "pay_123wkejnsakd"
                ],
                "notes"=> []
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenWithoutEncryptedAndPlainTextCardNumber' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                "customer_id"=> "cust_1Aa00000000001",
                "method"=> "card",
                "card"=> [
                    "cvv"=> "123",
                    "expiry_month"=> "12",
                    "expiry_year"=> "21",
                    "name"=> "Gaurav Kumar"
                ],
                "authentication"=> [
                    "provider"=> "razorpay",
                    "provider_reference_id"=> "pay_123wkejnsakd"
                ],
                "notes"=> []
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenEncryptedWithInvalidData' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                "customer_id"=> "cust_1Aa00000000001",
                "method"=> "card",
                "card"=> [
                    "encrypted_number"=> "rlB8mdvErUA2/hebcJUZ0tB1QjmjbFr/UmhFaE7Stao=",
                    "cvv"=> "123",
                    "expiry_month"=> "12",
                    "expiry_year"=> "21",
                    "name"=> "Gaurav Kumar"
                ],
                "authentication"=> [
                    "provider"=> "razorpay",
                    "provider_reference_id"=> "pay_123wkejnsakd"
                ],
                "notes"=> []
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenWithCardNumberSpaceData' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143 6600 0012 3456',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                    'name' => 'Gaurav Kumar',
                ],
                'notes' => [
                    'test' => 'test',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchToken' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchCryptogram' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testTokenDelete' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCard' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '08',
                    'expiry_year' => '23',
                ],
                'notes' => [
                    'test1' => 'test2'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardGatewayError' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
                'notes' => [
                    'test1' => 'test2'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateDualTokenAndTokenizeCardVisa' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
                'notes' => [
                    'test1' => 'test2'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateDualTokenAndTokenizeCardVisaFailure' =>[
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testCreateTokenAndTokenizeCardNotAllowed' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
                'notes' => [
                    'test1' => 'test2'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardMC' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardAmex' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '1234',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardRuPay' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '6071489111111111',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
                'authentication' => [
                    'provider' => 'razorpay',
                    'provider_reference_id' => 'pay_123wkejnsakd',
                    'authentication_reference_number' => '100222021120200000000742753928',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardValidationFailure' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testCreateTokenAndTokenizeCardVaultFailure' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testFetchCryptogramLive' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchCryptogramAmexLive' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchCryptogramLiveBadRequest' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],


    'testFetchCryptogramLiveInvalidTokenId' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestException::class,
            'internal_error_code'   => 'BAD_REQUEST_INVALID_ID',
        ],
    ],

    'testFetchCryptogramLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/service_provider_tokens/token_transactional_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testFetchTokenLive' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchTokenLiveInvalidToken' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestException::class,
            'internal_error_code'   => 'BAD_REQUEST_INVALID_ID',
        ],
    ],

    'testFetchTokenLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testTokenDeleteLive' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testTokenDeleteLiveExpiredCard' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testTokenDeleteLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testTokenDeleteLiveInvalidToken' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestException::class,
            'internal_error_code'   => 'BAD_REQUEST_INVALID_ID',
        ],
    ],

    'testTokenStatusLive' => [
        'request' => [
            'url' => '/internal/tokens/status',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testTokenStatusDualWrite' => [
        'request' => [
            'url' => '/internal/tokens/status',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testTokenStatusLiveFailure' => [
        'request' => [
            'url' => '/internal/tokens/status',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestException::class,
            'internal_error_code'   => 'BAD_REQUEST_NO_RECORDS_FOUND',
        ],
    ],
    'testGetAllCustomerTokensWithNetworkTokenizedFlag' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'customer_id' => 'cust_100000customer',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                ],
                'notes' => [
                    'test1' => 'test2'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkTokenisation' => [
        'request'  => [
            'url'    => '/tokenisation/local_cards/bulk',
            'method' => 'post',
            'content' => [
                'merchant_id' => '10000000000000',
                'token_ids'   => []
            ]
        ],
        'response' => [
            'content' => [
                'success'     => true,
                'message'     => 'Tokenisation is triggered on valid token ids',
                'merchantId'  => '10000000000000',
            ],
        ],
    ],

    'testAsyncTokenisation' => [
        'request'  => [
            'url'      => '/tokenisation/local_cards',
            'method'   => 'post',
            'content'  => []
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testGlobalCardsAsyncTokenisation' => [
        'request'  => [
            'url'      => '/tokenisation/global_cards',
            'method'   => 'post',
            'content'  => []
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testGlobalCardsAsyncTokenisationValidationFailure' => [
        'request'  => [
            'url'      => '/tokenisation/global_cards',
            'method'   => 'post',
            'content' => [
                'batch_size' => '20000',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'The batch size may not be greater than 10000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchMerchantsWithToken' => [
        'request' => [
            'url' => '/tokens/list',
            'method' => 'post',
            'content' => [
                "method" => "card",
                "card"=> [
                  "number" => "6070760101451996",
                  "expiry_month" => "12",
                  "expiry_year" => "21",
                  "cvv" => "123"
                ],
                "notes" => [],
                "account_ids" => [
                    "acc_J312gerdk2aaaa",
                    "acc_10000000000000"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'account_ids' => [
                    "acc_J312gerdk2aaaa",
                    "acc_10000000000000"
                ]
            ],
        ],
    ],

    'testCreateTokenForRearch' => [
        'request'  => [
            'url'      => '/internal/tokens',
            'method'   => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [],
        ],
    ],
];

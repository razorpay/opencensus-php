<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
//                    'paytm' => false,
                ],
                'cod' => false,
            ],
        ],
    ],

    'testGetPaymentMethodsRouteWithNetbankingFalse' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [],
                'wallet' => [
//                    'paytm' => false,
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsRouteWithFpxFalse' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'fpx' => [],
            ],
        ],
    ],

    'testBulkMethodUpdate' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'debit_card' => true,
                    'credit_card' => true,
                    'netbanking' => true,
                    'card_networks' => [
                        'DICL' => '0',
                        'MAES' => '1',
                        'RUPAY'=> '0'
                    ],
                    'cod' => true,
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateCreditEmiEnable' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'credit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success' => 1,
            ],
        ],
    ],

    'testBulkMethodUpdateCreditEmiEnableInvalidCategory' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'credit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success'=> 0,
                'failed'=> 1,
                'failedIds'=> [
                    '10000000000000'
                ]
            ],
        ],
    ],

    'testBulkMethodUpdateDebitEmiEnableInvalidCategory' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'debit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success'=> 0,
                'failed'=> 1,
                'failedIds'=> [
                    '10000000000000'
                ]
            ],
        ],
    ],

    'testBulkMethodUpdateEnableBanks' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'enabled_banks' => ['HDFC']
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateDisableBanks' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'disabled_banks' => ['ICIC']
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateInvalidMerchantId' => [
        'request'  => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'merchants' => ['10000000000000', '1000000000000x'],
                'methods'   => [
                    'debit_card'  => true,
                    'credit_card' => true,
                    'netbanking'  => true
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total'     => 2,
                'failed'    => 1,
                'success'   => 1,
                'failedIds' => ['1000000000000x']
            ],
        ],
    ],

    'testBulkMethodUpdateInvalidMethodsInput' => [
        'request'   => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods'   => [
                    'debit_card' => true,
                    'credit_card',
                    'netbanking' => true
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => '0 is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testBulkMethodUpdateMissingInput' => [
        'request'   => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'methods' => [
                    'debit_card'  => true,
                    'credit_card' => true,
                    'netbanking'  => true
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchants field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRecurringCards' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                    'mobikwik' => true,
                ],
                'recurring' => [
                    'card' => [
                        'credit' => [
                            'MasterCard',
                            'Visa',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRecurringNetbankingOnChargeAtWill' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                ],
                'recurring' => [
                    'card' => [
                        'credit' => [
                            'MasterCard',
                            'Visa',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMethods' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => false
            ],
        ],
    ],

    'testOnecardMerchantMethods' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => true,
                'emi_plans' => [
                    'onecard' => [
                        'min_amount' =>300000,
                        'plans' => [
                            '3' => 12,
                        ],
                    ],
                ],
                'emi_options' => [
                    'onecard' => [
                        [
                            'duration'   => 3,
                            'interest'   => 12,
                            'min_amount' => 300000,
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFetchGooglePayForCardsMethod' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchGooglePayMethod' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testEnableCreditEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                ]
            ],
        ],
    ],

    'testEnableCreditEmiInvalidCategory' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                ],
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'EMI cannot be enabled for this MCC: 5944',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnableDebitEmiInvalidCategory' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit' => '1',
                ],
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'EMI cannot be enabled for this MCC: 5944',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEnableCardnetworksAmexForBlacklistedMccs' => [
            'request' => [
                'url' => '/merchants/10000000000000/methods',
                'method' => 'put',
                'content'   => [
                    'card_networks' => [
                        'AMEX' => '1',
                    ]
                ]
            ],
            'response'  => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'AMEX card network cannot be enabled for this MCC: 4411',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnablePaylaterForBlacklistedMccs' => [
            'request' => [
                'url' => '/merchants/10000000000000/methods',
                'method' => 'put',
                'content'   => [
                        'paylater' => '1',
                ]
            ],
            'response'  => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Paylater cannot be enabled for this MCC: 5960',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnableAmazonPayForBlacklistedMccs' => [
            'request' => [
                'url' => '/merchants/10000000000000/methods',
                'method' => 'put',
                'content'   => [
                        'amazonpay' => '1',
                ]
            ],
            'response'  => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'AmazonPay cannot be enabled for this MCC: 6211',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testMerchantsMethodUpdateInternal' => [
        'request' => [
            'url' => '/internal/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                "methods" => [
                    "disabled_banks" => [
                        "ALLA",
                        "ICIC"
                    ],
                    "enabled_banks" => [
                        "AUBL"
                    ],
                    "emi" => [
                        "credit" => "1",
                        "debit" => "0"
                    ],
                    "debit_emi_providers" => [
                        "HDFC" => false,
                        "KKBK" => false,
                        "INDB" => false

                    ],
                    "card_networks" => [
                        "AMEX" => true,
                        "MC" => false
                    ],
                    "phonepe" => true,
                    "paypal" => false,
                    "cardless_emi" => true,
                    "paylater" => false
                ],
                "merchants" => [
                    "10000000000000"
                ]
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                "total" => 1,
                "success" => 1,
                "failed" => 0,
                "failedIds" => []
            ],
        ],
    ],

    'testEnableDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'debit',
                ]
            ],
        ],
    ],

    'testEnableCreditAndDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                    'debit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                    'debit',
                ],
            ],
        ],
    ],

    'testDisableEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '0',
                    'debit'  => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [],
            ],
        ],
    ],

    'testDisableCreditEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'debit',
                ],
            ],
        ],
    ],

    'testDisableDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit'  => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                ],
            ],
        ],
    ],

    'testBulkEnableHdfcDebitEmiProvider' => [
        'request' => [
            'url' => '/methods/hdfc_debit_emi',
            'method' => 'post',
            'content'   => [
                'count'  => 10,
            ]
        ],
        'response' => [
            'content' => [
                'count' => "10",
                'success' => 2,
                'failure'=> 0,
                'total' => 2
            ],
        ],
    ],

    'testMerchantPaybackInEmiOptions' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => true
            ],
        ],
    ],

    'testEnableRazorpaywallet' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'PUT',
            'content' => [
                'razorpaywallet' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'razorpaywallet' => true,
            ],
        ],
    ],

    'testEnableOfflineMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'offline' => 1
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
        ],
    ],

    'testInternalGetPaymentInstruments' => [
        'request' => [
            'url' => '/internal/payment_instruments_fetch',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'card' => [
                    ['network' => 'AMEX', 'type' => 'credit'],
                    ['network' => 'AMEX', 'type' => 'debit'],
                    ['network' => 'AMEX', 'type' => 'prepaid'],
                    ['network' => 'DICL', 'type' => 'credit'],
                    ['network' => 'DICL', 'type' => 'debit'],
                    ['network' => 'DICL', 'type' => 'prepaid'],
                    ['network' => 'MAES', 'type' => 'credit'],
                    ['network' => 'MAES', 'type' => 'debit'],
                    ['network' => 'MAES', 'type' => 'prepaid'],
                    ['network' => 'MC', 'type' => 'credit'],
                    ['network' => 'MC', 'type' => 'debit'],
                    ['network' => 'MC', 'type' => 'prepaid'],
                    ['network' => 'RUPAY', 'type' => 'credit'],
                    ['network' => 'RUPAY', 'type' => 'debit'],
                    ['network' => 'RUPAY', 'type' => 'prepaid'],
                    ['network' => 'VISA', 'type' => 'credit'],
                    ['network' => 'VISA', 'type' => 'debit'],
                    ['network' => 'VISA', 'type' => 'prepaid'],
                    ['network' => 'UNP', 'type'=> 'credit'],
                    ['network' => 'UNP', 'type'=> 'debit'],
                    ['network' => 'UNP', 'type' => 'prepaid'],
                    ['network' => 'BAJAJ', 'type' => 'credit'],
                    ['network' => 'BAJAJ', 'type' => 'debit'],
                    ['network' => 'BAJAJ', 'type' => 'prepaid']
                ],
                'netbanking' => [
                    ['bank' => 'AUBL'],
                    ['bank' => 'AUBL_C'],
                    ['bank' => 'AIRP'],
                    ['bank' => 'UTIB'],
                    ['bank' => 'UTIB_C'],
                    ['bank' => 'BDBL'],
                    ['bank' => 'BBKM'],
                    ['bank' => 'BARB_C'],
                    ['bank' => 'BARB_R'],
                    ['bank' => 'VIJB'],
                    ['bank' => 'BKID'],
                    ['bank' => 'MAHB'],
                    ['bank' => 'BACB'],
                    ['bank' => 'CNRB'],
                    ['bank' => 'CSBK'],
                    ['bank' => 'CBIN'],
                    ['bank' => 'CIUB'],
                    ['bank' => 'COSB'],
                    ['bank' => 'DCBL'],
                    ['bank' => 'DEUT'],
                    ['bank' => 'DBSS'],
                    ['bank' => 'DLXB'],
                    ['bank' => 'DLXB_C'],
                    ['bank' => 'ESAF'],
                    ['bank' => 'ESFB'],
                    ['bank' => 'FDRL'],
                    ['bank' => 'FSFB'],
                    ['bank' => 'HDFC'],
                    ['bank' => 'HDFC_C'],
                    ['bank' => 'HSBC'],
                    ['bank' => 'ICIC'],
                    ['bank' => 'ICIC_C'],
                    ['bank' => 'IBKL'],
                    ['bank' => 'IBKL_C'],
                    ['bank' => 'IDFB'],
                    ['bank' => 'IDIB'],
                    ['bank' => 'ALLA'],
                    ['bank' => 'IDIB_C'],
                    ['bank' => 'IOBA'],
                    ['bank' => 'INDB'],
                    ['bank' => 'JAKA'],
                    ['bank' => 'JSFB'],
                    ['bank' => 'JSBP'],
                    ['bank' => 'KCCB'],
                    ['bank' => 'KJSB'],
                    ['bank' => 'KARB'],
                    ['bank' => 'KVBL'],
                    ['bank' => 'KKBK'],
                    ['bank' => 'KKBK_C'],
                    ['bank' => 'LAVB_C'],
                    ['bank' => 'LAVB_R'],
                    ['bank' => 'MSNU'],
                    ['bank' => 'NKGS'],
                    ['bank' => 'NSPB'],
                    ['bank' => 'NESF'],
                    ['bank' => 'ORBC'],
                    ['bank' => 'UTBI'],
                    ['bank' => 'PSIB'],
                    ['bank' => 'PUNB_C'],
                    ['bank' => 'PUNB_R'],
                    ['bank' => 'RATN'],
                    ['bank' => 'RATN_C'],
                    ['bank' => 'ABNA'],
                    ['bank' => 'SVCB'],
                    ['bank' => 'SVCB_C'],
                    ['bank' => 'SRCB'],
                    ['bank' => 'SIBL'],
                    ['bank' => 'SCBL'],
                    ['bank' => 'SBBJ'],
                    ['bank' => 'SBHY'],
                    ['bank' => 'SBIN'],
                    ['bank' => 'SBMY'],
                    ['bank' => 'STBP'],
                    ['bank' => 'SBTR'],
                    ['bank' => 'SURY'],
                    ['bank' => 'SYNB'],
                    ['bank' => 'TJSB'],
                    ['bank' => 'TMBL'],
                    ['bank' => 'TNSC'],
                    ['bank' => 'TBSB'],
                    ['bank' => 'UCBA'],
                    ['bank' => 'UJVN'],
                    ['bank' => 'UBIN'],
                    ['bank' => 'CORP'],
                    ['bank' => 'VARA'],
                    ['bank' => 'YESB'],
                    ['bank' => 'YESB_C'],
                    ['bank' => 'ZCBL'],
                ],
                'emi' => [
                    ['type' => 'debit', 'provider' => 'HDFC'],
                    ['type' => 'debit', 'provider' => 'KKBK'],
                    ['type' => 'debit', 'provider' => 'INDB'],
                    ['type' => 'credit', 'provider' => null],
                ],
                'paylater' => [
                    ['provider' => 'epaylater'],
                    ['provider' => 'getsimpl'],
                    ['provider' => 'icic'],
                    ['provider' => 'flexmoney'],
                    ['provider' => 'lazypay'],
                ],
                'wallets' => [
                    ['provider' => 'mobikwik'],
                    ['provider' => 'olamoney'],
                    ['provider' => 'paytm'],
                    ['provider' => 'payumoney'],
                    ['provider' => 'payzapp'],
                    ['provider' => 'airtelmoney'],
                    ['provider' => 'freecharge'],
                    ['provider' => 'jiomoney'],
                    ['provider' => 'sbibuddy'],
                    ['provider' => 'openwallet'],
                    ['provider' => 'razorpaywallet'],
                    ['provider' => 'mpesa'],
                    ['provider' => 'amazonpay'],
                    ['provider' => 'phonepe'],
                    ['provider' => 'paypal'],
                    ['provider' => 'phonepeswitch'],
                    ['provider' => 'itzcash'],
                    ['provider' => 'oxigen'],
                    ['provider' => 'amexeasyclick'],
                    ['provider' => 'paycash'],
                    ['provider' => 'citibankrewards'],
                ],
                'cardless_emi' => [
                    ['provider' => 'earlysalary'],
                    ['provider' => 'zestmoney'],
                    ['provider' => 'flexmoney'],
                    ['provider' => 'walnut369'],
                    ['provider' => 'sezzle'],
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithoutOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm'    => true,
                        'grabpay'  => true,
                        'touchngo' => true,
                        'boost'    => true,
                        'mcash'    => true
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ],
    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportForPaymentLinkWithOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [
                        'usd' => 1,
                        'swift' => 1
                    ],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ],
    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithNonPaymentLinkOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ],
    'testGetPaymentMethodsAndOffersForCheckoutWithInvoiceId' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ],
    'testGetPaymentMethodsAndOffersForCheckoutWithSubscriptionId' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'subscription_id' => '', // Filled by the TestCase
                'subscription_card_change' => false,
            ],
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ]
];

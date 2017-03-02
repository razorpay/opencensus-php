<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateCardOffer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testCreateCardOfferWithIin' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'card',
                'iins'            => ['411111'],
                'percent_rate'    => 1000,
                'processing_time' => 86400,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'          => true,
                'name'            => 'Test Offer',
                'iins'            => ['411111'],
                'percent_rate'    => 1000,
                'processing_time' => 86400,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],

    'testCreateWalletOffer' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'payment_network' => 'airtelmoney',
                'percent_rate'    => 1000,
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'active'          => true,
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'payment_network' => 'airtelmoney',
                'percent_rate'    => 1000,
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],

    'testCreateNetbankingOffer' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'netbanking',
                'payment_network' => 'UTBI',
                'percent_rate'    => 1000,
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'active'          => true,
                'name'            => 'Test Offer',
                'payment_method'  => 'netbanking',
                'payment_network' => 'UTBI',
                'percent_rate'    => 1000,
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],

    'testCreateFlatCashbackOffer' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'payment_network' => 'airtelmoney',
                'flat_cashback'   => 300,
                'min_amount'      => 500,
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'flat_cashback'   => 300,
                'min_amount'      => 500,
                'active'          => true,
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'payment_network' => 'airtelmoney',
                'payment_count'   => 2,
                'processing_time' => 172800,
                'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],

    'testCreateIdenticalOffers' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'payment_count'       => 2,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OFFER_ALREADY_EXISTS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS
        ]
    ],

    'testCreateOfferWithoutCashbackCriteria' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'payment_count'       => 2,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CASHBACK_CRITERIA_MISSING,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CASHBACK_CRITERIA_MISSING
        ]
    ],

    'testCreateCardOfferWithInvalidNetwork' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'XXXX',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for card should be a valid card network'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithUnsupportedNetwork' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DISC',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => '1',
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This card payment network is not supported'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateWalletOfferWithInvalidWallet' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'wallet',
                'payment_network'    => 'airtelhoney',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'payment_count'      => 2,
                'processing_time'    => '2',
                'starts_at'          => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'       => 'Some more details',
                'terms'              => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED
        ]
    ],

    'testCreateNetbankingOfferWithInvalidBankCode' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'netbanking',
                'payment_network'    => 'XXXX',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'min_amount'         => 500,
                'payment_count'      => 2,
                'processing_time'    => '2',
                'starts_at'          => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text' => 'Some more details',
                'terms' => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for bank should be a valid bank name'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithInvalidPaymentMethod' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'tokens',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'payment_count'       => 2,
                'processing_time'     => '1',
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method: tokens'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDF1',
                'percent_rate'        => 1000,
                'payment_count'       => 2,
                'processing_time'     => '1',
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Issuer name : HDF1'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithPercentRateAndFlatCashback' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'flat_cashback'       => 200,
                'payment_count'       => 2,
                'processing_time'     => '1',
                'starts_at'           => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK
        ]
    ],

    'testCreateOfferWithInvalidOfferPeriod' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'payment_count'       => 2,
                'processing_time'     => '1',
                'starts_at'           => Carbon::createFromTimestamp(1419457070, 'Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::createFromTimestamp(1350993070, 'Asia/Kolkata')->timestamp,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_OFFER_DURATION
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION
        ]
    ],

    'testAddIinsToCardOffer' => [
        'request' => [
            'content' => [
                "iins"    => ["411111"]
            ],
            'url'     => '',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'                        => null,
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'iins'                      => ["123456", "411111"],
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'payment_count'             => 2,
                'starts_at'                 => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
            ]
        ]
    ],

    'testAddIinsInvalidFormat' => [
        'request' => [
            'content' => [
                "iins"    => [ 1  => "411111" ]
            ],
            'url'     => '',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_FORMAT_FOR_IINS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_FORMAT_FOR_IINS
        ]
    ],

    'testAddIinsToNonCardOffer' => [
        'request' => [
            'content'  => [
                "iins" => [411111]
            ],
            'url'    => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER
        ]
    ],

    'testDeactivateOffer' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                "active" => 0
            ]
        ],
        'response' => [
            'content' => [
                'id'                        => null,
                'active'                    => false,
                'name'                      => 'Test Offer',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'payment_count'             => 2,
                'starts_at'                 => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
            ]
        ]
    ],

    'testUpdateExistingOffer' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'  => 'Updated name'
            ]
        ],
        'response' => [
            'content' => [
                'id'                        => null,
                'active'                    => true,
                'name'                      => 'Updated name',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'payment_count'             => 2,
                'starts_at'                 => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
            ]
        ]
    ],

    'testFetchOfferById' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'id'                        => null,
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'payment_count'             => 2,
                'starts_at'                 => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
            ]
        ]
    ],

    'testGetMultipleOffers' => [
        'request' => [
            'url'      => '/offers',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [

                        'active'                    => true,
                        'name'                      => 'Test Offer',
                        'payment_method'            => 'card',
                        'payment_method_type'       => 'credit',
                        'payment_network'           => 'VISA',
                        'issuer'                    => 'HDFC',
                        'percent_rate'              => 1000,
                        'processing_time'           => 86400,
                        'payment_count'             => 2,
                        'starts_at'                 => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
                        'ends_at'                   => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
                    ]
                ]
            ]
        ]
    ],

    'testDeactivateAllOffer' => [
        'request' => [
            'url' => '/offers/deactivate',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ]
];

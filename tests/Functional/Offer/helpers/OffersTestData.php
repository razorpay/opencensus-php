<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Carbon\Carbon;

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
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'custom_short_display_text' => '10% cashback on HDFC Bank Ltd Visa credit card.',
                'custom_long_display_text'  => 'Get 10% cashback on HDFC Bank Ltd Visa credit card. Valid till ' .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 1 business day(s). Some more details',
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'starts_at'                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'        => 'Some more details',
            ]
        ]
    ],

    'testCreateCardOfferWithIin' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'iins'                => ['411111'],
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'custom_short_display_text' => '10% cashback on selected card.',
                'custom_long_display_text'  => 'Get 10% cashback on selected card. Valid till ' .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 1 business day(s). Some more details',
                'active'                    => true,
                'name'                      => 'Test Offer',
                'iins'                      => ['411111'],
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'starts_at'                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'        => 'Some more details',
            ]
        ]
    ],

    'testCreateWalletOffer' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'wallet',
                'payment_network'    => 'airtelmoney',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'min_amount'         => 500,
                'payment_count'      => 2,
                'processing_time'    => 172800,
                'starts_at'          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details' => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'max_cashback'              => 200,
                'min_amount'                => 500,
                'custom_short_display_text' => '10% cashback upto Rs 2 on Airtelmoney wallet.',
                'custom_long_display_text'  => 'Get 10% cashback upto Rs 2 on Airtelmoney wallet. For first 2 payments Transactions above Rs 5. Valid till ' .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 2 business day(s). Some more details',
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'wallet',
                'payment_network'           => 'airtelmoney',
                'percent_rate'              => 1000,
                'payment_count'             => 2,
                'processing_time'           => 172800,
                'starts_at'                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'        => 'Some more details',
            ]
        ]
    ],

    'testCreateNetbankingOffer' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'netbanking',
                'payment_network'    => 'UTBI',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'min_amount'         => 500,
                'payment_count'      => 2,
                'processing_time'    => 172800,
                'starts_at'          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details' => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'max_cashback'              => 200,
                'min_amount'                => 500,
                'custom_short_display_text' => '10% cashback upto Rs 2 on United Bank of India netbanking.',
                'custom_long_display_text'  => 'Get 10% cashback upto Rs 2 on United Bank of India netbanking. For first 2 payments Transactions above Rs 5. Valid till ' .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 2 business day(s). Some more details',
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'netbanking',
                'payment_network'           => 'UTBI',
                'percent_rate'              => 1000,
                'payment_count'             => 2,
                'processing_time'           => 172800,
                'starts_at'                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'        => 'Some more details',
            ]
        ]
    ],

    'testCreateFlatCashbackOffer' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'wallet',
                'payment_network'    => 'airtelmoney',
                'flat_cashback'      => 300,
                'min_amount'         => 500,
                'payment_count'      => 2,
                'processing_time'    => 172800,
                'starts_at'          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details' => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'flat_cashback'             => 300,
                'min_amount'                => 500,
                'custom_short_display_text' => 'Rs 3 cashback on Airtelmoney wallet.',
                'custom_long_display_text'  => 'Get Rs 3 cashback on Airtelmoney wallet. For first 2 payments Transactions above Rs 5. Valid till ' .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 2 business day(s). Some more details',
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'wallet',
                'payment_network'           => 'airtelmoney',
                'payment_count'             => 2,
                'processing_time'           => 172800,
                'starts_at'                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'        => 'Some more details',
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
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OFFER_ALREADY_EXISTS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
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
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CASHBACK_CRITERIA_MISSING,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CASHBACK_CRITERIA_MISSING
        ]
    ],

    'testCreateCardOfferWithInvalidNetwork' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'XXXX',
                'issuer'              => 'DBS',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for card should be a valid card network'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithUnsupportedNetwork' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DISC',
                'issuer'              => 'DBS',
                'percent_rate'        => 1000,
                'processing_time'     => '1',
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This card payment network is not supported'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
                'starts_at'          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details' => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
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
                'starts_at'          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'            => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details' => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for bank should be a valid bank name'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method: tokens'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
                'starts_at'           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'ends_at'             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
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
                'starts_at'           => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                'ends_at'             => Carbon::today('Asia/Kolkata')->subMonth()->timestamp,
                'additional_details'  => 'Some more details'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_OFFER_DURATION
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION
        ]
    ],

    'testAddIinsToCardOffer' => [
        'request' => [
            'content' => [
                "411111"
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'custom_short_display_text' => '10% cashback on select HDFC Bank Ltd Visa credit card.',
                'custom_long_display_text'  => 'Get 10% cashback on select HDFC Bank Ltd Visa credit card. For first 2 payments Transactions above Rs 10. Valid till ' .  Carbon::today('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 1 business day(s).',
                'id'                        => null,
                'active'                    => true,
                'name'                      => 'Test Offer',
                'payment_method'            => 'card',
                'payment_method_type'       => 'credit',
                'payment_network'           => 'VISA',
                'issuer'                    => 'HDFC',
                'iins'                      => ['411111'],
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'payment_count'             => 2,
                'starts_at'                 => Carbon::today('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
            ]
        ]
    ],

    'testAddIinsInvalidFormat' => [
        'request' => [
            'content' => [
                1 => "411111"
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_FORMAT_FOR_IINS
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_FORMAT_FOR_IINS
        ]
    ],

    'testAddIinsToNonCardOffer' => [
        'request' => [
            'content' => [
                "411111"
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER
        ]
    ],

    'testDeactivateOffer' => [
        'request' => [
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'custom_short_display_text' => '10% cashback on HDFC Bank Ltd Visa credit card.',
                'custom_long_display_text'  => 'Get 10% cashback on HDFC Bank Ltd Visa credit card. For first 2 payments Transactions above Rs 10. Valid till ' .  Carbon::today('Asia/Kolkata')->addMonth()->format('d-m-Y') . '. Cashback will get credited in 1 business day(s).',
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
                'starts_at'                 => Carbon::today('Asia/Kolkata')->timestamp,
                'ends_at'                   => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
            ]
        ]
    ],

    'testDeactivateAlreadyDeactivatedOffer' => [
        'request' => [
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OFFER_ALREADY_DEACTIVATED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OFFER_ALREADY_DEACTIVATED
        ]
    ]
];

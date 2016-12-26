<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Carbon\Carbon;

return [
    'testCreateCardOffer' => [
        'request' => [
            'content' => [
                "name"                => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "percent_rate"        => 1000,
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "custom_short_display_text" => "10% cashback on HDFC Visa credit card",
                "custom_long_display_text"  => "Get 10% cashback on HDFC Visa credit card for first 2 payments. Valid till " .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . ". Cashback will get credited in 1 business day(s). Some more details",
                "active"                    => true,
                "name"                      => "Test Offer",
                "payment_method"            => "card",
                "payment_method_type"       => "credit",
                "payment_network"           => "VISA",
                "issuer"                    => "HDFC",
                "percent_rate"              => 10,
                "payment_count"             => 2,
                "processing_time"           => 86400,
                "starts_at"                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"        => "Some more details",
                "admin"                     => true
            ]
        ]
    ],
    'testCreateWalletOffer' => [
        'request' => [
            'content' => [
                "name"               => "Test Offer",
                "payment_method"     => "wallet",
                "payment_network"    => "airtelmoney",
                "percent_rate"       => 1000,
                "max_cashback"       => 200,
                "payment_count"      => 2,
                "processing_time"    => "2",
                "starts_at"          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details" => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "max_cashback"              => 200,
                "custom_short_display_text" => "10% cashback upto Rs 200 on Airtelmoney wallet",
                "custom_long_display_text"  => "Get 10% cashback upto Rs 200 on Airtelmoney wallet for first 2 payments. Valid till " .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . ". Cashback will get credited in 2 business day(s). Some more details",
                "active"                    => true,
                "name"                      => "Test Offer",
                "payment_method"            => "wallet",
                "payment_network"           => "airtelmoney",
                "percent_rate"              => 10,
                "payment_count"             => 2,
                "processing_time"           => 172800,
                "starts_at"                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"        => "Some more details",
                "admin"                     => true
            ]
        ]
    ],
    'testCreateNetbankingOffer' => [
        'request' => [
            'content' => [
                "name"               => "Test Offer",
                "payment_method"     => "netbanking",
                "payment_network"    => 'UTBI',
                "percent_rate"       => 1000,
                "max_cashback"       => 200,
                'min_amount'         => 500,
                "payment_count"      => 2,
                "processing_time"    => "2",
                "starts_at"          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details" => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "max_cashback"              => 200,
                "min_amount"                => 500,
                "custom_short_display_text" => "10% cashback upto Rs 200 on United Bank of India netbanking",
                "custom_long_display_text"  => "Get 10% cashback upto Rs 200 on United Bank of India netbanking for first 2 payments above Rs 500. Valid till " .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . ". Cashback will get credited in 2 business day(s). Some more details",
                "active"                    => true,
                "name"                      => "Test Offer",
                "payment_method"            => "netbanking",
                "payment_network"           => "UTBI",
                "percent_rate"              => 10,
                "payment_count"             => 2,
                "processing_time"           => 172800,
                "starts_at"                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"        => "Some more details",
                "admin"                     => true
            ]
        ]
    ],
    'testCreateFlatCashbackOffer' => [
        'request' => [
            'content' => [
                "name"               => "Test Offer",
                "payment_method"     => "wallet",
                "payment_network"    => 'airtelmoney',
                "flat_cashback"      => 300,
                'min_amount'         => 500,
                "payment_count"      => 2,
                "processing_time"    => "2",
                "starts_at"          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details" => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "flat_cashback"             => 300,
                "min_amount"                => 500,
                "custom_short_display_text" => "Rs 300 cashback on Airtelmoney wallet",
                "custom_long_display_text"  => "Get Rs 300 cashback on Airtelmoney wallet for first 2 payments above Rs 500. Valid till " .  Carbon::now('Asia/Kolkata')->addMonth()->format('d-m-Y') . ". Cashback will get credited in 2 business day(s). Some more details",
                "active"                    => true,
                "name"                      => "Test Offer",
                "payment_method"            => "wallet",
                "payment_network"           => "airtelmoney",
                "payment_count"             => 2,
                "processing_time"           => 172800,
                "starts_at"                 => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"                   => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"        => "Some more details",
                "admin"                     => true
            ]
        ]
    ],
    'testCreateIdenticalOffers' => [
        'request' => [
            'content' => [
                "name"                => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "percent_rate"        => 1000,
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
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
    'testCreateOfferWithoutCashbackDefinitionParams' => [
        'request' => [
            'content' => [
                "name"                => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CASHBACK_CALCULATION_PARAMS_MISSING,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CASHBACK_CALCULATION_PARAMS_MISSING
        ]
    ],
    'testCreateCardOfferWithInvalidNetwork' => [
        'request' => [
            'content' => [
                "name" => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "XXXX",
                "issuer"              => "DBS",
                "percent_rate"        => 1000,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Payment network for card should be a valid card network"
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
                "name" => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "DISC",
                "issuer"              => "DBS",
                "percent_rate"        => 1000,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "This card payment network is not supported"
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
                "name"               => "Test Offer",
                "payment_method"     => "wallet",
                "payment_network"    => "airtelhoney",
                "percent_rate"       => 1000,
                "max_cashback"       => 200,
                "payment_count"      => 2,
                "processing_time"    => "2",
                "starts_at"          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"            => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details" => "Some more details"
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
                "name"               => "Test Offer",
                "payment_method"     => "netbanking",
                "payment_network"    => 'XXXX',
                "percent_rate"       => 1000,
                "max_cashback"       => 200,
                'min_amount'         => 500,
                "payment_count"      => 2,
                "processing_time"    => "2",
                "starts_at"          => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"            => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details" => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Payment network for bank should be a valid bank name"
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
                "name"                => "Test Offer",
                "payment_method"      => "tokens",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "percent_rate"        => 1000,
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Invalid payment method: tokens"
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
                "name"                => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "percent_rate"        => 1000,
                "flat_cashback"       => 200,
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                "ends_at"             => Carbon::now('Asia/Kolkata')->addMonth()->timestamp,
                "additional_details"  => "Some more details"
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
                "name"                => "Test Offer",
                "payment_method"      => "card",
                "payment_method_type" => "credit",
                "payment_network"     => "VISA",
                "issuer"              => "HDFC",
                "percent_rate"        => 1000,
                "payment_count"       => 2,
                "processing_time"     => "1",
                "starts_at"           => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                "ends_at"             => Carbon::today('Asia/Kolkata')->subMonth()->timestamp,
                "additional_details"  => "Some more details"
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
    ]
];

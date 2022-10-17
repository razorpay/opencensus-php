<?php

use Carbon\Carbon;

use RZP\Exception;
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
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
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
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testCreateOfferWithNullMethod' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testOfferPrivateAuth' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer over private auth',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1639758567,
                'ends_at'             => 1639758568,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer over private auth',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1639758567,
                'ends_at'             => 1639758568,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testOfferPrivateAuthWithoutFeature' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer over private auth',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1639758567,
                'ends_at'             => 1639758568,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        ],
    ],

    'testOfferCreateBulk' => [
        'request' => [
            'content' => [
                'offer' => [
                    'name'                => 'Test Offer over private auth',
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'VISA',
                    'issuer'              => 'HDFC',
                    'international'       => true,
                    'percent_rate'        => 1000,
                    'processing_time'     => 86400,
                    'starts_at'           => 1639758567,
                    'ends_at'             => 1639758568,
                    'display_text'        => 'Some more details',
                    'terms'               => 'Some more details',
                    'block'               =>  1,
                    'type'                =>  'instant'
                ],
                'merchant_ids' => [
                    '10000000000000',
                    '100000Razorpay',
                    'NotARealMercId',
                ],
            ],
            'url'    => '/offers/bulk',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success'  => 2,
                'failures' => [
                    'NotARealMercId',
                ]
            ],
        ],
    ],

    'testCreateOfferWithNullMethodAndInvalidIssuer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'issuer'              => 'XXXX',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : XXXX',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithMaxPaymentCount' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'max_payment_count'   => 2,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
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
                'max_payment_count'   => 2,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testCreateCardOfferWithLinkedOfferIds' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1500,
                'max_payment_count'   => 2,
                'linked_offer_ids'    => null,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
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
                'percent_rate'        => 1500,
                'max_payment_count'   => 2,
                'linked_offer_ids'    => null,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testCreateCardOfferWithInvalidLinkedOfferIds' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1500,
                'max_payment_count'   => 2,
                'linked_offer_ids'    => null,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked offer ids submitted are not valid',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            =>  'instant'
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
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],

    'testCreateDcCardOfferWithIin' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'card',
                'issuer'          => 'HDFC_DC',
                'percent_rate'    => 1000,
                'processing_time' => 86400,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'          => true,
                'name'            => 'Test Offer',
                'issuer'          => 'HDFC',
                'payment_method_type' => 'debit',
                'percent_rate'    => 1000,
                'processing_time' => 86400,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ]
        ]
    ],
    'testCreateHDFCDebitCardNoCostEMIOffer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC_DC',
                'emi_subvention'      => true,
                'emi_durations'       => [6],
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'HDFC Debit Card Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'payment_method_type' => 'debit',
                'emi_durations'       => [6],
                'max_payment_count'   => 2,
                'min_amount'          => 500000,
                'display_text'        => 'HDFC Debit Card Emi Subvention offers',
                'terms'               => 'Some more details'
            ],
        ],
    ],
    'testCreateHDFCDebitCardEMIOffer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'payment_method_type' => 'debit',
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'emi_subvention'      => true,
                'emi_durations'       => [6],
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'HDFC Debit Card EMI offers',
                'terms'               => 'HDFC Debit Card EMI offers',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'payment_method_type' => 'debit',
                'display_text'        => 'HDFC Debit Card EMI offers',
                'terms'               => 'HDFC Debit Card EMI offers',
                'type'                => 'instant'
            ],
        ],
    ],
    'testCreateWalletOffer' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'issuer'          => 'airtelmoney',
                'percent_rate'    => 1000,
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            =>  'instant'
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
                'issuer'          => 'airtelmoney',
                'percent_rate'    => 1000,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
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
                'issuer'          => 'UTIB',
                'percent_rate'    => 1000,
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            =>  'instant'
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
                'issuer'          => 'UTIB',
                'percent_rate'    => 1000,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
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
                'issuer'          => 'airtelmoney',
                'flat_cashback'   => 300,
                'min_amount'      => 500,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            =>  'instant'
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
                'issuer'          => 'airtelmoney',
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
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
                'max_payment_count'   => 2,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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
                'max_payment_count'   => 2,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for card should be a valid card network code'
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
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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
                'issuer'             => 'airtelhoney',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'processing_time'    => '2',
                'starts_at'          => 1514764800,
                'ends_at'            => 1546300800,
                'display_text'       => 'Some more details',
                'terms'              => 'Some more details',
                'block'              =>  1,
                'type'               => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : airtelhoney',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateNetbankingOfferWithInvalidBankCode' => [
        'request' => [
            'content' => [
                'name'               => 'Test Offer',
                'payment_method'     => 'netbanking',
                'issuer'             => 'XXXX',
                'percent_rate'       => 1000,
                'max_cashback'       => 200,
                'min_amount'         => 500,
                'processing_time'    => '2',
                'starts_at'          => 1514764800,
                'ends_at'            => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : XXXX'
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
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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
                'issuer'              => 'HDFD',
                'percent_rate'        => 1000,
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : HDFD'
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
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'starts_at'           => 1419457070,
                'ends_at'             => 1350993070,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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

    'testCreateEmiSubventionOffer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'emi_subvention'      => true,
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'max_payment_count'   => 2,
                'min_amount'          => 316389,
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details'
            ]
        ]


    ],

    'testPaymentMethodTypeForCreditCardOfferCreation' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'emi_subvention'      => true,
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'payment_method_type' => 'credit',
                'max_payment_count'   => 2,
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details'
            ]
        ]


    ],

    'testConflictingEmiSubOffers' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_network'     => 'AMEX',
                'emi_subvention'      => true,
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'starts_at'           => 1519457060,
                'ends_at'             => 1546300800,
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
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

    'testEmiSubventionOfferWithInvalidAmount' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_network'     => 'AMEX',
                'emi_subvention'      => true,
                'min_amount'          => 200000,
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Min amount for this offer should be greater than 3191.49'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testEmiSubventionWithDuration' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'emi_subvention'      => true,
                'emi_durations'       => ['6'],
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'HDFC',
                'max_payment_count'   => 2,
                'min_amount'          => 26366,
                'emi_durations'       => [6],
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testEmiSubventionWithIssuerAndNetwork' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_network'     => 'AMEX',
                'issuer'              => 'HDFC',
                'emi_subvention'      => true,
                'emi_durations'       => [9],
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either issuer or payment network should be sent'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferBajaj' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_network'     => 'BAJAJ',
                'emi_subvention'      => true,
                'emi_durations'       => [9],
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant',
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'          => true,
                'name'            => 'Test Offer',
                'payment_method'  => 'emi',
                'payment_network' => 'BAJAJ',
                'display_text'    => 'Emi Subvention offers',
                'terms'           => 'Some more details',
                'min_amount'      => 300000,
            ]
        ]
    ],

    'testOfferWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'issuer'              => 'random',
                'emi_subvention'      => true,
                'emi_durations'       => [9],
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name: random'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testInvalidEmiDuration' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_network'     => 'AMEX',
                'emi_subvention'      => true,
                'emi_durations'       => [3, 7, 5],
                'max_payment_count'   => 2,
                'processing_time'     => '1',
                'ends_at'             => Carbon::tomorrow()->getTimestamp(),
                'display_text'        => 'Emi Subvention offers',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid emi durations given 3, 7, 5'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testAddIinsToCardOffer' => [
        'request' => [
            'content' => [
                'iins'    => ['411111']
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
                'iins'                      => ['123456', '411111'],
                'percent_rate'              => 1000,
                'processing_time'           => 86400,
                'max_payment_count'         => 2,
                'starts_at'                 => 1514764800,
                'ends_at'                   => 1546300800,
            ]
        ]
    ],

    'testAddIinsInvalidFormat' => [
        'request' => [
            'content' => [
                'iins'    => [ 1  => '411111' ]
            ],
            'url'     => '',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'IINs should be a valid array',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testAddIinsToNonCardOffer' => [
        'request' => [
            'content'  => [
                'iins' => ['411111']
            ],
            'url'    => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'IINs can be only edited for card / emi offer'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testDeactivateOffer' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'active' => 0
            ]
        ],
        'response' => [
            'content' => [
                'id'                  => null,
                'active'              => false,
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'max_payment_count'   => 2,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
            ]
        ]
    ],

    'testUpdateExistingOffer' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'    => 'Updated name',
                'ends_at' => 1550999999,
            ]
        ],
        'response' => [
            'content' => [
                'id'                  => null,
                'active'              => true,
                'name'                => 'Updated name',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'max_payment_count'   => 2,
                'starts_at'           => 1514764800,
                'ends_at'             => 1550999999,
            ]
        ]
    ],

    'testUpdateWalletOfferWithMaxPaymentCount' => [
        'request' => [
            'content' => [
                'max_payment_count' => 2,
            ],
            'url'    => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'max_payment_count can only be set for card or emi offera',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testUpdateCardOfferWithNoMaxPaymentCount' => [
        'request' => [
            'content' => [
                'linked_offer_ids' => null,
            ],
            'url'    => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'linked_offer_ids can only be set for offer with max_payment_count',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testUpdateCardOfferWithInvalidLinkedOfferIds' => [
        'request' => [
            'content' => [
                'linked_offer_ids' => null,
            ],
            'url'    => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked offer ids submitted are not valid',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testFetchOfferById' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'id'                  => null,
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'max_payment_count'   => 2,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
            ]
        ]
    ],

    'testFetchSubscriptionOfferById' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
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
                'max_payment_count'   => 2,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'redemption_type'     => 'cycle',
                'applicable_on'       => 'both',
                'no_of_cycles'        => 10,
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
                        'active'              => true,
                        'name'                => 'Test Offer',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'VISA',
                        'issuer'              => 'HDFC',
                        'percent_rate'        => 1000,
                        'processing_time'     => 86400,
                        'max_payment_count'   => 2,
                        'starts_at'           => 1514764800,
                        'ends_at'             => 1546300800,
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
    ],

    'testOfferFixForAttemptedOrders' => [
        'request' => [
            'url'      => '/payments/fix_attempted_orders',
            'method'   => 'POST',
        ],
        'response' => [
            'content' => [
                'success'          => 1,
                'failed'           => 0,
                'failedPaymentIds' => [],
            ]
        ],
    ],

    'testCreateOfferValidateMerchant' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method not enabled for the merchant : card',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithCorporateOrRetailIssuer' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'netbanking',
                'issuer'          => 'BARB_C',
                'min_amount'      => 1000,
                'flat_cashback'   => 800,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'          => true,
                'name'            => 'Test Offer',
                'payment_method'  => 'netbanking',
                'issuer'          => 'BARB_C',
                'min_amount'      => 1000,
                'flat_cashback'   => 800,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details'
            ],
        ],
    ],

    'testCreateOfferValidateMaxCashback' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'wallet',
                'issuer'          => 'airtelmoney',
                'max_cashback'    => 200,
                'min_amount'      => 500,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Max cashback should be combined wih percent rate'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MAX_CASHBACK_WITHOUT_PERCENT_RATE
        ],
    ],

    'testCreateOfferInternationalEmi' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'BARB_C',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'emi',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'BARB_C',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ],
        ],
    ],

    'testCreateOfferValidateMethodType' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'netbanking',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'BARB_C',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method type field may be sent only when payment method is card',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOfferMinAmount' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'netbanking',
                'issuer'          => 'UTIB',
                'min_amount'      => 500,
                'flat_cashback'   => 800,
                'processing_time' => 172800,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Flat cashback cannot be greater than minimum amount',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCardOfferWithInvalidIinLength' => [
        'request' => [
            'content' => [
                'name'            => 'Test Offer',
                'payment_method'  => 'card',
                'iins'            => ['4111111'],
                'percent_rate'    => 1000,
                'processing_time' => 86400,
                'starts_at'       => 1514764800,
                'ends_at'         => 1546300800,
                'display_text'    => 'Some more details',
                'terms'           => 'Some more details',
                'block'           =>  1,
                'type'            => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IIN : All IINs should have exactly 6 digits',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCardOfferWithInvalidFullNetworkName' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MasterCard',
                'issuer'              => 'BARB_C',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                => 'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for card should be a valid card network code',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOfferWithSameIIN' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'VISA',
                'issuer'              => 'HDFC',
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'iins'                =>  ['411111'],
                'type'                =>  'instant'
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
                'international'       => true,
                'percent_rate'        => 1000,
                'processing_time'     => 86400,
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testFindOffersInPaymentResponseWithExpandsForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'offers',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
            ],
        ],
    ],

    'testPaymentResponseWithNoExpandsForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
            ],
        ],
    ],

    'testDbRequestsBeforeMigrationMetric' => [
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
                        'active'              => true,
                        'name'                => 'Test Offer',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'VISA',
                        'issuer'              => 'HDFC',
                        'percent_rate'        => 1000,
                        'processing_time'     => 86400,
                        'max_payment_count'   => 2,
                        'starts_at'           => 1514764800,
                        'ends_at'             => 1546300800,
                    ]
                ]
            ]
        ]
    ],

    'testCreateCardlessEmiOfferWithIssuer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'cardless_emi',
                'issuer'              => 'zestmoney',
                "min_amount"          =>  10000,
                "flat_cashback"       =>  1000,
                'starts_at'           =>  1641014736,
                'ends_at'             =>  1704091568,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'cardless_emi',
                'issuer'              => 'zestmoney',
                'min_amount'          =>  10000,
                'flat_cashback'       =>  1000,
                'starts_at'           =>  1641014736,
                'ends_at'             =>  1704091568,
                'type'                =>  'instant',
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],

    'testCreateCardlessEmiOfferWithoutIssuer' => [
        'request' => [
            'content' => [
                'name'                => 'Test Offer',
                'payment_method'      => 'cardless_emi',
                "min_amount"          =>  200000,
                "flat_cashback"       =>  10000,
                'starts_at'           =>  1641014736,
                'ends_at'             =>  1704091568,
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details',
                'block'               =>  1,
                'type'                =>  'instant'
            ],
            'url'    => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'active'              => true,
                'name'                => 'Test Offer',
                'payment_method'      => 'cardless_emi',
                'min_amount'          =>  200000,
                'flat_cashback'       =>  10000,
                'starts_at'           =>  1641014736,
                'ends_at'             =>  1704091568,
                'type'                =>  'instant',
                'display_text'        => 'Some more details',
                'terms'               => 'Some more details'
            ]
        ]
    ],
];

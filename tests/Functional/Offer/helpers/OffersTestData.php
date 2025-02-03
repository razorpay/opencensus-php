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
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateOfferWithApiReadsConflictingOffers' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS
        ]
    ],
    'testCreateOfferWithoutApiReadsConflictingOffers' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateOfferWithApiReadsValidateMerchantMethod' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method not enabled for the merchant : card',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithoutApiReadsValidateMerchantMethod' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details',
                ],
            ],
        ],
    ],

    'testCreateOfferWithApiReadsValidateMerchantCategory' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Offer creation is not allowed for this Merchant category',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithoutApiReadsValidateMerchantCategory' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details',
                ],
            ]
        ]
    ],

    'testCreateOfferWithApiReadsValidateOfferFeatureBlock' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Offer creation is not allowed for Merchant',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithoutApiReadsValidateOfferFeatureBlock' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details',
                ],
            ]
        ]
    ],

    'testCreateOfferWithoutApiReadsTenureDiscountMap' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC_DC',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'max_payment_count' => 2,

                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'HDFC Debit Card Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'payment_method_type' => 'debit',
                    'emi_durations' => [6],
                    'max_payment_count' => 2,
                    'min_amount' => 500000,
                    'display_text' => 'HDFC Debit Card Emi Subvention offers',
                    'terms' => 'Some more details',
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                ]
            ],
        ],
    ],

    'testCreateOfferWithNullMethod' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testOfferPrivateAuth' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer over private auth',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1639758567,
                'ends_at' => 1639758568,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer over private auth',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',

                    'percent_rate' => 1000,
                    'starts_at' => 1639758567,
                    'ends_at' => 1639758568,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testOfferPrivateAuthWithoutFeature' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer over private auth',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1639758567,
                'ends_at' => 1639758568,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
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
                    'name' => 'Test Offer over private auth',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',

                    'percent_rate' => 1000,
                    'starts_at' => 1639758567,
                    'ends_at' => 1639758568,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details',
                    'block' => 1,
                    'type' => 'instant'
                ],
                'merchant_ids' => [
                    '10000000000000',
                    '100000Razorpay',
                    'NotARealMercId',
                ],
            ],
            'url' => '/offers/bulk',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success' => 2,
                'failures' => [
                    'NotARealMercId',
                ]
            ],
        ],
    ],

    'testCreateOfferWithNullMethodAndInvalidIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'issuer' => 'XXXX',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : XXXX',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithMaxPaymentCount' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',
                    'percent_rate' => 1000,
                    'max_payment_count' => 2,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateCardOfferWithLinkedOfferIds' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'ICIC',
                'percent_rate' => 1500,
                'max_payment_count' => 2,
                'linked_offer_ids' => null,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'ICIC',
                    'percent_rate' => 1500,
                    'max_payment_count' => 2,
                    'linked_offer_ids' => null,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateCardOfferWithInvalidLinkedOfferIds' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1500,
                'max_payment_count' => 2,
                'linked_offer_ids' => null,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked offer ids submitted are not valid',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithIin' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'iins' => ['411111'],
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'iins' => ['411111'],
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateDcCardOfferWithIin' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'issuer' => 'HDFC_DC',
                'iins' => ['411111'],
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'issuer' => 'HDFC',
                    'payment_method_type' => 'debit',
                    'iins' => ['411111'],
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],
    'testCreateHDFCDebitCardNoCostEMIOfferWithoutDuration' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer Without duration',
                'payment_method' => 'emi',
                'issuer' => 'HDFC_DC',
                'min_amount' => 500000,
                'emi_subvention' => true,
                'max_payment_count' => 2,

                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Test Offer Without duration',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_SUBVENTION_PARAMS,
        ]
    ],
    'testCreateHDFCDebitCardNoCostEMIOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC_DC',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'max_payment_count' => 2,

                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'HDFC Debit Card Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'payment_method_type' => 'debit',
                    'emi_durations' => [6],
                    'max_payment_count' => 2,
                    'min_amount' => 500000,
                    'display_text' => 'HDFC Debit Card Emi Subvention offers',
                    'terms' => 'Some more details',
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                ]
            ],
        ],
    ],
    'testCreateHDFCDebitCardEMIOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'payment_method_type' => 'debit',
                'max_payment_count' => 2,

                'emi_subvention' => true,
                'emi_durations' => [6],
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'HDFC Debit Card EMI offers',
                'terms' => 'HDFC Debit Card EMI offers',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'payment_method_type' => 'debit',
                    'display_text' => 'HDFC Debit Card EMI offers',
                    'terms' => 'HDFC Debit Card EMI offers',
                    'type' => 'instant'
                ]
            ],
        ],
    ],
    'testCreateWalletOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'wallet',
                'issuer' => 'airtelmoney',
                'percent_rate' => 1000,
                'max_cashback' => 200,
                'min_amount' => 500,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'max_cashback' => 200,
                    'min_amount' => 500,
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'wallet',
                    'issuer' => 'airtelmoney',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateNetbankingOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'netbanking',
                'issuer' => 'UTIB',
                'percent_rate' => 1000,
                'max_cashback' => 200,
                'min_amount' => 500,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'max_cashback' => 200,
                    'min_amount' => 500,
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'netbanking',
                    'issuer' => 'UTIB',
                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateFlatCashbackOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'wallet',
                'issuer' => 'airtelmoney',
                'flat_cashback' => 300,
                'min_amount' => 500,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'flat_cashback' => 300,
                    'min_amount' => 500,
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'wallet',
                    'issuer' => 'airtelmoney',
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateIdenticalOffers' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS
        ]
    ],

    'testCreateOfferWithoutCashbackCriteria' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CASHBACK_CRITERIA_MISSING
        ]
    ],

    'testCreateCardOfferWithInvalidNetwork' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'XXXX',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment network for card should be a valid card network code'
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
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'DISC',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
                'name' => 'Test Offer',
                'payment_method' => 'wallet',
                'issuer' => 'airtelhoney',
                'percent_rate' => 1000,
                'max_cashback' => 200,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : airtelhoney',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateNetbankingOfferWithInvalidBankCode' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'netbanking',
                'issuer' => 'XXXX',
                'percent_rate' => 1000,
                'max_cashback' => 200,
                'min_amount' => 500,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : XXXX'
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
                'name' => 'Test Offer',
                'payment_method' => 'tokens',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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

    'testCreateOfferWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFD',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name : HDFD'
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
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'flat_cashback' => 200,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK
        ]
    ],

    'testCreateOfferWithInvalidOfferPeriod' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1419457070,
                'ends_at' => 1350993070,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_OFFER_DURATION
        ]
    ],

    'testCreateNCEmiSubventionOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'emi_durations' => [6],
                    'max_payment_count' => 2,
                    'min_amount' => 316389,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Emi Subvention offers',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateLCEmiSubventionOfferWithZeroPercentRate' => [
        'request' => [
            'content' => [
                'name' => 'Test LC EMI Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'low_cost_emi' => [
                    'discount_to_avail' => [
                        'discount_percentage' => 0,
                    ],
                    'issuer' => 'HDFC',
                    'tenure' => 6
                ],
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' =>
                    [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Percentage rate Should be minimum .01%'
                    ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateLCEmiSubventionOffer' => [
        'request' => [
            'content' => [
                'name' => 'Test LC EMI Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'low_cost_emi' => [
                    'discount_to_avail' => [
                        'discount_percentage' => 1,
                    ],
                    'issuer' => 'HDFC',
                    'tenure' => 6
                ],
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test LC EMI Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'emi_durations' => [6],
                    'percent_rate' => 1,
                    'max_payment_count' => 2,
                    'min_amount' => 316389,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Emi Subvention offers',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testPaymentMethodTypeForCreditCardOfferCreation' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'max_payment_count' => 2,
                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'emi_durations' => [6],
                    'issuer' => 'HDFC',
                    'payment_method_type' => 'credit',
                    'max_payment_count' => 2,
                    'display_text' => 'Emi Subvention offers',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testConflictingEmiSubOffers' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_network' => 'AMEX',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'max_payment_count' => 2,
                'starts_at' => 1519457060,
                'ends_at' => 1546300800,
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' =>
                    [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
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
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_network' => 'AMEX',
                'emi_subvention' => true,
                'emi_durations' => [6],
                'min_amount' => 200000,
                'max_payment_count' => 2,

                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Min amount for this offer should be greater than 3191.49'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testEmiSubventionWithDuration' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'emi_durations' => ['6'],
                'max_payment_count' => 2,

                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'issuer' => 'HDFC',
                    'max_payment_count' => 2,
                    'min_amount' => 26366,
                    'emi_durations' => [6],
                    'display_text' => 'Emi Subvention offers',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testEmiSubventionWithIssuerAndNetwork' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_network' => 'AMEX',
                'issuer' => 'HDFC',
                'emi_subvention' => true,
                'emi_durations' => [9],
                'max_payment_count' => 2,

                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [

                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either issuer or payment network should be sent'
                ]
            ],
            'status_code' => 400,
        ],

        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferBajaj' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_network' => 'BAJAJ',
                'emi_subvention' => true,
                'emi_durations' => [9],
                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant',
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'payment_network' => 'BAJAJ',
                    'display_text' => 'Emi Subvention offers',
                    'terms' => 'Some more details',
                    'min_amount' => 300000,
                ]
            ]
        ]
    ],

    'testOfferWithInvalidIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'issuer' => 'random',
                'emi_subvention' => true,
                'emi_durations' => [9],
                'max_payment_count' => 2,

                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid issuer name: random'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testInvalidEmiDuration' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_network' => 'AMEX',
                'emi_subvention' => true,
                'emi_durations' => [3, 7, 5],
                'max_payment_count' => 2,

                'ends_at' => Carbon::tomorrow()->getTimestamp(),
                'display_text' => 'Emi Subvention offers',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid emi durations given 3, 7, 5'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testAddIinsToCardOffer' => [
        'request' => [
            'content' => [
                'iins' => ['411111']
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id' => null,
                'active' => true,
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'iins' => ['123456', '411111'],
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
            ]
        ]
    ],

    'testAddIinsInvalidFormat' => [
        'request' => [
            'content' => [
                'iins' => [1 => '411111']
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'IINs should be a valid array',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testAddIinsToNonCardOffer' => [
        'request' => [
            'content' => [
                'iins' => ['411111']
            ],
            'url' => '',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'IINs can be only edited for card / emi offer'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testDeactivateOffer' => [
        'request' => [
            'url' => '',
            'method' => 'PATCH',
            'content' => [
                'active' => 0
            ]
        ],
        'response' => [
            'content' => [
                'id' => null,
                'active' => false,
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
            ]
        ]
    ],

    'testUpdateExistingOffer' => [
        'request' => [
            'url' => '',
            'method' => 'PATCH',
            'content' => [
                'name' => 'Updated name',
                'ends_at' => 1550999999,
            ]
        ],
        'response' => [
            'content' => [
                'id' => null,
                'active' => true,
                'name' => 'Updated name',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1550999999,
            ]
        ]
    ],

    'testUpdateWalletOfferWithMaxPaymentCount' => [
        'request' => [
            'content' => [
                'max_payment_count' => 2,
            ],
            'url' => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'max_payment_count can only be set for card or emi offera',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testUpdateCardOfferWithNoMaxPaymentCount' => [
        'request' => [
            'content' => [
                'linked_offer_ids' => null,
            ],
            'url' => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'linked_offer_ids can only be set for offer with max_payment_count',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testUpdateCardOfferWithInvalidLinkedOfferIds' => [
        'request' => [
            'content' => [
                'linked_offer_ids' => null,
            ],
            'url' => null,
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked offer ids submitted are not valid',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testFetchOfferById' => [
        'request' => [
            'url' => '',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id' => null,
                'active' => true,
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
            ]
        ]
    ],

    'testFetchSubscriptionOfferById' => [
        'request' => [
            'url' => '',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'active' => true,
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',
                'percent_rate' => 1000,
                'max_payment_count' => 2,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'redemption_type' => 'cycle',
                'applicable_on' => 'both',
                'no_of_cycles' => 10,
            ]
        ]
    ],

    'testGetMultipleOffers' => [
        'request' => [
            'url' => '/offers',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'active' => true,
                        'name' => 'Test Offer',
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'VISA',
                        'issuer' => 'HDFC',
                        'percent_rate' => 1000,
                        'max_payment_count' => 2,
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
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
            'url' => '/payments/fix_attempted_orders',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 1,
                'failed' => 0,
                'failedPaymentIds' => [],
            ]
        ],
    ],

    'testCreateOfferValidateMerchant' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method not enabled for the merchant : card',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithCorporateOrRetailIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'netbanking',
                'issuer' => 'BARB_C',
                'min_amount' => 1000,
                'flat_cashback' => 800,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'netbanking',
                    'issuer' => 'BARB_C',
                    'min_amount' => 1000,
                    'flat_cashback' => 800,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ],
        ],
    ],

    'testCreateOfferValidateMaxCashback' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'wallet',
                'issuer' => 'airtelmoney',
                'max_cashback' => 200,
                'min_amount' => 500,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
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
                'name' => 'Test Offer',
                'payment_method' => 'emi',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'BARB_C',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'emi',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'BARB_C',

                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ],
        ],
    ],

    'testCreateOfferValidateMethodType' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'netbanking',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'BARB_C',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method type field may be sent only when payment method is card',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferMinAmount' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'netbanking',
                'issuer' => 'UTIB',
                'min_amount' => 500,
                'flat_cashback' => 800,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Flat cashback cannot be greater than minimum amount',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithInvalidIinLength' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'iins' => ['4111111'],
                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IIN : All IINs should have exactly 6 digits',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateCardOfferWithInvalidFullNetworkName' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'MasterCard',
                'issuer' => 'BARB_C',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' =>
                    [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Payment network for card should be a valid card network code',
                    ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOfferWithSameIIN' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'VISA',
                'issuer' => 'HDFC',

                'percent_rate' => 1000,
                'starts_at' => 1514764800,
                'ends_at' => 1546300800,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'iins' => ['411111'],
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network' => 'VISA',
                    'issuer' => 'HDFC',

                    'percent_rate' => 1000,
                    'starts_at' => 1514764800,
                    'ends_at' => 1546300800,
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testFindOffersInPaymentResponseWithExpandsForPrivateAuth' => [
        'request' => [
            'url' => '/payments/',
            'method' => 'get',
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
            'url' => '/payments/',
            'method' => 'get',
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
            'url' => '/offers',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'active' => true,
                        'name' => 'Test Offer',
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'VISA',
                        'issuer' => 'HDFC',
                        'percent_rate' => 1000,
                        'max_payment_count' => 2,
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ]
                ]
            ]
        ]
    ],

    'testCreateCardlessEmiOfferWithIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'cardless_emi',
                'issuer' => 'zestmoney',
                "min_amount" => 10000,
                "flat_cashback" => 1000,
                'starts_at' => 1641014736,
                'ends_at' => 1704091568,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'cardless_emi',
                    'issuer' => 'zestmoney',
                    'min_amount' => 10000,
                    'flat_cashback' => 1000,
                    'starts_at' => 1641014736,
                    'ends_at' => 1704091568,
                    'type' => 'instant',
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testCreateCardlessEmiOfferWithoutIssuer' => [
        'request' => [
            'content' => [
                'name' => 'Test Offer',
                'payment_method' => 'cardless_emi',
                "min_amount" => 200000,
                "flat_cashback" => 10000,
                'starts_at' => 1641014736,
                'ends_at' => 1704091568,
                'display_text' => 'Some more details',
                'terms' => 'Some more details',
                'block' => 1,
                'type' => 'instant'
            ],
            'url' => '/offers',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'active' => true,
                    'name' => 'Test Offer',
                    'payment_method' => 'cardless_emi',
                    'min_amount' => 200000,
                    'flat_cashback' => 10000,
                    'starts_at' => 1641014736,
                    'ends_at' => 1704091568,
                    'type' => 'instant',
                    'display_text' => 'Some more details',
                    'terms' => 'Some more details'
                ]
            ]
        ]
    ],

    'testAdminFetchOffer' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/offer?count=20&skip=0',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'admin'  => true,
                'items'  => [
                    [
                        'id'             => 'offer_10000000000000',
                        'name'           => 'Test Offer',
                        'display_text'   => 'Some more details',
                        'terms'          => 'Some more details',
                        'merchant_id'    => '10000000000000',
                        'starts_at'      => 1514764800,
                        'ends_at'        => 1546300800,
                        'active'         => true,
                        'block'          => false,
                        'default_offer'  => true,
                        'emi_subvention' => false,
                        'type'           => 'instant',
                        'flat_cashback'  => null,
                        'percent_rate'   => 1000,
                        'max_cashback'   => 0,
                        'payment_method' => 'card',
                        'iins'           => [
                            '411111'
                        ],
                        'emi_durations'  => [],
                        'error_message'  => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                        'entity'         => 'offer',
                        'admin'          => true,
                    ],
                    [
                        'id'             => 'offer_10000000000001',
                        'name'           => 'Test Offer',
                        'display_text'   => 'Some more details',
                        'terms'          => 'Some more details',
                        'merchant_id'    => '8K4v0EqHDl342o',
                        'starts_at'      => 1514764800,
                        'ends_at'        => 1546300800,
                        'active'         => true,
                        'block'          => true,
                        'default_offer'  => false,
                        'emi_subvention' => false,
                        'type'           => 'instant',
                        'flat_cashback'  => null,
                        'percent_rate'   => 1000,
                        'max_cashback'   => 0,
                        'payment_method' => 'card',
                        'iins'           => [
                            '411111'
                        ],
                        'emi_durations'  => [],
                        'error_message'  => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                        'entity'         => 'offer',
                        'admin'          => true,
                    ],
                ],
            ]
        ]
    ],

    'testAdminFetchOfferWithMerchantId' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/offer?count=20&skip=0&merchant_id=8K4v0EqHDl342o',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items'  => [
                    [
                        'id'             => 'offer_10000000000000',
                        'name'           => 'Test Offer',
                        'display_text'   => 'Some more details',
                        'terms'          => 'Some more details',
                        'merchant_id'    => '8K4v0EqHDl342o',
                        'starts_at'      => 1514764800,
                        'ends_at'        => 1546300800,
                        'active'         => true,
                        'block'          => true,
                        'default_offer'  => true,
                        'emi_subvention' => false,
                        'type'           => 'instant',
                        'flat_cashback'  => null,
                        'percent_rate'   => 1000,
                        'max_cashback'   => 0,
                        'payment_method' => 'card',
                        'iins'           => [
                            '411111'
                        ],
                        'emi_durations'  => [],
                        'error_message'  => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                        'entity'         => 'offer',
                        'admin'          => true,
                    ],
                ],
            ]
        ]
    ],

    'testAdminFetchOfferById' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/offer/offer_10000000000000',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'             => 'offer_10000000000000',
                'name'           => 'Test Offer',
                'display_text'   => 'Some more details',
                'terms'          => 'Some more details',
                'merchant_id'    => '10000000000000',
                'starts_at'      => 1514764800,
                'ends_at'        => 1546300800,
                'active'         => true,
                'block'          => true,
                'default_offer'  => true,
                'emi_subvention' => false,
                'type'           => 'instant',
                'flat_cashback'  => null,
                'percent_rate'   => 1000,
                'max_cashback'   => 0,
                'payment_method' => 'card',
                'iins'           => [
                    '411111'
                ],
                'emi_durations'  => [],
                'error_message'  => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                'entity'         => 'offer',
                'admin'          => true,
            ]
        ]
    ],

    'testAdminFetchOfferByIdFailure' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/offer/offer_10000000000000',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testAdminFetchOfferByInvalidIdValidationFailure' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/admin/offer/[{"id": "PbOeXfWCjyFOVQP3T9nIwXdjeESQ"}]',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The offer id may only contain letters and numbers.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testFetchOffersDiscountForSubscription' => [
        'request'  => [
            'content' => [
                "expired"         => "0",
                "active"          => "1",
                "amount"          => "11620",
            ],
            'url'     => '/offers/subscription/discounted_amount',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'offer_valid' => 1
            ]
        ]
    ],
    'testFetchOffersCreateInfoWithoutEmiPlans' => [
        'request'  => [
            'content' => [
                "merchant_id" => "10000000000000",
                "offer"       => [
                    "is_no_cost_emi"      => false,
                    "emi_durations"       => [
                        3,
                        2
                    ],
                    "issuer"              => "HDFC",
                    "payment_network"     => "",
                    "payment_method"      => "card",
                    "payment_method_type" => ""
                ],
            ],
            'url'     => '/offers/creation-info',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                "merchant_methods" => [
                    "merchant_id"                  => "10000000000000",
                    "card"                         => 1,
                    "netbanking"                   => true,
                    "amex"                         => false,
                    "disabled_banks"               => [
                    ],
                    "paytm"                        => false,
                    "mobikwik"                     => false,
                    "olamoney"                     => false,
                    "phonepe"                      => false,
                    "paypal"                       => false,
                    "phonepeswitch"                => false,
                    "payzapp"                      => false,
                    "payumoney"                    => false,
                    "openwallet"                   => false,
                    "razorpaywallet"               => false,
                    "airtelmoney"                  => false,
                    "amazonpay"                    => false,
                    "jiomoney"                     => false,
                    "sbibuddy"                     => false,
                    "mpesa"                        => false,
                    "emi"                          => [
                    ],
                    "freecharge"                   => false,
                    "credit_card"                  => true,
                    "debit_card"                   => true,
                    "card_subtype"                 => 1,
                    "prepaid_card"                 => true,
                    "upi"                          => false,
                    "upi_type"                     => [
                        "collect" => 0,
                        "intent"  => 0
                    ],
                    "bank_transfer"                => false,
                    "aeps"                         => false,
                    "emandate"                     => false,
                    "nach"                         => false,
                    "cardless_emi"                 => false,
                    "paylater"                     => false,
                    "card_networks"                => [
                        "AMEX"  => 0,
                        "DICL"  => 0,
                        "MC"    => 1,
                        "MAES"  => 1,
                        "VISA"  => 1,
                        "JCB"   => 0,
                        "RUPAY" => 1,
                        "BAJAJ" => 0,
                        "UNP"   => 0
                    ],
                    "apps"                         => [
                        "cred"    => 0,
                        "twid"    => 0,
                        "trustly" => 0,
                        "poli"    => 0,
                        "sofort"  => 0,
                        "giropay" => 0
                    ],
                    "debit_emi_providers"          => [
                        "HDFC" => 0,
                        "KKBK" => 0,
                        "INDB" => 0,
                        "ICIC" => 0
                    ],
                    "cod"                          => false,
                    "offline"                      => false,
                    "fpx"                          => false,
                    "itzcash"                      => false,
                    "oxigen"                       => false,
                    "amexeasyclick"                => false,
                    "paycash"                      => false,
                    "citibankrewards"              => false,
                    "in_app"                       => 0,
                    "in_app_credit_card"           => 0,
                    "cc_on_upi"                    => 0,
                    "wallet_on_upi"                => 0,
                    "creditline_on_upi"            => 0,
                    "credit_emi_providers"         => [
                        "HDFC"    => 0,
                        "SBIN"    => 0,
                        "UTIB"    => 0,
                        "ICIC"    => 0,
                        "AMEX"    => 0,
                        "BARB"    => 0,
                        "CITI"    => 0,
                        "HSBC"    => 0,
                        "INDB"    => 0,
                        "KKBK"    => 0,
                        "RATN"    => 0,
                        "SCBL"    => 0,
                        "YESB"    => 0,
                        "onecard" => 0,
                        "BAJAJ"   => 0,
                        "FDRL"    => 0,
                        "IDFB"    => 0
                    ],
                    "cardless_emi_providers"       => [
                        "walnut369"   => 0,
                        "zestmoney"   => 0,
                        "earlysalary" => 0,
                        "hdfc"        => 0,
                        "icic"        => 0,
                        "barb"        => 0,
                        "kkbk"        => 0,
                        "fdrl"        => 0,
                        "idfb"        => 0,
                        "hcin"        => 0,
                        "krbe"        => 0,
                        "cshe"        => 0,
                        "tvsc"        => 0,
                        "liquiloans"  => 0,
                        "instant_emi" => 0
                    ],
                    "offline_debit_emi_providers"  => [
                        "HDFC" => 0,
                        "ICIC" => 0,
                        "KKBK" => 0
                    ],
                    "offline_credit_emi_providers" => [
                        "AMEX"    => 0,
                        "AUBL"    => 0,
                        "BARB"    => 0,
                        "CITI"    => 0,
                        "CNRB"    => 0,
                        "FDRL"    => 0,
                        "HDFC"    => 0,
                        "HSBC"    => 0,
                        "ICIC"    => 0,
                        "IDFB"    => 0,
                        "INDB"    => 0,
                        "JAKA"    => 0,
                        "KKBK"    => 0,
                        "PUNB"    => 0,
                        "RATN"    => 0,
                        "SBIN"    => 0,
                        "SCBL"    => 0,
                        "UTIB"    => 0,
                        "YESB"    => 0,
                        "ONECARD" => 0
                    ],
                    "paylater_providers"           => [
                        "getsimpl"      => 0,
                        "lazypay"       => 0,
                        "icic"          => 0,
                        "hdfc"          => 0,
                        "amazonpay"     => 0,
                        "rzpx_postpaid" => 0,
                        "atome"         => 0
                    ],
                    "bajajpay"                     => false,
                    "intl_bank_transfer"           => [
                    ],
                    "boost"                        => false,
                    "mcash"                        => false,
                    "grabpay"                      => false,
                    "touchngo"                     => false,
                    "sodexo"                       => false,
                    "online_conversion_enabled"    => false,
                    "duitnow_pay"                  => false,
                    "razorpay_giftcard"            => 0
                ]
            ]
        ]
    ],
    'testFetchOffersCreateInfoWithoutMethodsAndWithoutEmiPlans' => [
        'request'  => [
            'content' => [
                "merchant_id" => "10000000000000",
                "offer"       => [
                    "is_no_cost_emi"      => false,
                    "emi_durations"       => [
                        3,
                        2
                    ],
                    "issuer"              => "HDFC",
                    "payment_network"     => "",
                    "payment_method"      => "",
                    "payment_method_type" => ""
                ],
            ],
            'url'     => '/offers/creation-info',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testFetchOffersCreateInfoWithoutMethods' => [
        'request'  => [
            'content' => [
                "merchant_id" => "10000000000000",
                "offer"       => [
                    "is_no_cost_emi"      => true,
                    "emi_durations"       => [
                       6
                    ],
                    "issuer"              => "HDFC",
                    "payment_network"     => "",
                    "payment_method"      => "",
                    "payment_method_type" => ""
                ],
            ],
            'url'     => '/offers/creation-info',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                "tenure_discount_map" => [
                    "6" => 518,
                ]
            ]
        ]
    ],
    'testFetchOffersCreateInfoSuccess' => [
        'request'  => [
            'content' => [
                "merchant_id" => "10000000000000",
                "offer"       => [
                    "is_no_cost_emi"      => true,
                    "emi_durations"       => [
                        6
                    ],
                    "issuer"              => "HDFC",
                    "payment_network"     => "",
                    "payment_method"      => "card",
                    "payment_method_type" => ""
                ],
            ],
            'url'     => '/offers/creation-info',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                "merchant_methods" => [
                    "merchant_id"                  => "10000000000000",
                    "card"                         => 1,
                    "netbanking"                   => true,
                    "amex"                         => false,
                    "disabled_banks"               => [
                    ],
                    "paytm"                        => false,
                    "mobikwik"                     => false,
                    "olamoney"                     => false,
                    "phonepe"                      => false,
                    "paypal"                       => false,
                    "phonepeswitch"                => false,
                    "payzapp"                      => false,
                    "payumoney"                    => false,
                    "openwallet"                   => false,
                    "razorpaywallet"               => false,
                    "airtelmoney"                  => false,
                    "amazonpay"                    => false,
                    "jiomoney"                     => false,
                    "sbibuddy"                     => false,
                    "mpesa"                        => false,
                    "emi"                          => [
                    ],
                    "freecharge"                   => false,
                    "credit_card"                  => true,
                    "debit_card"                   => true,
                    "card_subtype"                 => 1,
                    "prepaid_card"                 => true,
                    "upi"                          => false,
                    "upi_type"                     => [
                        "collect" => 0,
                        "intent"  => 0
                    ],
                    "bank_transfer"                => false,
                    "aeps"                         => false,
                    "emandate"                     => false,
                    "nach"                         => false,
                    "cardless_emi"                 => false,
                    "paylater"                     => false,
                    "card_networks"                => [
                        "AMEX"  => 0,
                        "DICL"  => 0,
                        "MC"    => 1,
                        "MAES"  => 1,
                        "VISA"  => 1,
                        "JCB"   => 0,
                        "RUPAY" => 1,
                        "BAJAJ" => 0,
                        "UNP"   => 0
                    ],
                    "apps"                         => [
                        "cred"    => 0,
                        "twid"    => 0,
                        "trustly" => 0,
                        "poli"    => 0,
                        "sofort"  => 0,
                        "giropay" => 0
                    ],
                    "debit_emi_providers"          => [
                        "HDFC" => 0,
                        "KKBK" => 0,
                        "INDB" => 0,
                        "ICIC" => 0
                    ],
                    "cod"                          => false,
                    "offline"                      => false,
                    "fpx"                          => false,
                    "itzcash"                      => false,
                    "oxigen"                       => false,
                    "amexeasyclick"                => false,
                    "paycash"                      => false,
                    "citibankrewards"              => false,
                    "in_app"                       => 0,
                    "in_app_credit_card"           => 0,
                    "cc_on_upi"                    => 0,
                    "wallet_on_upi"                => 0,
                    "creditline_on_upi"            => 0,
                    "credit_emi_providers"         => [
                        "HDFC"    => 0,
                        "SBIN"    => 0,
                        "UTIB"    => 0,
                        "ICIC"    => 0,
                        "AMEX"    => 0,
                        "BARB"    => 0,
                        "CITI"    => 0,
                        "HSBC"    => 0,
                        "INDB"    => 0,
                        "KKBK"    => 0,
                        "RATN"    => 0,
                        "SCBL"    => 0,
                        "YESB"    => 0,
                        "onecard" => 0,
                        "BAJAJ"   => 0,
                        "FDRL"    => 0,
                        "IDFB"    => 0
                    ],
                    "cardless_emi_providers"       => [
                        "walnut369"   => 0,
                        "zestmoney"   => 0,
                        "earlysalary" => 0,
                        "hdfc"        => 0,
                        "icic"        => 0,
                        "barb"        => 0,
                        "kkbk"        => 0,
                        "fdrl"        => 0,
                        "idfb"        => 0,
                        "hcin"        => 0,
                        "krbe"        => 0,
                        "cshe"        => 0,
                        "tvsc"        => 0,
                        "liquiloans"  => 0,
                        "instant_emi" => 0
                    ],
                    "offline_debit_emi_providers"  => [
                        "HDFC" => 0,
                        "ICIC" => 0,
                        "KKBK" => 0
                    ],
                    "offline_credit_emi_providers" => [
                        "AMEX"    => 0,
                        "AUBL"    => 0,
                        "BARB"    => 0,
                        "CITI"    => 0,
                        "CNRB"    => 0,
                        "FDRL"    => 0,
                        "HDFC"    => 0,
                        "HSBC"    => 0,
                        "ICIC"    => 0,
                        "IDFB"    => 0,
                        "INDB"    => 0,
                        "JAKA"    => 0,
                        "KKBK"    => 0,
                        "PUNB"    => 0,
                        "RATN"    => 0,
                        "SBIN"    => 0,
                        "SCBL"    => 0,
                        "UTIB"    => 0,
                        "YESB"    => 0,
                        "ONECARD" => 0
                    ],
                    "paylater_providers"           => [
                        "getsimpl"      => 0,
                        "lazypay"       => 0,
                        "icic"          => 0,
                        "hdfc"          => 0,
                        "amazonpay"     => 0,
                        "rzpx_postpaid" => 0,
                        "atome"         => 0
                    ],
                    "bajajpay"                     => false,
                    "intl_bank_transfer"           => [
                    ],
                    "boost"                        => false,
                    "mcash"                        => false,
                    "grabpay"                      => false,
                    "touchngo"                     => false,
                    "sodexo"                       => false,
                    "online_conversion_enabled"    => false,
                    "duitnow_pay"                  => false,
                    "razorpay_giftcard"            => 0
                ],
                "tenure_discount_map" => [
                    "6" => 518,
                ]
            ]
        ]
    ],
    'testFetchOffersCreateInfoMerchantNotFound' => [
        'request'  => [
            'content' => [
                "merchant_id" => "10000000000020",
                "offer"       => [
                    "is_no_cost_emi"      => true,
                    "emi_durations"       => [
                        6
                    ],
                    "issuer"              => "HDFC",
                    "payment_network"     => "",
                    "payment_method"      => "card",
                    "payment_method_type" => ""
                ],
            ],
            'url'     => '/offers/creation-info',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
    'testFetchOffersCreateInfoMerchantMethodsNotFound' => [
    'request'  => [
        'content' => [
            "merchant_id" => "10000000000013",
            "offer"       => [
                "is_no_cost_emi"      => false,
                "emi_durations"       => [
                    6
                ],
                "issuer"              => "HDFC",
                "payment_network"     => "",
                "payment_method"      => "card",
                "payment_method_type" => ""
            ],
        ],
        'url'     => '/offers/creation-info',
        'method'  => 'POST'
    ],
    'response' => [
        'content' => [
            "merchant_methods" => null,
        ]
    ]
],

    'testFetchOffersWithGlobalLimitsFromOE' => [
        'request'  => [
            'url'    => '/offers/offer_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'                  => 'offer_10000000000000',
                'active'              => true,
                'name'                => 'Test Offer',
                'starts_at'           => 1514764800,
                'ends_at'             => 1546300800,
                'current_offer_usage' => 200
            ]
        ]
    ],
];


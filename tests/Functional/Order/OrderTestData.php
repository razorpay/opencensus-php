<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testCreateOrderWithNegativeAmount' => [
        'request' => [
            'content' => [
                'amount'          => -200,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
         'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateAutoCaptureOrder' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'status'        => 'created'
            ],
        ],
    ],
    'testCreateTPVOrder' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '040304030403040',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],
    'testCreateTPVOrderWithInvalidAccountNumber' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '0040304030403040',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_INCORRECT_LENGTH
        ],
    ],
    'testGetOrder' => [
        'amount'        => 50000,
        'currency'      => 'INR',
        'receipt'       => 'rcptid42',
    ],

    'testGetMultipleOrders' => [
        'request' => [
            'url' => '/orders',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testStatusAfterPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID
        ],
    ],

    'testPaymentForTPVMerchantWithoutOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED
        ],
    ],

    'testPaymentWithIncorrectBankForTPVMerchantWithOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Order bank does not match the payment bank',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testOrderAndPaymentAmountMismatch' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH
        ],
    ],

    'testPreferencesForTPVMerchants' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'netbanking' => [
                        'ALLA' => 'Allahabad Bank',
                        'ANDB' => 'Andhra Bank',
                        'UTIB' => 'Axis Bank',
                        'BKID' => 'Bank of India',
                        'CIUB' => 'City Union Bank',
                        'CORP' => 'Corporation Bank',
                        'HDFC' => 'HDFC Bank',
                        'ICIC' => 'ICICI Bank',
                        'IBKL' => 'IDBI',
                        'INDB' => 'Indusind Bank',
                        'KVBL' => 'Karur Vysya Bank',
                        'KKBK' => 'Kotak Mahindra Bank',
                        'SBHY' => 'State Bank of Hyderabad',
                        'SBIN' => 'State Bank of India',
                        'SBMY' => 'State Bank of Mysore',
                        'STBP' => 'State Bank of Patiala',
                        'SBTR' => 'State Bank of Travancore',
                        'SBBJ' => 'State Bank of Bikaner and Jaipur',
                        'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    ],
                ],
                'order' => [
                    'bank'           => 'UTIB',
                    'account_number' => 'XXXXXXXXXXXXX40',
                ],
            ],
        ],
    ],

    'testCreateOrderWithOffer' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
        ],
    ],

    'testCreateOrderWithNotApplicableOffer' => [
        'request' => [
            'content' => [
                'amount'        => 900,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_INVALID_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER
        ]
    ],

    'testCreateOrderWithExpiredOffer' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_INVALID_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER
        ]
    ],

    'testPaymentWithFailedOfferCheck' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithFailedOfferCheckOnNullMethodOffer' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Custom error message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithFailedOfferWithCustomErrorMessage' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Custom error message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithMaxPaymentCountOfferAppliedOnOrderWithNoCardSaving' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountAppliedOnOrderWithGlobalSavedCard' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountAppliedOnOrderWithLocallySavedCard' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountOfferButPaymentsAlreadyMadeOnLinkedOffers' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ]
];

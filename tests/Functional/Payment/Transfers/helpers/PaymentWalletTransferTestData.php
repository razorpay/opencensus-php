<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testCaptureAndTransferToInvalidCustomerId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_asd',
                        'amount'   => 200,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testCreateWalletWithNonIndianContact' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_3030300000cust',
                        'amount'   => 200
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED
        ],
    ],

    'testCustomerTransferB2bNotEnabled' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_10000000000001',
                        'amount' => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCaptureAndTransferToUnknownCustomerId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_3030300000cust',
                        'amount'   => 200,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testTransferToExistingCustomerWithNoExistingWallet' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'transfer'
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testTransferAndVerifyCustomerBalance' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null,
                        'currency' => 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'transfer'
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testTransferAndVerifyPricing' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null,
                        'currency' => 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'transfer'
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testTransferCustomerUsageFirstTxn' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => []
        ],
    ],

    'testTransferToWalletFundsOnHold' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_10000000000000',
                        'amount'   => 200,
                        'currency' => 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ]
];

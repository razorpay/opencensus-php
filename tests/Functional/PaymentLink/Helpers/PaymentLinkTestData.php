<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\User;

return [
    'testCreatePaymentLinkWithSinglePaymentPageItem' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'user_id'       => User::MERCHANT_USER_ID,
                'receipt'       => '00000000000001',
                'amount'        => NULL,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => 100000,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => 'dummy',
                        'stock' => 10000,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => 2,
                        'max_purchase' => 10000,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ]
                ],
            ],
        ],
    ],

    'testCreatePaymentLinkWithMinPurchaseGreaterThanMaxPurchase' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => NULL,
                        'min_purchase'      => 100,
                        'max_purchase'      => 50,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'min purchase should not be greater than max purchase',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentLinkWithMinAmountGreaterThanMaxAmount' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => NULL,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => NULL,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => 10000,
                        'max_amount'        => 5000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'min amount should not be greater than max amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentLinkByPassingAmountWhenAmountPassed' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 1000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => NULL,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => 10000,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount not required when min amount or max amount is present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentButtonWithMultipleItems' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'view_type'     => 'button',
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings'=>[
                    'payment_button_text' => 'Please pay',
                    'payment_button_theme'=> 'rzp-dark-standard',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => NULL,
                        'stock'             => NULL,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ],
                    [
                        'item' => [
                            'name'        =>  'donate',
                            'description' => NULL,
                            'amount'      => 500000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => FALSE,
                        'image_url'         => NULL,
                        'stock'             => 10000,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'user_id'       => User::MERCHANT_USER_ID,
                'receipt'       => '00000000000001',
                'amount'        => NULL,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => 100000,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => NULL,
                        'stock' => NULL,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ],
                    [
                        'item' => [
                            'name' =>  'donate',
                            'description' => NULL,
                            'amount' => 500000,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => FALSE,
                        'image_url' => NULL,
                        'stock' => 10000,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ]
                ],
            ],
        ],
    ],

    'testCreatePaymentLinkWithMultiplePaymentPageItem' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => NULL,
                        'stock'             => NULL,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ],
                    [
                        'item' => [
                            'name'        =>  'donate',
                            'description' => NULL,
                            'amount'      => 500000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => FALSE,
                        'image_url'         => NULL,
                        'stock'             => 10000,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'user_id'       => User::MERCHANT_USER_ID,
                'receipt'       => '00000000000001',
                'amount'        => NULL,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => 100000,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => NULL,
                        'stock' => NULL,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ],
                    [
                        'item' => [
                            'name' =>  'donate',
                            'description' => NULL,
                            'amount' => 500000,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => FALSE,
                        'image_url' => NULL,
                        'stock' => 10000,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ]
                ],
            ],
        ],
    ],

    'testCreatePaymentLinkWithDifferentCurrency' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => NULL,
                        'stock'             => NULL,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ],
                    [
                        'item' => [
                            'name'        =>  'donate',
                            'description' => NULL,
                            'amount'      => 500000,
                            'currency'    => 'USD',
                        ],
                        'mandatory'         => FALSE,
                        'image_url'         => NULL,
                        'stock'             => 10000,
                        'min_purchase'      => NULL,
                        'max_purchase'      => NULL,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'payment page currency and payment page item currency should be same',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentLinkWithoutItem' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => []
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment page items field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentLinkWithMoreThanLimitedItem' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'amount'      => 100000,
                        ],
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The total number of payment page items may not be greater than 25',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentLinkWithoutAmountOrCurrency' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => NULL,
                            'currency' => 'INR',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => NULL,
                        'stock' => NULL,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => 100,
                        'max_amount' => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'user_id'       => User::MERCHANT_USER_ID,
                'receipt'       => '00000000000001',
                'amount'        => null,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => NULL,
                            'currency' => 'INR',
                            'type' => 'payment_page',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => NULL,
                        'stock' => NULL,
                        'quantity_sold' => 0,
                        'total_amount_paid' => 0,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => 100,
                        'max_amount' => NULL,
                    ]
                ],
            ],
        ],
    ],

    'testCreatePaymentLinkWithBadExpireBy' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'currency'      => 'INR',
                'expire_by'     => 1400000000,
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' =>  'amount',
                            'description' => NULL,
                            'amount' => NULL,
                            'currency' => 'INR',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => NULL,
                        'stock' => NULL,
                        'min_purchase' => NULL,
                        'max_purchase' => NULL,
                        'min_amount' => 100,
                        'max_amount' => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchPaymentLink' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'          => 'pl_100000000000pl',
                'user_id'     => User::MERCHANT_USER_ID,
                'receipt'     => '00000000000001',
                'amount'      => NULL,
                'currency'    => 'INR',
                'title'       => 'Sample title',
                'description' => '{"value":[{"insert":"Sample description"}],"metaText":"Sample description"}',
                'notes'       => [],
            ],
        ],
    ],

    'testFetchPaymentLinks' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'user_id'     => User::MERCHANT_USER_ID,
                        'receipt'     => '00000000000001',
                        'amount'      => NULL,
                        'currency'    => 'INR',
                        'title'       => 'Sample title',
                        'description' => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                        'notes'       => [],
                    ],
                ],
            ],
        ],
    ],

    'testFetchPaymentButtons' => [
        'request'  => [
            'url'     => '/payment_pages?view_type=button',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'user_id'     => User::MERCHANT_USER_ID,
                        'receipt'     => '00000000000001',
                        'amount'      => NULL,
                        'currency'    => 'INR',
                        'title'       => 'Sample title',
                        'description' => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                        'notes'       => [],
                    ],
                ],
            ],
        ],
    ],

    'testFetchButtonNotInPagesList' => [
        'request'  => [
            'url'     => '/payment_pages?view_type=button',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 0,
            ],
        ],
    ],

    'testFetchPageNotInButtonList' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 0,
            ],
        ],
    ],

    'testUpdatePaymentLinkWithBadExpireBy' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000002',
                'expire_by'     => 1400000000,
                'title'         => 'Sample test title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample test notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentLinkDeletingItem' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID_2
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'          => 'pl_100000000000pl',
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID_2
                    ],
                ],
            ],
        ],
    ],

    'testUpdatePaymentLinkAddingItem' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID_2
                    ],
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID
                    ],
                    [
                        'item' => [
                            'name' =>  'unique_name',
                            'description' => 'unique_name',
                            'amount' => 1232145,
                            'currency' => 'INR',
                        ],
                        'mandatory' => FALSE,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'          => 'pl_100000000000pl',
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID
                    ],
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID_2
                    ],
                    [
                        'item' => [
                            'name' =>  'unique_name',
                            'description' => 'unique_name',
                            'amount' => 1232145,
                            'currency' => 'INR',
                        ],
                        'mandatory' => FALSE,
                    ],
                ],
            ],
        ],
    ],

    'testUpdatePaymentLinkRemoveAllItem' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'payment_page_items'         => [
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'payment_page_items must be an array',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkSendNotification' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/notify',
            'method'  => 'post',
            'content' => [
                'emails'   => ['test@rzp.com'],
                'contacts' => ['9090908080']
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testInactivePaymentLinkSendNotification' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/notify',
            'method'  => 'post',
            'content' => [
                'emails'   => ['test@rzp.com'],
                'contacts' => ['9090908080']
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link is not active.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testExpirePaymentLinks' => [
        'request'  => [
            'url'     => '/payment_pages/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testPaymentLinkMakePayment' => [
        // Used to assert payment link's attributes after payment in test
        'payment_link' => [
            'times_paid'        => 1,
            'total_amount_paid' => 10100,
            'status'            => 'active',
            'status_reason'     => null,
        ],
    ],

    'testPaymentLinkMakePaymentCustomerFeeBearer' => [
        // Used to assert payment link's attributes after payment in test
        'payment_link' => [
            'total_amount_paid' => 15000,
            'status'            => 'active',
            'status_reason'     => null,
        ],
    ],

    'testDeactivatePaymentLink' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/deactivate',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'status'        => 'inactive',
                'status_reason' => 'deactivated',
            ],
        ],
    ],

    'testDeactivateAlreadyDeactivatedPaymentLink' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/deactivate',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link cannot be deactivated as it is already inactive',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testActivatePaymentLink' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/activate',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'status'        => 'active',
                'status_reason' => null,
            ],
        ],
    ],

    'testActivateLinkAlreadyActivated' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/activate',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link cannot be activated as it is already active',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testActivateWithTimesPayableLessThanTimesPaid' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl/activate',
            'method'  => 'patch',
            'content' => [
                'times_payable' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Times payable should be greater than or equal to the number of payments already made',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMinExpiryTimeForActivation' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl/activate',
            'method'  => 'patch',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditPaymentLinkToCompleteAndExcessPaymentRefunded' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'times_payable' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'times_payable' => 1,
                'status'        => 'inactive',
                'status_reason' => 'completed',
                'times_paid'    => 1
            ],
        ],
    ],

    'testGetSlugExistsApi' => [
        'request' => [
            'url'    => '/payment_pages/sampleslug/exists',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'exists' => false,
            ],
        ],
    ],

    'testCreateOrderForPaymentLink' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 10000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'amount' => 10000,
                ],
                'line_items' => [
                    [
                        'item_id'  => 'item_10000000000ppi',
                        'ref_id'   => 'ppi_10000000000ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 10000,
                        'currency' => 'INR',
                    ]
                ]
            ],
        ],
    ],

    'testCreateOrderForPaymentLinkAndVerifyProductTypePage' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 10000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'amount' => 10000,
                ],
                'line_items' => [
                    [
                        'item_id'  => 'item_10000000000ppi',
                        'ref_id'   => 'ppi_10000000000ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 10000,
                        'currency' => 'INR',
                    ]
                ]
            ],
        ],
    ],

    'testCreateOrderForPaymentLinkAndVerifyProductTypeButton' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 10000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'amount' => 10000,
                ],
                'line_items' => [
                    [
                        'item_id'  => 'item_10000000000ppi',
                        'ref_id'   => 'ppi_10000000000ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 10000,
                        'currency' => 'INR',
                    ]
                ]
            ],
        ],
    ],

    'testCreateOrderForPaymentLinkWithAmountLessThanMinAmount' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount should not be lesser than to payment page item min amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithAmountGreaterThanMaxAmount' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount should not be greater than to payment page item max amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithFixedAmount' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1001,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount should be equal to payment page item amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithPurchaseGreaterThanMaxPurchase' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1000,
                        'quantity'             => 5,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'quantity should not be greater than to payment page item max purchase',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithPurchaseLesserThanMinPurchase' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1000,
                        'quantity'             => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'quantity should not be lesser than to payment page item min purchase',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithoutRequiredItem' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID_2,
                        'amount'               => 10000,
                        'quantity'             => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'ppi_10000000000ppi is mandatory payment page item, should be ordered',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithDuplicateItem' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID,
                        'amount'               => 1000,
                        'quantity'             => 3,
                    ],
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID,
                        'amount'               => 1000,
                        'quantity'             => 3,
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'all payment page item id should be unique',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithRequiredItem' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID,
                        'amount'               => 5000,
                        'quantity'             => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testCreateOrderForPaymentLinkWhenQuantitySoldOut' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID,
                        'amount'               => 5000,
                        'quantity'             => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'no stock left',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkWhenPageIsInactive' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID,
                        'amount'               => 5000,
                        'quantity'             => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'order cannot be created for payment page which is not active',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkMakePaymentWithoutOrder' => [
        'request' => [
            'url'    => '/payments',
            'method' => 'post',
            'content' => [
                'payment_link_id' => 'pl_' . PaymentLinkTest::TEST_PL_ID,
                'amount'          => 5000,
                'currency'          => 'INR',
                'email'             => 'a@b.com',
                'contact'           => '9918899029',
                'description'       => 'random description',
                'bank'              => 'IDIB',
                'card'              => [
                    'number'            => '4012001038443335',
                    'name'              => 'Harshil',
                    'expiry_month'      => '12',
                    'expiry_year'       => '2024',
                    'cvv'               => '566',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'order_id is required to create payment for payment page',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkMakePaymentWithDifferentOrder' => [
        'request' => [
            'url'    => '/payments',
            'method' => 'post',
            'content' => [
                'payment_link_id' => 'pl_' . PaymentLinkTest::TEST_PL_ID,
                'amount'          => 10000,
                'currency'          => 'INR',
                'email'             => 'a@b.com',
                'contact'           => '9918899029',
                'description'       => 'random description',
                'bank'              => 'IDIB',
                'card'              => [
                    'number'            => '4012001038443335',
                    'name'              => 'Harshil',
                    'expiry_month'      => '12',
                    'expiry_year'       => '2024',
                    'cvv'               => '566',
                ],
                'order_id' => 'order_' . PaymentLinkTest::TEST_ORDER_ID,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'order does not belongs to the given payment page',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkMakePaymentWhenPageIsInactive' => [
        'request' => [
            'url'    => '/payments',
            'method' => 'post',
            'content' => [
                'payment_link_id' => 'pl_' . PaymentLinkTest::TEST_PL_ID,
                'amount'          => 15000,
                'currency'          => 'INR',
                'email'             => 'a@b.com',
                'contact'           => '9918899029',
                'description'       => 'random description',
                'bank'              => 'IDIB',
                'card'              => [
                    'number'            => '4012001038443335',
                    'name'              => 'Harshil',
                    'expiry_month'      => '12',
                    'expiry_year'       => '2024',
                    'cvv'               => '566',
                ],
                'order_id' => 'order_' . PaymentLinkTest::TEST_ORDER_ID,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment cannot be made on this payment link',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE,
        ],
    ],

    'testCreateOrderForPaymentLinkWithMultipleItem' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 10000,
                    ],
                    [
                        'payment_page_item_id' => 'ppi_10000000001ppi',
                        'amount'               => 10000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'amount' => 20000,
                ],
                'line_items' => [
                    [
                        'item_id'  => 'item_10000000000ppi',
                        'ref_id'   => 'ppi_10000000000ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 10000,
                        'currency' => 'INR',
                    ],
                    [
                        'item_id'  => 'item_10000000001ppi',
                        'ref_id'   => 'ppi_10000000001ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 10000,
                        'currency' => 'INR',
                    ]
                ]
            ],
        ],
    ],

    'testSetMerchantDetails' => [
        'request' => [
            'url'       => '/payment_pages/merchant_details',
            'method'    => 'post',
            'content'   => [
                'text_80g_12a'  => 'text',
                'image_url_80g' => 'https://url',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'text_80g_12a'  => 'text',
                'image_url_80g' => 'https://url',
            ]
        ]
    ],

    'testFetchMerchantDetails' => [
        'request' => [
            'url'       => '/payment_pages/merchant_details/10000000000000',
            'method'    => 'get',
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => [
                'text_80g_12a'  => 'text',
                'image_url_80g' => 'https://url',
            ]
        ]
    ],

    'testSetReceiptDetails' => [
        'request'   => [
            'url'       => '/payment_pages/pl_100000000000pl/receipt',
            'method'    => 'post',
            'content'   => [
                'enable_receipt' => true,
                'selected_udf_field' => 'email',
                'enable_custom_serial_number' => true,
            ]
        ],
        'response'  => [
            'status_code'   => 200,
            'content'       => [
                'enable_receipt'    => '1',
                'selected_udf_field' => 'email',
                'enable_custom_serial_number' => '1',
            ]
        ]
    ],

    'testSetReceiptDetailsEmpty' => [
        'request'   => [
            'url'       => '/payment_pages/pl_100000000000pl/receipt',
            'method'    => 'post',
            'content'   => []
        ],
        'response'  => [
            'status_code'   => 200,
            'content'       => [
                'enable_receipt'    => '1',
                'selected_udf_field' => 'email',
                'enable_custom_serial_number' => '1',
            ]
        ]
    ],

    'testCreateOrderLineItemsEmptyArray' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Please select an amount to pay.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderLineItemsNotArray' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => 2
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'line items must be array',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderForPaymentLinkAndFetchProductType' => [
        'request' => [
            'url'    => '/v1/orders/',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'order',
                'amount' => 10000,
                'product_type' => 'payment_page',
                'payment_page' => [
                    'id'  => 'pl_100000000000pl',
                ]
            ]
        ],
    ],

    'testFetchButtonPreferencesForSuspendedMerchant' => [
        'request' => [
            'url'    => '/payment_buttons/pl_100000000000pl/button_preferences',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This account is suspended',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchButtonDetailsForSuspendedMerchant' => [
        'request' => [
            'url'    => '/payment_buttons/pl_100000000000pl/button_details',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This account is suspended',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentPageDetails' => [
        'request' => [
            'url'     => '/v1/payment_pages/pl_100000000000pl/details',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'                      => 'pl_100000000000pl',
                'captured_payments_count' => 1,
                'status'                  => 'active',
            ]
        ],
    ],

    'testCreatePaymentLinkWithAlphabetSupportNumber' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "support_email" => "abc@razorpay.com",
                "support_contact"   => "adasdadasdsad",
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT,
        ],
    ],
    'testCreatePaymentLinkWithSupportNumberLessDigits' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "support_email" => "abc@razorpay.com",
                "support_contact"   => "123432",
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
        ],
    ],
    'testCreatePaymentLinkWithSupportNumberLargeDigits' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "support_email" => "abc@razorpay.com",
                "support_contact"   => "123123123123123123",
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
        ],
    ],

    'testPaymentPagePaidWebhookEventData' =>  [
        "entity" => "event",
        "account_id" => "acc_10000000000000",
        "event" => "zapier.payment_page.paid.v1",
        "contains" => [
            "payment",
            "payment_page",
            "order"
        ],
        "payload" => [
            "payment" => [
                "entity" => "payment",
                "amount" => 15000,
                "currency" => "INR",
                "status" => "captured",
                "invoice_id" => null,
                "international" => false,
                "method" => "card",
                "amount_refunded" => 0,
                "refund_status" => null,
                "captured" => true,
                "description" => "random description",
                "card" => [
                    "entity" => "card",
                    "name" => "Harshil",
                    "last4" => "3335",
                    "network" => "Visa",
                    "type" => "credit",
                    "issuer" => "HDFC",
                    "international" => false,
                    "emi" => true,
                    "sub_type" => "consumer"
                ],
                "bank" => null,
                "wallet" => null,
                "vpa" => null,
                "email" => "a@b.com",
                "contact" => "+919918899029",
                "notes" => [],
                "fee" => 300,
                "tax" => 0,
                "error_code" => null,
                "error_description" => null,
                "error_source" => null,
                "error_step" => null,
                "error_reason" => null,
                "acquirer_data" => [
                    "auth_code" => null
                ],
            ],
            "payment_page" => [
                "id" => "pl_100000000000pl",
                "amount" => null,
                "currency" => "INR",
                "currency_symbol" => "₹",
                "expire_by" => null,
                "times_payable" => null,
                "times_paid" => 0,
                "total_amount_paid" => 15000,
                "status" => "active",
                "status_reason" => null,
                "short_url" => null,
                "user_id" => "MerchantUser01",
                "title" => "Sample title",
                "notes" => [],
                "support_contact" => null,
                "support_email" => null,
                "terms" => null,
                "type" => "payment",
                "payment_page_items" => [
                    [
                        "id" => "ppi_10000000000ppi",
                        "entity" => "payment_page_item",
                        "payment_link_id" => "pl_100000000000pl",
                        "item" => [
                            "id" => "item_10000000000ppi",
                            "active" => true,
                            "name" => "amount",
                            "description" => "Some item description",
                            "amount" => 5000,
                            "unit_amount" => 5000,
                            "currency" => "INR",
                            "type" => "payment_page",
                            "unit" => null,
                            "tax_inclusive" => false,
                            "hsn_code" => null,
                            "sac_code" => null,
                            "tax_rate" => null,
                            "tax_id" => null,
                            "tax_group_id" => null,
                        ],
                        "mandatory" => true,
                        "image_url" => null,
                        "stock" => null,
                        "quantity_sold" => 0,
                        "total_amount_paid" => 0,
                        "min_purchase" => null,
                        "max_purchase" => null,
                        "min_amount" => null,
                        "max_amount" => null,
                        "plan_id" => null,
                        "product_config" => null
                    ],
                    [
                        "id" => "ppi_10000000001ppi",
                        "entity" => "payment_page_item",
                        "payment_link_id" => "pl_100000000000pl",
                        "item" => [
                            "active" => true,
                            "name" => "amount",
                            "description" => "Some item description",
                            "amount" => 10000,
                            "unit_amount" => 10000,
                            "currency" => "INR",
                            "type" => "payment_page",
                            "unit" => null,
                            "tax_inclusive" => false,
                            "hsn_code" => null,
                            "sac_code" => null,
                            "tax_rate" => null,
                            "tax_id" => null,
                            "tax_group_id" => null,
                        ],
                        "mandatory" => true,
                        "image_url" => null,
                        "stock" => null,
                        "quantity_sold" => 0,
                        "total_amount_paid" => 0,
                        "min_purchase" => null,
                        "max_purchase" => null,
                        "min_amount" => null,
                        "max_amount" => null,
                        "plan_id" => null,
                        "product_config" => null
                    ]
                ],
            ],
            "order" => [
                "entity" => "order",
                "amount" => 15000,
                "amount_paid" => 15000,
                "amount_due" => 0,
                "currency" => "INR",
                "offer_id" => null,
                "offers" => [
                    "entity" => "collection",
                    "count" => 0,
                    "items" => []
                ],
                "status" => "paid",
                "attempts" => 1,
                "notes" => [],
                "items" => [
                    [
                        "ref_type" => "payment_page_item",
                        "name" => "Some item name",
                        "description" => "Some item description",
                        "amount" => 5000,
                        "unit_amount" => 5000,
                        "gross_amount" => 100000,
                        "tax_amount" => 0,
                        "taxable_amount" => 100000,
                        "net_amount" => 100000,
                        "currency" => "INR",
                        "type" => "invoice",
                        "tax_inclusive" => false,
                        "hsn_code" => null,
                        "sac_code" => null,
                        "tax_rate" => null,
                        "unit" => null,
                        "quantity" => 1
                    ],
                    [
                        "ref_type" => "payment_page_item",
                        "name" => "Some item name",
                        "description" => "Some item description",
                        "amount" => 10000,
                        "unit_amount" => 10000,
                        "gross_amount" => 100000,
                        "tax_amount" => 0,
                        "taxable_amount" => 100000,
                        "net_amount" => 100000,
                        "currency" => "INR",
                        "type" => "invoice",
                        "tax_inclusive" => false,
                        "hsn_code" => null,
                        "sac_code" => null,
                        "tax_rate" => null,
                        "unit" => null,
                        "quantity" => 1
                    ]
                ]
            ]
        ]
    ],

    'testFetchPaymentsForPaymentPage' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/payments',
            'method'  => 'get',
            'content' => [],
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => [
                'id'  => 'pl_100000000000pl',
                'payment_page_items' => [
                    [
                        'id' => 'ppi_10000000000ppi',
                    ]
                ],
                'payments' => [
                    [
                        'amount' => 15000,
                        'status' => 'captured',
                        'order' => [
                            'amount' => 15000,
                            'status' => 'paid',
                            'items' => [
                                [
                                    'amount' => 5000
                                ],
                                [
                                    'amount' => 10000
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ],

    'testSendPaymentPageReceipt' => [
        'request'  => [
            'url'     => 'to_be_replaced_from_function',
            'method'  => 'post',
            'content' => [
                'receipt' => 'thisisareceipt'
            ],
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => [
                'success' => true,
            ]
        ]
    ],

    'testUploadPaymentPageImages' => [
        'request'  => [
            'url'     => '/payment_pages/images',
            'method'  => 'post',
            'content' => [
                'images' => []
            ],
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => []
        ]
    ],

    'testPaymentPageItemUpdate' => [
        'request'  => [
            'url'     => '/payment_pages/payment_page_item/ppi_10000000000ppi',
            'method'  => 'patch',
            'content' => [
                'item' => [
                    'amount' => '7500'
                ],
                'stock' => 2,
            ],
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => []
        ]
    ],
    'testCreatePaymentPageOrderWithFloatAmountShouldThrowValidationError' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 100.99,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => trans("validation.mysql_unsigned_int", ['attribute' => 'amount']),
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreatePaymentPageOrderWithOutOfScopeIntegerAmountShouldThrowValidationError' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 1000000000000000000000000000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => trans("validation.mysql_unsigned_int", ['attribute' => 'amount']),
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testZapierWebhookNotPresentInEventsList' => [
        'request'  => [
            'url'     => '/webhooks/events/all',
            'method'  => 'get',
            'content' => [],
        ],
        'response'  => [
            'status_code'   => 200,
            'content'   => []
        ]
    ],

    'testCreatePaymentPageOrderWithOutAmountShouldThrowValidationError' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => trans("validation.required", ['attribute' => 'amount']),
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSettingsInPaymentPageItemsInPaymentButton' => [
        'request' => [
            'url'     => '/v1/payment_pages/pl_100000000000pl/details',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'                      => 'pl_100000000000pl',
                'payment_page_items'      => [
                    [
                        'settings'        => [
                            'position'    => '0'
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testSettingsInPaymentPageItemsInSubscriptionButton'  => [
        'request' => [
            'url'     => '/v1/payment_pages/pl_100000000000pl/details',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'                      => 'pl_100000000000pl',
                'payment_page_items'      => [
                    [
                        'settings'        => [
                            'position'    => '0'
                        ]
                    ]
                ]
            ]
        ],
    ],
    'testOrderCreateShowStoreNotes' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/order',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'payment_page_item_id' => 'ppi_10000000000ppi',
                        'amount'               => 5000,
                    ]
                ],
                "notes" => [
                    "email"     => "fake@email.com",
                    "mobile"    => "9878678798",
                    "something" => "nothing"
                ],
            ],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'amount' => 5000,
                    "notes" => [
                        "email"     => "fake@email.com",
                        "mobile"    => "9878678798",
                        "something" => "nothing"
                    ],
                ],
                'line_items' => [
                    [
                        'item_id'  => 'item_10000000000ppi',
                        'ref_id'   => 'ppi_10000000000ppi',
                        'ref_type' => 'payment_page_item',
                        'amount'   => 5000,
                        'currency' => 'INR',
                    ]
                ],
            ],
        ],
    ],

    'testCreatePaymentPageWithDonationGoalTrackerShouldBeReturnedInDetailsApi' => [
        'request' => [
            'url'    => '/payment_pages/pl_100000000000pl/details',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "settings" => [
                    'goal_tracker'    => [
                        'tracker_type'  => 'donation_amount_based',
                        "is_active"     => "1",
                        "meta_data"     => [
                            "goal_amount"               => "10000",
                            "display_days_left"         => "0",
                            "display_supporter_count"   => "0",
                            "collected_amount"          => "0"
                        ]
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageWithDonationGoalTrackerAmountBasedSuccessfully' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
                "settings" => [
                    'goal_tracker'    => [
                        'tracker_type'  => 'donation_amount_based',
                        "is_active"     => "1",
                        "meta_data"     => [
                            "goal_amount"               => "10000",
                            "display_days_left"         => "0",
                            "display_supporter_count"   => "1",
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreatePaymentPageWithDonationGoalTrackerSupporterBasedSuccessfully' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
                "settings" => [
                    'goal_tracker'    => [
                        'tracker_type'  => 'donation_supporter_based',
                        "is_active"     => "1",
                        "meta_data"     => [
                            "available_units"           => "10000",
                            "display_available_units"   => "1",
                            "display_sold_units"        => "1",
                            "display_days_left"         => "0",
                            "display_supporter_count"   => "1",
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDonationGoalTrackerAmountBasedOnMultipleOrderMakePaymentShouldIncrementKeys' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/order',
            'method'  => 'post',
            'content' => [
                "line_items" => [
                    [
                        "payment_page_item_id" => "ppi_10000000000ppi",
                        "amount" => 5000,
                        "quantity" => 1,
                    ],
                    [
                        "payment_page_item_id" => "ppi_10000000001ppi",
                        "amount" => 10000,
                        "quantity" => 2,
                    ]
                ],
                "notes" => [
                    "email" => "some@email.com",
                    "phone" => "898989898",
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDonationGoalTrackerSupporterBasedOnMultipleOrderMakePaymentShouldIncrementKeys' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/order',
            'method'  => 'post',
            'content' => [
                "line_items" => [
                    [
                        "payment_page_item_id" => "ppi_10000000000ppi",
                        "amount" => 5000,
                        "quantity" => 3,
                    ],
                    [
                        "payment_page_item_id" => "ppi_10000000001ppi",
                        "amount" => 10000,
                        "quantity" => 2,
                    ]
                ],
                "notes" => [
                    "email" => "some@email.com",
                    "phone" => "898989898",
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPaymentHandleCreation'   => [
        'request'  => [
            'url'     => '/payment_handle',
            'method'  => 'post',
            'content' => [
                'title'      =>    'sample title',
                'slug'       =>    '@sampleHandle'
            ]
        ],
        'response' => [
            'content'  => [
                'title'      => 'sample title',
                'slug'       => '@sampleHandle',
                'url'        => 'https://pages.razorpay.com/@sampleHandle',
            ]
        ]
    ],
];

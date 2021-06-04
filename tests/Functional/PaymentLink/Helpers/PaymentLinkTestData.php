<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
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
];

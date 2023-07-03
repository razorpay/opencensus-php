<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\PaymentLink\CustomDomain\Plans as CDSPlans;

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

    'testFetchPaymentLinksForFileUpload' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'get',
            'content' => [
                'view_type' => 'file_upload_page'
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'user_id'     => User::MERCHANT_USER_ID,
                        'amount'      => NULL,
                        'currency'    => 'INR',
                        'title'       => 'Sample title',
                        'description' => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                    ],
                ],
            ],
        ],
    ],

    'testFetchPaymentLinksForFileUploadWithoutFeature' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'get',
            'content' => [
                'view_type' => 'file_upload_page'
            ],
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
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

    'testUpdatePaymentLinkFileUpload' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                "support_email"=> "nikhilesh.tripathi@razorpay.com",
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"contact2\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200
        ],
    ],

    'testUpdatePaymentLinkFileUploadException' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                "support_email"=> "nikhilesh.tripathi@razorpay.com",
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}]",
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Mandatory field Primary reference ID missing.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testUpdatePaymentLinkFileUploadWithoutSecondaryReferenceId1' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                "support_email"=> "nikhilesh.tripathi@razorpay.com",
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}]",
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Mandatory field Secondary reference ID 1 missing.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentLinkFileUploadWithoutFeature' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                "support_email"=> "nikhilesh.tripathi@razorpay.com",
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}]",
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
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

    'testPaymentLinkSendNotificationForAllRecords' => [
        'request' => [
            'url' => '/payment_pages/pl_100000000000pl/fetch_notify_details',
            'method' => 'post',
            'content' => [
                'notify_on' => [
                    'email'
                ],
                'batch_id' => 'batch_KoGILWQCoVkOz5',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testPaymentLinkSendNotificationForAllRecordsEmail' => [
        'request' => [
            'url' => '/payment_pages/pl_100000000000pl/fetch_notify_details',
            'method' => 'post',
            'content' => [
                'notify_on' => [
                    'email'
                ],
                'batch_id' => 'batch_KoGILWQCoVkOz5',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testPaymentLinkSendNotificationForAllRecordsSms' => [
        'request' => [
            'url' => '/payment_pages/pl_100000000000pl/fetch_notify_details',
            'method' => 'post',
            'content' => [
                'notify_on' => [
                    'sms'
                ],
                'batch_id' => 'batch_KoGILWQCoVkOz5',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testPaymentLinkSendNotificationForAllRecordsFailure' => [
        'request' => [
            'url' => '/payment_pages/pl_100000000000pl/fetch_notify_details',
            'method' => 'post',
            'content' => [
                'notify_on' => [
                    'random'
                ],
                'batch_id' => 'batch_KoGILWQCoVkOz5',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either email or contact should be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkSendNotificationForAllRecordsValidationFailure' => [
        'request' => [
            'url' => '/payment_pages/pl_100000000000pl/fetch_notify_details',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The notify on field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchRecordsForPL' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567890',
                'sec__ref__id_1' => '0987654321',
            ],
        ],
        'response'=> [
            'status_code' => 200,
            'content' => [
                'data' => [
                    'pri__ref__id' => '1234567890',
                    'sec__ref__id_1' =>"0987654321",
                    'phone' => '0987654321',
                    'email' => "paridhi.jain@rzp.com",
                ],
                'other_details' =>[
                    'amount' => 101,
                    'contact' => "0987654321",
                    'sec__ref__id_1' =>"0987654321",
                    'status' =>"unpaid",
                ],
            ]
        ],
    ],

    'testFetchRecordsForPLIdFailure' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567890',
                'sec__ref__id_1' => '0987654321',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testFetchRecordsForPLWithOnlyPrimaryRefId' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567890'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The sec  ref  id 1 field is required.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchRecordsForPLWithOnlyPrimaryRefIdFailure' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567890',
                'sec__ref__id_1' => '0987654320',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Secondary Reference Id\'s Mismatch.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchRecordsForPLFailure' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567890'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The sec  ref  id 1 field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchRecordsForPLFailureIncorrectPriRefId' => [
        'request' => [
            'url' => '/payment_pages/{pl_id}/fetch_records',
            'method' => 'post',
            'content' => [
                'pri__ref__id' => '1234567891',
                'sec__ref__id_1' => '0987654320',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Primary Reference Id\'s Mismatch.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testPaymentPageStatusUpdate' => [
        'request' => [
            'url' => '',
            'method' => 'post',
            'content' => [],
        ],
        'response' => []
    ],

    'testPaymentPageStatusUpdateWithoutFeatureFlag' => [
        'request' => [
            'url' => '',
            'method' => 'post',
            'content' => [],
        ],
        'response' => []
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

    'testShiprocketPaymentPagePaidWebhookEventData' =>  [
        "entity" => "event",
        "account_id" => "acc_10000000000000",
        "event" => "shiprocket.payment_page.paid.v1",
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

    'testShiprocketPaymentPagePaid1CCWebhookEventData' =>  [
        "entity" => "event",
        "account_id" => "acc_10000000000000",
        "event" => "shiprocket.payment_page.paid.v1",
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
                "notes" => [
                    "email"  => "a@b.com",
                    "phone"  => "+919918899029",
                    "name"    =>  "demo name",
                    "address" => "xyz 1xyz 2",
                    "city" => "Bengaluru",
                    "state" => "Karnataka",
                    "pincode"=> "560001"
                ],
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
                "notes" => [
                    "email"  => "a@b.com",
                    "phone"  => "+919918899029",
                    "name"    =>  "demo name",
                    "address" => "xyz 1xyz 2",
                    "city" => "Bengaluru",
                    "state" => "Karnataka",
                    "pincode"=> "560001"
                ],
                "customer_details" => [
                    "email"  => "a@b.com",
                    "phone"  => "+919918899029",
                    "shipping_address" => [
                        "name" => "demo name",
                        "type" => "shipping_address",
                        "line1"=> "xyz 1",
                        "line2"=> "xyz 2",
                        "zipcode"=> "560001",
                        "city"=> "Bengaluru",
                        "state"=> "Karnataka",
                        "country"=> "in",
                        "contact"=> "+919918899029"
                    ],
                    "billing_address" => [
                        "name" => "demo name",
                        "line1"=> "xyz 1",
                        "line2"=> "xyz 2",
                        "zipcode"=> "560001",
                        "city"=> "Bengaluru",
                        "state"=> "Karnataka",
                        "country"=> "in",
                        "contact"=> "+919918899029"
                    ],
                ],
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

    'testCreateSubscriptionButton' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                "view_type"     => "subscription_button",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title subscription button',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'plan_id'           => 'plan_1000000000plan',
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
                'title'         => 'Sample title subscription button',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'          => 'amount',
                            'description'   => 'SAMPLE DESCRIPTION',
                            'amount'        => 100000,
                            'currency'      => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'quantity_sold'     => 0,
                        'total_amount_paid' => 0,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
    ],

    'testCreateSubscription' => [
        'request'  => [
            'url'     => '/subscription_buttons/pl_100000000000pl/create_subscription',
            'method'  => 'post',
            'content' => [
                "payment_page_item_id"  => "ppi_10000000000ppi"
            ],
        ],
        'response' => [
            'content' => [
                'subscription_id'   => "plan_1000000000plan"
            ],
        ],
    ],

    'testCreateSubscriptionWithNoPlanIdShouldThrowException' => [
        'request'  => [
            'url'     => '/subscription_buttons/pl_100000000000pl/create_subscription',
            'method'  => 'post',
            'content' => [
                "payment_page_item_id"  => "ppi_10000000000ppi"
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'plan is not present to create a subscription',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleCreationApi'    => [
        'request' => [
            'url'      => '/payment_handle',
            'method'   => 'post',
        ],
        'response'  => [
            'content'  => [
                'title' => 'Test Merchant',
                'slug'  => '@testmerchant',
                'url'   => 'https://razorpay.me/@testmerchant',
            ]
        ]
    ],

    'testPaymentHandleCreationApiCallAfterActivation'  => [
        'request' => [
            'url'      => '/payment_handle',
            'method'   => 'post',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Payment Handle already created for this merchant"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleCreationWhenPaymentHandleAlreadyExists'    => [
        'request' => [
            'url'      => '/payment_handle',
            'method'   => 'post',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Payment Handle already created for this merchant"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleCreationFromTestMode'    => [
        'request' => [
            'url'      => '/payment_handle',
            'method'   => 'post',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment Handle can be created in live mode only.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleCreationWithBillingLabelLengthLessThanFour' => [
        'request' => [
            'url'      => '/payment_handle',
            'method'   => 'post',
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testPaymentHandleUpdateWithWrongSlug' => [
        'request' => [
            'url'    => '/payment_handle',
            'method' => 'patch',
            'content'  => [
                'slug' => 'updateHandle'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Slug must contain @ at beginning",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleUpdateSlugLengthLessThanFour' => [
        'request' => [
            'url'    => '/payment_handle',
            'method' => 'patch',
            'content'  => [
                'slug' => '@xy'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "The slug must be at least 4 characters.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleUpdateSlugLengthGreaterThanThirty' => [
        'request' => [
            'url'    => '/payment_handle',
            'method' => 'patch',
            'content'  => [
                'slug' => '@updatedHandleGreaterThanThirtyCharacters'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "The slug may not be greater than 30 characters.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleSuggestionApiWithBillingLabelLengthMoreThanThirty' => [
        'request' => [
            'url'    => '/payment_handle/suggestion',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'suggestions'  => [
                    "@handlegreaterthanthirtycharac"
                ]
            ],
        ],
    ],

    'testPaymentHandleFetch'       => [
        'request'  => [
            'url'     => '/payment_handle',
            'method'  => 'get',
        ],
        'response' => [
            'content'   => [
                'title'      =>    'Test Merchant',
                'slug'       =>    '@testmerchant',
                'url'        =>    'https://razorpay.me/@testmerchant'
            ]
        ]
    ],

    'testPaymentHandleUpdate' => [
        'request' => [
            'method' => 'PATCH',
            'url' => '/v1/payment_handle',
            'content' => [
                'slug' => "@updatedPaymentHandle"
            ],
        ],
        'response'  => [
            'content'  => [
                'slug' => '@updatedPaymentHandle',
                'url'  => 'https://razorpay.me/@updatedPaymentHandle',
                'title'=> 'Test Merchant'
            ]
        ]
    ],

    'testPaymentHandleFetchWhenHandleDoesNotExists' => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'get',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment Handle does not exists for this merchant. Please create a new one'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentHandleCreationBillingLabelLengthMoreThanThirty' => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'post',
        ],
        'response' => [
            'content'   => [
                'title'      =>    'Test Billing Label Private Limited',
                'slug'       =>    '@testbillinglabelprivatelimite',
                'url'        =>    'https://razorpay.me/@testbillinglabelprivatelimite'
            ]
        ]
    ],

    'testPaymentHandleCreationBillingLabelLengthMoreThanEighty' => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'post',
        ],
        'response' => [
            'content'   => [
                'title'      =>    'Test Billing Label Private Limited Lorem Ipsum is simply dummy text of the print',
                'slug'       =>    '@testbillinglabelprivatelimite',
                'url'        =>    'https://razorpay.me/@testbillinglabelprivatelimite'
            ]
        ]
    ],

    'testPaymentHandlePrecreation'  => [
        'request'  => [
            'url'   => '/precreate_payment_handle',
            'method'=> 'post',
        ],
        'response' => [
            'content'   => [
                'title'      =>    'Test Merchant',
                'slug'       =>    '@testmerchant',
                'url'        =>    'https://razorpay.me/@testmerchant'
            ]
        ]
    ],

    'testPaymentHandleCreationPrecreateNotCalled'  => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'post',
        ],
        'response' => [
            'content'   => [
                'title'   => 'Test Merchant',
                'slug'    => '@testmerchant',
                'url'     => 'https://razorpay.me/@testmerchant'
            ]
        ]
    ],

    'testPaymentHandleCreationPrecreateCalled' => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'post',
        ],
        'response' => [
            'content'   => [
                'title'   => 'Test Merchant',
                'slug'    => '@testmerchant',
                'url'     => 'https://razorpay.me/@testmerchant'
            ]
        ]
    ],

    'testPaymentHandleUpdateAtPrecreateState' => [
        'request'  => [
            'url'   => '/payment_handle',
            'method'=> 'patch',
            'content'  => [
                'slug' => '@newhandle'
            ],
        ],
        'response' => [
            'content' => [
                'url'    => 'https://razorpay.me/@newhandle',
                'title'  => 'Test Merchant',
                'slug'   => '@newhandle'
            ]
        ]
    ],

    'testPaymentHandleGetAtPrecreateState' => [
        'request' => [
            'url'   => '/payment_handle',
            'method'=> 'get',
        ],
        'response' => [
            'content' => [
                'url' => 'https://razorpay.me/@testmerchant',
                'title'  => 'Test Merchant',
                'slug' => '@testmerchant'
            ]
        ]
    ],

    'testPaymentHandleEncryptCustomAmount' => [
        'request'  => [
            'url'   => '/payment_handle/custom_amount',
            'method'=> 'post',
            'content' => [
                'amount' => '100'
            ]
        ],
        'response'  => [
            'content' => [
                'encrypted_amount' => 'GJTihh0TE3BLUUz12vhIgQ%3D%3D'
            ]
        ]

    ],

    'testUpdatePageWithGoalTrackerInactiveAndEndDatePastShouldNotThrowValidationError' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID
                    ]
                ],
                "settings" => [
                    'goal_tracker'    => [
                        'tracker_type'  => 'donation_supporter_based',
                        "is_active"     => "0",
                        "meta_data"     => [
                            "display_available_units"   => "0",
                            "display_days_left"         => "0",
                            "display_supporter_count"   => "0",
                            "display_sold_units"        => "0",
                            "available_units"           => "55",
                            "goal_end_timestamp"        => (string) (new Carbon())->subDays(1)->getTimestamp()
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdatePageWithGoalTrackerActiveEndDatePastShouldThrowValidationError' => [
        'request' => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'payment_page_items'         => [
                    [
                        'id' => 'ppi_' . PaymentLinkTest::TEST_PPI_ID
                    ]
                ],
                "settings" => [
                    'goal_tracker'    => [
                        'tracker_type'  => 'donation_supporter_based',
                        "is_active"     => "1",
                        "meta_data"     => [
                            "display_available_units"   => "0",
                            "display_days_left"         => "0",
                            "display_supporter_count"   => "0",
                            "display_sold_units"        => "0",
                            "available_units"           => "55",
                            "goal_end_timestamp"        => (string) (new Carbon())->subDays(1)->getTimestamp()
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOnPaymentPageCreateDedupeCallIsDispatchedInLiveMode' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'headers'  => [
                "X-Dashboard-User-Role" => 'owner',
            ],
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
            'content' => [],
        ],
    ],

    'testOnPaymentPageCreateDedupeCallIsNotDispatchedInTestMode' => [
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
            ],
        ],
    ],

    'testCreatePaymentPageWithShiprocketEnabledWithoutShiprocketFieldShouldThrowError' => [
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
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "1",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithShiprocketDisabledWithoutShiprocketFieldShouldPass' => [
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
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "0",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageWithShiprocketEnabledWithShiprocketFieldShouldPass' => [
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
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "1",
                    ],
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 200,
        ],
    ],


    'testCreatePaymentPageWithMandatoryPayerNameAndExpiryWithoutPayerName' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                "expire_by" => 1670914048,
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "support_email" => "abc@razorpay.com",
                "support_contact"   => "adasdadasdsad",
                "settings" => [
                    "allow_social_share" => "0",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testCreatePaymentPageWithMandatoryPayerNameAndExpiryWithoutExpiry' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "support_email" => "abc@razorpay.com",
                "support_contact"   => "adasdadasdsad",
                "settings" => [
                    "allow_social_share" => "0",
                    "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithMandatoryPayerNameAndExpiry' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                "expire_by"     =>  1671009406,
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "1",
                    ],
                    "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageUpdateWithPositive' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageUpdateWithPositive2' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email3\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageUpdateWithPositive3' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                "expire_by"     =>  "",
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentPageUpdateWithNegative' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema" => "[{\"name\":\"pname\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email3\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description'   => 'Mandatory field Payer Name missing.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testCreatePaymentPageUpdateWithNegative2' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                "expire_by"     =>  null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description'   => 'Mandatory field Expires By must be set',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithPartnerSettingsAndInvalidPartnerShouldThrowError' => [
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
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_something" => "1",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreatePaymentPageWithPartnerSettingsAndInvalidPartnerValueShouldThrowError' => [
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
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "11",
                    ],
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithShiprocketEnabledWithoutShiprocketFieldShouldThrowError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "1",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithShiprocketDisabledWithoutShiprocketFieldShouldPass' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "0",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdatePaymentPageWithShiprocketEnabledWithShiprocketFieldShouldPass' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "1",
                    ],
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdatePaymentPageWithPartnerSettingsAndInvalidPartnerShouldThrowError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_something" => "1",
                    ],
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testUpdatePaymentPageWithPartnerSettingsAndInvalidPartnerValueShouldThrowError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "partner_webhook_settings" => [
                        "partner_shiprocket" => "11",
                    ],
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithInvalidCharacterInTitleThrowsError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title 𤨒',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithInvalidCharacterInDescriptionThrowsError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description 𤨒"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithInvalidCharacterInPaymentSuccessMessageThrowsError' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you 𤨒",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithInvalidCharacterInTitleThrowsError' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title 𤨒',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithInvalidCharacterInDescriptionThrowsError' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description 𤨒"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithInvalidCharacterInPaymentSuccessMessageThrowsError' => [
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
                "settings" => [
                    "payment_success_message" =>  "Thank you 𤨒",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOnMultipleOrderMakePaymentShouldUpdateCapturedPaymentCount' => [
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

    'testOnGetDetailsCallAndNoCapturedPaymentCountShouldUpdateCapturedPaymentCount' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/details',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'captured_payments_count' => 1
            ],
        ],
    ],

    'testGoalTrackerAmountMoreThenACrOnMakingMultiplePaymentShouldIncrementKeys' => [
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

    'testGoalTrackerAmountMoreThenACrShouldbeAllowed' => [
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
                            "goal_amount"               => "750000000000000",
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

    'testOnCreatePaymentPageViewCallShouldBeCached' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '{"value":[{"insert":"Product Image(s)"}],"metaText":""}',
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
            'content' => [],
        ],
    ],

    'testCreatePaymentPageWithYoutubeVideoInDescription' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                "description"   => "{\"value\":[{\"insert\":{\"video\":\"https://youtu.be/qVdPh2cBTN0\"}},{\"insert\":\"\\n\"}]}",
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
                'title'         => 'Sample title',
                'description'   => "{\"value\":[{\"insert\":{\"video\":\"https://youtu.be/qVdPh2cBTN0\"}},{\"insert\":\"\\n\"}]}",
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
    ],

    'testCreatePaymentPageWithVimeoVideoInDescription' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                "description"   => "{\"value\":[{\"insert\":{\"video\":\"https://vimeo.com/267392220\"}},{\"insert\":\"\\n\"}]}",
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
                'title'         => 'Sample title',
                'description'   => "{\"value\":[{\"insert\":{\"video\":\"https://vimeo.com/267392220\"}},{\"insert\":\"\\n\"}]}",
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
    ],

    'testCreatePaymentPageWithOtherVideoInDescription' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                "description"   => "{\"value\":[{\"insert\":{\"video\":\"https://google.com\"}},{\"insert\":\"\\n\"}]}",
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
                    'description' => 'Only Youtube and Vimeo videos allowed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    "testOnUpdatePaymentPageViewCallShouldBeCached" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '{"value": [{"insert":"Sample description"},{"insert":"\\n"}],"metaText":""}',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "payment_success_message" =>  "Thank you",
                    "udf_schema" => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
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
            'content' => [],
        ]
    ],

    "testOnPageActivateViewCallShouldBeCached" => [
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

    'testOnPageExpireViewCallShouldBeCached' => [
        'request'  => [
            'url'     => '/payment_pages/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_count' => 1,
                'failed_ids'  => [],
            ],
        ],
    ],

    "testOnPageSetRecieptDetailsViewCallShouldBeCached" => [
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

    'testOnPageUpdateItemViewCallShouldBeCached' => [
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

    'testOnCreateWithSlugNocodeCustomUrlShouldBeCreated'    => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'slug'          => 'testslug',
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
            ],
        ],
    ],

    'testOnCreateWithExistingDeletedSlugNocodeCustomUrlShouldBeCreated'    => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'slug'          => 'testslug12',
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
            ],
        ],
    ],

    'testCreatePaymentPageWithUdfSchemaPatternInvalidShouldFail' => [
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
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithUdfSchemaPatternInvalidShouldFail' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithUdfSchemaTypeInvalidShouldFail' => [
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
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"unknown\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithUdfSchemaTypeInvalidShouldFail' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"unknown\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithUdfSchemaOptionCmpInvalidShouldFail' => [
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
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"email\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}, {\"name\":\"nmin\",\"title\":\"nmin\",\"required\":false,\"type\":\"string\",\"options\":{\"cmp\":\"unknown\"},\"settings\":{\"position\":3}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithUdfSchemaOptionCmpInvalidShouldFail' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"email\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}, {\"name\":\"nmin\",\"title\":\"nmin\",\"required\":false,\"type\":\"string\",\"options\":{\"cmp\":\"unknown\"},\"settings\":{\"position\":3}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePaymentPageWithUdfSchemaXssShouldFail' => [
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
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"autofocus\": \"1\",\"onfocus\": \"console.log(`XSS`)\", \"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testUpdatePaymentPageWithUdfSchemaXssShouldFail' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"autofocus\": \"1\",\"onfocus\": \"console.log(`XSS`)\", \"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testPaymentPageCreateWithNonUtf8InTerms'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'terms'         => 'abcde 𤨒',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"autofocus\": \"1\",\"onfocus\": \"console.log(`XSS`)\", \"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentPageCreateForFileUploadAllFieldsInSettings' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"string\",\"pattern\":\"alphanumeric\",\"minLength\":\"3\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"string\",\"pattern\":\"alphanumeric\",\"settings\":{\"position\":3}},{\"name\":\"address\",\"required\":true,\"title\":\"Address\",\"type\":\"string\",\"pattern\":\"alphanumeric\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"alphanumeric\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'item1',
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
                    ],
                    [
                        'item' => [
                            'name'        =>  'item2',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],


    'testPaymentPageUpdateForFileUploadAllFieldsInSettings' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'patch',
            'content' => [
                'title'         => 'Sample title 2',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"father_name\",\"required\":true,\"title\":\"Father Name\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"Address\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'item3',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    "testCreatePaymentPageRecordWithCustomFieldsSchema" => [
        'request'  => [
            'url'     => '/create_record',
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'contact'        => '0987654321',
                'amount'         => '101',
                'Address'         => 'test',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB' => 'test123',
                "item1" => 100001,
                "item2" => 20000
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],


    'testFetchPaymentPageRecordsAfterPPUpdate' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'patch',
            'content' => [
                'title'         => 'Sample title 2',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"address\",\"required\":true,\"title\":\"Address\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"Address\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],


    "testCreatePaymentPageRecordSecurityValidations" => [
        'request'  => [
            'url'     => '/create_record',
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'contact'        => '0987654321',
                'amount'         => '101',
                'Address'        => 'test',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => 'test123',
                'item1'          => 10000,
                'item2'          => 10000
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],
    'testPaymentPageCreateWithMoreThan5SeccRefIds' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"address\",\"required\":true,\"title\":\"Address\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_2\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_3\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_4\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_5\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"sec__ref__id_6\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "Number of secondary reference ID's cannot be more than 5"
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    "testCreatePaymentPageRecordSecurityValidationsNegative" => [
        'request'  => [
            'url'     => '/create_record',
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'contact'        => '0987654321',
                'amount'         => '101',
                'Address'        => 'test',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => '1234567890',
                'item1'          => 10000,
                'item2'          => 10000
            ],
        ],
        'response' => [
            'content' => [
                'error_description' => 'Secondary reference id cannot be same as primary reference id'
                ]
            ],
            'status_code' => 200,
    ],

    'testPaymentPageCreateForFileUpload' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}, {\"name\":\"sec__ref__id_1\",\"title\":\"Phone2\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPaymentPageCreateForFileUploadWithoutPhone' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"sec__ref__id_1\",\"title\":\"Phone2\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":3}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPaymentPageCreateForFileUploadWithSecRefId' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],


    'testPaymentPageCreateForFileUploadWithoutFeature'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"autofocus\": \"1\",\"onfocus\": \"console.log(`XSS`)\", \"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"unknown\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testPaymentPageCreateForFileUploadMissingPrimaryRefID'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\", \"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Mandatory field Primary reference ID missing.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testPaymentPageCreateForFileUploadWithoutSecondaryReferenceId1'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Mandatory field Secondary reference ID 1 missing.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testPaymentPageCreateForFileUploadWithPrimaryRefIdNotRequired'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":false,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}, {\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Primary reference ID cannot be optional field.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testPaymentPageCreateForFileUploadWithSecRefId1NotRequired'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}, {\"name\":\"sec__ref__id_1\",\"required\":false,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Secondary reference ID 1 cannot be optional field.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testPaymentPageCreateWithSameTitleForPrimaryAndSecRefIds'     => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}}, {\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"phone\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}}]",
                ],
                'view_type' => 'file_upload_page',
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
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Primary reference ID and Secondary reference ID 1 cannot be have same title.'
                    ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'setUpPaymentPageForFileUpload' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"alphanumeric\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'setupPaymentPageForUDFSchemaValidations' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Primary Reference Id\",\"required\":true,\"type\":\"string\",\"pattern\":\"alphanumeric\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"url\",\"required\":true,\"title\":\"URL\",\"type\":\"string\",\"pattern\":\"url\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"Secondary Reference Id\",\"type\":\"string\",\"pattern\":\"alphanumeric\",\"settings\":{\"position\":4}},{\"name\":\"dob\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"date\",\"settings\":{\"position\":5}}, {\"name\":\"amount\",\"required\":true,\"title\":\"Amount 2\",\"type\":\"string\",\"pattern\":\"amount\",\"settings\":{\"position\":6}}, {\"name\":\"phone\",\"required\":true,\"title\":\"Phone\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":7}},{\"name\":\"pan\",\"required\":true,\"title\":\"PAN\",\"type\":\"string\",\"pattern\":\"pan\",\"settings\":{\"position\":8}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
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
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    "testPaymentPageRecordForFileUpload" => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'contact'        => '0987654321',
                'amount'         => '101',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => '0987654321'

            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    "testPaymentPageRecordForFileUploadMissingUdfParams" => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'contact'        => '0987654321',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => '0987654321'
            ],
        ],
        'response' => [
            'content' => [
                'error_description' => 'Mandatory field entry missing for Phone'
                ]
            ],
            'status_code' => 200,
    ],

    "testPaymentPageRecordForFileUploadAmountValidationFailure" => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'contact'        => '0987654321',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => '0987654321'

            ],
        ],
        'response' => [
            'content' => [
                'error_description' => 'Mandatory field entry missing for amount'
                ]
            ],
            'status_code' => 200,
        ],

    "testPaymentPageRecordForFileUploadAmountLessTHanMinAllowed" => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                "Email"          => 'paridhi.jain@rzp.com',
                'Phone'          => '1234567890',
                'amount'         => '10',
                'contact'        => '0987654321',
                'sms_notify'     => TRUE,
                'email_notify'   => TRUE,
                'DOB'            => '0987654321'
            ],
        ],
        'response' => [
            'content' => [
                'error_description' => 'Payment amount is lesser than the minimum amount allowed'
                ]
            ],
            'status_code' => 200,
        ],

    "testPaymentPagePendingPaymentsAndRevenue" => [
        'request'  => [
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    "testGetMultipleBatchesForPaymentPage" => [
        'request'  => [
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'has_more' => false,
                'items' => [
                    [
                        'id'     => 'batch_00000000000001',
                        'type'   => 'payment_page',
                        'status' => 'processed'
                    ]

                ],
            ],
        ],
        'status_code' => 200,
    ],

    "testGetMultipleBatchesForPaymentPageCount" => [
        'request'  => [
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'has_more' => false,
                'items' => [
                    [
                        'id'     => 'batch_00000000000001',
                        'type'   => 'payment_page',
                        'status' => 'processed'
                    ]

                ],
            ],
        ],
        'status_code' => 200,
    ],

    "testGetAllBatchesForPaymentPage" => [
        'request'  => [
            'method'  => 'get',
            'content' => [
                "all_batches" => true
            ],
        ],
        'response' => [
            'content' => [
                'KoGILWQCoVkOz5',
                'KoGILWQCoVkOw7',
                'KoGILWQCoVkO0s',
                'KoGILWQCoVkO2k'
            ],
        ],
        'status_code' => 200,
    ],

    "testCreatePaymentPageWithCustomDomain" => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                "slug"          => "myslug",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => "cds.razorpay.in"
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
            'content' => [],
        ],
    ],

    "testUpdatePaymentPageWithCustomDomain" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                "slug"          => "myslug1",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => "cds1.razorpay.in"
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
            'content' => [],
        ],
    ],

    "testCreatePaymentPageWithCustomDomainEmptySlug" => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                "slug"          => "",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => "cds.razorpay.in"
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
            'content' => [],
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainEmptySlugAnotherCustomDomain" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                "slug"          => "",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => "random.razorpay.in"
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
            'content' => [],
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainToRzpDomainWithSlug" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                "slug"          => "myslug",
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => ""
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
            'content' => [],
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainToRzpDomainWithOutSlug" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => ""
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
            'content' => [],
        ],
    ],

    "testCreatePaymentPageWithCustomDomainNoSlugError" => [
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
                'settings' => [
                    "custom_domain" => "cds.razorpay.in"
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    "testCreatePaymentPageWithOutCustomDomainEmptySlugError" => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                "slug"          => "",
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainNoSlugError" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => "cds.razorpay.in"
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
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainEmptySlugToRzpDomainNoSlug" => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000001',
                'title'         => 'Sample title',
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
                'settings' => [
                    "custom_domain" => ""
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
            'content' => [],
        ],
    ],

    "testUpdatePaymentPageWithCustomDomainEmptySlugToDetailsShouldReturnEmptySlug" => [
        'request' => [
            'url'     => '/v1/payment_pages/pl_100000000000pl/details',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'    => 'pl_100000000000pl',
                'slug'  => '',
            ]
        ],
    ],

    "testOnCreateCustomDomainShouldWhitelistDomain" => [
        'request' => [
            "url"     => "/v1/payment_pages/cds/domains",
            "method"  => "post",
            "content" => [
                "domain_name" => "https://subdomain.razorpay.com"
            ],
        ],
        "response" => [
            "content" => []
        ],
    ],

    "testOnDeleteCustomDomainShouldRemoveFromWhitelistDomain" => [
        'request' => [
            "url"     => "/v1/payment_pages/cds/domains",
            "method"  => "delete",
            "content" => [
                "domain_name" => "https://subdomain.razorpay.com"
            ],
        ],
        "response" => [
            "content" => []
        ],
    ],
    "testCustomDomainServiceCreatePlans" => [
        "request"   => [
            "url"       => "/v1/payment_pages/cds/plans",
            "method"    => "post",
            "content"   => [
                "plans" => [
                    [
                        "alias"    => "cds_pricing_monthly",
                        "period"   => "monthly",
                        "interval" => "1"
                    ],
                    [
                        "alias"    => "cds_pricing_quarterly",
                        "period"   => "monthly",
                        "interval" => "3"
                    ]
                ]
            ]
        ],
        "response"    => [
            "content" => [
                "plans"   => [
                    [
                        "alias"         => "cds_pricing_monthly",
                        "period"        => "monthly",
                        "interval"      => "1"
                    ],
                    [
                        "alias"         => "cds_pricing_quarterly",
                        "period"        => "monthly",
                        "interval"      => "3"
                    ]
                ]
            ]
        ]
    ],
    "testCustomDomainServiceCreatePlansDuplicateAlias" => [
        "request"   => [
            "url"       => "/v1/payment_pages/cds/plans",
            "method"    => "post",
            "content"   => [
                "plans" => [
                    [
                        "alias"    => "cds_pricing_monthly",
                        "period"   => "monthly",
                        "interval" => "1"
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    "testCustomDomainServicePlansGet" => [
        "request"   => [
            "url"       => "/v1/payment_pages/cds/plans",
            "method"    => "get"
        ],
        "response"    => [
            "content" => [
                "plans"   => [
                    [
                        "alias"         => CDSPlans\Aliases::MONTHLY_ALIAS,
                        "name"          => "1 Month",
                        "period"        => "monthly",
                        "interval"      => 1,
                        "metadata"      => []
                    ],
                    [
                        "alias"         => CDSPlans\Aliases::QUARTERLY_ALIAS,
                        "name"          => "3 Months",
                        "period"        => "monthly",
                        "interval"      => 3,
                        "metadata"      => []
                    ],
                    [
                        "alias"         => CDSPlans\Aliases::BIYEARLY_ALIAS,
                        "name"          => "6 Months",
                        "period"        => "monthly",
                        "interval"      => 6,
                        "metadata"      => []
                    ]
                ],
            ]
        ]
    ],

    'testCustomDomainServiceDeletePlans' => [
        "request"   => [
            "url"       => "/v1/payment_pages/cds/plans",
            "method"    => "delete",
            "content"   => []
        ],
        "response"    => [
            "status"  => 200,
            "content" => [
                'total_plans' => 1,
                'failed'      => []
            ]
        ]
    ],

    "testCustomDomainPlanIdUpdateWhenIdNotValid" => [
        "request"   => [
            "url"       => "/v1/payment_pages/cds/plans/plan",
            "method"    => "patch",
            "content"   => [
                "old_plan_id"  => "abcde",
                "new_plan_id"  => "abcde"
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreate1CCPaymentLink' => [
        'request' => [
            'url' => '/payment_pages',
            'method' => 'post',
            'content' => [
                'receipt' => '00000000000001',
                'title' => 'Sample title',
                'description' => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes' => [
                    'sample_key' => 'Sample notes',
                ],
                "settings" => [
                    "one_click_checkout" => '1',
                    "shipping_fee_rule" => [
                        "rule_type" => "free",
                        "fee" => 0,
                        "slabs" => [],
                    ],

                    "udf_schema" => "[{\"name\":\"email\",\"required\":false,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":false,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}}, {\"name\":\"name\",\"title\":\"Name\",\"required\":false,\"type\":\"string\", \"options\":{},\"settings\":{\"position\":2}}]",
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' => 'amount',
                            'description' => NULL,
                            'amount' => 100000,
                            'currency' => 'INR',
                        ],
                        'mandatory' => TRUE,
                        'image_url' => 'dummy',
                        'stock' => 10000,
                        'min_purchase' => 2,
                        'max_purchase' => 10000,
                        'min_amount' => NULL,
                        'max_amount' => NULL,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'user_id' => User::MERCHANT_USER_ID,
                'receipt' => '00000000000001',
                'amount' => NULL,
                'currency' => 'INR',
                'title' => 'Sample title',
                'description' => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'notes' => [
                    'sample_key' => 'Sample notes',
                ],
                'payment_page_items' => [
                    [
                        'item' => [
                            'name' => 'amount',
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

    'testUpdatePaymentPageWithWrongCheckoutOptions' => [
        'request' => [
            'url' => '/payment_pages/pl_'. PaymentLinkTest::TEST_PL_ID,
            'method' => 'patch',
            'content' => [
                'settings' => [
                    "checkout_options" => [
                        0 => 'A',
                        1 => 'r'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testUpdatePaymentPageWithCheckoutOptionsNoEmail' => [
        'request' => [
            'url' => '/payment_pages/pl_'. PaymentLinkTest::TEST_PL_ID,
            'method' => 'patch',
            'content' => [
                'settings' => [
                    "checkout_options" => [
                        "phone" => "phone"
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePaymentPageWithWrongCheckoutOptionsNoPhone' => [
        'request' => [
            'url' => '/payment_pages/pl_'. PaymentLinkTest::TEST_PL_ID,
            'method' => 'patch',
            'content' => [
                'settings' => [
                    "checkout_options" => [
                        "email" => "email"
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];

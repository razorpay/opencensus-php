<?php

namespace RZP\Tests\Functional\Store;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\Fixtures\Entity\User;

return [
    'testCreateStore' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'post',
            'content' => [
                'title'         => 'test store',
                'description'   => 'test store description',
                'slug'          => 'test-store',
            ],
        ],
        'response' => [
            'content' => [
                'title'         => 'test store',
                'description'   => 'test store description',
                'slug'          => 'test-store',
                'status'        => 'active',
                'store_url'     => 'https://stores.razorpay.com/test-store'

            ],
        ],
    ],

    'testCreateStoreWithoutTitle' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'post',
            'content' => [
                'description'   => 'test store description',
                'slug'          => 'test-store',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The title field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateStoreWithoutSlug' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'post',
            'content' => [
                'description'   => 'test store description',
                'title'          => 'test store',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The slug field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateStoreWithDuplicateSlug' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'post',
            'content' => [
                'description'   => 'test store description',
                'title'          => 'test store',
                'slug'          => 'test-store'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Failed to create store with given slug, please try a different value',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchStore' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'title'         => 'test store',
                'description'   => 'test store description',
                'slug'          => 'test-store',
                'status'        => 'active',
                'store_url'     => 'https://stores.razorpay.com/test-store'

            ],
            'status_code' => 200,
        ],
    ],

    'testFetchWithoutStore' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Store does not exists for this merchant. Please create a new one',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateStoreWithExistingStore' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'post',
            'content' => [
                'title'         => 'test store',
                'description'   => 'test store description',
                'slug'          => 'test-store',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Store already exists for this merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditStore' => [
        'request'  => [
            'url'     => '/store',
            'method'  => 'put',
            'content' => [
                'title'         => 'test store edit',
                'description'   => 'test store description edit',
                'slug'          => 'test-store-edit',
                'settings'      => [
                    'shipping_fees' => "1200",
                    'shipping_days' => "10"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'title'         => 'test store edit',
                'description'   => 'test store description edit',
                'slug'          => 'test-store-edit',
                'status'        => 'active',
                'store_url'     => 'https://stores.razorpay.com/test-store-edit',
                'settings'      => [
                    'shipping_fees' => "1200",
                    'shipping_days' => "10"
                ]
            ],
        ],
    ],

    'testAddProductToStore' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'post',
            'content' => [
                'name'             => 'test product',
                'description'      => 'test product description',
                'stock'            => 1,
                'selling_price'    => 25000,
                'discounted_price' => 10000,
                'images'           => [
                    'https://google.com',
                    'https://abcd.com',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'name'          => 'test product',
                'description'   => 'test product description',
                'selling_price' => 25000,
                'stock'         => 1,
            ],
        ],
    ],

    'testAddProductToStoreWithoutDiscountedPrice' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'post',
            'content' => [
                'name'             => 'test product',
                'description'      => 'test product description',
                'stock'            => 1,
                'selling_price'    => 25000,
                'images'           => [
                    'https://google.com',
                    'https://abcd.com',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'name'             => 'test product',
                'description'      => 'test product description',
                'selling_price'    => 25000,
                'discounted_price' => 25000,
                'stock'            => 1,
            ],
        ],
    ],

    'testAddProductToStoreWithoutSellingPrice' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'post',
            'content' => [
                'name'             => 'test product',
                'description'      => 'test product description',
                'stock'            => 1,
                'discounted_price' => 25000,
                'images'           => [
                    'https://google.com',
                    'https://abcd.com',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selling price field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddProductToStoreWithoutStock' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'post',
            'content' => [
                'name'             => 'test product',
                'description'      => 'test product description',
                'selling_price'    => 25000,
                'discounted_price' => 25000,
                'images'           => [
                    'https://google.com',
                    'https://abcd.com',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The stock field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateProduct' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'put',
            'content' => [
                'name'             => 'test product update',
                'description'      => 'test product description update',
                'selling_price'    => 35000,
                'stock'            => 2,
                'discounted_price' => 20000,
                'images'           => [
                    'https://google.com',
                    'https://abcd.com',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'name'             => 'test product update',
                'description'      => 'test product description update',
                'selling_price'    => 35000,
                'discounted_price' => 20000,
                'stock'            => 2,
            ],
        ],
    ],

    'testPatchProduct' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'patch',
            'content' => [
                'status'             => 'inactive',
            ],
        ],
        'response' => [
            'content' => [
                'name'          => 'test product',
                'description'   => 'test product description',
                'selling_price' => 25000,
                'stock'         => 1,
                'status'        => 'inactive'
            ],
        ],
    ],

    'testFetchInactiveProduct' => [
        'request'  => [
            'url'     => '/store/products',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'name'          => 'test product',
                'description'   => 'test product description',
                'selling_price' => 25000,
                'stock'         => 1,
                'status'        => 'inactive'
            ],
        ],
    ],

    'testFetchPublicStoreData' => [
        'request'  => [
            'url'     => '/store/public/test-store',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id' => '10000000000000',
                ],
                'store' => [
                    'title'         => 'test store',
                    'description'   => 'test store description',
                    'slug'          => 'test-store',
                    'status'        => 'active',
                    'store_url'     => 'https://stores.razorpay.com/test-store',
                    'products'      => [
                        [
                            'name'             => 'test product',
                            'description'      => 'test product description',
                            'selling_price'    => 25000,
                            'discounted_price' => 10000,
                            'stock'            => 1,
                        ],
                        [
                            'name'             => 'test product',
                            'description'      => 'test product description',
                            'selling_price'    => 25000,
                            'discounted_price' => 10000,
                            'stock'            => 1,
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFetchPublicStoreDataWithInactiveProduct' => [
        'request'  => [
            'url'     => '/store/public/test-store',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id' => '10000000000000',
                ],
                'store' => [
                    'title'         => 'test store',
                    'description'   => 'test store description',
                    'slug'          => 'test-store',
                    'status'        => 'active',
                    'store_url'     => 'https://stores.razorpay.com/test-store',
                    'products'      => [
                        [
                            'name'             => 'test product',
                            'description'      => 'test product description',
                            'selling_price'    => 25000,
                            'discounted_price' => 10000,
                            'stock'            => 1,
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testCreateOrder' => [
        'request'     => [
            'url'     => '/store/store_100000000000pl/order',
            'method'  => 'post',
            'content' => [
                'line_items'      => [
                    [
                        'payment_page_item_id'   => 'ppi_10000000000ppi',
                        "quantity"               =>  1
                    ],
                ],
            ],
        ],
        'response'     => [
            'content'  => [
                'order'    => [
                    'entity'        => 'order',
                    'amount'        => 1000,
                    'amount_paid'   => 0,
                ],
                'line_items' => [
                    [
                        'ref_id'       => 'ppi_' . STORETEST::TEST_PPI_ID,
                        'ref_type'     => 'payment_page_item',
                        'quantity'     => 1,
                        'amount'       => 1000,
                    ]
                ]
            ],
            'status_code'          => 200,
        ]
    ],

    'testMakePaymentAndCheckStoreStatus' => [
    'request' => [
        'url'    => '/payments',
        'method' => 'post',
        'content' => [
            'payment_link_id' => 'pl_' . StoreTest::TEST_STORE_ID,
            'amount'          => 1000,
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
            'order_id' => 'order_' . StoreTest::TEST_ORDER_ID,
            ],
        ],
        'response' => [
            'content'   => [
                ],
            'status_code'  => 200
            ]
    ],
];

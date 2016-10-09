<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePlan' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'interval'          => 'month',
                'interval_count'    => 1,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'amount' =>  2000,
                'currency' => 'INR',
                'interval' => 'month',
                'interval_count' => 1,
                'name' => 'test plan',
                'notes' => [],
            ],
        ],
    ],

    'testCreateMoreShippingAddressThanMaxAllowed' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'zipcode'       => '560078',
                'country'       => 'India',
                'type'  => 'shipping_address'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot have more than 3 shipping_address for customer',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSetPrimaryAddressForNonPrimaryAddressWithNoSwitch' => [
        'response'  => [
            'content'   => [
                'type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'zipcode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'in'
            ],
        ],
    ],

    'testDeleteNonPrimaryAddress' => [
        'response'  => [
            'content'   => []
        ],
    ],

    'testGetCustomerAddresses' => [
        'request'   => [
            'url'   => '/customers/cust_100000customer/addresses',
            'method' => 'get'
        ],
        'response'  => [
            'content'   => [
                'count' => 4,
                'items' => [
                    [
                        'type' => 'shipping_address',
                    ],
                    [
                        'type' => 'shipping_address',
                    ],
                    [
                        'type' => 'shipping_address',
                    ],
                    [
                        'type' => 'shipping_address',
                    ]
                ],
            ],
        ],
    ],
];
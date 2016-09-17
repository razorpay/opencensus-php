<?php

namespace RZP\Tests\Functional\Address;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateShippingAddress' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address'
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
        ],
    ],

    'testCreateShippingAddressWithPrimaryFalse' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address',
                'primary'       => '0',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => false,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
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
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address'
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

    'testCreateShippingAddressPrimarySwitch' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address'
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
        ],
    ],

    'testCreateShippingAddressNoPrimarySwitch' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address',
                'primary'       => '0',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => false,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
        ],
    ],

    'testSetPrimaryAddressForNonPrimaryAddressWithNoSwitch' => [
        'response'  => [
            'content'   => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
        ],
    ],

    'testSetPrimaryAddressForNonPrimaryAddressWithSwitch' => [
        'response'  => [
            'content'   => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
        ],
    ],

    'testDeleteNonPrimaryAddress' => [
        'response'  => [
            'content'   => []
        ],
    ],

    'testDeletePrimaryAddressWithNoSwitch' => [
        'response'  => [
            'content'   => []
        ],
    ],

    'testDeletePrimaryAddressWithSwitch' => [
        'response' => [
            'content'   => [],
        ],
    ],

    'testCreateTwoShippingAddressesForCustomer' => [
        'request' => [
            'url' => '/customers/cust_100000customer/addresses',
            'method' => 'post',
            'content' => [
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560078',
                'country'       => 'India',
                'address_type'  => 'shipping_address'
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'     => 'cust_100000customer',
                'entity_type'   => 'customer',
                'address_type'  => 'shipping_address',
                'primary'       => true,
                'line1'         => 'some line one',
                'line2'         => 'some line two',
                'pincode'       => '560078',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'country'       => 'India'
            ],
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
                        'entity_id' => 'cust_100000customer',
                        'entity_type' => 'customer',
                        'address_type' => 'shipping_address',
                    ],
                    [
                        'entity_id' => 'cust_100000customer',
                        'entity_type' => 'customer',
                        'address_type' => 'shipping_address',
                    ],
                    [
                        'entity_id' => 'cust_100000customer',
                        'entity_type' => 'customer',
                        'address_type' => 'shipping_address',
                    ],
                    [
                        'entity_id' => 'cust_100000customer',
                        'entity_type' => 'customer',
                        'address_type' => 'shipping_address',
                    ]
                ],
            ],
        ],
    ],
];
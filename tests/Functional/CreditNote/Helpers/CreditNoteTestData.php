<?php

namespace RZP\Tests\Functional\CreditNote;

use RZP\Error\ErrorCode;

return [

    'testCreateCreditNote' => [
        'request'  => [
            'url'     => '/creditnote',
            'method'  => 'post',
            'content' => [
                'currency'                      => 'INR',
                'amount'                    => '1000000',
                'name'                   => 'Test credit note',
                'customer_id'                  => 'cust_100000customer',
                'description'               => 'test description',
            ],
        ],
        'response' => [
            'content' => [
                'currency'          => 'INR',
                'customer_id'   => 'cust_100000customer',
                'name'       => 'Test credit note',
                'amount'       => '1000000',
                'description'     => 'test description',
                'amount_available'   => '1000000',
                'amount_refunded'   => 0,
                'amount_allocated'   => 0,
            ],
        ],
    ],

    'testCreateCreditNoteWithoutCustomer' => [
        'request'  => [
            'url'     => '/creditnote',
            'method'  => 'post',
            'content' => [
                'currency'                      => 'INR',
                'amount'                    => '1000000',
                'name'                   => 'Test credit note',
                'customer_id'                  => null,
                'description'               => 'test description',
            ],
        ],
        'response' => [
            'content' => [
                'currency'          => 'INR',
                'customer_id'   => null,
                'name'       => 'Test credit note',
                'amount'       => '1000000',
                'description'     => 'test description',
                'amount_available'   => '1000000',
                'amount_refunded'   => 0,
                'amount_allocated'   => 0,
            ],
        ],
    ],

    'testApplyCreditNoteWithSingleInvoice' => [
        'request'  => [
            'url'     => '/creditnote/',
            'method'  => 'post',
            'content' => [
                'action'   => 'refund',
                'invoices' => [],

            ],
        ],
        'response'  => [
            'content' => [
                'currency'      => 'INR',
                'customer_id'   => 'cust_100000customer',
                'name'          => 'Test credit note',
                'amount'        => 1000000,
                'description'      => 'test description',
                'amount_available' => 999000,
                'amount_refunded'  => 1000,
                'amount_allocated' => 0,
            ],
        ],
    ],

    'testApplyCreditNoteWithSingleInvoiceWithoutCustomer' => [
        'request'  => [
            'url'     => '/creditnote/',
            'method'  => 'post',
            'content' => [
                'action'   => 'refund',
                'invoices' => [],

            ],
        ],
        'response'  => [
            'content' => [
                'currency'      => 'INR',
                'customer_id'   => null,
                'name'          => 'Test credit note',
                'amount'        => 1000000,
                'description'      => 'test description',
                'amount_available' => 999000,
                'amount_refunded'  => 1000,
                'amount_allocated' => 0,
            ],
        ],
    ],

    'testApplyCreditNoteWithSingleInvoiceAndFullAmount' => [
        'request'  => [
            'url'     => '/creditnote/',
            'method'  => 'post',
            'content' => [
                'action'   => 'refund',
                'invoices' => [],

            ],
        ],
        'response'  => [
            'content' => [
                'currency'      => 'INR',
                'customer_id'   => 'cust_100000customer',
                'name'          => 'Test credit note',
                'amount'        => 1000,
                'description'      => 'test description',
                'amount_available' => 0,
                'amount_refunded'  => 1000,
                'amount_allocated' => 0,
            ],
        ],
    ],

    'testApplyCreditNoteWithSingleInvoiceAndFullAmountWithoutCustomer' => [
        'request'  => [
            'url'     => '/creditnote/',
            'method'  => 'post',
            'content' => [
                'action'   => 'refund',
                'invoices' => [],

            ],
        ],
        'response'  => [
            'content' => [
                'currency'      => 'INR',
                'customer_id'   => null,
                'name'          => 'Test credit note',
                'amount'        => 1000,
                'description'      => 'test description',
                'amount_available' => 0,
                'amount_refunded'  => 1000,
                'amount_allocated' => 0,
            ],
        ],
    ],
];

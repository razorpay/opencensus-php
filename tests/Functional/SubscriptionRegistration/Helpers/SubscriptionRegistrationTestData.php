<?php

namespace RZP\Tests\Functional\SubscriptionRegistration;

use RZP\Error\ErrorCode;

return [

    'testCreateAuthLinkWithoutMandate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '10000',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',

                ],
                'description'               => 'test description',
                'subscription_registration' => []
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 10000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithCardMandate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => '10000',
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 10000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithBankMandate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method' => 'emandate',

                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'           => 'issued',
                'sms_status'       => 'pending',
                'email_status'     => 'pending',
                'amount'           => 0,
                'currency'         => 'INR',
                'payment_id'       => null,
                'type'             => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithBankAccount' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method'       => 'emandate',
                    'expire_at'    => '1484512480',
                    'bank_account' => [
                        'bank_name'          => 'HDFC',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithIncompleteBankData' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method'       => 'emandate',
                    'expire_at'    => '1484512480',
                    'bank_account' => [
                        'bank_name'          => 'HDFC',
                        'ifsc_code'          => 'HDFC0001233',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'account_number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAuthLinkWithCardAndZeroAmount' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method' => 'card',

                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAuthLinkWithBankAndNonZeroAmount' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '1000',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method' => 'emandate',

                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchAuthLinks' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links/inv_1000000invoice',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchAuthLinksWithMandateAndBankAttributes' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links/inv_1000000invoice',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchTokenByMerchant' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testDeleteTokenByMerchant' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens/token_10000000000000',
            'method'  => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
        ],
    ],

    'testFetchDeletedTokenByMerchant' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens/token_10000000000000',
            'method'  => 'get',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testPayAuthLinkAndCopyNotes' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => '0',
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',
                'notes'       => [
                    'note_key_1' => 'note_value_1',
                ],
                'subscription_registration' => [
                    'method'  => 'emandate',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
                'notes'        => [
                    'note_key_1' => 'note_value_1',
                ],
            ],
        ],
    ],

    'testPayAuthLink' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => '10000',
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',
                'notes'       => ['note_key_1' => 'note_value_1'],

                'subscription_registration' => [
                    'method' => 'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 10000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
                'notes'       => ['note_key_1' => 'note_value_1'],
            ],
        ],
    ],

    'testFutureTokenConfirmedEmandateLinks' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => '0',
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',
                'notes'       => [
                    'note_key_1' => 'note_value_1',
                ],
                'subscription_registration' => [
                    'method'  => 'emandate',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
                'notes'        => [
                    'note_key_1' => 'note_value_1',
                ],
            ],
        ],
    ],

    'testCancelAuthLinkWithCardMandate' => [
        'request' => [
            'url' => '/subscription_registration/auth_links/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id' => 'inv_1000000invoice',
                'entity' => 'invoice',
                'status' => 'cancelled',
            ],
        ],
    ],

    'testCancelAuthLinkWithBankMandate' => [
        'request' => [
            'url' => '/subscription_registration/auth_links/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id' => 'inv_1000000invoice',
                'entity' => 'invoice',
                'status' => 'cancelled',
            ],
        ],
    ],

    'testCancelAuthLinksViaBatch' => [
        'request' => [
            'url' => '/subscription_registration/auth_links/batch/batch_100000000batch/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],
    // ----------------------------------------------------------------------
];

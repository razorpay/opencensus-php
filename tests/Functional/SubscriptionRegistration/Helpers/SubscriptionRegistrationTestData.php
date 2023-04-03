<?php

namespace RZP\Tests\Functional\SubscriptionRegistration;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 10000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkNullMandate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '10000',
                'currency'                  => 'INR',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
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
                'amount'       => 10000,
                'currency'     => 'INR',
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 10000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkInternationalCard' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => '100',
                'currency'    => 'USD',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'International description',

                'subscription_registration' => [
                    'method' => 'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 100,
                'currency'     => 'USD',
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
                'sms_status'       => 'sent',
                'email_status'     => 'sent',
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
                    'expire_at'    => Carbon::now()->addDay(30)->getTimestamp(),
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithPastExpireAtValue' => [
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
                    'expire_at' => Carbon::now()->subDay(1)->getTimestamp()
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'expire_at',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testAutoChargeEmandateToken' => [
        'request'  => [
            'url'     => '/subscription_registration/auto_charge',
            'method'  => 'post',
            'content' => [
                ],
        ],
        'response' => [
            'content' => [

            ],
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
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

    'testResendAuthLinkViaSms' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify_by/sms',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => TRUE,
            ],
        ],
    ],

    'testResendAuthLinkViaEmail' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify_by/email',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => TRUE,
            ],
        ],
    ],

    'testCreateAuthLinkWithBankAndNonZeroAmountAllowed' => [
        'request' => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '1500',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method'       => 'emandate',
                    'auth_type'    => 'netbanking',
                    'expire_at'    => Carbon::now()->addDay(30)->getTimestamp(),
                    'bank_account' => [
                        'bank_name'          => 'ICIC',
                        'ifsc_code'          => 'ICIC0004245',
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
                'amount'       => 1500,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testCreateAuthLinkWithUPIAndMaxAllowedAmount' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                    'type'     => 'link',
                    'amount'   => '510000',
                    'customer' => [
                        'email'   => 'test@razorpay.com',
                        'contact' => '9999999999',
                        'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method'     => 'upi',
                    'max_amount' => 21000000,
                    'frequency'  => 'monthly'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Max amount for UPI recurring payment cannot be greater than Rs. 200000.00'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFirstChargeAmountInAuthLinkCreate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'receipt'     => '00000000000001',
                'amount'      => 0,
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'emandate',
                    'first_payment_amount' => 1100,
                    'max_amount' => 10000,
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testMinFirstChargeAmountInAuthLinkCreate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'receipt'     => '00000000000001',
                'amount'      => 0,
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'emandate',
                    'first_payment_amount' => 1,
                    'max_amount' => 10000,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The first payment amount must be atleast INR 1.00',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFirstChargeAmountGreaterThanMaxAmount' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'receipt'     => '00000000000001',
                'amount'      => 0,
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'emandate',
                    'first_payment_amount' => 11000,
                    'max_amount' => 10000,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'first payment amount cannot be greater than maximum amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDefaultMaxAmountAuthLinkCreate' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'receipt'     => '00000000000001',
                'amount'      => 0,
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'emandate',
                    'first_payment_amount' => 1100,
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
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 0,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'link',
            ],
        ],
    ],

    'testListTokens' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens',
            'method'  => 'get',
            'content' => [
                'skip'  => 0,
                'count' => 25,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testListTokensWithFilters' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens',
            'method'  => 'get',
            'content' => [
                'skip'  => 0,
                'count' => 25,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testListTokensWithPaymentIdFilter' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens',
            'method'  => 'get',
            'content' => [
                'skip'  => 0,
                'count' => 25,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testListTokensRecurringStatusFilter' => [
        'request'  => [
            'url'     => '/subscription_registration/tokens',
            'method'  => 'get',
            'content' => [
                'skip'  => 0,
                'count' => 25,
                'recurring_status' => 'confirmed',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateAuthLinkBlankContact' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '',
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
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTH_LINK_CONTACT_EMPTY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAuthLinkBlankContactIgnoreFeatureFlag' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '',
                    'name'    => 'test',
                ],
                'description'               => 'test description',
                'subscription_registration' => [
                    'method'       => 'emandate',
                    'expire_at'    => Carbon::now()->addDay(30)->getTimestamp(),
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
        'response'  => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testCreateAuthLinkBlankEmail' => [
        'request'   => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'                      => 'link',
                'amount'                    => '0',
                'receipt'                   => '00000000000001',
                'customer'                  => [
                    'email'   => '',
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
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTH_LINK_EMAIL_EMPTY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    // ----------------------------------------------------------------------
];

<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [

    'testSmartRouting_CreatePayout_RoutingFailureDueToAmountValidation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 10,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Minimum transaction amount should be 100 paise',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSmartRouting_CreatePayout_RoutingFailureDueToAmountBreachingUpperThreshold' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 100000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 10000000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSmartRouting_CreatePayoutFailureDueToMissingAccountNumber' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 10000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSmartRouting_WithRoutingChoosingDirectAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testSmartRouting_CreatePayoutWithNoValidAccountsForRouting' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'API payouts are not available for this account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRouting_WithRoutingChoosingLiteAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testSmartRouting_CreatePayoutWithRoutingForDashboardPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testSmartRouting_CreateCompositePayoutWithRouting' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Prashanth YV',
                        'ifsc'           => 'SBIN0007105',
                        'account_number' => '111000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'IMPS',
                'tax'          => 162,
                'fees'         => 1062,
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Prashanth YV',
                        'notes'          => [],
                        'account_number' => '111000'
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForInternalPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'mode'                 => 'NEFT',
                'amount'               => 20000,
                'origin'               => 'dashboard',
                'purpose'              => 'salary',
                'currency'             => 'INR',
                'narration'            => 'RameshParekh Salary Jan 2021',
                'fund_account'         => [
                    'contact'      => [
                        'name'         => 'Manpreet Balan',
                        'type'         => 'vendor',
                        'email'        => 'manpreet.balan@mailinator.com',
                        'notes'        => [
                            'notes_key_1' => 'Ramesh-Parekh Salary Jan 2021',
                        ],
                        'contact'      => '8147309514',
                        'reference_id' => '2149',
                    ],
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0005943',
                        'name'           => 'Manpreet Balan',
                        'account_number' => '2598294895',
                    ],
                ],
                'reference_id'         => 'xpayroll_merchant_payouts_161',
                'source_details'       => [
                    0 => [
                        'priority'    => '1',
                        'source_id'   => 'xpayroll_merchant_payouts_161',
                        'source_type' => 'xpayroll',
                    ],
                ],
                'queue_if_low_balance' => true,
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'entity'       => 'payout',
                'mode'         => 'NEFT',
                'amount'       => 20000,
                'origin'       => 'dashboard',
                'purpose'      => 'salary',
                'currency'     => 'INR',
                'narration'    => 'RameshParekh Salary Jan 2021',
                'fund_account' => [
                    'contact'      => [
                        'name'         => 'Manpreet Balan',
                        'type'         => 'vendor',
                        'email'        => 'manpreet.balan@mailinator.com',
                        'notes'        => [
                            'notes_key_1' => 'Ramesh-Parekh Salary Jan 2021',
                        ],
                        'contact'      => '8147309514',
                        'reference_id' => '2149',
                    ],
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0005943',
                        'name'           => 'Manpreet Balan',
                        'account_number' => '2598294895',
                    ],
                ],
                'reference_id' => 'xpayroll_merchant_payouts_161',
                'source_details' => [
                    0 => [
                        'priority'    => 1,
                        'source_id'   => 'xpayroll_merchant_payouts_161',
                        'source_type' => 'xpayroll',
                    ],
                ]
            ]
        ]
    ],

    'testSmartRouting_CheckSmartRoutingForScheduledPayouts' => [
        'request'  => [
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'scheduled_at'    => Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp(),
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'scheduled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForPayoutToCards' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'business disbursal',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card'         => [
                        'name'         => 'Prashanth YV',
                        'number'       => '340169570990137',
                        'cvv'          => '123',
                        'expiry_month' => 8,
                        'expiry_year'  => 2025,
                        'input_type'   => 'card'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth 98',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 20000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'business disbursal',
                'status'       => 'processing',
                'mode'         => 'card',
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card'         => [
                        'last4'      => '0137',
                        'network'    => 'MasterCard',
                        'type'       => 'credit',
                        'issuer'     => 'YESB',
                        'input_type' => 'card',
                    ],
                ],
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForAmazonPayWalletPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'         => 2000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'amazonpay',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'wallet',
                    'wallet' => [
                        'provider' => 'amazonpay',
                        'phone'    => '+918124632237',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'amazonpay',
                'tax'          => 90,
                'fees'         => 590,
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'wallet',
                    'wallet' => [
                        'provider' => 'amazonpay',
                        'phone'    => '+918124632237'
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForBulkPayouts' => [
        'request'   => [
            'server'  => [
                'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
                'HTTP_X_Creator_Type' => 'user',
                'HTTP_X_Creator_Id'   => 'MerchantUser01'
            ],
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'payout'                    => [
                        'amount_in_rupees'      => '10.23',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 1023,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'amount'                => 1613,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 1613,
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForPayoutsToFundAccountTypeVpa' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000003fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'UPI',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForMerchantPayouts' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1000,
                'merchant_id'    => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testSmartRouting_CheckSmartRoutingForCustomerWalletPayouts' => [
        'request'  => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 800,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'customer_id'     => 'cust_100000customer',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
                'amount'          => 800,
                'status'          => 'processing',
            ]
        ],
    ],

    'testSmartRoutingSummary_ModeIMPS_SharedPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'IMPS',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => [
                    "success_rate_with_mar" => 100,
                    "success_rate_without_mar" => 66.67,
                    "total_payouts" => 15,
                    "total_primary_successful_payouts" => 5,
                    "total_secondary_successful_payouts" => 10,
                    "total_payouts_from_primary_channel" => 5,
                    "total_payouts_from_secondary_channel" => 10,
                    "total_payouts_amount" => 1500,
                    "total_payouts_processed_amount" => 1500,
                    "total_payouts_amount_from_primary_channel" => 500,
                    "total_payouts_amount_from_secondary_channel" => 1000,
                    "total_payouts_processed_amount_from_primary_channel" => 500,
                    "total_payouts_processed_amount_from_secondary_channel" => 1000
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeIMPS_DirectPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'IMPS',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => [
                    "success_rate_with_mar" => 100,
                    "success_rate_without_mar" => 66.67,
                    "total_payouts" => 15,
                    "total_primary_successful_payouts" => 5,
                    "total_secondary_successful_payouts" => 10,
                    "total_payouts_from_primary_channel" => 5,
                    "total_payouts_from_secondary_channel" => 10,
                    "total_payouts_amount" => 1500,
                    "total_payouts_processed_amount" => 1500,
                    "total_payouts_amount_from_primary_channel" => 500,
                    "total_payouts_amount_from_secondary_channel" => 1000,
                    "total_payouts_processed_amount_from_primary_channel" => 500,
                    "total_payouts_processed_amount_from_secondary_channel" => 1000
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeUPI_SharedPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'UPI',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "UPI" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeUPI_DirectPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'UPI',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "UPI" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeAll_SharedPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'ALL',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
                "UPI" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeAll_DirectPriority' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'ALL',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
                "UPI" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_FailureDueToInvalidMode' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'INVALID_MODE',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \Exception::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRoutingSummary_FailureFtsServerError' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'IMPS',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \Exception::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRoutingSummary_FailureDueToInvalidFTSResponse' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'IMPS',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \Exception::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRoutingSummary_ModeALL_IMPSDoesNotExistsUPIExists' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'ALL',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => null,
                "UPI" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
            ],
        ],
    ],

    'testSmartRoutingSummary_ModeALL_IMPSExistsUPIDoesNotExists' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_summary',
            'content' => [
                'mode' => 'ALL',
                'start_time' => time() - 1000000,
                'end_time' => time(),
            ],
        ],
        'response' => [
            'content' => [
                "IMPS" => [
                    'success_rate_with_mar' => 100,
                    'success_rate_without_mar' => 75,
                    'total_payouts' => 10,
                    'total_primary_successful_payouts' => 5,
                    'total_secondary_successful_payouts' => 5,
                    'total_payouts_from_primary_channel' => 5,
                    'total_payouts_from_secondary_channel' => 5,
                    'total_payouts_amount' => 1000,
                    'total_payouts_processed_amount' => 1000,
                    'total_payouts_amount_from_primary_channel' => 500,
                    'total_payouts_amount_from_secondary_channel' => 500,
                    'total_payouts_processed_amount_from_primary_channel' => 500,
                    'total_payouts_processed_amount_from_secondary_channel' => 500
                ],
                "UPI" => null,
            ],
        ],
    ],

    'testSmartRoutingRules_FetchRulesForMerchant_UPIEnabled_Success' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'IMPS' => [
                    'RBL',
                    'ICICI',
                    'SHARED'
                ],
                'NEFT' => [
                    'ICICI',
                    'RBL',
                    'SHARED'
                ],
                'UPI' => [
                    'RBL',
                    'ICICI'
                ]
            ],
        ],
    ],

    'testSmartRoutingRules_FetchRulesForMerchant_UPINotEnabled_Success' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'IMPS' => [
                    'RBL',
                    'ICICI',
                    'SHARED'
                ],
                'NEFT' => [
                    'ICICI',
                    'RBL',
                    'SHARED'
                ]
            ],
        ],
    ],

    'testSmartRoutingRules_FetchRulesForMerchant_NoActiveSharedAccountsFoundForMerchant' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'IMPS' => [
                    'RBL',
                    'ICICI',
                ],
                'NEFT' => [
                    'ICICI',
                    'RBL',
                ]
            ],
        ],
    ],

    'testSmartRoutingRules_FetchRulesForMerchant_FTSNoRulesFoundForMerchant' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \Exception::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRoutingRules_FetchRulesForMerchant_NoActiveDirectAccountsFoundForMerchant' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \Exception::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSmartRoutingRules_ModifyRulesForMerchant_Success' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/smart_routing_rules',
            'content' => [
                'merchant_id' => '10000000000000',
                'merchant_customized_priority_rules' => [
                    'IMPS' => [
                        'RBL',
                        'ICICI',
                        'SHARED'
                    ],
                    'NEFT' => [
                        'ICICI',
                        'RBL',
                        'SHARED'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'IMPS' => [
                    'RBL',
                    'ICICI',
                    'SHARED'
                ],
                'NEFT' => [
                    'ICICI',
                    'RBL',
                    'SHARED'
                ]
            ],
        ],
    ],
];

<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestException;

return [
    'testCreateCompositePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutForWalletAccountTypeAmazonpay' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutWithOldNewIfsc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                        'ifsc'           => 'ORBC0101685',
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
                        'ifsc'           => 'ORBC0101685',
                        'bank_name'      => 'Oriental Bank of Commerce',
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

    'testCreateCompositePayoutWithOldNewIfscWithExistingAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                        'ifsc'           => 'ORBC0101685',
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
                        'ifsc'           => 'ORBC0101685',
                        'bank_name'      => 'Oriental Bank of Commerce',
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

    'testCreateCompositePayoutWithOldCreditsFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                'tax'          => 0,
                'fees'         => 900,
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

    'testCreateCompositePayoutWithNewCreditsFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutWithNewCreditsFlowMerchantWithCredits' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                'tax'          => 0,
                'fees'         => 900,
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

    'testCreateCompositePayoutWithNewCreditsFlowMerchantWithNoCredits' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                'tax'          => 0,
                'fees'         => 900,
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

    'testCreateCompositePayoutWithDuplicateContactDifferentFundAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                        'ifsc'           => 'HDFC0001234',
                        'account_number' => '222000'
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
                        'ifsc'           => 'HDFC0001234',
                        'bank_name'      => 'HDFC Bank',
                        'name'           => 'Prashanth YV',
                        'notes'          => [],
                        'account_number' => '222000'
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

    'testCreateCompositePayoutWithFundAccountId' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'fund_account_id' => 'fa_100000000000fa',
                'fund_account'    => [
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'fund_account_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateCompositePayoutWithContactId' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                    'contact_id'   => '1000001contact',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'fund_account.contact_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateCompositePayoutWithContactValidationFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithFundAccountValidationFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account type field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithPayoutValidationFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The purpose field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithoutFundAccountIdAndFundAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fund account id field is required when fund account is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutForNonSavedCardFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
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
                'purpose'      => 'payout',
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

    'testCreateCompositePayoutForNonSavedCardFlowAndReceiveProcessedWebhookForMCS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutToThirdPartyTokenisedCardThroughBankRails' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card'         => [
                        'name'           => 'Prashanth YV',
                        'number'         => '340169570990137',
                        'expiry_month'   => 8,
                        'expiry_year'    => 2025,
                        'input_type'     => 'service_provider_token',
                        'token_provider' => 'xyz'
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
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout mode is not supported for tokenised cards',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS,
        ],
    ],

    'testCreateCompositePayoutToRzpTokenisedCardThroughBankRails' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card'         => [
                        'token_id'       => 'token_100000000token',
                        'input_type'     => 'razorpay_token',
                        'token_provider' => 'razorpay'
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
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout mode is not supported for tokenised cards',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS,
        ],
    ],

    'testCreateCompositePayoutWithTokenisedCardFromOtherTSP' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card'         => [
                        'number'         => '4610151724696781',
                        'expiry_month'   => 8,
                        'expiry_year'    => 2025,
                        'input_type'     => 'service_provider_token',
                        'token_provider' => 'xyz'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth',
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
                'purpose'      => 'payout',
                'status'       => 'processing',
                'mode'         => 'card',
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card'         => [
                    ],
                ],
            ],
        ],
    ],

    'testCreateCompositePayoutForTokenisedRzpSavedCardFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card'         => [
                        'token_id'       => 'token_100000000token',
                        'input_type'     => 'razorpay_token',
                        'token_provider' => 'razorpay'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth',
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
                'purpose'      => 'payout',
                'status'       => 'processing',
                'mode'         => 'card',
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card'         => [
                    ],
                ],
            ],
        ],
    ],

    'testCreateCompositePayoutForCred' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name'      => 'Prashanth YV',
    		            'number'    => '04111111111111111',
                        'ifsc'      => 'KKBK0000430',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth',
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
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '1111',
                        'network'   =>  'Visa',
                        'type'      =>  'credit',
                        'issuer'    =>  'HDFC',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth',
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

    'testCreateCompositePayoutWithSkipWfAtPayoutAndSkipWorkflowTrue' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'skip_workflow'  => true,
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

    'testCreateCompositePayoutWithSkipWfAtPayoutAndSkipWorkflowFalse' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'skip_workflow'  => 0,
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only true is valid for skip_workflow key.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithInsufficientBalanceAndQueueFlagUnset' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testRetryCompositePayoutCreate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutWithOriginField' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'origin'         => 'dashboard',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'origin is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithSourceDetailsField' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'source_details'  => [
                    [
                        'source_id' => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority' => 1,
                    ],
                    [
                        'source_id' => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority' => 2,
                    ],
                ],
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'source_details is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithoutFundAccountIdAndFundAccountNewApiError' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fund account id field is required when fund account is not present.',
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositePayoutWithoutFundAccountIdAndFundAccountNewApiErrorOnLiveMode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fund account id field is required when fund account is not present.',
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCompositeM2PPayoutForDebitCardWithUpperCaseCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name' => 'Prashanth YV',
                        'number' => '340169570990137',
                        'cvv' => '123',
                        'expiry_month' => 8,
                        'expiry_year' => 2025
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
                'amount'       => 20000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'payout',
                'status'       => 'processing',
                'mode'         => 'card',
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'MasterCard',
                        'type'      =>  'debit',
                        'issuer'    =>  'YESB',
                    ],
                ],
            ],
        ],
    ],

    'testCreateCompositeM2PPayoutForDebitCardWithLowerCaseCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name' => 'Prashanth YV',
                        'number' => '340169570990137',
                        'cvv' => '123',
                        'expiry_month' => 8,
                        'expiry_year' => 2025
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
                'amount'       => 20000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'payout',
                'status'       => 'processing',
                'mode'         => 'card',
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'MasterCard',
                        'type'      =>  'debit',
                        'issuer'    =>  'YESB',
                    ],
                ],
            ],
        ],
    ],

    'testCreateCompositeM2PPayoutForDebitCardWithoutSupportedModes' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name' => 'Prashanth YV',
                        'number' => '340169570990137',
                        'cvv' => '123',
                        'expiry_month' => 8,
                        'expiry_year' => 2025
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card not supported for fund account creation',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateCompositeM2PPayoutForDebitCardMerchantBlockedByProduct' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name' => 'Prashanth YV',
                        'number' => '340169570990137',
                        'cvv' => '123',
                        'expiry_month' => 8,
                        'expiry_year' => 2025
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => \RZP\Error\PublicErrorDescription::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT,
        ],
    ],

    'testCreateCompositeM2PPayoutForDebitCardMerchantBlockedByNetwork' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 20000,
                'currency'       => 'INR',
                'purpose'        => 'payout',
                'narration'      => 'Batman',
                'mode'           => 'card',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name' => 'Prashanth YV',
                        'number' => '340169570990137',
                        'cvv' => '123',
                        'expiry_month' => 8,
                        'expiry_year' => 2025
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => \RZP\Error\PublicErrorDescription::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK,
        ],
    ],

    'testCreateCompositePayoutForWalletApiRequestNoMerchantDisabledField' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutForBankAccountApiRequestNoMerchantDisabledField' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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

    'testCreateCompositePayoutSelectsOldestActiveFundAccountWhenDuplicateFundAccountIsFound' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
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
                        'notes'   => [],
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
];

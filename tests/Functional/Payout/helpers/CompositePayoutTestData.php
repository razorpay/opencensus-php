<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
                    'account_type' => 'card',
                    'card' => [
                        'name'      => 'Prashanth YV',
                        'last4'     =>  '1111',
                        'network'   =>  'Visa',
                        'type'      =>  'credit',
                        'issuer'    =>  'HDFC',
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
];

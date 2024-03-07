<?php

use RZP\Models\Batch\Header;

return [

    'testBulkOnboardedTerminalEditValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'upi_onboarded_terminal_edit',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '6217',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '1',
                        Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '1',
                    ],
                ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceForUpiInstruments' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => true,
                            'http_status_code'   => 200,
                            'error'              => [
                                'code'        => '',
                                'description' => '',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '',
                            'Allow CC'           => '1',
                            'Allow Wallet'       => '1',
                            'Allow Credit Line'  => '1',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceForOneNonUpiInstrument' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => true,
                            'http_status_code'   => 200,
                            'error'              => [
                                'code'        => '',
                                'description' => '',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '1',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],
                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceForMultipleNonUpiInstruments' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'Updating too many categories for a single terminal is not allowed in one go.',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '1',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '1',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],
                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceForMixOfUpiAndNonUpiInstruments' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '0',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'Updating Merchant Size with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '',
                            'Allow CC'           => '1',
                            'Allow Wallet'       => '0',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '1',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],
                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithMccForUpiYesbank' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_yesbank',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '0',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '6217',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'validation.prohibited_if',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_yesbank',
                            'Online'             => '',
                            'Allow CC'           => '0',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '1',
                            'MCC'                => '6217',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithBillingLabelForUpiIcici' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_icici',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'validation.prohibited_if',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_icici',
                            'Online'             => '',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '1',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithIncorrectMcc' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_yesbank',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '62172',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => '62172 is an invalid MCC',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_yesbank',
                            'Online'             => '',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '62172',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithUnsupportedGateway' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_airtel',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '1',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'upi_airtel gateway is not supported for bulk terminal edit',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_airtel',
                            'Online'             => '1',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithAllFieldsEmpty' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => false,
                            'http_status_code'   => 400,
                            'error'              => [
                                'code'        => 'BAD_REQUEST_VALIDATION_FAILURE',
                                'description' => 'Empty row uploaded',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

    'testBulkOnboardedTerminalEditForBatchServiceWithOnlyOneFieldToEdit' => [
        'request'  => [
            'url'     => '/upi_onboarded_terminal_edit/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                                     => 'randomIdempotencyKey',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID       => 'term_10RandomTermId',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY           => 'upi_axis',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE            => '0',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC          => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET      => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC               => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL     => '',
                    Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER     => '',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' =>
                    [
                        [
                            'idempotency_key'    => 'randomIdempotencyKey',
                            'success'            => true,
                            'http_status_code'   => 200,
                            'error'              => [
                                'code'        => '',
                                'description' => '',
                            ],
                            'Terminal Id'        => 'term_10RandomTermId',
                            'Gateway'            => 'upi_axis',
                            'Online'             => '0',
                            'Allow CC'           => '',
                            'Allow Wallet'       => '',
                            'Allow Credit Line'  => '',
                            'Merchant Size'      => '',
                            'MCC'                => '',
                            'Edit Billing Label' => '',
                            'Edit Mobile Number' => '',
                        ],

                    ],
            ],
        ],
    ],

];

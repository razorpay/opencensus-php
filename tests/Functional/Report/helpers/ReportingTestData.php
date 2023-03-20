<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;

return [
    'testMerchantConfigsForInvalidReportType' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'content' => [

            ],
            'server' => [
                'HTTP_X-Report-Type' => 'random',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid report type',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGettingPartnerConfigsByNonPartner' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'content' => [

            ],
            'server' => [
                'HTTP_X-Report-Type' => 'partner',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testReportPrivilegeAuth' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
                'HTTP_X-Admin-Token' => Org::DEFAULT_ADMIN_TOKEN,
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant",
                        "type" => null,
                        "scheduled" => false,
                        "name" => "SBI Bulk Refunds",
                        "description" => "SBI Bulk Refunds",
                        "template" => [],
                        "pipeline_params" => null,
                        "emails" => null,
                        "created_by" => "100000Razorpay",
                        "status" => null,
                        "created_at" => 1565703916,
                        "updated_at" => 1565703916
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant",
                        "type" => "transactions",
                        "scheduled" => false,
                        "name" => "RX Account Statement",
                        "description" => "RX Account Statement",
                        "template" => [],
                        "pipeline_params" => [
                            "file_push" => [
                                "channel" => "beam",
                                "job_name" => "rzp_test_beam",
                                "protocol" => "sftp"
                            ],
                        ],
                        "emails" => null,
                        "created_by" => "100000Razorpay",
                        "status" => null,
                        "created_at" => 1553610628,
                        "updated_at" => 1553610628
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant",
                        "type" => "fund_accounts",
                        "scheduled" => false,
                        "name" => "RX fund accounts",
                        "description" => "RX fund_accounts",
                        "template" => [],
                        "pipeline_params" => [
                            "file_push" => [
                                "channel" => "beam",
                                "job_name" => "rzp_test_beam",
                                "protocol" => "sftp"
                            ]
                        ],
                        "emails" => null,
                        "created_by" => "BDRXQLRCjCiQ6U",
                        "status" => null,
                        "created_at" => 1551339252,
                        "updated_at" => 1551339252
                    ]
                ]
            ]
        ]
    ],

    'testReportXDashboardAuth' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 2,
                "items" => [
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant",
                        "type" => "transactions",
                        "scheduled" => false,
                        "name" => "RX Account Statement",
                        "description" => "RX Account Statement",
                        "template" => [
                            "formats" => [
                                "date" => "d/m/Y H =>i =>s"
                            ],
                            "fields_map" => [
                                "tax" => [
                                    "payouts.tax"
                                ],
                                "utr" => [
                                    "payouts.utr"
                                ],
                                "mode" => [
                                    "payouts.mode"
                                ],
                                "amount" => [
                                    "transactions.amount"
                                ],
                                "currency" => [
                                    "transactions.currency"
                                ],
                                "source_id" => [
                                    "transactions.entity_id"
                                ],
                                "created_at" => [
                                    "transactions.created_at"
                                ],
                                "source_type" => [
                                    "transactions.type"
                                ],
                                "account_number" => [
                                    "balance.account_number"
                                ],
                                "transaction_id" => [
                                    "transactions.id"
                                ],
                                "closing_balance" => [
                                    "transactions.balance"
                                ],
                                "fees (tax inclusive)" => [
                                    "payouts.fees"
                                ]
                            ],
                            "output_fields" => [
                                "transaction_id",
                                "account_number",
                                "amount",
                                "currency",
                                "closing_balance",
                                "utr",
                                "mode",
                                "source_type",
                                "source_id",
                                "fees (tax inclusive)",
                                "tax",
                                "created_at"
                            ],
                            "attach_to_email" => true
                        ],
                        "pipeline_params" => [
                            "file_push" => [
                                "channel" => "beam",
                                "job_name" => "rzp_test_beam",
                                "protocol" => "sftp"
                            ],
                        ],
                        "emails" => null,
                        "created_by" => "100000Razorpay",
                        "status" => null,
                        "created_at" => 1553610628,
                        "updated_at" => 1553610628
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant",
                        "type" => "fund_accounts",
                        "scheduled" => false,
                        "name" => "RX fund accounts",
                        "description" => "RX fund_accounts",
                        "template" => [
                            "formats" => [
                                "date" => "d/m/Y H =>i =>s"
                            ],
                            "file_meta" => [
                                "filename" => "RZP_FUND_ACCOUNTS_{date=Ymd}"
                            ],
                            "fields_map" => [
                                "contact_id" => [
                                    "contacts.id"
                                ],
                                "contact_name" => [
                                    "contacts.name"
                                ],
                                "contact_type" => [
                                    "contacts.type"
                                ],
                                "contact_email" => [
                                    "contacts.email"
                                ],
                                "contact_notes" => [
                                    "contacts.notes"
                                ],
                                "contact_active" => [
                                    "contacts.active"
                                ],
                                "contact_mobile" => [
                                    "contacts.contact"
                                ],
                                "fund_account_id" => [
                                    "fund_accounts.id"
                                ],
                                "fund_account_ifsc" => [
                                    "bank_accounts.ifsc_code"
                                ],
                                "fund_account_name" => [
                                    "bank_accounts.beneficiary_name"
                                ],
                                "fund_account_type" => [
                                    "fund_accounts.account_type"
                                ],
                                "contact_created_at" => [
                                    "contacts.created_at"
                                ],
                                "fund_account_active" => [
                                    "fund_accounts.active"
                                ],
                                "fund_account_number" => [
                                    "bank_accounts.account_number"
                                ],
                                "contact_reference_id" => [
                                    "contacts.reference_id"
                                ],
                                "fund_account_created_at" => [
                                    "fund_accounts.created_at"
                                ],
                                "fund_account_vpa_address" => [
                                    "vpas.address"
                                ]
                            ],
                            "output_fields" => [
                                "contact_id",
                                "contact_name",
                                "contact_type",
                                "contact_mobile",
                                "contact_email",
                                "contact_reference_id",
                                "contact_active",
                                "contact_notes",
                                "contact_created_at",
                                "fund_account_id",
                                "fund_account_type",
                                "fund_account_ifsc",
                                "fund_account_number",
                                "fund_account_name",
                                "fund_account_vpa_address",
                                "fund_account_active",
                                "fund_account_created_at"
                            ],
                            "attach_to_email" => true
                        ],
                        "pipeline_params" => [
                            "file_push" => [
                                "channel" => "beam",
                                "job_name" => "rzp_test_beam",
                                "protocol" => "sftp"
                            ]
                        ],
                        "emails" => null,
                        "created_by" => "BDRXQLRCjCiQ6U",
                        "status" => null,
                        "created_at" => 1551339252,
                        "updated_at" => 1551339252
                    ]
                ]
            ]
        ]
    ],

    'testReportPgDashboardAuth' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Report-Type' => 'merchant'
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 1,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant",
                        "type" => null,
                        "scheduled" => false,
                        "name" => "SBI Bulk Refunds",
                        "description" => "SBI Bulk Refunds",
                        "template" => [
                            "raw_sql" => [
                                "query" => ""
                            ],
                            "file_meta" => [
                                "filename" => "RAZORPAY_{checksum}",
                                "delimiter" => "|",
                                "extension" => "txt"
                            ]
                        ],
                        "pipeline_params" => null,
                        "emails" => null,
                        "created_by" => "100000Razorpay",
                        "status" => null,
                        "created_at" => 1565703916,
                        "updated_at" => 1565703916
                    ]
                ]
            ]
        ]
    ],

    'testPGReportLogForInvalidEmails' => [
        'request' => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"     => "config_D5RAgPWrrUgP9K",
                "start_time"    => "1614537000",
                "end_time"      => "1617215399",
                "send_email"    => true,
                "emails"        => ["test2@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'test2@razorpay.com is not a registered email address',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPGReportLogForValidEmails' => [
        'request' => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"     => "config_D5RAgPWrrUgP9K",
                "start_time"    => "1614537000",
                "end_time"      => "1617215399",
                "send_email"    => false,
                "emails"        => ["test@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testRXReportLogSkipEmailValidation' => [
    'request' => [
        'url'     => '/reporting/logs',
        'method'  => 'POST',
        'content' => [
            "config_id"     => "config_G8AP5UeeaLLWj6",
            "start_time"    => "1614537000",
            "end_time"      => "1617215399",
            "send_email"    => true,
            "emails"        => ["test3@razorpay.com"],
        ],
        'server' => [
            'HTTP_X-Report-Type' => 'razorpayx',
        ],
    ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
],

    'testRXReportLogForInvalidEmails' => [
        'request' => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"     => "config_HABdF4z6EKiBth",
                "start_time"    => "1614537000",
                "end_time"      => "1617215399",
                "send_email"    => true,
                "emails"        => ["test3@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'razorpayx',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'test3@razorpay.com is not a registered email address',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRXReportLogForValidEmails' => [
        'request' => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"     => "config_HABdF4z6EKiBth",
                "start_time"    => "1614537000",
                "end_time"      => "1617215399",
                "send_email"    => true,
                "emails"        => ["test2@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testRXReportingLogForValidMasterAndSubMerchantIds' => [
        'request'  => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"  => "config_HABdF4z6EKiBth",
                "start_time" => "1614537000",
                "end_time"   => "1617215399",
                "send_email" => true,
                "emails"     => ["test2@razorpay.com"],
                "sub_merchant_ids" => ["sub_merchant_1"],
            ],
            'server'  => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count"  => 3,
                "items"  => [
                    [
                        "id"          => "config_D5RAgPWrrUgP9K",
                        "consumer"    => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id"          => "config_CC39ZQphE0ox5U",
                        "consumer"    => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id"          => "config_C1eAjMzFDEU074",
                        "consumer"    => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]

    ],

    'testRXReportingLogForValidMasterMerchantAndInvalidSubMerchantIds' => [
        'request'  => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"  => "config_HABdF4z6EKiBth",
                "start_time" => "1614537000",
                "end_time"   => "1617215399",
                "send_email" => true,
                "emails"     => ["test2@razorpay.com"],
                "sub_merchant_ids" => ["sub_merchant_1", "sub_merchant_3"],
            ],
            'server'  => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid sub_merchant_ids list in input',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRXReportingLogForInvalidMasterMerchant' => [
        'request'  => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"  => "config_HABdF4z6EKiBth",
                "start_time" => "1614537000",
                "end_time"   => "1617215399",
                "send_email" => true,
                "emails"     => ["test2@razorpay.com"],
                "sub_merchant_ids" => ["sub_merchant_1", "sub_merchant_3"],
            ],
            'server'  => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid master merchant id: 10000000000000',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRXReportingLogForInvalidPayerMerchantIdInFilters' => [
        'request'  => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"  => "config_HABdF4z6EKiBth",
                "start_time" => "1614537000",
                "end_time"   => "1617215399",
                "send_email" => true,
                "emails"     => ["test2@razorpay.com"],
                "sub_merchant_ids" => ["sub_merchant_1", "sub_merchant_2"],
                'template_overrides' => [
                    'filters' => [
                        'credit_transfers' => [
                            'payer_merchant_id' => [
                                'op' => ['IN'],
                                'values' => ['10000000000001']
                            ]
                        ]
                    ]
                ]
            ],
            'server'  => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testRXReportingLogForInvalidAccountNumbersInFilters' => [
        'request'  => [
            'url'     => '/reporting/logs',
            'method'  => 'POST',
            'content' => [
                "config_id"  => "config_HABdF4z6EKiBth",
                "start_time" => "1614537000",
                "end_time"   => "1617215399",
                "send_email" => true,
                "emails"     => ["test2@razorpay.com"],
                "sub_merchant_ids" => ["sub_merchant_1", "sub_merchant_2"],
                'template_overrides' => [
                    'filters' => [
                        'balance' => [
                            'account_number' => [
                                'op' => ['IN'],
                                'values' => [
                                    '343411111111', //correct account number
                                    '343488888888', //wrong account number
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'server'  => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testPGReportLogEditForInvalidEmails' => [
        'request' => [
            'url'     => '/reporting/logs/log_100000Squirtle',
            'method'  => 'PATCH',
            'content' => [
                "send_email"    => true,
                "emails"        => ["test2@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'test2@razorpay.com is not a registered email address',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPGReportLogEditForValidEmails' => [
        'request' => [
            'url'     => '/reporting/logs/log_100000Squirtle',
            'method'  => 'PATCH',
            'content' => [
                "send_email"    => false,
                "emails"        => ["test@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testRXReportLogEditForInvalidEmails' => [
        'request' => [
            'url'     => '/reporting/logs/log_100000Squirtle',
            'method'  => 'PATCH',
            'content' => [
                "send_email"    => true,
                "emails"        => ["test3@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'razorpayx',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'test3@razorpay.com is not a registered email address',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRXReportLogEditForValidEmails' => [
        'request' => [
            'url'     => '/reporting/logs/log_100000Squirtle',
            'method'  => 'PATCH',
            'content' => [
                "send_email"    => true,
                "emails"        => ["test2@razorpay.com"],
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'razorpayx'
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testConfigCreateForInvalidFeatures' => [
        'request'  => [
            'url'     => '/reporting/configs',
            'method'  => 'post',
            'content' => [
                'type'              => 'payments',
                'scheduled'         => false,
                'name'              => 'Config Test 1',
                'description'       => 'Config Test 1 - description',
                'template'          => [],
                'created_by'        => '20000000000000',
                'feature_names'     => ["dummy123"]
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'merchant',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid features',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testConfigCreateWithAdminAuthValid' => [
        'request' => [
            'url'     => '/admin-reporting/configs',
            'method'  => 'POST',
            'content' => [
                "type"     => "payments",
                "template" => []
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'admin',
                'HTTP_X-Consumer'    => '100000razorpay'
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testConfigCreateWithAdminAuthInvalid' => [
        'request' => [
            'url'     => '/admin-reporting/configs',
            'method'  => 'POST',
            'content' => [
                "type"     => "payments",
                "template" => []
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'admin',
                'HTTP_X-Consumer'    => '20000000000000'
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ]
    ],

    'testConfigUpdateWithAdminAuthValid' => [
        'request' => [
            'url'     => '/admin-reporting/configs/config_D5RAgPWrrUgP9K',
            'method'  => 'PATCH',
            'content' => [
                "type"     => "payments",
                "template" => []
            ],
            'server' => [
                'HTTP_X-Report-Type' => 'admin',
                'HTTP_X-Consumer'    => '100000razorpay'
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

    'testConfigDeleteWithAdminAuthValid' => [
        'request' => [
            'url'     => '/admin-reporting/configs/config_D5RAgPWrrUgP9K',
            'method'  => 'DELETE',
            'content' => [],
            'server' => [
                'HTTP_X-Report-Type' => 'admin',
                'HTTP_X-Consumer'    => '100000razorpay'
            ],
        ],
        'response' => [
            'content'   => [
                "entity" => "collection",
                "count" => 3,
                "items" => [
                    [
                        "id" => "config_D5RAgPWrrUgP9K",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_CC39ZQphE0ox5U",
                        "consumer" => "100000Razorpay",
                        "report_type" => "merchant"
                    ],
                    [
                        "id" => "config_C1eAjMzFDEU074",
                        "consumer" => "10000000000000",
                        "report_type" => "merchant"
                    ]
                ]
            ]
        ]
    ],

];

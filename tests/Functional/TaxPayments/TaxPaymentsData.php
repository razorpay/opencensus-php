<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSettingsInternalApiAddOrUpdate'                                  => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => [
                'test_key' => 'test_value'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],
    'testSettingsInternalApiGet'                                          => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => []
        ],
        'response' => [
            'content' => [
                'settings' => [
                    'test_key' => 'test_value'
                ]
            ],
        ]
    ],
    'testTaxPaymentSettingGetCallsServiceMethods'                         => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentSettingAddOrUpdateCallsServiceMethods'                 => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetTaxPaymentCallsServiceMethod'                                 => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/txpy_1234',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testListTaxPaymentCallsServiceMethod'                                => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testPayTaxPaymentCallsServiceMethod'                                 => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/some_payment_id/pay',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPayContactCreationFailsWhenNotVendorPaymentApp'               => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/contacts',
            'content' => [
                'name' => 'some name',
                'type' => 'rzp_tax_pay',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ]
    ],
    'testTaxContactCreationSuccessWithTheRightVendorApp'                  => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/contacts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'name' => 'test_name',
                'type' => 'rzp_tax_pay',
            ],
        ],
        'response' => [
            'content'     => [
                'name'   => 'test_name',
                'entity' => 'contact',
                'type'   => 'rzp_tax_pay'
            ],
            'status_code' => '201',
        ]
    ],
    'testTaxPayFundAccountCreationFailsWhenNotVendorPaymentApp'           => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name'           => 'asdsd',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ]
    ],
    'testTaxFundAccountCreationSuccessWithTheRightVendorApp'              => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => '',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000020',
                    'bank_name'      => 'ICICI Bank',
                    'name'           => 'test name',
                    'notes'          => [],
                    'account_number' => '000205031288',
                ]
            ],
            'status_code' => 201,
        ],
    ],
    'testTaxPaymentInternalContactUpdateForbidden'                        => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/contacts/',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'name' => 'new name'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ]
    ],
    'testUpdatingContactTypeToInternalContactForbidden'                   => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/contacts/',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'type' => 'rzp_tax_pay'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid type: rzp_tax_pay',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testPayoutCreateOnRzpInternalContactSucceeds'                        => [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'payout',
                'amount'    => 2000000,
                'currency'  => 'INR',
                'narration' => 'Batman',
                'purpose'   => 'refund',
                'status'    => 'processing',
                'mode'      => 'IMPS',
                'tax'       => 162,
                'fees'      => 1062,
                'notes'     => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],
    'testPayoutInternalPayoutRouteFailsWhenFundAccountIdMissing'          => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
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
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_ID_IS_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testPayoutInternalPayoutRouteFailsWhenContactIsNotInternalType'      => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_ONLY_INTERNAL_CONTACT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testInternalPayoutFailsWhenInternalContactIsRestrictedForCurrentApp' => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_APP_NOT_PERMITTED_TO_CREATE_PAYOUT_ON_THIS_CONTACT_TYPE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testBulkPayTaxPaymentCallsServiceMethod'                             => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/bulk-pay',
        ],
        'response' => [
            'content' => []
        ]
    ],
];

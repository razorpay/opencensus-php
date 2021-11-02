<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetGstDetailsSuccess' => [
        'request'     => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response'    => [
            'content' => ["results"=> [
                    "13AAACR5055K1ZG",
                    "26AAACR5055K1Z9"
            ]],
        ],
        'status_code' => 200,
    ],
    'testGetGstDetailsSuccessFromStore' => [
        'request'     => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response'    => [
            'content' => ["results"=> [
                "22AAACR5055K1ZH",
                "03AAACR5055K2ZG"
            ]],
        ],
        'status_code' => 200,
    ],
    'testGetGstDetailsFailure' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []],
        ],
        'status_code' => 200,
    ],

    'testGetGstDetailsRateLimitExhausted' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []]
        ],
        'status_code' => 200,
    ],

    'testGetGstDetailsInvalidBusinessType' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []]
        ],
        'status_code' => 200,
    ],
    'testSaveActivationDetailsWithBankDetails' => [
        'request'  => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
        ],
        'status_code' => 200,
    ],
    'testSaveActivationDetailsWithDifferentBankDetails' => [
        'request'  => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
        ],
        'status_code' => 200,
    ],
    'testSaveActivationDetailsWithoutBankDetails' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111001',
                'bank_branch_ifsc'    => 'SBIN0007105',
            ],
        ],
        'status_code' => 200,
    ],
    'testSaveActivationDetailsWithBankDetailsLimitBreached' => [
        'request'  => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
            'status_code' => 200,
        ],
    ],
    'testSaveActivationDetailsWithoutBankDetailsLimitBreached' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111001',
                'bank_branch_ifsc'    => 'SBIN0007105',
            ],
            'status_code' => 200,
        ],
    ],
    'testSubmitFormPennyTestingLimitBreached' => [
        'request'  => [
            'content' => [
                'submit'=> 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test1',
                'bank_account_number' => '111001',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
        ],
        'status_code' => 200,
    ],
    'testSubmitFormPennyTestingLimitNotBreachedWithoutPreviousInSyncBVSCall' => [
        'request'  => [
            'content' => [
                'submit'=> 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test1',
                'bank_account_number' => '111001',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
        ],
        'status_code' => 200,
    ],
    'testSubmitFormPennyTestingLimitNotBreachedWithPreviousInSyncBVSCall' => [
        'request'  => [
            'content' => [
                'submit'=> 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_account_name'   => 'Test1',
                'bank_account_number' => '111001',
                'bank_branch_ifsc'    => 'SBIN0007105'
            ],
        ],
        'status_code' => 200,
    ],
];

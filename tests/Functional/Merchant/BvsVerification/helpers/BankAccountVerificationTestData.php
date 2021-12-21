<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

return [
    'testFillingBankDetailsWhenEmptyExperimentOff' => [
        'request'  => [
            'content' => [
                'bank_branch_ifsc'    => 'CBIN0281697',
                'bank_account_number' => '0002020000304030434',

            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'pending'
            ],
        ],
    ],
    'testFillingBankDetailsWhenEmptyExperimentOn' => [
        'request'  => [
            'content' => [
                'bank_branch_ifsc'    => 'CBIN0281697',
                'bank_account_number' => '0002020000304030434',

            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testFillingOtherDetailsWhenEmptyExperimentOff' => [
        'request'  => [
            'content' => [
                'promoter_pan'    => 'BRRPK8070K',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testFillingOtherDetailsWhenEmptyExperimentOn' => [
        'request'  => [
            'content' => [
                'promoter_pan'    => 'BRRPK8070K',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'pending'
            ],
        ],
    ],
    'testL2SubmitWhenBankVerifiedExperimentOff' => [
        'request'  => [
            'content' => [
                'submit'    => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
    'testL2SubmitWhenBankVerifiedExperimentOn' => [
        'request'  => [
            'content' => [
                'submit'    => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
    'testL2SubmitWhenBankNotVerifiedExperimentOff' => [
        'request'  => [
            'content' => [
                'submit' => true
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testL2SubmitWhenBankNotVerifiedExperimentOn' => [
        'request'  => [
            'content' => [
                'submit'    => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testNCBankDetailsSubmitWhenBankNotVerifiedExperimentOn' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'bank_account_number'              => '0002020000304030435',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testNCBankDetailsSubmitWhenBankNotVerifiedExperimentOff' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'bank_account_number'              => '0002020000304030435',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'initiated'
            ],
        ],
    ],
    'testNCOtherDetailsSubmitWhenBankVerifiedExperimentOn' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'promoter_pan_name'              => 'vasanthi kakarla',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
    'testNCOtherDetailsSubmitWhenBankVerifiedExperimentOff' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'promoter_pan_name'              => 'vasanthi kakarla',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
    'testNCOtherDetailsSubmitWhenBankNotVerifiedExperimentOn' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'promoter_pan_name'              => 'vasanthi kakarla',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'incorrect_details'
            ],
        ],
    ],
    'testNCOtherDetailsSubmitWhenBankNotVerifiedExperimentOff' => [
        'request'  => [
            'content' => [
                'submit'    => true,
                'promoter_pan_name'              => 'vasanthi kakarla',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'incorrect_details'
            ],
        ],
    ],
    'testFillingBankAccountNameAfterL2ExperimentOn' => [
        'request'  => [
            'content' => [
                'bank_account_name'    => 'vasanthi k',

            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
    'testFillingBankAccountNameAfterL2ExperimentOff' => [
        'request'  => [
            'content' => [
                'bank_account_name'    => 'vasanthi k',

            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_details_verification_status' => 'verified'
            ],
        ],
    ],
];

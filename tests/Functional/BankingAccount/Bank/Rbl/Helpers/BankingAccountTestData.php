<?php

use RZP\Error\PublicErrorCode;
use RZP\Error\ErrorCode;

return [
    'testCreateBankingAccount' => [
        'request' => [
            'url' => '/banking_account',
            'method' => 'POST',
            'content' => [
                'bank'          => 'rbl',
                'pincode'       => '560034',
            ],
        ],
        'response' => [
            'content' => [
              /*  'merchant_id' => '10000000000000',
                'bank'        => 'rbl',
                'status'      => 'created'*/
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceablePincode' => [
        'request' => [
            'url' => '/banking_account',
            'method' => 'POST',
            'content' => [
                'bank'          => 'rbl',
                'pincode'       => '899090',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'bank'        => 'rbl',
                'status'      => 'unserviceable'
            ],
        ],
    ],

    'testCreateBankingAccountWithEmptyPincode' => [
      'response' =>  [
          'content' => [
              'error' => [
                  'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                  'description' => 'The pincode field is required when bank is rbl.',
              ],
          ],
          'status_code' => 400,
      ],
      'exception' => [
          'class' => 'RZP\Exception\BadRequestValidationFailureException',
          'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
      ],
    ],

    'testCreateBankingAccountWithInvalidBank' => [
        'response' =>  [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected bank is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];

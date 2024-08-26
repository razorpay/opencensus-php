<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
  'testFetchUpiPaymentsByRrn' => [
      'request' => [
          'url' => '/payments?rrn=422012444250',
          'method' => 'GET'
      ],
      'response' => [
          'content' => [
              'entity' => "collection",
              'count' => 2,
              'items' => [
                  [
                      'id' => "pay_Ohx4E6GLjW1KDT",
                      'entity' => "payment",
                      'amount' => 1000000,
                      'currency' => "INR",
                      'base_amount' => 1000000,
                      'status' => "created",
                      'order_id' => null,
                      'invoice_id' => null,
                      'international' => null,
                      'method' => "card",
                      'amount_refunded' => 0,
                      'amount_transferred' => 0,
                      'refund_status' => null,
                      'captured' => false,
                      'description' => null,
                      'card_id' => null,
                      'bank' => null,
                      'wallet' => null,
                      'vpa' => null,
                      'notes' => [],
                      'fee' => 0,
                      'tax' => null,
                      'error_code' => null,
                      'error_description' => null,
                      'error_source' => null,
                      'error_step' => null,
                      'error_reason' => null,
                      'acquirer_data' => [
                          'auth_code' => null,
                          'rrn' => "422012444250"
                      ],
                      'created_at' => 1723021115
                  ],
                  [
                      'id' => "pay_Ohx4E6GLjW1KDQ",
                      'entity' => "payment",
                      'amount' => 1000000,
                      'currency' => "INR",
                      'base_amount' => 1000000,
                      'status' => "created",
                      'order_id' => null,
                      'invoice_id' => null,
                      'international' => null,
                      'method' => "card",
                      'amount_refunded' => 0,
                      'amount_transferred' => 0,
                      'refund_status' => null,
                      'captured' => false,
                      'description' => null,
                      'card_id' => null,
                      'bank' => null,
                      'wallet' => null,
                      'vpa' => null,
                      'notes' => [],
                      'fee' => 0,
                      'tax' => null,
                      'error_code' => null,
                      'error_description' => null,
                      'error_source' => null,
                      'error_step' => null,
                      'error_reason' => null,
                      'acquirer_data' => [
                          'auth_code' => null,
                          'rrn' => "422012444250"
                      ],
                      'created_at' => 1723021115
                  ]
              ]
          ]
      ]
  ],
  'testFetchUpiPaymentsByRrnPrivateAuth' => [
        'request' => [
            'url' => '/payments?rrn=422012444250',
            'method' => 'GET'
        ],
      'response'  => [
          'content'     => [
              'error' => [
                  'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                  'description' => 'rrn is/are not required and should not be sent',
              ],
          ],
          'status_code' => 400,
      ],
      'exception' => [
          'class'               => RZP\Exception\ExtraFieldsException::class,
          'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
      ],
    ],
  'testFetchUpiPaymentsByRrnWithoutFeatureFlag' => [
    'request' => [
        'url' => '/payments?rrn=422012444250',
        'method' => 'GET'
    ],
    'response' => [
        'content' => [
            'entity' => "collection",
            'count' => 3,
            'has_more' => false,
            'items' => [
                [
                    'id' => "pay_Ohx4E6GLjW1KDT",
                    'entity' => "payment",
                    'amount' => 1000000,
                    'currency' => "INR",
                    'base_amount' => 1000000,
                    'status' => "created",
                    'order_id' => null,
                    'invoice_id' => null,
                    'international' => null,
                    'method' => "card",
                    'amount_refunded' => 0,
                    'amount_transferred' => 0,
                    'refund_status' => null,
                    'captured' => false,
                    'description' => null,
                    'card_id' => null,
                    'bank' => null,
                    'wallet' => null,
                    'vpa' => null,
                    'notes' => [],
                    'fee' => 0,
                    'tax' => null,
                    'error_code' => null,
                    'error_description' => null,
                    'error_source' => null,
                    'error_step' => null,
                    'error_reason' => null,
                    'acquirer_data' => [
                        'auth_code' => null,
                        'rrn' => "422012444250"
                    ],
                    'created_at' => 1723021115
                ],
                [
                    'id' => "pay_Ohx4E6GLjW1KDR",
                    'entity' => "payment",
                    'amount' => 1000000,
                    'currency' => "INR",
                    'base_amount' => 1000000,
                    'status' => "created",
                    'order_id' => null,
                    'invoice_id' => null,
                    'international' => null,
                    'method' => "card",
                    'amount_refunded' => 0,
                    'amount_transferred' => 0,
                    'refund_status' => null,
                    'captured' => false,
                    'description' => null,
                    'card_id' => null,
                    'bank' => null,
                    'wallet' => null,
                    'vpa' => null,
                    'notes' => [],
                    'fee' => 0,
                    'tax' => null,
                    'error_code' => null,
                    'error_description' => null,
                    'error_source' => null,
                    'error_step' => null,
                    'error_reason' => null,
                    'acquirer_data' => [
                        'auth_code' => null,
                        'rrn' => "422012444251"
                    ],
                    'created_at' => 1723021115
                ],
                [
                    'id' => "pay_Ohx4E6GLjW1KDQ",
                    'entity' => "payment",
                    'amount' => 1000000,
                    'currency' => "INR",
                    'base_amount' => 1000000,
                    'status' => "created",
                    'order_id' => null,
                    'invoice_id' => null,
                    'international' => null,
                    'method' => "card",
                    'amount_refunded' => 0,
                    'amount_transferred' => 0,
                    'refund_status' => null,
                    'captured' => false,
                    'description' => null,
                    'card_id' => null,
                    'bank' => null,
                    'wallet' => null,
                    'vpa' => null,
                    'notes' => [],
                    'fee' => 0,
                    'tax' => null,
                    'error_code' => null,
                    'error_description' => null,
                    'error_source' => null,
                    'error_step' => null,
                    'error_reason' => null,
                    'acquirer_data' => [
                        'auth_code' => null,
                        'rrn' => "422012444250"
                    ],
                    'created_at' => 1723021115
                ]
            ]
        ]
    ]
    ],
  'testFetchUpiPaymentsByRrnWithAdditionalFilters' => [
        'request' => [
            'url' => '/payments?rrn=422012444250&from=1723021114&to=1723021116&status=captured&email=test@razorpay.com',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count' => 1,
                'items' => [
                    [
                        'id' => "pay_Ohx4E6GLjW1KDT",
                        'entity' => "payment",
                        'amount' => 1000000,
                        'currency' => "INR",
                        'base_amount' => 1000000,
                        'status' => "captured",
                        'order_id' => null,
                        'invoice_id' => null,
                        'international' => null,
                        'method' => "card",
                        'amount_refunded' => 0,
                        'amount_transferred' => 0,
                        'refund_status' => null,
                        'captured' => false,
                        'description' => null,
                        'card_id' => null,
                        'bank' => null,
                        'wallet' => null,
                        'vpa' => null,
                        'notes' => [],
                        'fee' => 0,
                        'tax' => null,
                        'email' => 'test@razorpay.com',
                        'error_code' => null,
                        'error_description' => null,
                        'error_source' => null,
                        'error_step' => null,
                        'error_reason' => null,
                        'acquirer_data' => [
                            'auth_code' => null,
                            'rrn' => "422012444250"
                        ],
                        'created_at' => 1723021115
                    ],
                ]
            ]
        ]
    ],

];

<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOrderTransfers' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '50000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 50000,
                        'currency'  => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransferToSuspendedLinkedAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '50000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_NOT_ALLOWED_TO_SUSPENDED_LINKED_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_SUSPENDED
        ],
    ],

    'testCreateOrderWithoutTransfers' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
            ],
        ],
    ],

    'testCreateOrderTransfersForCredits' => [
        'request' => [
            'method' => 'POST',
            'url' => '/orders',
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    "description" => "adding refund credits",
                ],
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'balance' => 'refund_credit',
                        'amount' => 50000,
                        'currency' => 'INR'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'transfers' => [
                    [
                        'recipient' => '10000000000000',
                        'currency' => 'INR',
                        'amount' => 50000,
                        'notes' => [
                            'type' => 'refund_credit'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransfersForReserveBalance' => [
        'request' => [
            'method' => 'POST',
            'url' => '/orders',
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    "description" => "adding reserve balance",
                ],
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'balance' => 'reserve_balance',
                        'amount' => 50000,
                        'currency' => 'INR'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'transfers' => [
                    [
                        'recipient' => '10000000000000',
                        'currency' => 'INR',
                        'amount' => 50000,
                        'notes' => [
                            'type' => 'reserve_balance'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransfersForFeeCreditWhenAmountCreditExists' => [
        'request' => [
            'method' => 'POST',
            'url' => '/orders',
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    "description" => "adding fee credits",
                ],
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'balance' => 'fee_credit',
                        'amount' => 50000,
                        'currency' => 'INR'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'transfers' => [
                    [
                        'recipient' => '10000000000000',
                        'currency' => 'INR',
                        'amount' => 50000,
                        'notes' => [
                            'type' => 'fee_credit'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransfersUsingAccountCode' => [
        'request' => [
            'url' => '/orders',
            'method' => 'POST',
            'content' => [
                'amount' => '50000',
                'currency' => 'INR',
                'transfers' => [
                    [
                        'account_code' => 'code-007',
                        'amount' => '20000',
                        'currency' => 'INR',
                        'notes' => [
                            'roll_no' => '150104062',
                        ],
                        'linked_account_notes' => [
                            'roll_no',
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'account_code' => 'code-007',
                        'amount' => 20000,
                        'currency' => 'INR',
                        'notes' => [
                            'roll_no' => '150104062',
                        ],
                        'linked_account_notes' => [
                            'roll_no',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetOrderTransfers' => [
        'request'  => [
            'method'  => 'GET',
            'content' => [
                'expand' => [
                    'transfers',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'order',
                'amount'      => 50000,
                'amount_paid' => 50000,
                'amount_due'  => 0,
                'currency'    => 'INR',
                'offer_id'    => null,
                'status'      => 'paid',
                'notes'       => [],
                'transfers'   => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'entity'                  => 'transfer',
                            'recipient'               => 'acc_10000000000001',
                            'amount'                  => 50000,
                            'currency'                => 'INR',
                            'amount_reversed'         => 0,
                            'notes'                   => [],
                            'fees'                    => 0,
                            'tax'                     => 0,
                            'on_hold'                 => false,
                            'on_hold_until'           => null,
                            'recipient_settlement_id' => null,
                            'linked_account_notes'    => [],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testReverseOrderTransfer' => [
        'request'  => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'   => 'reversal',
                'amount'   => 50000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testWebhookOrderTransferProcessed'       => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event'    => 'transfer.processed',
            'contains' => [
                'transfer',
            ],
            'payload'  => [
                'transfer' => [
                    'entity' => [
                        'entity'                  => 'transfer',
                        'recipient'               => 'acc_10000000000001',
                        'amount'                  => 50000,
                        'currency'                => 'INR',
                        'amount_reversed'         => 0,
                        'notes'                   => [],
                        'fees'                    => 0,
                        'tax'                     => 0,
                        'on_hold'                 => false,
                        'on_hold_until'           => null,
                        'recipient_settlement_id' => null,
                        'linked_account_notes'    => [],
                    ],
                ],
            ],
        ],
    ],

    'testProcessOrderTransfersPartialPayment' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'          => '50000',
                'currency'        => 'INR',
                'partial_payment' => true,
                'transfers'       => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '50000',
                        'currency' => 'INR',
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Partial payment not allowed for transfers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCronProcessPendingOrderTransfers' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/transfers/process_pending',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testCronProcessFailedOrderTransfers' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/transfers/process_failed',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testTransferFailedWebhook' => [
        'entity'=> 'event',
        'account_id'=> 'acc_10000000000000',
        'event'=> 'transfer.failed',
        'contains'=> [
            'transfer'
        ],
        'payload'=> [
            'transfer'=> [
                'entity'=> [
                    'entity'=> 'transfer',
                    'recipient'=> 'acc_10000000000001',
                    'amount'=> 50000,
                    'currency'=> 'INR',
                ],
            ],
        ],
    ],

    'testGetOrderTransfersWithPaymentId' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'items'  => [
                    [
                        'entity'                  => 'transfer',
                        'recipient'               => 'acc_10000000000001',
                        'amount'                  => 50000,
                        'currency'                => 'INR',
                        'status'                  => 'processed',
                        'amount_reversed'         => 0,
                        'notes'                   => [],
                        'fees'                    => 0,
                        'tax'                     => 0,
                        'on_hold'                 => false,
                        'on_hold_until'           => null,
                        'recipient_settlement_id' => null,
                        'linked_account_notes'    => [],
                    ],
                ],
            ]
        ],
    ],

    'testFetchLinkedAccountTransferByPaymentIdOfOrder' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'items'  => [
                    [
                        'entity'                  => 'transfer',
                        'recipient'               => 'acc_10000000000001',
                        'amount'                  => 50000,
                        'currency'                => 'INR',
                        'status'                  => 'processed',
                        'amount_reversed'         => 0,
                        'notes'                   => [],
                        'fees'                    => 0,
                        'tax'                     => 0,
                        'on_hold'                 => false,
                        'on_hold_until'           => null,
                        'recipient_settlement_id' => null,
                        'linked_account_notes'    => [],
                    ],
                ],
            ]
        ],
    ],

    'testFetchLinkedAccountTransferByPaymentIdOfOrderWithTransferId' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'items'  => [
                    [
                        'entity'                  => 'transfer',
                        'recipient'               => 'acc_10000000000001',
                        'amount'                  => 50000,
                        'currency'                => 'INR',
                        'status'                  => 'processed',
                        'amount_reversed'         => 0,
                        'notes'                   => [],
                        'fees'                    => 0,
                        'tax'                     => 0,
                        'on_hold'                 => false,
                        'on_hold_until'           => null,
                        'recipient_settlement_id' => null,
                        'linked_account_notes'    => [],
                    ],
                ],
            ]
        ],
    ],

    'testCreateOrderTransferWithPartnerAuthForMarketplace' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 10000,
                        'currency'  => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransferWithOAuthForMarketplace' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 10000,
                        'currency'  => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransferWithOAuthForMarketplaceWithAppLevelFeature' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 10000,
                        'currency'  => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransferEntityOriginWithPartnerAuthForMarketplace' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 10000,
                        'currency'  => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderTransferWithPartnerAuthForInvalidPartnerType' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid partner action',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testCreateOrderTransferWithPartnerAuthForInvalidPartnerMerchantMapping' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '10000',
                        'currency' => 'INR',
                        'notes'    => [
                            'roll_no' => 'iec2011025'
                        ],
                        'linked_account_notes' => [
                            'roll_no'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The partner does not have access to the merchant',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCronProcessPendingOrderTransfersAsync' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/order_transfers/process_pending',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testCronProcessPendingOrderTransfersSync' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/order_transfers/process_pending?sync=true',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],
];

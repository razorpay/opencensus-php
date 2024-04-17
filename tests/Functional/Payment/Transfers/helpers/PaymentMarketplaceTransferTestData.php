<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testTransferToInvalidOrUnlinkedId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],
    'testTransferWithFeatureNotEnabled' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],
    'testMultipleTransfersOnSameAccountId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testTransfersPaymentAsyc' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testTransferPaymentAmountGreaterThanCaptured' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED
        ],
    ],
    'testTransferToCustomerAndAccount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN
        ],
    ],
    'dummy' => [
        'content' => [
            'count' => 1,
            'items' => [
                [
                    'source_type'     => 'payment',
                    'source_id'       => '000',
                    'to_type'         => 'merchant',
                    'to_id'           => 'acc_10000000000001',
                    'amount'          => 4000,
                    'amount_reversed' => 0
                ],
            ],
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

    'testCreateTransferFromBatch' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'account'   => 'acc_10000000000001',
                'amount'    => 1000,
                'currency'  => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'recipient' => 'acc_10000000000001',
                'amount'    => 1000,
                'currency'  => 'INR',
            ],
        ],
    ],

    'testCreateTransferFromBatchWithOnHold' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'account'   => 'acc_10000000000001',
                'amount'    => 1500,
                'currency'  => 'INR',
                'on_hold'   => true
            ],
        ],
        'response' => [
            'content' => [
                'recipient' => 'acc_10000000000001',
                'amount'    => 1500,
                'currency'  => 'INR',
                'on_hold'   => true,
            ],
        ],
    ],

    'testErrorFieldInGetTransfer' => [
        'request' => [
            'url' => '/transfers',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'recipient' => 'acc_10000000000001',
                'amount'    => 50000,
                'currency'  => 'INR',
                'error'     => [
                    'code'          => 'BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE',
                    'description'   => 'Account does not have sufficient balance to carry out transfer operation',
                    'reason'        => 'insufficient_account_balance',
                    'field'         => 'amount',
                    'step'          => 'transfer_processing',
                    'source'        => NULL,
                    'metadata'      => NULL,
                ]
            ],
        ],
    ],


    'testPaymentTransferFetch' => [
        'request' => [
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                ],
            ],
        ],
    ],

    'testPaymentPlatformTransferFetch' => [
        'request' => [
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        "id"                        => 'trf_LhV9fg1fXagWCD',
                        "recipient"                 => 'acc_10000000000003',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                        'partner_details'           => [
                            'name' => 'partner_test',
                            'id' => '10000000000002',
                            'email' => 'testmail@mail.info',
                        ],
                    ],
                    [
                        "id"                        => 'trf_LhV9fg1fXagWCN',
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ]
                ],
            ],
        ],
    ],

    'testFetchLinkedAccountTransferByPaymentId' => [
        'request' => [
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'recipient'                 => 'acc_10000000000001',
                        'currency'                  => "INR",
                        'amount'                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                ],
            ],
        ],
    ],

    'testFetchLinkedAccountTransferByPaymentIdAndTransferId' => [
        'request' => [
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'recipient'                 => 'acc_10000000000001',
                        'currency'                  => "INR",
                        'amount'                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                ],
            ],
        ],
    ],

    'testCronProcessPendingPaymentTransfers' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/payment_transfers/process_pending',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testCronProcessFailedOrderTransfers' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/order_transfers/process_failed',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testCronProcessPendingPaymentTransfersSync' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/payment_transfers/process_pending?sync=true',
            'content'   => [],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testCreatePaymentTransferWithPartnerAuthForMarketplace' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithPartnerAuthForMarketplaceWithInsufficientBalance' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 10000,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 10000,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithOAuthForMarketplace' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithOAuthForMarketplaceInReverseShadow' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithOAuthForMarketplaceWithAppLevelFeature' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithOAuthForMarketplaceWithOauthPricing' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferEntityOriginWithPartnerAuthForMarketplace' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePaymentTransferWithPartnerAuthForInvalidPartnerType' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
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

    'testCreatePaymentTransferWithPartnerAuthForInvalidPartnerMerchantMapping' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
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

    'testReleaseSubmerchantPaymentByPartner' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerUsingOAuth' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerUsingOAuthWithAppLevelFeature' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testReleaseSubmerchantPaymentByInvalidPartnerTypeUsingOAuth' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWithFeatureDisabled' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MANUAL_SETTLEMENT_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MANUAL_SETTLEMENT_NOT_ALLOWED
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWithInvalidPaymentMerchant' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWithUnmappedMerchant' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWhichIsNotCaptured' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWithTrxnNotOnHold' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_NOT_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_NOT_ON_HOLD
        ],
    ],

    'testReleaseSubmerchantPaymentByPartnerWithTrxnAlreadySettled' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_SETTLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_SETTLED
        ],
    ],

    'testReleaseSubmerchantPaymentByInvalidPartnerType' => [
        'request' => [
            'url'       => '/payments/:id/settle',
            'method'    => 'POST',
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

    'testTransferToSuspendedLinkedAccount' => [
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

    'testPaymentTransferReverseShadowOutboxPushFailureAmountGreaterThanCapturedFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED
        ],
    ],

    'testReverseShadowCronRetrySuccessForPaymentTransferProcessedEvent' => [
        'request' => [
            'url' => '/ledger_outbox/retry?type=transfer',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 1,
                'failed entries count' =>  0,
            ],
            'status_code' => 200,
        ]
    ],

    'testReverseShadowCronRetryRetryableFailureForPaymentTransferProcessedEvent' => [
        'request' => [
            'url' => '/ledger_outbox/retry?type=transfer',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
            'status_code' => 200,
        ]
    ],

    'testReverseShadowCronRetryTransferFailsDueToInsufficientBalanceAfterMaxRetry' => [
        'request' => [
            'url' => '/ledger_outbox/retry?type=transfer',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
            'status_code' => 200,
        ]
    ],

    'testReverseShadowCronRetryNonRetryableFailureForPaymentTransferProcessedEvent' => [
        'request' => [
            'url' => '/ledger_outbox/retry?type=transfer',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferProcessedWebhookEventWithTransactionIsolation' => [
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
                    'status'                  => 'processed',
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

    'testFetchPendingTransfersCount' => [
        'request'   => [
            'method'    => 'get',
            'url'       => '/transfers/pending',
            'content'   => [],
        ],
        'response'  => [
            'content' => [
                'payment_transfers_count' => [
                    'category1' => 0,
                    'category2' => 0,
                    'category3' => 2,
                ],
                'order_transfers_count' => [
                    'category1' => 0,
                    'category2' => 0,
                    'category3' => 0,
                ]
            ],
        ],
    ],

    'testFetchPendingTransfersCountCategory1' => [
        'request'   => [
            'method'    => 'get',
            'url'       => '/transfers/pending',
            'content'   => [],
        ],
        'response'  => [
            'content' => [
                'payment_transfers_count' => [
                    'category1' => 2,
                    'category2' => 0,
                    'category3' => 0,
                ],
                'order_transfers_count' => [
                    'category1' => 0,
                    'category2' => 0,
                    'category3' => 0,
                ]
            ],
        ],
    ],

    'testCronCreateMissingTransactionForTransfers' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/transfers/transactions/create',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testReverseShadowCronRetrySuccessForPaymentTransferAlreadyProcessedByPendingCron' => [
        'request' => [
            'url' => '/ledger_outbox/retry?type=transfer',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 1,
                'failed entries count' =>  0,
            ],
            'status_code' => 200,
        ]
    ],

    'testReverseShadowCronRetryForPaymentTransferProcessedEventFailureWrongRoute' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  0,
            ],
            'status_code' => 200,
        ]
    ],

];

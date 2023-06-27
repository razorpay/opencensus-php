<?php

use RZP\Exception;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Method;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Balance;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\QueuedReasons;
use RZP\Models\BankingAccount\Channel;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Counter\Entity as CounterEntity;

return [
    'testCreatePayoutEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "merchant_id"          => "10000000000000",
                "origin"               => "api",
                "channel"              => "",
                "amount"               => 100,
                "status"               => "create_request_submitted",
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
                "workflow_details"     => [
                    'id'                       => '',
                    'config_id'                => '',
                    'workflow_service_enabled' => false
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'create_request_submitted',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayoutServiceTransaction' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'created',
                'error'         => null,
                'queued_reason' => null,
                'status_code'   => null
            ],
        ],
    ],

    'testDeductCreditsViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "create_request_submitted",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
            ],
        ],
        'response' => [
            'content' => [
                "fees"          => 100,
                "tax"           => 0,
                "credits_used"  => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testDeductCreditsViaPayoutServiceAndCreditsNotAvailable' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "create_request_submitted",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
            ],
        ],
        'response' => [
            'content' => [
                "fees"          => 200,
                "tax"           => 100,
                "credits_used"  => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testDeductCreditsViaPayoutServiceAndDoubleCreditRequestReceived' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "create_request_submitted",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
            ],
        ],
        'response' => [
            'content' => [
                "fees"          => 100,
                "tax"           => 0,
                "credits_used"  => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testDeductCreditsViaPayoutServiceAndInternalServerErrorCase' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "create_request_submitted",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL123",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\ServerErrorException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testDeductCreditsViaPayoutServiceBadRequestInvalidStatus' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "random_status",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid status:random_status sent for merchant credits deduction via payout service',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeductCreditsViaPayoutServiceBadRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/deduct_credits',
            'content' => [
                "fees"          => 200,
                "tax"           => 100,
                "status"        => "random_status",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payout id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testReverseCreditsViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "reversal_id"   => "Revxyk0gB5Fx11",
                "entity_type"   => "payout",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response' => [
            'content' => [
                "success"       => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testReverseCreditsViaPayoutServiceAndDoubleReversalRequestReceived' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "reversal_id"   => "Revxyk0gB5Fx11",
                "entity_type"   => "payout",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response' => [
            'content' => [
                "success"       => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testReverseCreditsViaPayoutServiceAndInternalServerErrorCase' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "reversal_id"   => "Revxyk0gB5Fx11",
                "entity_type"   => "payout",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\ServerErrorException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testReverseCreditsViaPayoutServiceBadRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "entity_type"   => "reversal",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The reversal id field is required when entity type is reversal.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testReverseCreditsViaPayoutServiceAndSourceReversal' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "reversal_id"   => "Revxyk0gB5Fx11",
                "entity_type"   => "reversal",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response' => [
            'content' => [
                "success"       => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testReverseCreditsViaPayoutServiceAndDoubleReversalRequestReceivedWithSourceReversal' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reverse_credits',
            'content' => [
                "reversal_id"   => "Revxyk0gB5Fx11",
                "entity_type"   => "reversal",
                "payout_id"     => "Gg7sgBZgvYTTTT",
                "merchant_id"   => "10000000000000",
                "balance_id"    => "GhidjxhfiCL7WT",
                "fee_type"      => "reward_fee",
            ],
        ],
        'response' => [
            'content' => [
                "success"       => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchPricingInfoForPayoutServiceWithoutUserID' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/fetch_pricing_info',
            'content' => [
                Entity::PAYOUT_ID   => "Gg7sgBZgvYTTTT",
                Entity::BALANCE_ID  => "GhidjxhfiCL7WT",
                Entity::MERCHANT_ID => "10000000000000",
                Entity::METHOD      => Method::FUND_TRANSFER,
                Entity::AMOUNT      => 100,
                Entity::MODE        => Mode::NEFT,
                Entity::CHANNEL     => Channel::ICICI,
            ],
        ],
        'response' => [
            'content' => [
                Entity::FEES            => 590,
                Entity::TAX             => 90,
                Entity::PRICING_RULE_ID => "Bbg7cl6t6I3XA5",
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchPricingInfoForPayoutServiceWithUserID' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/fetch_pricing_info',
            'content' => [
                Entity::PAYOUT_ID   => "Gg7sgBZgvYTTTT",
                Entity::BALANCE_ID  => "GhidjxhfiCL7WT",
                Entity::MERCHANT_ID => "10000000000000",
                Entity::METHOD      => Method::FUND_TRANSFER,
                Entity::AMOUNT      => 100,
                Entity::MODE        => Mode::NEFT,
                Entity::CHANNEL     => Channel::ICICI,
                Entity::USER_ID     => "user123",
            ],
        ],
        'response' => [
            'content' => [
                Entity::FEES            => 590,
                Entity::TAX             => 90,
                Entity::PRICING_RULE_ID => "Bbg7cl6t6I3XA7",
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchPricingInfoForPayoutServiceForXPayrollApp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/fetch_pricing_info',
            'content' => [
                Entity::PAYOUT_ID   => "Gg7sgBZgvYTTTT",
                Entity::BALANCE_ID  => "GhidjxhfiCL7WT",
                Entity::MERCHANT_ID => "10000000000000",
                Entity::METHOD      => Method::FUND_TRANSFER,
                Entity::AMOUNT      => 100,
                Entity::MODE        => Mode::NEFT,
                Entity::CHANNEL     => Channel::ICICI,
            ],
        ],
        'response' => [
            'content' => [
                Entity::FEES            => 0,
                Entity::TAX             => 0,
                Entity::PRICING_RULE_ID => "Bbg7cl6t6I3XB8",
            ],
            'status_code' => 200,
        ],
    ],

    // We don't want to retry at payout service. Hence it expects 200 response
    'testFetchPricingInfoForPayoutServiceBadRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/fetch_pricing_info',
            'content' => [
                Entity::PAYOUT_ID   => "Gg7sgBZgvYTTTT",
                Entity::BALANCE_ID  => "GhidjxhfiCL7WT",
                Entity::MERCHANT_ID => "10000000000000",
                Entity::METHOD      => Method::FUND_TRANSFER,
                Entity::AMOUNT      => 50,
                Entity::MODE        => Mode::NEFT,
                Entity::CHANNEL     => Channel::ICICI,
            ],
        ],
        'response' => [
            'content'     => [
                Entity::ERROR            => "Minimum transaction amount allowed is Re. 1",
                Error::PUBLIC_ERROR_CODE => "BAD_REQUEST_VALIDATION_FAILURE",
            ],
            'status_code' => 200,
        ],
    ],

    // We don't want to retry at payout service. Hence it expects 200 response
    'testFetchPricingInfoForPayoutServiceInternalServerError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/fetch_pricing_info',
            'content' => [
                Entity::PAYOUT_ID   => "Gg7sgBZgvYTTTT",
                Entity::BALANCE_ID  => "GhidjxhfiCL7WT",
                Entity::MERCHANT_ID => "10000000000000",
                Entity::METHOD      => "invalid_method",
                Entity::AMOUNT      => 100,
                Entity::MODE        => Mode::NEFT,
                Entity::CHANNEL     => Channel::ICICI,
            ],
        ],
        'response' => [
            'content'     => [
                Entity::ERROR            => "Invalid rule count: 0, Merchant Id: 10000000000000",
                Error::PUBLIC_ERROR_CODE => "SERVER_ERROR_PRICING_RULE_ABSENT",
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutServiceFtaCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_fta/Gg7sgBZgvYjlSB',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
                'error'  => null
            ],
        ],
    ],

    'testPayoutServiceFtaCreationWithoutPayoutInAPI' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_fta/Gg7sgBZgvYjlSB',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayoutServicePaymentCreation' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payouts_service/payments/create/axis',
            'content' => [
                "amount" => 50000,
                "currency" => "INR",
                "email" => "gaurav.kumar@example.com",
                "contact" => 9123456789,
                "method" => "card",
                "card" =>
                    [
                        "number" => "5104060000000008",
                        "name" => "Gaurav Kumar",
                        "expiry_month" => "01",
                        "expiry_year" => \Carbon\Carbon::now()->addYear()->format('y')
                    ],
                "auth_type" => "skip"
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreatePayoutWithAttachments' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                "tds"             => [
                    "category_id" => 17,
                    "amount"      => 1100,
                ],
                "attachments"     => [
                    [
                        "file_id"   => "file_10SampleFile",
                        "file_name" => "random file.pdf",
                        "file_hash" => hash_hmac('sha256', 'file_10SampleFile', getenv('PAYOUTS_ATTACHMENTS_HASH_SECRET')),
                    ],
                    [
                        "file_id"   => "file_11SampleFile",
                        "file_name" => "random file1.pdf",
                        "file_hash" => hash_hmac('sha256', 'file_11SampleFile', getenv('PAYOUTS_ATTACHMENTS_HASH_SECRET')),
                    ],
                ],
                "subtotal_amount" => 100,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testPayoutSetStatusQueuePushForPayoutsServicePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/source_update',
            'content' => [
                'source_details'          => [
                    [
                        'source_id'   => 'randomid111121',
                        'source_type' => 'refund',
                        'priority'    => 1
                    ],
                ],
                'payout_id'               => 'randomid111121',
                'previous_status'         => 'initiated',
                'expected_current_status' => 'processed',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPayoutSetStatusQueuePushForPayoutsServicePayoutWithDualWrittenAPIData' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/source_update',
            'content' => [
                'source_details'          => [
                    [
                        'source_id'   => 'randomid111121',
                        'source_type' => 'refund',
                        'priority'    => 1
                    ],
                ],
                'payout_id'               => 'randomid111121',
                'previous_status'         => 'initiated',
                'expected_current_status' => 'processed',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPayoutSetStatusQueuePushWithMockQueue' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/source_update',
            'content' => [
                'source_details'          => [
                    [
                        'source_id'   => 'randomid111121',
                        'source_type' => 'refund',
                        'priority'    => 1
                    ],
                ],
                'payout_id'               => 'randomid111121',
                'previous_status'         => 'initiated',
                'expected_current_status' => 'processed',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCreatePayoutWithNewBankingError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'error'           => [
                    'source'      => '',
                    'reason'      => '',
                    'description' => '',
                    'code'        => '',
                    'step'        => '',
                    'metadata'    => [],
                ],
            ],
        ],
    ],

    'testCreatePayoutViaMicroserviceAndPassFundAccountInfo' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'error'           => [
                    'source'      => '',
                    'reason'      => '',
                    'description' => '',
                    'code'        => '',
                    'step'        => '',
                    'metadata'    => [],
                ],
            ],
        ],
    ],

    'testCreateInternalPayoutViaMicroService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreatePayoutWithFeeRewards' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 500,
            ],
        ],
    ],

    'testCreatePayoutInsufficientBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testCreatePayoutInternalContact' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 100,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'test Merchant Fund Transfer',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => false,
                'fund_account_id'                      => 'fa_100000000000fa',
                'origin'                               => 'api',
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'origin'          => 'api',
            ],
        ],
    ],

    'testCreatePayoutInternalContactWithoutWorkflowsFlag' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 100,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'test Merchant Fund Transfer',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => false,
                'fund_account_id'                      => 'fa_100000000000fa',
                'origin'                               => 'api',
            ],
        ],
        'response' => [
            'status_code'     => 200,
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'origin'          => 'api',
            ],
        ],
    ],

    'testCreatePayoutInternalContactWithWorkflows' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 100,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'test Merchant Fund Transfer',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => true,
                'fund_account_id'                      => 'fa_100000000000fa',
                'origin'                               => 'api',
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'origin'          => 'api',
            ],
        ],
    ],

    'testFetchPayoutById' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"              => "pout_Gg7sgBZgvYjlSB",
                "entity"          => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount"          => 100,
                "currency"        => "INR",
                "merchant_id"     => "10000000000000",
                "notes"           => "",
                "fees"            => 0,
                "tax"             => 0,
                "status"          => "processing",
                "purpose"         => "refund",
                "utr"             => "",
                "reference_id"    => null,
                "narration"       => "test Merchant Fund Transfer",
                "batch_id"        => "",
                "initiated_at"    => 1614325830,
                "failure_reason"  => null,
                "created_at"      => 1614325826,
                "fee_type"        => null
            ],
        ],
    ],

    'testFetchPayoutByIdWithExpandParam' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB?expand[]=user&expand[]=fund_account.contact',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"              => "pout_Gg7sgBZgvYjlSB",
                "entity"          => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount"          => 100,
                "currency"        => "INR",
                "merchant_id"     => "10000000000000",
                "notes"           => "",
                "fees"            => 0,
                "tax"             => 0,
                "status"          => "processing",
                "purpose"         => "refund",
                "utr"             => "",
                "reference_id"    => null,
                "narration"       => "test Merchant Fund Transfer",
                "batch_id"        => "",
                "initiated_at"    => 1614325830,
                "failure_reason"  => null,
                "created_at"      => 1614325826,
                "fee_type"        => null
            ],
        ],
    ],

    'testFetchPayoutByIdWithErrorFromService' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testFetchPayoutByIdWithUnsupportedParamsForService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB?contact_id="10000000000000',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"       => "pout_Gg7sgBZgvYjlSB",
                "entity"   => "payout",
                "currency" => "INR",
                "notes" => [],
            ],
        ],
    ],

    'testFetchPayoutByIdOnTestModeForService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB?',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testFetchPayoutByIdWithPrivilegeAuth' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts_internal/pout_Gg7sgBZgvYjlSB?expand[]=user&expand[]=fund_account.contact',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"              => "pout_Gg7sgBZgvYjlSB",
                "entity"          => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount"          => 100,
                "currency"        => "INR",
                "merchant_id"     => "10000000000000",
                "notes"           => "",
                "fees"            => 0,
                "tax"             => 0,
                "status"          => "processing",
                "purpose"         => "refund",
                "utr"             => "",
                "reference_id"    => null,
                "narration"       => "test Merchant Fund Transfer",
                "batch_id"        => "",
                "initiated_at"    => 1614325830,
                "failure_reason"  => null,
                "created_at"      => 1614325826,
                "fee_type"        => null
            ],
        ],
    ],

    'testFetchPayoutByIdWithBearerAuth' => [
        'request'           => [
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'url'     => '/payouts',
            'content' => [
                'product' => 'banking',
                'count'   => 10,
                'expand'  => [
                    'fund_account.contact',
                    'user'
                ]
            ],
        ],
        'response'          => [
            'content' => [
                "id"       => "pout_Gg7sgBZgvYjlSB",
                "entity"   => "payout",
                "currency" => "INR",
                "notes" => [],
            ],
        ],
        'expected_passport' => [
            'mode'          => 'live',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth'         => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'prod',
            ],
            'credential'    => [
                'username'   => 'rzp_live_oauth_TheTestAuthKey',
                'public_key' => 'rzp_live_oauth_TheTestAuthKey',
            ],
            'roles'         => [
                'oauth::scope::rx_read_write',
            ],
        ],
    ],

    'testFetchPayoutByIdWithIdNotFoundErrorFromService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"       => "pout_Gg7sgBZgvYjlSB",
                "entity"   => "payout",
                "currency" => "INR",
                "notes" => [],
            ],
        ],
    ],

    'testFetchPayoutMultiple' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&mode=imps',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => true,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => "",
                        "fees"            => 0,
                        "tax"             => 0,
                        "status"          => "processing",
                        "purpose"         => "refund",
                        "utr"             => "",
                        "reference_id"    => null,
                        "narration"       => "test Merchant Fund Transfer",
                        "batch_id"        => "",
                        "initiated_at"    => 1614325830,
                        "failure_reason"  => null,
                        "created_at"      => 1614325826,
                        "fee_type"        => null
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithExpandParam' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&mode=imps&expand[]=user&expand[]=fund_account.contact',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => true,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => "",
                        "fees"            => 0,
                        "tax"             => 0,
                        "status"          => "processing",
                        "purpose"         => "refund",
                        "utr"             => "",
                        "reference_id"    => null,
                        "narration"       => "test Merchant Fund Transfer",
                        "batch_id"        => "",
                        "initiated_at"    => 1614325830,
                        "failure_reason"  => null,
                        "created_at"      => 1614325826,
                        "fee_type"        => null
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithIdParam' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?id=pout_Gg7sgBZgvYjlSB&count=10&account_number=2224440041626905',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => true,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => "",
                        "fees"            => 0,
                        "tax"             => 0,
                        "status"          => "processing",
                        "purpose"         => "refund",
                        "utr"             => "",
                        "reference_id"    => null,
                        "narration"       => "test Merchant Fund Transfer",
                        "batch_id"        => "",
                        "initiated_at"    => 1614325830,
                        "failure_reason"  => null,
                        "created_at"      => 1614325826,
                        "fee_type"        => null
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithErrorFromService' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&mode=imps',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testFetchPayoutMultipleWithUnsupportedParamsForService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => false,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => [],
                        "fees"            => 590,
                        "tax"             => 90,
                        "status"          => "processing",
                        "purpose"         => "refund",
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithNonSharedBalanceType' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => false,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => [],
                        "fees"            => 590,
                        "tax"             => 90,
                        "status"          => "processing",
                        "purpose"         => "refund",
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithNoAccountNumberAndOnlyDirectBankingAccount' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?product=banking&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => false,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => [],
                        "fees"            => 590,
                        "tax"             => 90,
                        "status"          => "processing",
                        "purpose"         => "refund",
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithNoAccountNumberAndOnlySharedBankingAccount' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?product=banking&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => true,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => "",
                        "fees"            => 0,
                        "tax"             => 0,
                        "status"          => "processing",
                        "purpose"         => "refund",
                        "utr"             => "",
                        "reference_id"    => null,
                        "narration"       => "test Merchant Fund Transfer",
                        "batch_id"        => "",
                        "initiated_at"    => 1614325830,
                        "failure_reason"  => null,
                        "created_at"      => 1614325826,
                        "fee_type"        => null
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithNoAccountNumberAndMoreThanOneBankingBalance' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?product=banking&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => false,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => [],
                        "fees"            => 590,
                        "tax"             => 90,
                        "status"          => "processing",
                        "purpose"         => "refund",
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithNonProxyOrPrivateAuth' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts_internal?account_number=2224440041626905&count=2',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => [],
                        "fees"            => 590,
                        "tax"             => 90,
                        "status"          => "processing",
                        "purpose"         => "refund",
                    ]
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleOnTestModeForService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?product=banking',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 0,
                "has_more" => false,
                "items"    => [
                ]
            ],
        ],
    ],

    'testFetchPayoutMultipleWithPayoutModeParam' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts?account_number=2224440041626905&payout_mode=imps&expand[]=user&expand[]=fund_account.contact',
            'server'  => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "entity"   => "collection",
                "count"    => 1,
                "has_more" => true,
                "items"    => [
                    [
                        "id"              => "pout_Gg7sgBZgvYjlSB",
                        "entity"          => "payout",
                        "fund_account_id" => "fa_100000000000fa",
                        "amount"          => 100,
                        "currency"        => "INR",
                        "merchant_id"     => "10000000000000",
                        "notes"           => "",
                        "fees"            => 0,
                        "tax"             => 0,
                        "status"          => "processing",
                        "purpose"         => "refund",
                        "utr"             => "",
                        "reference_id"    => null,
                        "narration"       => "test Merchant Fund Transfer",
                        "batch_id"        => "",
                        "initiated_at"    => 1614325830,
                        "failure_reason"  => null,
                        "created_at"      => 1614325826,
                        "fee_type"        => null
                    ]
                ]
            ],
        ],
    ],

    'testValidatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/validate_payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'OK'
            ],
        ],
    ],

    'testGetPayoutAnalytics' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/analytics',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'data' => [
                    'payouts_count' => [
                        'result' => [
                                    [
                                        'value' => 3
                                    ]
                                    ],
                                   'last_updated_at' => 1637643003
                            ],
                    'payouts_daywise' => [
                        'result' => [
                                [
                                    'value' => 0,
                                    'timestamp' =>  1635051003,
                                ],
                                [
                                    'value' => 100,
                                    'timestamp' => 1635137403,
                                ]
                            ],
                            'last_updated_at' => 1637643003
                        ],
                    'payouts' => [
                        'result' => [
                                    [
                                        'value' => 300
                                    ]
                                ],
                                'last_updated_at' => 1637643003
                            ],
                    ]
            ],
        ],
    ],

    'testValidatePayoutFailCase' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/validate_payouts',
            'content' => [
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutServiceFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                "id"              => "Gg7sgBZgvYjlSB",
                "merchant_id"     => "10000000000000",
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateReversalEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reversal/create',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testUpdateFTAAndPayoutProcessed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testUpdateFTAAndPayoutProcessedWithExperiment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
                'gateway_ref_no'      => '43426',
                'source_account_id'   => '632563563',
                'bank_account_type'   => 'saving'
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testUpdateFTAAndPayoutInitiated' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'INITIATED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testUpdateFTAAndPayoutToFailed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'INVALID',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testUpdateFTAAndPayoutDetailsFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],


    'testUpdateFTAAndPayoutStatusFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreatePayoutForCard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'NEFT',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testServiceCancelQueuedPayoutProxyAuth' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testServiceCancelQueuedPayoutPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'remarks' => 'test remark'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreateScheduledPayoutViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'test Merchant Fund Transfer',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'scheduled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreatePayoutWithIdempotencyKey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
            ],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_PAYOUT_IDEMPOTENCY => 'idem_key_test',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreateOnHoldPayoutViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateQueuedPayoutViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 500,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateQueuedPayoutViaAPIWhenWorkflowAndOnHoldEnabledForMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 500,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testRetryPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/retry',
            'content' => [
                'payout_ids' => []
            ],
        ],
        'response' => [
            'content' => [
                'total_count'       => 1,
                'success_count'     => 1,
                'failure_count'     => 0,
                'failed_payout_ids' => [],
            ],
        ],
    ],

    'testRetryPayoutServiceFail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/retry',
            'content' => [
                'payout_ids' => []
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateLedgerForQueuedPayoutCreatedViaService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "queue_if_low_balance" => true,
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'queued',
                'error'         => null,
                "queued_reason" => QueuedReasons::LOW_BALANCE,
            ],
        ],
    ],

    'testCreateLedgerForOnHoldPayoutCreatedViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id"  => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'created',
                'error'         => null,
                'queued_reason' => null,
                'status_code'   => null
            ],
        ],
    ],

    'testServiceCancelFailure' => [
        'request'  => [
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateLedgerForStatusCodeValueFowLowBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'failed',
                'error'         => 'Insufficient balance to process payout',
                'queued_reason' => null,
                'status_code'   => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_workflow_for_payout',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "channel"              => "",
                "amount"               => 54321,
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
            ],
        ],
        'response' => [
            'content' => [
                'is_workflow_activated'  => true,
                'error'                  => null,
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntryDuplicateRequest' =>   [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_workflow_for_payout',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "channel"              => "",
                "amount"               => 54321,
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
            ],
        ],
        'response' => [
            'content' => [
                'is_workflow_activated'  => true,
                'error'                  => null,
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntryForNonWorkflowPayout' => [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/payouts_service/create_workflow_for_payout',
                'server' => [
                    'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
                ],
                'content' => [
                    "id"                   => "Gg7sgBZgvYjlSB",
                    "mode"                 => "IMPS",
                    "currency"             => "INR",
                    "purpose"              => "refund",
                    "fund_account_id"      => "fa_100000000000fa",
                    "balance_id"           => "GhidjxhfiCL7WT",
                    "channel"              => "",
                    "amount"               => 54321,
                    "type"                 => "",
                    "reference_id"         => null,
                    "narration"            => "test Merchant Fund Transfer",
                    "fee_type"             => "",
                    "queue_if_low_balance" => false,
                    "notes"                => []
                ],
            ],
            'response' => [
                'content' => [
                    'is_workflow_activated'  => false,
                    'error'                  => null,
                ],
            ],
        ],

    'testApprovePayoutForPayoutServicePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content'   => [

            ],
            'status_code' => 200,
        ],
    ],

    'testApprovePayoutForPayoutServicePayout_Without_WorkflowExperiment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testRejectPayoutForPayoutServicePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content'   => [

            ],
            'status_code' => 200,
        ],
    ],

    'testRejectPayoutForPayoutServicePayout_Without_WorkflowExperiment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testAdminFetchPayoutsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payouts/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchReversalsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.reversals/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchPayoutLogsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payout_logs/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchPayoutSourcesViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payout_sources/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFreePayoutMigrationAdminAction' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION           => EntityConstants::ENABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutMigrationAdminActionWithLedgerReverseShadowNotAssigned' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION           => EntityConstants::ENABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutMigrationAdminActionWithPayoutServiceEnabledFeature' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION           => EntityConstants::ENABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutMigrationAdminActionDisableAction' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION => EntityConstants::DISABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutMigrationAdminActionValidationFailure' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION           => EntityConstants::ENABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The ids.0.balance_id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFreePayoutMigrationAdminActionWithPayoutsServiceFailure' => [
        'request'  => [
            'url'     => '/admin/payouts/free_payout_migration',
            'method'  => 'post',
            'content' => [
                EntityConstants::ACTION           => EntityConstants::ENABLE,
                'ids' => [
                    [
                        Entity::MERCHANT_ID => '10000000000000'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutRollback' => [
        'request'  => [
            'url'     => '/payouts_service/free_payout_rollback',
            'method'  => 'post',
            'content' => [
                Entity::MERCHANT_ID                                => '10000000000000',
                EntityConstants::BALANCE_TYPE                      => 'shared',
                CounterEntity::FREE_PAYOUTS_CONSUMED               => 200,
                CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 1656613800,
                Balance\FreePayout::FREE_PAYOUTS_COUNT             => 300,
                Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES   => ['IMPS', 'RTGS'],
            ]
        ],
        'response' => [
            'content'     => [
                EntityConstants::COUNTERS_ROLLBACK  => true,
                EntityConstants::SETTINGS_ROLLBACK  => true
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutRollbackValidationFailure' => [
        'request'  => [
            'url'     => '/payouts_service/free_payout_rollback',
            'method'  => 'post',
            'content' => [
                Entity::MERCHANT_ID                                => '10000000000000',
                EntityConstants::BALANCE_TYPE                      => 'shared',
                CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 1656613800,
                Balance\FreePayout::FREE_PAYOUTS_COUNT             => 300,
                Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES   => ['IMPS', 'RTGS'],
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The free payouts consumed field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFreePayoutRollbackWithCounterAttributes' => [
        'request'  => [
            'url'     => '/payouts_service/free_payout_rollback',
            'method'  => 'post',
            'content' => [
                Entity::MERCHANT_ID                                => '10000000000000',
                EntityConstants::BALANCE_TYPE                      => 'shared',
                CounterEntity::FREE_PAYOUTS_CONSUMED               => 200,
                CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 1656613800,
            ]
        ],
        'response' => [
            'content'     => [
                EntityConstants::COUNTERS_ROLLBACK  => true,
                EntityConstants::SETTINGS_ROLLBACK  => true
            ],
            'status_code' => 200,
        ],
    ],

    'testFreePayoutRollbackWithoutLedgerReverseShadowFeatureAssigned' => [
        'request'  => [
            'url'     => '/payouts_service/free_payout_rollback',
            'method'  => 'post',
            'content' => [
                Entity::MERCHANT_ID                                => '10000000000000',
                EntityConstants::BALANCE_TYPE                      => 'shared',
                CounterEntity::FREE_PAYOUTS_CONSUMED               => 200,
                CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 1656613800,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR
        ],
    ],

    'testFreePayoutRollbackWithoutPayoutServiceEnabledFeatureAssigned' => [
        'request'  => [
            'url'     => '/payouts_service/free_payout_rollback',
            'method'  => 'post',
            'content' => [
                Entity::MERCHANT_ID                                => '10000000000000',
                EntityConstants::BALANCE_TYPE                      => 'shared',
                CounterEntity::FREE_PAYOUTS_CONSUMED               => 200,
                CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 1656613800,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR
        ],
    ],

    'testUpdateFreePayoutsCountAndMode' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'           => 12,
                'free_payouts_supported_modes' => ['IMPS']
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_count'           => 12,
                'free_payouts_supported_modes' => ['IMPS']
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateFreePayoutsServiceFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/balance/{id}/free_payout',
            'content' => [
                'free_payouts_count'           => 12,
                'free_payouts_supported_modes' => ['IMPS']
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],


    'testAdminGetFreePayoutsCountFromPS' => [
        'request'  => [
            'url'     => '/admin/payouts/{id}/free_payout',
            'method'  => 'GET',
            'content' => [
                'free_payouts_count'             => 12,
                'free_payouts_supported_modes'   => ['IMPS']
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_count'             => 12,
                'free_payouts_supported_modes'   => ['IMPS']
            ],
        ],
        'status_code' => 200,
    ],

    'testXDashboardGetFreePayoutsCountFromPS' => [
        'request'  => [
            'url'     => '/admin/payouts/{id}/free_payout',
            'method'  => 'GET',
            'content' => [
                'free_payouts_count'             => 12,
                'free_payouts_supported_modes'   => ['IMPS']
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_count'             => 12,
                'free_payouts_supported_modes'   => ['IMPS']
            ],
            'status_code' => 200,
        ],
    ],

    'testBulkPayout_NotesAsEmptyArray' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => ' 2224440041626905 ',
                    'notes'                     => [],
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => ' 2224440041626905 ',
                    'notes'                     => [
                        "place" => "bangalore"
                    ],
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAccount_SinglePayout_SpacesInAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => ' 2224440041626905 ',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAccount_MultiplePayout_SpacesInAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => ' 2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905  ',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_DirectAccount_SinglePayout_SpacesInAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_DirectAccount_MultiplePayout_SpacesInAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => ' 2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => ' 2224440041626907 ',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAndDirectAccounts_SpacesInAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905 ',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => ' 2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => ' 2224440041626907 ',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907 ',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 4,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc12345'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123456'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAccount_BalanceRecordNotAvailableForMerchant' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905 ',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'Balance records are not available for the merchant',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_BALANCE_RECORDS_NOT_AVAILABLE_FOR_MERCHANT,
        ],
    ],

    'testBulkPayout_InvalidAccountNumber' => [
        'request'  => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number' => '2224440041626909',
                    'payout'                   => [
                        'amount'       => '100',
                        'currency'     => 'INR',
                        'mode'         => 'IMPS',
                        'purpose'      => 'refund',
                        'narration'    => '123',
                        'reference_id' => ''
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Vivek Karna',
                        'account_IFSC'   => 'HDFC0003780',
                        'account_number' => '50100244702362',
                        'account_vpa'    => ''
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Vivek Karna',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'idempotency_key'          => 'batch_abc123'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc123',
                        'error'           => [
                            'description' => 'The RazorpayX Account number is invalid.',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_ValidSharedAccount_And_InvalidAccountNumber' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 4,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc12345',
                        'error'           => [
                            'description' => 'The RazorpayX Account number is invalid.',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc123456',
                        'error'           => [
                            'description' => 'The RazorpayX Account number is invalid.',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAccount' => [
        'request'  => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number' => '2224440041626905',
                    'payout'                   => [
                        'amount'       => '100',
                        'currency'     => 'INR',
                        'mode'         => 'IMPS',
                        'purpose'      => 'refund',
                        'narration'    => '123',
                        'reference_id' => ''
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Vivek Karna',
                        'account_IFSC'   => 'HDFC0003780',
                        'account_number' => '50100244702362',
                        'account_vpa'    => ''
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Vivek Karna',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'idempotency_key'          => 'batch_abc123'
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'          => 'payout',
                        'fund_account'    => [
                            'entity'       => 'fund_account',
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'ifsc'           => 'HDFC0003780',
                                'bank_name'      => 'HDFC Bank',
                                'name'           => 'Vivek Karna',
                                'account_number' => '50100244702362',
                            ],
                            'active'       => true,
                        ],
                        'amount'          => 100,
                        'currency'        => 'INR',
                        'fees'            => 590,
                        'tax'             => 90,
                        'status'          => 'processing',
                        'purpose'         => 'refund',
                        'utr'             => null,
                        'user_id'         => 'MerchantUser01',
                        'mode'            => 'IMPS',
                        'reference_id'    => null,
                        'narration'       => '123',
                        'idempotency_key' => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_MultiplePayouts_SameSharedAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_DirectAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_MultiplePayouts_SameDirectAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAndDirectAccounts' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 4,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc12345'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 200,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123456'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAndDirectAccount_ExceptionFromSharedAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testBulkPayout_SharedAndDirectAccount_TimeoutFromPayoutsService' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\ServerErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testBulkPayout_ExceptionFromDirectAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'batch_id not present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkPayout_SharedAndDirectAccount_ExceptionFromDirectAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123455'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123456'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123457'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123458'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123459'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123460'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc1234461'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123462'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc1234463'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc1234464'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123465'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123466'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123467'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123468'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123469'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'idempotency_key'           => 'batch_abc123470'
                ],
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc1234'
                    ],
                ]
            ],
        ],
    ],

    'testBulkPayout_SharedAndDirectAccounts_ExperimentOff' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc1234'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc12345'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626907',
                    'payout'                    => [
                        'amount'                => '200',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123456'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'CA merchant should not come into Bulk Payout VA flow',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_CA_MERCHANT_IN_BULK_PAYOUT_VA_FLOW,
        ],
    ],

    'testBulkPayoutServiceFailure' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testProcessBulkPayoutDelayedInitiationForPayoutsService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/process',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutToFundAccount_BatchID_In_Input' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'batch_id'        => '123456',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'batch_id, idempotency_key is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreatePayoutToFundAccount_IdempotencyKey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'idempotency_key' => 'ikey_12345',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'batch_id, idempotency_key is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testDccPayoutsDetailsFetch' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/consistency_checker/fetch',
        ],
        'response' => [
            'content'     => [
                "payout_details" => [[]]
            ],
            'status_code' => 200,
        ],
    ],

    'testInitiatePayoutsConsistencyCheck' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/consistency_checker',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testInitiatePayoutsConsistencyCheckError' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/consistency_checker',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testInitiateBatchSubmittedCron' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/batch/process',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testInitiateBatchSubmittedCronFailure' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/batch/process',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPayoutsServiceCreateFailureProcessingCron' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/cron/create_failure_processing',
            'content' => [
                'count'  => 1,
                'days'   => 2,
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPayoutsServiceCreateFailureProcessingCronAndCountMissing' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/cron/create_failure_processing',
            'content' => [
                'days'   => 2,
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The count field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutsServiceCreateFailureProcessingCronAndDaysMissing' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/cron/create_failure_processing',
            'content' => [
                'count'  => 1,
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The days field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutsServiceUpdateFailureProcessingCron' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/cron/update_failure_processing',
            'content' => [
                'count'  => 1,
                'days'   => 2,
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDccPayoutsDetailsFetchPayoutCountValidationFailure' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/consistency_checker/fetch',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payout ids may not have more than 1000 items.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDccPayoutsDetailsFetchPayoutIdLengthValidationFailure' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/consistency_checker/fetch',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payout_ids.0 must be 14 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutServiceMerchantFeatureAddition' => [
        'request'   => [
            'content' => [
                'names'       => ['new_banking_error'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response'  => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPayoutServiceMerchantFeatureAdditionServiceRequestFailure' => [
        'request'   => [
            'content' => [
                'names'       => ['new_banking_error'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response'  => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPayoutServiceMerchantFeatureDeletion' => [
        'request'   => [
            'content' => [
                'should_sync'  => false,
            ],
            'url'     => '/accounts/10000000000000/features/free_payout_ledger_via_ps',
            'method'  => 'DELETE',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response'  => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPayoutServiceMerchantFeatureDeletionServiceRequestFailure' => [
        'request'   => [
            'content' => [
                'should_sync'  => false,
            ],
            'url'     => '/accounts/10000000000000/features/free_payout_ledger_via_ps',
            'method'  => 'DELETE',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response'  => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testRemoveFeatureFromMerchantDashboard' => [
        'request'   => [
            'url'     => '/merchants/me/features',
            'method'  => 'post',
            'content' => [
                "features"    => [
                    'skip_workflow_for_api' => 0
                ],
            ],
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'test@razorpay.com',
            ],
        ],
        'response'  => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDecrementFreePayoutsConsumedForPayoutsService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/decrement_free_payouts',
            'content' => [
                "merchant_id"          => "10000000000000",
                "balance_id"           => "bal12345678909",
                "payout_id"            => "dummypayout123",
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200
        ],
    ],

    'testDecrementFreePayoutsConsumedForPayoutsServiceValidationFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/decrement_free_payouts',
            'content' => [
                "merchant_id"          => "10000000000000",
                "balance_id"           => "bal12345678909",
                "payout_id"            => "dummypayout123",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The balance id must be 14 characters.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactWithPayoutsServiceInternalAuth' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
                'batch_id'        => 'KNLfqctfnSg4yY',
                'idempotency_key' => 'batch_KJjiE5OFofdtBE',
            ],
            'url'     => '/contacts_internal',
            'server'  =>  [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
                'batch_id'        => 'batch_KNLfqctfnSg4yY',
            ],
            'status_code' => '201'
        ],
    ],

    'testPayoutsServiceInternalFundAccountCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => '',
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

    'testGetScheduleTimeSlotsForDashboard' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/schedule/timeslots'
        ],
        'response' => [
            'content' => [
                '9',
                '13',
                '17',
                '21',
            ],
        ],
    ],

    'testWorkflowStateCallbackFromPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/state/callback',
            'content' => [
                "Id"         => "FSYqHROoUij6TF",
                "GroupName"  => "ABC",
                "Name"       => "Owner_Approval",
                "Rules"      => [
                    "ActorPropertyKey"   => "role",
                    "ActorPropertyValue" => "owner",
                ],
                "Status"     => "created",
                "Type"       => "checker",
                "WorkflowId" => "FSYpen1s24sSbs",
            ],
        ],
        'response' => [
            'content' => [
                "workflow_id"      => "FSYpen1s24sSbs",
                "merchant_id"      => "10000000000000",
                "org_id"           => "100000razorpay",
                "actor_type_key"   => "role",
                "actor_type_value" => "owner",
                "state_id"         => "FSYqHROoUij6TF",
                "state_name"       => "Owner_Approval",
                "status"           => "created",
                "group_name"       => "ABC",
                "type"             => "checker"
            ],
        ],
    ],

    'testWorkflowStateCallbackWithoutEntityMapFromPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/state/callback',
            'content' => [
                "Id"         => "FSYqHROoUij6TF",
                "GroupName"  => "ABC",
                "Name"       => "Owner_Approval",
                "Rules"      => [
                    "ActorPropertyKey"   => "role",
                    "ActorPropertyValue" => "owner",
                ],
                "Status"     => "created",
                "Type"       => "checker",
                "WorkflowId" => "randomid111127",
            ],
        ],
        'response' => [
            'content' => [
                "workflow_id"      => "randomid111127",
                "merchant_id"      => "10000000000000",
                "org_id"           => "100000razorpay",
                "actor_type_key"   => "role",
                "actor_type_value" => "owner",
                "state_id"         => "FSYqHROoUij6TF",
                "state_name"       => "Owner_Approval",
                "status"           => "created",
                "group_name"       => "ABC",
                "type"             => "checker"
            ],
        ],
    ],

    'testWorkflowStateCallbackWithoutEntityMapFromPayoutServiceFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/state/callback',
            'content' => [
                "Id"         => "FSYqHROoUij6TF",
                "GroupName"  => "ABC",
                "Name"       => "Owner_Approval",
                "Rules"      => [
                    "ActorPropertyKey"   => "role",
                    "ActorPropertyValue" => "owner",
                ],
                "Status"     => "created",
                "Type"       => "checker",
                "WorkflowId" => "randomid111127",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testWorkflowStateUpdateCallbackFromPayoutService' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/wf-service/state/FSYqHROoUij6TF/callback',
            'content' => [
                "Status"     => "processed",
                "WorkflowId" => "FSYpen1s24sSbs",
            ],
        ],
        'response' => [
            'content' => [
                "workflow_id"      => "FSYpen1s24sSbs",
                "merchant_id"      => "10000000000000",
                "org_id"           => "100000razorpay",
                "actor_type_key"   => "role",
                "actor_type_value" => "owner",
                "state_id"         => "FSYqHROoUij6TF",
                "state_name"       => "Owner_Approval",
                "status"           => "processed",
                "group_name"       => "ABC",
                "type"             => "checker"
            ],
        ],
    ],

    'testEnablePayoutServiceFeature' => [
        'request'   => [
            'content' => [
                'names'       => ['payout_service_enabled'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature payout_service_enabled is not allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRenameAttachmentsForPayoutService' => [
        'request'  => [
            'content' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_10RandomFileId',
                        'file_name' => 'Random File.pdf',
                    ],
                    [
                        'file_id'   => 'file_11RandomFileId',
                        'file_name' => 'Random File 1.pdf',
                    ],
                    [
                        'file_id'   => 'file_12RandomFileId',
                        'file_name' => 'Random File 2.pdf',
                    ],
                    [
                        'file_id'   => 'file_13RandomFileId',
                        'file_name' => 'Random File 3.pdf',
                    ],
                ],
            ],
            'url'     => '/payouts_service/renameAttachments/10RandomPoutID',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => ['result' => [null, null, null, null]],
            'status_code' => 200,
        ],
    ],

    'testUpdateAttachmentWithProxyAuthForPayoutServicePayout' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testUpdateAttachmentWithProxyAuthForPayoutServicePayoutWithPayoutSourceNotNull' => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Payout Source for Update',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PAYOUT_SOURCE_FOR_UPDATE,
        ],
    ],

    'testUpdateAttachmentWithProxyAuthForPayoutServicePayoutWithErrorFromPayoutService' => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'Failed to update attachment for payout',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_ATTACHMENT_UPDATE_FAILURE,
            'description'         => 'Could not update attachment for Payout'
        ],
    ],

    'testUpdateAttachmentForPayoutLinkForPayoutServicePayout' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts_internal/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithEmptySource' => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/payouts_internal/attachments',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Update Payout Attachments request',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UPDATE_PAYOUT_ATTACHMENTS,
        ],
    ],

    'testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithFailureFromPayoutService' => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/payouts_internal/attachments',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'Failed to update attachment for payout',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_ATTACHMENT_UPDATE_FAILURE,
            'description'         => 'Could not update attachment for Payout'
        ],
    ],
];

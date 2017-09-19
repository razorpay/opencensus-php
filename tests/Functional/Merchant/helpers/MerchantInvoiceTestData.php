<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testInvoiceEntityCreateForPrevMonth' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testInvoiceEntityCreateForGivenMonthYear' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testFeeAdjustment' => [
        'merchant_id'   => '10000000000000',
        'gstin'         => '29kjsngjk213922',
        'type'          => 'adjustment',
        'amount'        => -1300,
        'tax'           => 0,
    ],

    'testInvoiceEntityCreateForGivenMerchant' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testEditGstinFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Invoice Number.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER,
        ],
    ],
];


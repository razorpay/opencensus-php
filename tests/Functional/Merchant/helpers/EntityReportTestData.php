<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testLinkedAccountExportNonMarketplace' => [
        'request' => [
            'url' => '/reports/account/file',
            'method' => 'get',
            'content' => []
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Exporting this data is not allowed for the merchant'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvoiceNew' => [
        'Tax Invoice' => [
            'Comission on Card Payments <= INR 2,000' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Comission on Card Payments <= INR 2,000',
                'Amount'        => 500,
                'SGST @ 9%'     => 11,
                'CGST @ 9%'     => 11,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 22,
                'Grand Total'   => 522,
            ],
            'Comission on Card Payments > INR 2,000' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Comission on Card Payments > INR 2,000',
                'Amount'        => 500,
                'SGST @ 9%'     => 11,
                'CGST @ 9%'     => 11,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 22,
                'Grand Total'   => 522,
            ],
            'Comission on All Methods Except Cards' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Comission on All Methods Except Cards',
                'Amount'        => 500,
                'SGST @ 9%'     => 11,
                'CGST @ 9%'     => 11,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 22,
                'Grand Total'   => 522,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 1500,
                'SGST @ 9%'     => 33,
                'CGST @ 9%'     => 33,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 66,
                'Grand Total'   => 1566,
            ],
        ],
        'Tax Debit Note' => [
            'Adjustment against extra commission' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Adjustment against extra commission',
                'Amount'        => 450,
                'SGST @ 9%'     => 9,
                'CGST @ 9%'     => 9,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 18,
                'Grand Total'   => 468,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 450,
                'SGST @ 9%'     => 9,
                'CGST @ 9%'     => 9,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 18,
                'Grand Total'   => 468,
            ],
        ],
        'Tax Credit Note' => [
            'Adjustment against uncharged fee' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Adjustment against uncharged fee',
                'Amount'        => 250,
                'SGST @ 9%'     => 4,
                'CGST @ 9%'     => 4,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 8,
                'Grand Total'   => 258,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 250,
                'SGST @ 9%'     => 4,
                'CGST @ 9%'     => 4,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 8,
                'Grand Total'   => 258,
            ],
        ],
    ],
];
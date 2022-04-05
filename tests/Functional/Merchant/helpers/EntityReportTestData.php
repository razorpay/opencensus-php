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
            'Commission on Card Payments <= INR 2,000' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Commission on Card Payments <= INR 2,000',
                'Amount'        => 50000,
                'SGST @ 9%'     => 1100,
                'CGST @ 9%'     => 1100,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 2200,
                'Grand Total'   => 52200,
            ],
            'Commission on Card Payments > INR 2,000' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Commission on Card Payments > INR 2,000',
                'Amount'        => 50000,
                'SGST @ 9%'     => 1100,
                'CGST @ 9%'     => 1100,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 2200,
                'Grand Total'   => 52200,
            ],
            'Commission on All Methods Except Cards' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Commission on All Methods Except Cards',
                'Amount'        => 50000,
                'SGST @ 9%'     => 1100,
                'CGST @ 9%'     => 1100,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 2200,
                'Grand Total'   => 52200,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 150000,
                'SGST @ 9%'     => 3300,
                'CGST @ 9%'     => 3300,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 6600,
                'Grand Total'   => 156600,
            ],
        ],
        'Tax Debit Note' => [
            'Adjustment against extra commission' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Adjustment against extra commission',
                'Amount'        => 45000,
                'SGST @ 9%'     => 900,
                'CGST @ 9%'     => 900,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 1800,
                'Grand Total'   => 46800,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 45000,
                'SGST @ 9%'     => 900,
                'CGST @ 9%'     => 900,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 1800,
                'Grand Total'   => 46800,
            ],
        ],
        'Tax Credit Note' => [
            'Adjustment against uncharged fee' => [
                'GST.SAC Code'  => '997158',
                'Description'   => 'Adjustment against uncharged fee',
                'Amount'        => 25000,
                'SGST @ 9%'     => 400,
                'CGST @ 9%'     => 400,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 800,
                'Grand Total'   => 25800,
            ],
            'Total' => [
                'GST.SAC Code'  => '',
                'Description'   => 'Total',
                'Amount'        => 25000,
                'SGST @ 9%'     => 400,
                'CGST @ 9%'     => 400,
                'IGST @ 18%'    => 0,
                'Tax Total'     => 800,
                'Grand Total'   => 25800,
            ],
        ],
    ],
];

<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetTax' => [
        'request' => [
            'url'     => '/taxes/tax_00000000000001',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'        => 'tax_00000000000001',
                'entity'    => 'tax',
                'name'      => 'Sample tax',
                'rate_type' => 'percentage',
                'rate'      => 1000,
            ],
        ],
    ],

    'testGetMultipleTaxes' => [
        'request' => [
            'url'     => '/taxes',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'        => 'tax_00000000000002',
                        'entity'    => 'tax',
                        'name'      => 'Sample tax',
                        'rate_type' => 'percentage',
                        'rate'      => 1000,
                    ],
                    [
                        'id'        => 'tax_00000000000001',
                        'entity'    => 'tax',
                        'name'      => 'Sample tax',
                        'rate_type' => 'percentage',
                        'rate'      => 1000,
                    ],
                ],
            ],
        ],
    ],

    'testCreateTax' => [
        'request' => [
            'url'     => '/taxes',
            'method'  => 'post',
            'content' => [
                'name'      => 'New tax',
                'rate'      => 1020,
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'tax',
                'name'      => 'New tax',
                'rate_type' => 'percentage',
                'rate'      => 1020,
            ],
        ],
    ],

    'testCreateTaxWithInvalidPercentageRateValue' => [
        'request' => [
            'url'     => '/taxes',
            'method'  => 'post',
            'content' => [
                'name'      => 'New tax',
                'rate_type' => 'percentage',
                'rate'      => 10200,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'rate should be between 0 to 10000 if rate_type is percentage',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateTax' => [
        'request' => [
            'url'     => '/taxes/tax_00000000000001',
            'method'  => 'patch',
            'content' => [
                'name'      => 'Updated tax name',
                'rate_type' => 'flat',
                'rate'      => 50000,
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'tax_00000000000001',
                'entity'    => 'tax',
                'name'      => 'Updated tax name',
                'rate_type' => 'flat',
                'rate'      => 50000,
            ],
        ],
    ],

    'testUpdateTaxWithInvalidRateTypeAndValueCombination' => [
        'request' => [
            'url'     => '/taxes/tax_00000000000001',
            'method'  => 'patch',
            'content' => [
                'rate_type' => 'percentage',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'rate should be between 0 to 10000 if rate_type is percentage',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteTax' => [
        'request' => [
            'url'     => '/taxes/tax_00000000000001',
            'method'  => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'      => 'tax_00000000000001',
                'deleted' => true,
            ],
        ],
    ],
];

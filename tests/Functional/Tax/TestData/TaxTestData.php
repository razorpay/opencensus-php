<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetTax' => [
        'request'  => [
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
        'request'  => [
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
        'request'  => [
            'url'     => '/taxes',
            'method'  => 'post',
            'content' => [
                'name' => 'New tax',
                'rate' => 1020,
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
        'request'   => [
            'url'     => '/taxes',
            'method'  => 'post',
            'content' => [
                'name'      => 'New tax',
                'rate_type' => 'percentage',
                'rate'      => 10200,
            ],
        ],
        'response'  => [
            'content'     => [
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
        'request'  => [
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
        'request'   => [
            'url'     => '/taxes/tax_00000000000001',
            'method'  => 'patch',
            'content' => [
                'rate_type' => 'percentage',
            ],
        ],
        'response'  => [
            'content'     => [
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
        'request'  => [
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

    'testGetTaxMetaTaxRates' => [
        'request'  => [
            'url'     => '/taxes/meta/gst_taxes',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'gst_tax_slabs'  => [
                    0,
                    500,
                    1200,
                    1800,
                    2800,
                ],
                'gst_tax_id_map' => [
                    "IGST_0"     => "tax_9nDpYboKAK9j7t",
                    "IGST_500"   => "tax_9nDpYciCWeNBzE",
                    "IGST_1200"  => "tax_9nDpYdbYNqD4Rw",
                    "IGST_1800"  => "tax_9nDpYf1tTUs2Vh",
                    "IGST_2800"  => "tax_9nDpYfqgnYW5Dx",
                    "CGST_0"     => "tax_9nDpYglSpU58lc",
                    "CGST_250"   => "tax_9nDpYhZ0d60X7V",
                    "CGST_500"   => "tax_9nDpYiArP6j0qT",
                    "CGST_600"   => "tax_9nDpYivRHUQQV8",
                    "CGST_900"   => "tax_9nDpYjuyZsOlMK",
                    "CGST_1200"  => "tax_9nDpYkng64GyTa",
                    "CGST_1400"  => "tax_9nDpYlTs7cWM80",
                    "CGST_1800"  => "tax_9nDpYmPK2K2mVi",
                    "CGST_2800"  => "tax_9nDpYnFEoqJQ5v",
                    "SGST_0"     => "tax_9nDpYnvgiGXrZh",
                    "SGST_250"   => "tax_9nDpYoeYBsXRvC",
                    "SGST_500"   => "tax_9nDpYpMRZgJEgU",
                    "SGST_600"   => "tax_9nDpYpuN72gdfY",
                    "SGST_900"   => "tax_9nDpYqgYcqpr8q",
                    "SGST_1200"  => "tax_9nDpYrIMXQTtPd",
                    "SGST_1400"  => "tax_9nDpYs1yK0pndD",
                    "SGST_1800"  => "tax_9nDpYsoU7subph",
                    "SGST_2800"  => "tax_9nDpYtb0S0JhMP",
                    "UTGST_0"    => "tax_9nDpYuFVNQcVaU",
                    "UTGST_250"  => "tax_9nDpYv53mqSsip",
                    "UTGST_500"  => "tax_9nDpYvgwu0p8WP",
                    "UTGST_600"  => "tax_9nDpYwRScK0Mz2",
                    "UTGST_900"  => "tax_9nDpYxMkO0LLhz",
                    "UTGST_1200" => "tax_9nDpYyC50acDzW",
                    "UTGST_1400" => "tax_9nDpYz26oaOHgI",
                    "UTGST_1800" => "tax_9nDpYznDzU7NKP",
                    "UTGST_2800" => "tax_9nDpZ0hEw4vZky",
                ],
            ],
        ],
    ],

    'testGetTaxMetaStates' => [
        'request'  => [
            'url'    => '/taxes/meta/states',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 37,
                'items'  => [
                    [
                        'name' => 'Andaman and Nicobar Islands',
                        'code' => '35'
                    ],
                    [
                        'name' => 'Andhra Pradesh',
                        'code' => '28'
                    ],
                    [
                        'name' => 'Andhra Pradesh (New)',
                        'code' => '37'
                    ],
                    [
                        'name' => 'Arunachal Pradesh',
                        'code' => '12'
                    ],
                    [
                        'name' => 'Assam',
                        'code' => '18'
                    ],
                    [
                        'name' => 'Bihar',
                        'code' => '10'
                    ],
                    [
                        'name' => 'Chandigarh',
                        'code' => '04'
                    ],
                    [
                        'name' => 'Chattisgarh',
                        'code' => '22'
                    ],
                    [
                        'name' => 'Dadra and Nagar Haveli',
                        'code' => '26'
                    ],
                    [
                        'name' => 'Daman and Diu',
                        'code' => '25'
                    ],
                    [
                        'name' => 'Delhi',
                        'code' => '07'
                    ],
                    [
                        'name' => 'Goa',
                        'code' => '30'
                    ],
                    [
                        'name' => 'Gujarat',
                        'code' => '24'
                    ],
                    [
                        'name' => 'Haryana',
                        'code' => '06'
                    ],
                    [
                        'name' => 'Himachal Pradesh',
                        'code' => '02'
                    ],
                    [
                        'name' => 'Jammu and Kashmir',
                        'code' => '01'
                    ],
                    [
                        'name' => 'Jharkhand',
                        'code' => '20'
                    ],
                    [
                        'name' => 'Karnataka',
                        'code' => '29'
                    ],
                    [
                        'name' => 'Kerala',
                        'code' => '32'
                    ],
                    [
                        'name' => 'Lakshadweep Islands',
                        'code' => '31'
                    ],
                    [
                        'name' => 'Madhya Pradesh',
                        'code' => '23'
                    ],
                    [
                        'name' => 'Maharashtra',
                        'code' => '27'
                    ],
                    [
                        'name' => 'Manipur',
                        'code' => '14'
                    ],
                    [
                        'name' => 'Meghalaya',
                        'code' => '17'
                    ],
                    [
                        'name' => 'Mizoram',
                        'code' => '15'
                    ],
                    [
                        'name' => 'Nagaland',
                        'code' => '13'
                    ],
                    [
                        'name' => 'Odisha',
                        'code' => '21'
                    ],
                    [
                        'name' => 'Pondicherry',
                        'code' => '34'
                    ],
                    [
                        'name' => 'Punjab',
                        'code' => '03'
                    ],
                    [
                        'name' => 'Rajasthan',
                        'code' => '08'
                    ],
                    [
                        'name' => 'Sikkim',
                        'code' => '11'
                    ],
                    [
                        'name' => 'Tamil Nadu',
                        'code' => '33'
                    ],
                    [
                        'name' => 'Telangana',
                        'code' => '36'
                    ],
                    [
                        'name' => 'Tripura',
                        'code' => '16'
                    ],
                    [
                        'name' => 'Uttar Pradesh',
                        'code' => '09'
                    ],
                    [
                        'name' => 'Uttarakhand',
                        'code' => '05'
                    ],
                    [
                        'name' => 'West Bengal',
                        'code' => '19'
                    ],
                ],
            ],
        ],
    ],
];

<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetTaxGroup' => [
        'request' => [
            'url'     => '/tax_groups/taxg_00000000000001',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'     => 'taxg_00000000000001',
                'entity' => 'tax_group',
                'name'   => 'Tax Group #1',
                'taxes'  => [
                    [
                        'id'        => 'tax_00000000000001',
                        'entity'    => 'tax',
                        'name'      => 'Tax #1',
                        'rate_type' => 'percentage',
                        'rate'      => 100000,
                    ],
                    [
                        'id'        => 'tax_00000000000002',
                        'entity'    => 'tax',
                        'name'      => 'Tax #2',
                        'rate_type' => 'percentage',
                        'rate'      => 200000,
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleTaxGroups' => [
        'request' => [
            'url'     => '/tax_groups',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'     => 'taxg_00000000000002',
                        'entity' => 'tax_group',
                        'name'   => 'Tax Group #2',
                        'taxes'  => [
                            [
                                'id'        => 'tax_00000000000001',
                                'entity'    => 'tax',
                                'name'      => 'Tax #1',
                                'rate_type' => 'percentage',
                                'rate'      => 100000,
                            ],
                            [
                                'id'        => 'tax_00000000000004',
                                'entity'    => 'tax',
                                'name'      => 'Flat Tax #4',
                                'rate_type' => 'flat',
                                'rate'      => 100,
                            ],
                        ],
                    ],
                    [
                        'id'     => 'taxg_00000000000001',
                        'entity' => 'tax_group',
                        'name'   => 'Tax Group #1',
                        'taxes'  => [
                            [
                                'id'        => 'tax_00000000000001',
                                'entity'    => 'tax',
                                'name'      => 'Tax #1',
                                'rate_type' => 'percentage',
                                'rate'      => 100000,
                            ],
                            [
                                'id'        => 'tax_00000000000002',
                                'entity'    => 'tax',
                                'name'      => 'Tax #2',
                                'rate_type' => 'percentage',
                                'rate'      => 200000,
                            ],
                        ],
                    ]
                ],
            ],
        ],
    ],

    'testCreateTaxGroup' => [
        'request' => [
            'url'     => '/tax_groups',
            'method'  => 'post',
            'content' => [
                'name'    => 'New tax group',
                'tax_ids' => [
                    'tax_00000000000001',
                    'tax_00000000000002'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'tax_group',
                'name' => 'New tax group',
                'taxes' => [
                    [
                        'id'         => 'tax_00000000000001',
                        'entity'     => 'tax',
                        'name'       => 'Tax #1',
                        'rate_type'  => 'percentage',
                        'rate'       => 100000,
                    ],
                    [
                        'id'         => 'tax_00000000000002',
                        'entity'     => 'tax',
                        'name'       => 'Tax #2',
                        'rate_type'  => 'percentage',
                        'rate'       => 200000,
                    ],
                ],
            ],
        ],
    ],

    'testCreateTaxGroupWithInvalidNumberOfTaxIds' => [
        'request' => [
            'url'     => '/tax_groups',
            'method'  => 'post',
            'content' => [
                'name'    => 'New tax group',
                'tax_ids' => [
                    'tax_00000000000001',
                    'tax_00000000000002',
                    'tax_00000000000003',
                    'tax_00000000000004',
                    'tax_00000000000005',
                    'tax_00000000000006',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The tax ids may not have more than 5 items.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateTaxGroup' => [
        'request' => [
            'url'     => '/tax_groups/taxg_00000000000001',
            'method'  => 'patch',
            'content' => [
                'name' => 'Updated tax group name',
                'tax_ids' => [
                    'tax_00000000000004',
                    'tax_00000000000001',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'taxg_00000000000001',
                'entity' => 'tax_group',
                'name'   => 'Updated tax group name',
                'taxes'  => [
                    [
                        'id'        => 'tax_00000000000001',
                        'entity'    => 'tax',
                        'name'      => 'Tax #1',
                        'rate_type' => 'percentage',
                        'rate'      => 100000,
                    ],
                    [
                        'id'        => 'tax_00000000000004',
                        'entity'    => 'tax',
                        'name'      => 'Flat Tax #4',
                        'rate_type' => 'flat',
                        'rate'      => 100,
                    ],
                ],
            ],
        ],
    ],

    'testDeleteTaxGroup' => [
        'request' => [
            'url'     => '/tax_groups/taxg_00000000000001',
            'method'  => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'      => 'taxg_00000000000001',
                'deleted' => true,
            ],
        ],
    ],
];

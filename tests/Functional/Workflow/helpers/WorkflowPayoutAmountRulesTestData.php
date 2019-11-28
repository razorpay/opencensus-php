<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

use RZP\Tests\Functional\Fixtures\Entity\Workflow;

return [
    'testCreateWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                    'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100001,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000001,
                        'max_amount'	=>	null
                    ]
                    ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 3,
                'admin'     => true,
                'items'     => [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  0,
                        'max_amount'    =>  100000,
                        'entity'        =>  'workflow_payout_amount_rules',
                        'admin'         =>  true
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  100001,
                        'max_amount'    =>  1000000,
                        'entity'        =>  'workflow_payout_amount_rules',
                        'admin'         =>  true
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'condition'     => null,
                        'min_amount'    => 1000001,
                        'max_amount'    => null,
                        'entity'        => 'workflow_payout_amount_rules',
                        'admin'         =>  true
                    ]
                ]
            ]
        ],
    ],

    'testCreateRulesWithOverlappingRanges' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100001
                    ],
                    [
                        'min_amount'	=>	100001,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000001,
                        'max_amount'	=>  null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ranges specified are overlapping',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRulesWithRangesLeavingGaps' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100002,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000001,
                        'max_amount'	=>  null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ranges specified are leaving gaps',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRulesWithWrongWorkflowId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'workflow_id'   =>  'wrongWorkflowId',
                        'min_amount'	=>	0,
                        'max_amount'	=>	null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'=> 'The id provided does not exist'
                ]

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testEditWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'workflow_id'	=>	'workflowId1000',
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'=> 'Workflow payout amount rules have already been created'
                ]

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_RULES_UPDATE_OR_DELETE_NOT_ALLOWED,
        ],
    ],

    'testGetAllPayoutAmountRulesWithPaginationLinks' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/workflows/rules/payout_amount/all?count=2&skip=2',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  1001,
                        'max_amount'    =>  null,
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
                    ]
                ],
                'links' => [
                    [
                        'rel'  =>   'self',
                    ],
                    [
                        'rel'  =>   'first',
                    ],
                    [
                        'rel'  =>   'prev',
                    ],
                    [
                        'rel'  =>   'next',
                    ],
                    [
                        'rel'  =>   'last',
                    ]
                ]
            ]
        ]
    ],

    'testGetMerchantWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/workflows/rules/payout_amount/10000000000000',
            'content' => [],
        ],
        'response' => [
            'content' => [

                "entity"    =>  "collection",
                'count'     =>  2,
                'admin'     =>  true,
                'items'     =>  [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
                        'entity'        =>  'workflow_payout_amount_rules',
                        'admin'         =>  true
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  101,
                        'max_amount'    =>  null,
                        'entity'        =>  'workflow_payout_amount_rules',
                        'admin'         =>  true
                    ]
                ]

            ],
        ]
    ],

];

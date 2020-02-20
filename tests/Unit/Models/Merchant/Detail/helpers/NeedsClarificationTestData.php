<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

return [

    'testClarificationReasonTransformationInput' => [
        'clarification_reasons' => [
            'contact_name'  => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email' => [
                [
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ],
        'additional_details'    => [
            'cancelled_cheque'     => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'business_description' => [
                [
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ],
    ],

    'testClarificationReasonTransformationOutput' => [
        'fields'    => [
            'contact_name'         => [
                [
                    'reason_code'        => 'others',
                    'display_name'       => 'Contact Name',
                    'reason_description' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email'        => [
                [
                    'reason_code'        => 'provide_poc',
                    'display_name'       => 'Contact Email',
                    'reason_description' => 'Please provide a POC that we can reach out to in case of issues associated with your account.',
                ]
            ],
            'business_description' => [
                [
                    'reason_code'        => 'provide_poc',
                    'display_name'       => 'Business Description',
                    'reason_description' => 'Please provide a POC that we can reach out to in case of issues associated with your account.',
                ]
            ]
        ],
        'documents' => [
            'cancelled_cheque' => [
                [
                    'reason_code'        => 'others',
                    'display_name'       => 'Cancelled Cheque',
                    'reason_description' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ]
    ],

    'testClarificationReasonMergeInput' => [
        'clarification_reasons' => [
            'business_type' => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
        'additional_details'    => [
            'business_proof_url' => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
    ],

    'testClarificationReasonMergeOutput' => [
        'clarification_reasons' => [
            'contact_name'  => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email' => [
                [
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'business_type' => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
        'additional_details'    => [
            'cancelled_cheque'     => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'business_description' => [
                [
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'business_proof_url'   => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
    ],


];

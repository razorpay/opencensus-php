<?php

return [
    'testGetDisputeDocumentTypesMetadata' => [
        'request'  => [
            'url'    => '/disputes/documents/types',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                [
                    'name'        => "shipping_proof",
                    'label'       => "Shipping Proof",
                    'description' => "Document(s) which serves as proof that the product was shipped to the customer at the customer provided address. It should show the customer’s full shipping address, if possible.",
                ],
                [
                    'name'        => "billing_proof",
                    'label'       => "Billing Proof",
                    'description' => "Document(s) which serves as proof of order confirmation such as receipt.",
                ],
                [
                    'name'        => "cancellation_proof",
                    'label'       => "Cancellation Proof",
                    'description' => "Document(s) that serves as a proof that this product/service was cancelled.",
                ],
                [
                    'name'        => "customer_communication",
                    'label'       => "Customer Communication",
                    'description' => "Document(s) listing any written/email communication from the customer confirming that the customer received the product/service or is satisfied with the product/service.",
                ],
                [
                    'name'        => "proof_of_service",
                    'label'       => "Proof Of Service",
                    'description' => "Documentation(s) showing proof of service provided to the customer.",
                ],
                [
                    'name'        => "explanation_letter",
                    'label'       => "Explanation Letter",
                    'description' => "Any explanation letter(s) from you specifying information pertinent to the dispute/ payment that needs to be taken into consideration for processing the dispute.",
                ],
                [
                    'name'        => "refund_confirmation",
                    'label'       => "Refund Confirmation",
                    'description' => "Documentation(s) showing proof that the refund was provided to the customer",
                ],
                [
                    'name'        => "access_activity_log",
                    'label'       => "Access Activity Log",
                    'description' => "Documentation(s) of any server or activity logs which prove that the customer accessed or downloaded the purchased digital product.",
                ],
                [
                    'name'        => "refund_cancellation_policy",
                    'label'       => "Refund Cancellation Policy",
                    'description' => "Document(s) listing your refund and/or cancellation policy, as shown to the customer.",
                ],
                [
                    'name'        => "terms_and_conditions",
                    'label'       => "Terms And Conditions",
                    'description' => "Document(s) listing your sales terms and conditions, as shown to the customer.",
                ],
                [
                    'name'        => "others",
                    'label'       => "Others",
                    'description' => "Field specifying any other type of evidence documents to be uploaded as a part of contesting a dispute",
                ],
            ],
        ],
    ],

    'testInitiateDraftEvidence' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 1000,
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_1cXSLlUU8V9sXm'],
                'billing_proof'  => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
                'others'         => [
                    [
                        'type'         => 'custom_proof_type_1',
                        'document_ids' => ['doc_1cXSLlUU8V9sXm', 'doc_1cXSLlUU8V9sXl'],
                    ],
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['doc_1cXSLlUU8V9sXm'],
                    ],
                ],
                'action'         => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 0,
                'reason_code'     => 'chargeback',
                'respond_by'      => 1610000000,
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    'amount'                     => 1000,
                    'summary'                    => 'sample contest summary',
                    'shipping_proof'             => ['doc_1cXSLlUU8V9sXm'],
                    'billing_proof'              => [
                        'doc_1cXSLlUU8V9sXl',
                        'doc_1cXSLlUU8V9sXm',
                    ],
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    'explanation_letter'         => null,
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testInitiateDraftEvidenceNoAmountProvided' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_1cXSLlUU8V9sXl'],
                'billing_proof'  => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
                'action'         => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'evidence' => [
                    'amount' => 1000000,
                ],
            ],
        ],
    ],

    'testInitiateDraftNoActionProvided' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_1cXSLlUU8V9sXl'],
                'billing_proof'  => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'evidence' => [
                    'amount' => 1000000,
                ],
            ],
        ],
    ],

    'testInitiateDraftEvidenceNoProofSubmitted' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'  => 1000,
                'summary' => 'sample contest summary',
                'action'  => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'action not allowed as it will lead to all proof becoming empty',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateDraftEvidenceInvalidProofSubmitted' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'             => 1000,
                'summary'            => 'sample contest summary',
                'invalid_proof_type' => ['doc_EFtmUsbwpXwBH9'],
                'action'             => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'invalid_proof_type is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => 'BAD_REQUEST_EXTRA_FIELDS_PROVIDED',
        ],
    ],

    'testInitiateDraftEvidenceInvalidContestAmount' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'        => 10000000, // dispute amount is 10000
                'summary'       => 'sample contest summary',
                'billing_proof' => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
                'action'        => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'contest amount cannot be greater than dispute amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateDraftEvidenceInvalidDisputeStatus' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'        => 100, // dispute amount is 10000
                'summary'       => 'sample contest summary',
                'billing_proof' => ['doc_1cXSLlUU8V9sXl'], //these fileids are hardcoded as valid files in ufh mock
                'action'        => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'cannot draft evidence when dispute is in %s status',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateDraftEvidenceInvalidAction' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 100, // dispute amount is 10000
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_EFtmUsbwpXwBH9'],
                'action'         => 'invalid action',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The selected action is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateDraftEvidenceInvalidDocumentId' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 100, // dispute amount is 10000
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_EFtmUsbwpXwBH9'], //some random docid which doesnt belong to this merchant [as per mock ufh]
                'action'         => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Invalid file ids provided: doc_EFtmUsbwpXwBH9',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateDraftEvidenceDisputeDoesntBelongToMerchant' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 100, // dispute amount is 10000
                'summary'        => 'sample contest summary',
                'billing_proof' => ['doc_1cXSLlUU8V9sXl'], //these fileids are hardcoded as valid files in ufh mock
                'action'         => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_INVALID_ID',
        ],
    ],


    'testUpdateDraftEvidence' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'             => 2000,
                'summary'            => 'new sample contest summary',
                'billing_proof'      => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
                'explanation_letter' => ['doc_1cXSLlUU8V9sXm'],
                'cancellation_proof' => null,
                'others'             => [
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['doc_1cXSLlUU8V9sXl'],
                    ],
                    [
                        'type'         => 'custom_proof_type_3',
                        'document_ids' => ['doc_1cXSLlUU8V9sXm'],
                    ],
                ],
                'action'             => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 0,
                'reason_code'     => 'chargeback',
                'respond_by'      => 1610000000,
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    // request updated the contest amount to 2000
                    'amount'                     => 2000,
                    'summary'                    => 'new sample contest summary',
                    // we didnt pass 'shipping_proof' in request -> should retain previous value
                    'shipping_proof'             => ['doc_1cXSLlUU8V9sXl'],
                    // ['doc_1cXSLlUU8V9sXm'] was the only doc id previously. assert that after request its updated
                    'billing_proof'              => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'],
                    //asserting cancellation_proof is null as it was explicitly nullified in above request
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    // explanation_letter was null initially. assert that if passed as a part of update request, its updated
                    'explanation_letter'         => ['doc_1cXSLlUU8V9sXm'],
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                    'others'                     => [
                        [
                            'type'         => 'custom_proof_type_1',
                            'document_ids' => ['doc_1cXSLlUU8V9sXl'],
                        ],
                        [
                            'type'         => 'custom_proof_type_2',
                            'document_ids' => ['doc_1cXSLlUU8V9sXl'],
                        ],
                        [
                            'type'         => 'custom_proof_type_3',
                            'document_ids' => ['doc_1cXSLlUU8V9sXm'],
                        ],
                    ],
                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testUpdateDraftEvidenceNullifyProof' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'shipping_proof' => null,
                'others'         => null,
                'action'         => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 0,
                'reason_code'     => 'chargeback',
                'respond_by'      => 1610000000,
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    'shipping_proof' => null,
                    'others'         => null,
                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testUpdateDraftEvidenceOnlyProofUpdated' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'billing_proof' => ['doc_1cXSLlUU8V9sXl'], //these fileids are hardcoded as valid files in ufh mock
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 0,
                'reason_code'     => 'chargeback',
                'respond_by'      => 1610000000,
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    // request should be the previous contest amount equal to 1000
                    'amount'        => 1000,
                    'summary'       => 'sample contest summary',
                    'billing_proof' => ['doc_1cXSLlUU8V9sXl'], //these fileids are hardcoded as valid files in ufh mock

                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testUpdateDraftEvidenceLeadingToNoProofSubmittedShouldFail' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'billing_proof'      => null,
                'shipping_proof'     => null,
                'cancellation_proof' => null,
                'others'             => null,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'action not allowed as it will lead to all proof becoming empty',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testGetDisputeByIDWithoutFeatureEnabled' => [
        'request'  => [
            'url'    => '/disputes/disp_0123456789abcd',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 0,
                'reason_code'     => 'chargeback',
                'respond_by'      => 1610000000,
                'status'          => 'open',
                'phase'           => 'chargeback',
                'created_at'      => 1600000000,
            ],
        ],
    ],
];
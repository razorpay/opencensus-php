<?php

use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
                'shipping_proof' => ['doc_shippingProfId'],
                'billing_proof'  => ['doc_billingProfId1', 'doc_billingProfId2'], //these fileids are hardcoded as valid files in ufh mock
                'others'         => [
                    [
                        'type'         => 'custom_proof_type_1',
                        'document_ids' => ['doc_customType1Id1', 'doc_customType1Id2'],
                    ],
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['doc_customType2Id1'],
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
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    'amount'                     => 1000,
                    'summary'                    => 'sample contest summary',
                    'shipping_proof'             => ['doc_shippingProfId'],
                    'billing_proof'              => ['doc_billingProfId1', 'doc_billingProfId2'], //these fileids are hardcoded as valid files in ufh mock
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

    'testDisputeLifecycleAttributesInProxyAuthActionPerformedInPrivateAuth' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/',
            'method'  => 'GET',
            'content' => [
                'amount'         => 1000,
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_shippingProfId'],
                'action'         => 'submit',
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'disp_0123456789abcd',
                'entity'    => 'dispute',
                'lifecycle' => [
                    [
                        'change' => [
                            'new' => [
                                'status'          => 'lost',
                                'amount_deducted' => 1000000,
                            ],
                            'old' => [
                                'status'          => 'open',
                                'amount_deducted' => 0,
                            ],
                        ],
                        'user_id'     => null,
                        'merchant_id' => '10000000000000',
                    ],
                ],
            ],
        ],
    ],

    'testDisputeLifecycleAttributesInProxyAuthActionPerformedInProxyAuth' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/',
            'method'  => 'GET',
            'content' => [
                'amount'         => 1000,
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_shippingProfId'],
                'action'         => 'submit',
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'disp_0123456789abcd',
                'entity'    => 'dispute',
                'lifecycle' => [
                    [
                        'change'      => [
                            'new' => [
                                'status'          => 'lost',
                                'amount_deducted' => 1000000,
                            ],
                            'old' => [
                                'status'          => 'open',
                                'amount_deducted' => 0,
                            ],
                        ],
                        'user_id'     => 'MerchantUser01',
                        'merchant_id' => '10000000000000',
                    ],
                ],
            ],
        ],
    ],

    'testDisputeContestInProxyAuthBlockedRoles' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'Unauthorized Action',
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testInitiateDraftEvidenceNoAmountProvided' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_shippingProfId'],
                'billing_proof'  => ['doc_billingProfId1', 'doc_billingProfId2'], //these fileids are hardcoded as valid files in ufh mock
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
                'shipping_proof' => ['doc_shippingProfId'],
                'billing_proof'  => ['doc_billingProfId1', 'doc_billingProfId2'], //these fileids are hardcoded as valid files in ufh mock
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
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'  => 1000,
                'summary' => 'sample contest summary',
                'action'  => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'evidence' => [
                    'amount'                     => 1000,
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    'explanation_letter'         => null,
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                ],
            ],
        ],
    ],

    'testInitiateDraftEvidenceOnlyActionSubmitted' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'action' => 'draft',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'evidence' => [
                    'amount'                     => 1000000,
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    'explanation_letter'         => null,
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                ],
            ],
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
                'billing_proof' => ['doc_billingProfId1', 'doc_billingProfId2'], //these fileids are hardcoded as valid files in ufh mock
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
                'billing_proof' => ['doc_billingProfId1'], //these fileids are hardcoded as valid files in ufh mock
                'action'        => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Action not allowed when dispute is in %s status.',

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


    'testInitiateDraftEvidenceInvalidDocumentTypeSubmittedAsEvidence' => [

        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 100, // dispute amount is 10000
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_1cXSLlUU8V9sXl'], //some random docid which doesnt belong to this merchant [as per mock ufh]
                'action'         => 'draft',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => "Only documents with purpose 'dispute_evidence' maybe submitted. doc_1cXSLlUU8V9sXl is of purpose 'delivery_proof'",
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
                'billing_proof'      => ['doc_billingProfId1', 'doc_billingProfId2'],
                'explanation_letter' => ['doc_explnationProf'],
                'cancellation_proof' => null,
                'others'             => [
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['doc_customType2Id1'],
                    ],
                    [
                        'type'         => 'custom_proof_type_3',
                        'document_ids' => ['doc_customType3Id1'],
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
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    // request updated the contest amount to 2000
                    'amount'                     => 2000,
                    'summary'                    => 'new sample contest summary',
                    // we didnt pass 'shipping_proof' in request -> should retain previous value
                    'shipping_proof'             => ['doc_shippingProfId'],
                    //  was the only doc id previously. assert that after request its updated to 2
                    'billing_proof'              => ['doc_billingProfId1', 'doc_billingProfId2'],
                    //asserting cancellation_proof is null as it was explicitly nullified in above request
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    // explanation_letter was null initially. assert that if passed as a part of update request, its updated
                    'explanation_letter'         => ['doc_explnationProf'],
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                    'others'                     => [
                        [
                            'type'         => 'custom_proof_type_1',
                            'document_ids' => ['doc_customType1Id1'],
                        ],
                        [
                            'type'         => 'custom_proof_type_2',
                            'document_ids' => ['doc_customType2Id1'],
                        ],
                        [
                            'type'         => 'custom_proof_type_3',
                            'document_ids' => ['doc_customType3Id1'],
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
                'billing_proof' => ['doc_billingProfId1'],
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
                'status'          => 'open',
                'phase'           => 'chargeback',
                'evidence'        => [
                    // request should be the previous contest amount equal to 1000
                    'amount'        => 1000,
                    'summary'       => 'sample contest summary',
                    'billing_proof' => ['doc_billingProfId1'],
                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testUpdateDraftEvidenceLeadingToNoProofSubmitted' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'billing_proof'      => null,
                'shipping_proof'     => null,
                'cancellation_proof' => null,
                'others'             => null,
                'summary'            => 'test summary',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'evidence' => [
                    'amount'                     => 1000,
                    'summary'                    => 'test summary',
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    'explanation_letter'         => null,
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                ],
            ],
        ],
    ],


    'testContestDispute' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'        => 1000,
                'summary'       => 'sample contest summary',
                'billing_proof' => ['doc_billingProfId1'],
                'action'        => 'submit',
            ],
        ],
        'response' => [
            'content' => [
                'id'         => 'disp_0123456789abcd',
                'status'     => 'under_review',
                'evidence'   => [
                    'amount'        => 1000,
                    'summary'       => 'sample contest summary',
                    'billing_proof' => ['doc_billingProfId1'],
                ],
                'created_at' => 1600000000,
            ],
        ],
    ],

    'testContestDisputeEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.under_review',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity' => 'payment',
                    'amount' => 1000000,
                    'id'     => 'pay_randomPayId123',
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'   => 'dispute',
                    'amount'   => 1000000,
                    'status'   => 'under_review',
                    'evidence' => [
                        'amount'         => 1000,
                        'summary'        => 'sample contest summary',
                        'billing_proof'  => ['doc_billingProfId1'],
                        'shipping_proof' => ['doc_shippingProfId'],
                        'others'         => [],
                    ],
                ],
            ],
        ],
    ],

    'testContestDisputePartialAmount' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'        => 100,
                'summary'       => 'sample contest summary',
                'billing_proof' => ['doc_billingProfId1'],
                'action'        => 'submit',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'status'   => 'under_review',
                'phase'    => 'chargeback',
                'evidence' => [
                    'amount' => 100,
                ],
            ],
        ],
    ],

    'testContestDisputeWithoutAmountProvided' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'summary'       => 'sample contest summary',
                'billing_proof' => ['doc_billingProfId1'],
                'action'        => 'submit',
            ],
        ],
        'response' => [
            'content' => [
                'id'       => 'disp_0123456789abcd',
                'status'   => 'under_review',
                'phase'    => 'chargeback',
                'evidence' => [
                    'amount' => 1000000,
                ],
            ],
        ],
    ],

    'testContestDisputeWithoutEvidenceSubmittedShouldFail' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'action' => 'submit',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Please upload atleast one evidence document to support your claim',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testContestDisputeInvalidDisputeStatus' => [
        'request'   => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'action'        => 'submit',
                'summary'       => 'test summary',
                'billing_proof' => ['doc_billingProfId1'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Action not allowed when dispute is in %s status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testDisputeReopenedFromUnderReviewWebhook' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd',
            'method'  => 'POST',
            'content' => [
                'status' => 'open',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testDisputeReopenedFromUnderReviewWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.action_required',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity' => 'payment',
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity' => 'dispute',
                    'status' => 'open',
                ],
            ],
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
                'status'          => 'open',
                'phase'           => 'chargeback',
                'created_at'      => 1600000000,
            ],
        ],
    ],


    'testAcceptDisputeWithoutFeatureEnabled' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/accept',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_URL_NOT_FOUND',
        ],
    ],

    'testAcceptDisputeBeyondRespondByDateShouldFail' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/accept',
            'method'  => 'POST',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Action not allowed as deadline to respond has elapsed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_DISPUTE_DEADLINE_ELAPSED',
        ],
    ],

    'testContestDisputeBeyondRespondByDateShouldFail' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Action not allowed as deadline to respond has elapsed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_DISPUTE_DEADLINE_ELAPSED',
        ],
    ],

    'testAcceptDispute' => [
        'request'  => [
            'url'     => '/disputes/disp_0123456789abcd/accept',
            'method'  => 'POST',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'disp_0123456789abcd',
                'entity'          => 'dispute',
                'payment_id'      => 'pay_randomPayId123',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'amount_deducted' => 1000000, //todo: needs to be dispute amount. functionality will be added in later PR
                'reason_code'     => 'chargeback',
                'status'          => 'lost',
                'phase'           => 'chargeback',
                'evidence'        => [
                    'amount'                     => 0,
                    'summary'                    => 'dispute accepted',
                    'shipping_proof'             => null,
                    'billing_proof'              => null,
                    'cancellation_proof'         => null,
                    'customer_communication'     => null,
                    'proof_of_service'           => null,
                    'explanation_letter'         => null,
                    'refund_confirmation'        => null,
                    'access_activity_log'        => null,
                    'refund_cancellation_policy' => null,
                    'terms_and_conditions'       => null,
                    'others'                     => null,
                ],
                'created_at'      => 1600000000,
            ],
        ],
    ],

    'testAcceptDisputeEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.lost',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity' => 'payment',
                    'amount' => 1000000,
                    'id'     => 'pay_randomPayId123',
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'   => 'dispute',
                    'amount'   => 1000000,
                    'status'   => 'lost',
                    'evidence' => [
                        'amount'                     => 0,
                        'summary'                    => 'dispute accepted',
                        'shipping_proof'             => NULL,
                        'billing_proof'              => NULL,
                        'cancellation_proof'         => NULL,
                        'customer_communication'     => NULL,
                        'proof_of_service'           => NULL,
                        'explanation_letter'         => NULL,
                        'refund_confirmation'        => NULL,
                        'access_activity_log'        => NULL,
                        'refund_cancellation_policy' => NULL,
                        'terms_and_conditions'       => NULL,
                        'others'                     => NULL,
                    ],
                ],
            ],
        ],
    ],

    'testAcceptDisputeRecoverViaAdjustmentEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.lost',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'          => 'payment',
                    'amount'          => 1000000,
                    'id'              => 'pay_randomPayId123',
                    'amount_refunded' => 1000000,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'          => 'dispute',
                    'amount'          => 1000000,
                    'status'          => 'lost',
                    'amount_deducted' => 1000000,
                    'evidence'        => [
                        'amount'                     => 0,
                        'summary'                    => 'dispute accepted',
                        'shipping_proof'             => NULL,
                        'billing_proof'              => NULL,
                        'cancellation_proof'         => NULL,
                        'customer_communication'     => NULL,
                        'proof_of_service'           => NULL,
                        'explanation_letter'         => NULL,
                        'refund_confirmation'        => NULL,
                        'access_activity_log'        => NULL,
                        'refund_cancellation_policy' => NULL,
                        'terms_and_conditions'       => NULL,
                        'others'                     => NULL,
                    ],
                ],
            ],
        ],
    ],

    'testAcceptDisputeRecoveryViaRefundWithNoPreExistingRefundsEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.lost',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'          => 'payment',
                    'amount'          => 1000000,
                    'id'              => 'pay_randomPayId123',
                    'amount_refunded' => 1000000,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'          => 'dispute',
                    'amount'          => 1000000,
                    'status'          => 'lost',
                    'amount_deducted' => 1000000,
                    'evidence'        => [
                        'amount'                     => 0,
                        'summary'                    => 'dispute accepted',
                        'shipping_proof'             => NULL,
                        'billing_proof'              => NULL,
                        'cancellation_proof'         => NULL,
                        'customer_communication'     => NULL,
                        'proof_of_service'           => NULL,
                        'explanation_letter'         => NULL,
                        'refund_confirmation'        => NULL,
                        'access_activity_log'        => NULL,
                        'refund_cancellation_policy' => NULL,
                        'terms_and_conditions'       => NULL,
                        'others'                     => NULL,
                    ],
                ],
            ],
        ],
    ],

];

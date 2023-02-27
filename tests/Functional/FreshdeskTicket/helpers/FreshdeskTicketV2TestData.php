<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetById' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'id'    => 'razorpayid0012',
                'body'  => 'some random body 12',

            ],
            'status_code'   => 200,
        ],
    ],

    'testGetByIdProhibitedShouldFail' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'No db records found.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_NO_RECORDS_FOUND',
        ],
    ],

    'testInternalFetchMerchantFreshdeskTickets' => [
        'request' => [
            'url' => '/internal/merchant_freshdesk_tickets?merchant_id=10000000000001',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'   => 1,
                'items' => [
                    [
                        'id' => 'razorpayid0034',
                    ],
                ],
            ],
        ],
    ],

    'testInternalFetchMerchantFreshdeskTicketsValidationError' => [
        'request' => [
            'url' => '/internal/merchant_freshdesk_tickets',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'The id field is required when none of merchant id / ticket id are present.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchTicketsForMerchantFailedForSomeInstance' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => [ 'cf_requester_category'    => 'Merchant',
                           'cf_requestor_subcategory' => 'Activation',
                           'cf_requester_item'        => '']
        ],
        'response' => [
            'content' => [
                'total'   => 2,
                'results' => [
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0034',
                    ],
                ],
            ],
        ],
    ],

    'testFetchTicketsForMerchant' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => [
                'cf_requester_category'    => 'Merchant',
                'cf_requestor_subcategory' => 'Activation',
                'cf_requester_item'        => '',
                'cf_workflow_id'           => 'w_action_1234',
            ]
        ],
        'response' => [
            'content' => [
                'total'   => 4,
                'results' => [
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0034',
                    ],
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0034',
                    ],
                ],
            ],
        ],
    ],

    'testFetchTicketsForMerchantWithFilter' => [
        'request'  => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => ['cf_requester_category'    => 'Merchant',
                          'cf_requestor_subcategory' => 'Activation',
                          'cf_requester_item'        => '',
                          'cf_created_by'            => 'merchant'
                ]
        ],
       'response' => [
            'content' => [
                'total'   => 2,
                'results' => [
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0012',
                    ],
                ],
            ],
        ],
    ],

    'testFetchTicketsForMerchantWithTagFilter' => [
        'request'  => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => ['cf_requester_category'    => 'Merchant',
                          'cf_requestor_subcategory' => 'Activation',
                          'cf_requester_item'        => '',
                          'cf_created_by'            => 'merchant',
                          'tags'                     => ['testing'],
            ]
        ],
        'response' => [
            'content' => [
                'total'   => 2,
                'results' => [
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0012',
                    ],
                ],
            ],
        ],
    ],

    'testFetchTicketsForAgentWithFilterNoCall' => [
        'request'  => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => ['cf_requester_category'    => 'Merchant',
                          'cf_requestor_subcategory' => 'Activation',
                          'cf_requester_item'        => '',
                          'cf_created_by'            => 'agent'
            ]
        ],
        'response' => [
            'content' => [
                'total'   => 0,
                'results' => [
                ],
            ],
        ],
    ],

    'testFetchTicketsForMerchantWithStatusOnly' => [
        'request'  => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => ['status' => 2]
        ],
        'response' => [
            'content' => [
                'total'   => 4,
                'results' => [
                    [
                        'id'    => 'razorpayid0012',
                    ],
                    [
                        'id'    => 'razorpayid0034',
                    ],
                    [
                        'id'    => 'razorpayid0012',
                    ],
                    [
                        'id'    => 'razorpayid0034',
                    ],
                ],
            ],
        ],
    ],

    'testFetchTicketsForMerchantSalesforceWrongAuth' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "error"            =>  [
                        "code"          =>  "BAD_REQUEST_ERROR",
                        "description"   =>  "The requested URL was not found on the server."
                    ],
            ],
            'status_code' => 400,
    ],
],

    'testFetchTicketsForMerchantInternalAuth' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'total'   => 4,
                'results' => [
                    [
                        'id'    => 'razorpayid0012',
                    ],
                    [
                        'id'    => 'razorpayid0034',
                    ],
                    [
                        'id'    => 'razorpayid0012',
                    ],
                    [
                        'id'    => 'razorpayid0034',
                    ],
                ],
            ],
        ],
    ],

    'testGetConversationsForTicket' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012/conversations',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'body'=> 'some random body1',
                    'id'=> 'redacted',
                    'ticket_id'=> 'razorpayid0012',
                ],
                [
                    'body'=> 'some random body2',
                    'id'=> 'redacted',
                    'ticket_id'=> 'razorpayid0012',
                ],
            ],
        ],
    ],

    'testGetConversationsForRazorpayxTicket' => [
        'request' => [
            'url' => '/fd/support_dashboard_x/ticket/razorpayid0013/conversations',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'body'=> 'some random body1',
                    'id'=> 'redacted',
                    'ticket_id'=> 'razorpayid0013',
                ],
                [
                    'body'=> 'some random body2',
                    'id'=> 'redacted',
                    'ticket_id'=> 'razorpayid0013',
                ],
            ],
        ],
    ],

    'testGetConversationsProhibitedShouldFail' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'No db records found.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_NO_RECORDS_FOUND',
        ],
    ],

    'testInsertIntoDB' => [
        'request' => [
            'url'     => '/fd/insert_into_db',
            'method'  => 'POST',
            'content' => [
                "id"=> "KyvhWca25YAvF8",
                "merchant_id"=> "10000000000000",
                "ticket_id"=>"349",
                "type"=> "support_dashboard",
                "ticket_details"=> [
                    "fr_due_by"=> "2022-12-24T08:18:45Z",
                    "fd_instance"=> "rzpind",
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success'        => true,
            ],
        ],
    ],

    'testReplyToTicket' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
                'user_id'=> '1000',
                'body'   => 'random reply',
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'redacted',
                'user_id'   => 890,
                'body'      => 'random reply',
                'ticket_id' => 'razorpayid0012',

            ],
        ],
    ],
    'testReplyToTicketWithOutDataInRedis' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
                'user_id'=> '1000',
                'body'   => 'random reply',
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'redacted',
                'user_id'   => 890,
                'body'      => 'random reply',
                'ticket_id' => 'razorpayid0012',

            ],
        ],
    ],
    'testReplyToTicketProhibitedShouldFail' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
                'user_id'=> '890',
                'body'   => 'random reply',
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'No db records found.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_NO_RECORDS_FOUND',
        ],
    ],

    'testRaiseGrievanceOnTicket' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/razorpayid0012/grievance',
            'method'  => 'POST',
            'content' => [
                'description'   => 'random grievance'
            ],
        ],
        'response' => [
            'content' => [
                'id'           => 'razorpayid0012',
                'description'  => 'random grievance',
                'status'       => 2,
                'priority'     => 4,
            ],
        ],
    ],

    'testCreateTicketRzpCheckingCCEmails'=> [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzp' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzpMobileSignup' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation',
                    'cf_workflow_id'              => 'w_action_1234',
                ],
                'tags' => ['workflow_ticket'],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketOpenTicketLimitExceeded' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'Bad request open tickets limit exceeded',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_OPEN_TICKETS_LIMIT_EXCEEDED',
        ],
    ],

    'testCreateTicketRzpWithHtmlTagsAndNoMerchantName' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => '<br>Ticket<b>Description</b><br><a href=test1.com></a>HTML',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => '<br>Ticket<b>Description</b><br>HTML',
            ],
        ],
    ],

    'testCreateTicketRzpWithInvalidCreationSource' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation',
                    'cf_creation_source'          => 'Dashboard Y',
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'Invalid Ticket Creation Source: Dashboard Y',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testCreateTicketRzpCreationSource' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation',
                    'cf_creation_source'          => 'Dashboard',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzpWithDCMigrationExperimentOn' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testGetTicketRzpInd' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'id'    => '',
            ],
            'status_code'   => 200,
        ],
    ],

    'testCreateTicketRzpSalesForce' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzpSol' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Technical support',
                    'cf_requester_item'           => 'Success rate',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzpCap' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'          => 'Merchant',
                    'cf_requestor_subcategory'       => 'Capital',
                    'cf_requester_item'              => 'Cash Advance',
                    'cf_creation_source'             => 'Dashboard X',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],
    'testCreateTicketRzpCapBehindExp' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'          => 'Merchant',
                    'cf_requestor_subcategory'       => 'Capital',
                    'cf_requester_item'              => 'Cash Advance',
                    'cf_creation_source'             => 'Dashboard X',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],
    'testCreateTicketRzpCapViaX' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'    => 'ticket description',
                'subject'        => '[Merchant] Corporate Credit Cards',
                'custom_fields'  => [
                        'cf_merchant_id'        => '10000000000000',
                        'cf_category'           => 'RazorpayX',
                        'cf_requestor_category' => 'Merchant',
                        'cf_query'              => 'Corporate Credit Cards',
                        'cf_ticket_queue'       => 'RazorpayX',
                ],
                'cc_emails' => ['a@b.com','merchantuser01@razorpay.com']
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],
    'testCreateTicketRzpCapViaXForLimit' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket',
            'method'  => 'POST',
            'content' => [
                    'subject'          => '[Merchant] Higher Corporate Card Spend Limit',
                    'description'      => 'Requested Limit: 50005\nReason: test for payload',
                    'custom_fields'    =>[
                        'cf_product' => 'Corporate Credit Cards'
                    ],
                    'cc_emails'        => ['a@b.com','merchantuser01@razorpay.com'],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'Requested Limit: ₹50005\nReason: test for payload',
            ],
        ],
    ],
    'testCreateTicketRzpXMobileSignUp' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketRzpX' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketForAUserWithoutNameRzpX' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
                'email' =>  'user@razorpay.com',
                'name'  => '',
                'phone' => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketForUserRzpX' => [
        'request' => [
            'url'     => '/fd/support_dashboard_x/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
                'email' =>  'user@razorpay.com',
                'phone' => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testCreateTicketFreshdeskError' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Invalid',
                    'cf_requestor_subcategory'    => 'activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'Something went wrong, please try again after sometime.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_FRESHDESK_TICKET_CREATION_FAILED',
        ],
    ],

    'testCreateTicketInvalidAttachmentExtension' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'Invalid Extension',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testReceiveFreshdeskWebhookToNotifyMerchant' => [
        'request'  => [
            'url'     => '/fd/webhook/notify_merchant',
            'method'  => 'POST',
            'content' => [
                'ticket_id'     => '12',
                'event'         => 'TICKET_CLOSED',
                'fd_instance'   => 'rzpind',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testReceiveFreshdeskWebhookToNotifyMerchantInvalidEvent' => [
        'request'   => [
            'url'     => '/fd/webhook/notify_merchant',
            'method'  => 'POST',
            'content' => [
                'ticket_id'   => '12',
                'event'       => 'invalid event',
                'fd_instance' => 'rzpind',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Something went wrong, please try again after sometime.',
                    'field'       => 'event',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_ERROR',
        ],
    ],

    'testReceiveFreshdeskWebhookOnTicketReplyFirstResponseTimeDataDoesntExist' => [
        'request'   => [
            'url'           => '/fd/webhook/support_ticket_first_agent_reply',
            'method'        => 'POST',
            'content'       => [
                'ticket_id'         =>  '12',
                'priority'          =>  'Low',
                'custom_fields'     => [
                    'cf_requestor_subcategory'  => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
            ],
        ],
    ],

    'testFreshdeskWebhookGetAgentCreatedTicket' => [
        'request'   => [
            'url'           => '/fd/webhook/get_agent_created_ticket',
            'method'        => 'POST',
            'content'       => [
            ],
        ],
        'response' => [
            'content'       => [
                'success' => true,
            ],
        ],
    ],

    'testReceiveFreshdeskWebhookOnTicketReplyFirstResponseTimeDataExist' => [
        'request'   => [
            'url'           => '/fd/webhook/support_ticket_first_agent_reply',
            'method'        => 'POST',
            'content'       => [
                'ticket_id'         =>  '12',
                'priority'          =>  'Urgent',
                'custom_fields'     => [
                    'cf_requestor_subcategory'  => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
            ],
        ],
    ],

    'testReceiveFreshdeskWebhookOnTicketReplyNoRazorpayResponseYet' => [
        'request'   => [
            'url'           => '/fd/webhook/support_ticket_first_agent_reply',
            'method'        => 'POST',
            'content'       => [
                'ticket_id'         =>  '12',
                'priority'          =>  'Urgent',
                'custom_fields'     => [
                    'cf_requestor_subcategory'  => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content'       => [
            ],
        ],
    ],

    'testCreateTicketWithRewrittenFrDueBy' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/',
            'method'  => 'POST',
            'content' => [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'       => 'Merchant',
                    'cf_requestor_subcategory'    => 'Activation'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description'  => 'ticket description',
            ],
        ],
    ],

    'testReceiveFreshdeskWebhookOnTicketStatusUpdate' => [
        'request'   => [
            'url'           => '/fd/webhook/ticket_status_update_callback',
            'method'        => 'POST',
            'content'       => [
                'merchant_id'       => '10000000000000',
                'ticket_id'         => '12',
                'type'              => 'support_dashboard',
                'status'            =>  'Closed',
                'ticket_details'    => [
                    'fd_instance'   => 'rzp',
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'success' => true,
            ],
        ],
    ],

    'testReceiveFreshdeskWebhookOnTicketCreated' => [
        'request'   => [
            'url'           => '/fd/webhook/ticket_create_callback',
            'method'        => 'POST',
            'content'       => [
                'merchant_id'       => '10000000000000',
                'ticket_id'         => '1234',
                'type'              => 'support_dashboard',
                'ticket_details'    => [
                    'fd_instance'   => 'rzpind',
                    'fr_due_by'     => '2020-12-08T16:04:20Z',
                ],
            ],
        ],
        'response' => [
            'content'       => [
                'success' => true,
            ],
        ],
    ],

    'testGetFreshdeskTicketCareApp' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'key2' => 'value2',
            ],
            'status_code'   => 200,
        ],
    ],

    'testUpdateFreshdeskTicketInternalSuccess' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/internal/freshdesk/ticket/razorpayid0012',
            'content' => [
                'account_id' => '10000000000000',
                'key1'       => 'value1',
            ],
        ],
        'response' => [
            'content' => [
                'id' => 'value2'
            ],
        ],
    ],

    'testUpdateFreshdeskTicketInternalFailed' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/internal/freshdesk/ticket/razorpayid0012',
            'content' => [
                'account_id' => '10000000000000',
                'key1'       => 'value1',
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'Failed to update freshdesk ticket',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED',
        ],
    ],

    'testCreateTicketForInternalAuth' => [
        'request'  => [
            'url'     => '/internal/fd/ticket/',
            'method'  => \Requests::POST,
            'content' => [
                'account_id'    => '10000000000000',
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com'],
                'tags'          => ['callback_requested'],
                'group_id'      => 123,
                'priority'      => 4,
                'due_by'        => '2021-06-04T05:21:22Z',
                'fr_due_by'     => '2021-06-04T05:21:22Z',
                'status'        => 2,
                'fd_instance'   => 'rzpind',
                'email'         => 'test@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Call Requested',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'description' => 'ticket description',
            ],
        ],
    ],

    'testGetAgentsFilterInternalAuth' => [
        'request'  => [
            'url'     => '/internal/fd/support_dashboard/agent?email=vinita.nirmal@razorpay.com&fd_instance=rzpind',
            'method'  => \Requests::GET,
        ],
        'response' => [
            'content' => [
                "count" => 1,
                "items" => [
                    [
                        "agent_id" => 14000004891643,
                        "email" => "vinita.nirmal@razorpay.com"
                    ]
                ]
            ],
        ],
    ],

    'testGetAgentDetailForTicketInternalAuth' => [
        'request'  => [
            'url'     => '/fd/ticket/1234/agent',
            'method'  => \Requests::GET,
        ],
        'response' => [
            'content' => [
                'agent_name' => 'test_agent',
                'agent_id'   => 'admin_6dLbNSpv5Ybbbd',
            ],
        ],
    ],

    'testGetAgentDetailForUnassignedTicketInternalAuthFail' => [
        'request'  => [
            'url'     => '/fd/ticket/1234/agent',
            'method'  => \Requests::GET,
        ],
        'response'  => [
            'content'       => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_ASSIGNED,
        ],
    ],

    'testInternalReplyToTicket' => [
        'request' => [
            'url'     => '/internal/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
                'account_id' => '10000000000000',
                'user_id'=> '890',
                'body'   => 'random reply',
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'redacted',
                'user_id'   => 890,
                'body'      => 'random reply',
                'ticket_id' => 'razorpayid0012',

            ],
        ],
    ],

    'testInternalReplyToTicketProhibitedShouldFail' => [
        'request' => [
            'url'     => '/internal/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
                'account_id' => '10000000000000',
                'user_id'=> '890',
                'body'   => 'random reply',
            ],
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'No db records found.',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_NO_RECORDS_FOUND',
        ],
    ],

];

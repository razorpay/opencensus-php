<?php


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

    'testFetchTicketsForMerchant' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => [
                'cf_requester_category'    => 'Merchant',
                'cf_requestor_subcategory' => 'Activation',
                'cf_requester_item'        => ''
            ]
        ],
        'response' => [
            'content' => [
                'total'   => 8,
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
                'total'   => 4,
                'results' => [
                    [
                        'id' => 'razorpayid0012',
                    ],
                    [
                        'id' => 'razorpayid0012',
                    ],
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

    'testFetchTicketsForMerchantWithStatusOnly' => [
        'request'  => [
            'url'     => '/fd/support_dashboard/ticket',
            'method'  => 'GET',
            'content' => ['status' => 2]
        ],
        'response' => [
            'content' => [
                'total'   => 8,
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
                'total'   => 8,
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

    'testReplyToTicket' => [
        'request' => [
            'url'     => '/fd/support_dashboard/ticket/razorpayid0012/reply',
            'method'  => 'POST',
            'content' => [
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
                'fd_instance'   => 'rzp',
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
                'fd_instance' => 'rzp',
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

    'testReceiveFreshdeskWebhookOnTicketCreated' => [
        'request'   => [
            'url'           => '/fd/webhook/ticket_create_callback',
            'method'        => 'POST',
            'content'       => [
                'merchant_id'       => '10000000000000',
                'ticket_id'         => '1234',
                'type'              => 'support_dashboard',
                'ticket_details'    => [
                    'fd_instance'   => 'rzp',
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
                'fd_instance'   => 'rzp',
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

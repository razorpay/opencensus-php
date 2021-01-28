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
                    'description' => 'No db records found',
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

    'testFetchTicketsForMerchant' => [
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

    'testGetConversationsProhibitedShouldFail' => [
        'request' => [
            'url' => '/fd/support_dashboard/ticket/razorpayid0012',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'error' => [
                    'description' => 'No db records found',
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
                    'description' => 'No db records found',
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

];

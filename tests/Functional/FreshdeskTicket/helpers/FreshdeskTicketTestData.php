<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetReserveBalanceTicketStatusForExistingTicket' => [
        'request' => [
            'url' => '/fd/reserve_balance/tickets/status',
            'method' => 'GET'
        ],
        'response' => [
            'content'       => [
                'ticket_id'     => 1,
                'ticket_status' => 'Processing',
                'ticket_exists' => true,
            ],
            'status_code'   => 200,
        ],
    ],

    'testGetReserveBalanceTicketStatusForNonExistingTicket' => [
        'request' => [
            'url' => '/fd/reserve_balance/tickets/status',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'ticket_exists' => false
            ],
            'status_code' => 200,
        ],
    ],

    'testStoreReserveBalanceTicketDetails' => [
        'request' => [
            'content' => [
                'ticket_details' => '{
                    \'email\': \'sujata@razorpay.com\',
                    \'subject\': \'Reserve Balance test ticket\',
                    \'description\': \'This is a testing ticket for the new ticket API, please ignore.\',
                    \'priority\': 1,
                    \'status\': 2,
                    \'custom_fields\' : {
                        \'cf_requester_category\': \'Prospect\',
                        \'cf_requestor_subcategory\': \'For reserve balance\'
                    }
                }',
                'ticket_id'       => '1',
                'merchant_id'     => '10000000000000',
                'type'            => 'reserve_balance_activate',
            ],
            'url' => '/fd/reserve_balance/tickets',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'ticket_details' => '{
                    \'email\': \'sujata@razorpay.com\',
                    \'subject\': \'Reserve Balance test ticket\',
                    \'description\': \'This is a testing ticket for the new ticket API, please ignore.\',
                    \'priority\': 1,
                    \'status\': 2,
                    \'custom_fields\' : {
                        \'cf_requester_category\': \'Prospect\',
                        \'cf_requestor_subcategory\': \'For reserve balance\'
                    }
                }',
                'ticket_id'       => '1',
                'merchant_id'     => '10000000000000',
                'type'            => 'reserve_balance_activate',
            ],
            'status_code'   => 200,
        ],
    ],

    'testPostTicketMissingField' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'otp'  => '0007',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The custom fields field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPostTicketPaymentId' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'otp'  => '0007',
                'custom_fields' => [
                    'cf_transaction_id' => '',
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ]
    ],

    'testPostTicketPaymentIdInvalidOtp' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'otp'  => '9999',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                    'cf_transaction_id' => ''
                ]
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
            ]
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'Verification failed because of incorrect OTP',
        ],
    ],

    'testOtpGenerateAndSendForMail' => [
        'request' => [
            'url' => '/freshdesk/tickets/otp',
            'method' => 'POST',
            'content' => [
                'email' => 'test@gmail.com',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ],
    ],

    'testOtpGenerateAndSendForMobile' => [
        'request' => [
            'url' => '/freshdesk/tickets/otp',
            'method' => 'POST',
            'content' => [
                'phone' => '9876543210',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testPostTicketInvalidId' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'custom_fields' => [
                    'cf_transaction_id' => '',
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
            ]
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'FRESHDESK_TICKET_INVALID_ID',
        ],
    ],

    'testPostTicketCustomerNoTransactionId' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
            ]
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'FRESHDESK_TICKET_INVALID_ID',
        ],
    ],

    'testPostTicketWithExperimentFreshdeskCustomerTicketCreationServerPickOn' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'otp'  => '0007',
                'custom_fields' => [
                    'cf_transaction_id' => '',
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'fd_instance'      => 'rzpind',
                'subject'          => 'Support needed..',
                'id'               =>  1,
                'description_text' => "Some details on the issue ..."
            ]
        ]
    ],

    'testPostTicketForAccountRecoveryForEmail' => [
        'request' => [
            'url'     => '/freshdesk/account_recovery_ticket',
            'method'  => 'POST',
            'content' => [
                'email'     => '8055@abc.com',
                'old_email' => '123@gmail.com',
                'otp'       => '0007',
                'pan'       => 'ABCCD1234A',
                'captcha'   => 'test'
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testPostTicketForAccountRecoveryForMobile' => [
        'request' => [
            'url'     => '/freshdesk/account_recovery_ticket',
            'method'  => 'POST',
            'content' => [
                'phone'     => '1234567891',
                'old_phone' => '1234567890',
                'otp'       => '0007',
                'pan'       => 'ABCCD1234A',
                'captcha'   => 'test'
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testGetFreshdeskTicketsForCustomer' => [
        'request' => [
            'url'     => '/freshdesk/tickets/customer',
            'method'  => 'POST',
            'content' => [
                'email' => 'success@gmail.com',
                'otp'   => '0007',
            ],
        ],
        'response' => [
            'content'     => [
                [
                    'number'            => 3368,
                    'status'            => 'Closed',
                    'subject'           => '',
                    'source'            => 2,
                    'type'              => 'Other',
                    'payment_id'        => 'FrTYsVAuCrW8Fm',
                    'refund_id'         => null,
                    'order_id'          => null,
                    'transaction_id'    => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'        => '2020-10-28T11:02:50Z',
                    'updated_at'        => '2020-10-28T11:02:51Z',
                ],
                [
                    'number'         => 3338,
                    'status'         => 'Closed',
                    'subject'        => '',
                    'source'         => 2,
                    'type'           => null,
                    'payment_id'     => 'FrTYsVAuCrW8Fm',
                    'refund_id'      => null,
                    'order_id'       => null,
                    'transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'     => '2020-10-28T11:02:50Z',
                    'updated_at'     => '2020-10-28T11:02:51Z',
                ],
                [
                    'number'         => 3339,
                    'status'         => 'Processing',
                    'subject'        => '',
                    'source'         => 2,
                    'type'           => null,
                    'payment_id'     => 'FrTYsVAuCrW8Fm',
                    'refund_id'      => null,
                    'order_id'       => null,
                    'transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'     => '2020-10-28T11:02:50Z',
                    'updated_at'     => '2020-10-28T11:02:51Z',
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testGetFreshdeskTicketsFailureIncorrectOtp' => [
        'request' => [
            'url'     => '/freshdesk/tickets/customer',
            'method'  => 'POST',
            'content' => [
                'email' => 'success@gmail.com',
                'otp'   => '0008',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'BAD_REQUEST_INCORRECT_OTP',
        ],
    ],

    'testGetFreshdeskTicketsFailureTicketsNotFound' => [
        'request' => [
            'url'     => '/freshdesk/tickets/customer',
            'method'  => 'POST',
            'content' => [
                'email' => 'failure@gmail.com',
                'otp'   => '0007',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testRaiseGrievanceAgainstTicket' => [
        'request' => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'          => 3328,
                'group_id'    => '123',
                'email'       => 'thatemail@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id' => ''
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'number'            => 3328,
                'status'            => 'Processing',
                'subject'           => '',
                'source'            => 2,
                'type'              => null,
                'payment_id'        => 'FrTYsVAuCrW8Fm',
                'refund_id'         => null,
                'order_id'          => null,
                'transaction_id'    => 'pay_FrTYsVAuCrW8Fm',
                'created_at'        => '2020-10-28T11:02:50Z',
                'updated_at'        => '2020-10-28T11:02:51Z',
            ],
        ],
    ],

    'testRaiseGrievanceAgainstTicketUpdateFailure' => [
        'request' => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'          => 3329,
                'description' => 'some description',
                'email'       => 'thatemail@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id' => ''
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED,
        ],
    ],

    'testRaiseGrievanceAgainstTicketInvalidEmail' => [
        'request' => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'          => 3330,
                'description' => 'some description',
                'email'       => 'thatemail@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id' => ''
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND,
        ],
    ],

    'testRaiseGrievanceAgainstTicketFdIndiaInstance' => [
        'request' => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'          => 3331,
                'group_id'    => '123',
                'email'       => 'thatemail@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id' => ''
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'number'            => 3331,
                'status'            => 'Processing',
                'subject'           => '',
                'source'            => 2,
                'type'              => null,
                'payment_id'        => 'FrTYsVAuCrW8Fm',
                'refund_id'         => null,
                'order_id'          => null,
                'transaction_id'    => 'pay_FrTYsVAuCrW8Fm',
                'created_at'        => '2020-10-28T11:02:50Z',
                'updated_at'        => '2020-10-28T11:02:51Z',
            ],
        ],
    ],

    'testPostTicketPartnerSuccess' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'abc' => 'strct',
                'mode' => 'test',
                'otp'  => '0007',
                'custom_fields' => [
                    'cf_requester_category' => 'Partner',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ]
    ],

    'testCreateTicketAttachments' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'mode' => 'test',
                'otp'  => '0007',
                'custom_fields' => [
                    'cf_transaction_id' => '',
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ]
    ],

    'testCreateTicketAttachmentsInvalidExtensions' => [
        'request' => [
            'url' => '/freshdesk/tickets',
            'method' => 'POST',
            'content' => [
                'name' => 'Test',
                'subject' => 'Subject',
                'description' => 'Description',
                'email' => 'test@gmail.com',
                'mode' => 'test',
                'otp'  => '0007',
                'custom_fields' => [
                    'cf_transaction_id' => '',
                    'cf_requester_category' => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
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
];

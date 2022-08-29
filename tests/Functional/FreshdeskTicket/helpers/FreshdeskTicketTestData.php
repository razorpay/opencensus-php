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
                    'cf_transaction_id'        => '',
                    'cf_requester_category'    => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
            ]
        ]
    ],

    'testNotVerifiedEmailPostCustomerTicket' => [
        'request'   => [
            'url'     => '/freshdesk/tickets',
            'method'  => 'POST',
            'content' => [
                'name'          => 'Test',
                'subject'       => 'Subject',
                'description'   => 'Description',
                'email'         => 'test@gmail.com',
                'abc'           => 'strct',
                'mode'          => 'test',
                'otp'           => '0007',
                'isPaPgEnable' => "true",
                'custom_fields' => [
                    'cf_transaction_id'        => '',
                    'cf_requester_category'    => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                ]
            ]
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED,
        ],
    ],

    'testPostTicketPaymentIdInvalidOtp' => [
        'request'   => [
            'url'     => '/freshdesk/tickets',
            'method'  => 'POST',
            'content' => [
                'name'          => 'Test',
                'subject'       => 'Subject',
                'description'   => 'Description',
                'email'         => 'test@gmail.com',
                'abc'           => 'strct',
                'mode'          => 'test',
                'otp'           => '9999',
                'custom_fields' => [
                    'cf_requester_category'    => 'Customer',
                    'cf_requestor_subcategory' => 'Sub category',
                    'cf_transaction_id'        => ''
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
                'g_recaptcha_response' => '***',
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
                'g_recaptcha_response' => '***',
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

    'testGetFreshdeskTicketsForCustomerNodal' => [
        'request' => [
            'url'     => '/freshdesk/tickets/customer',
            'method'  => 'POST',
            'content' => [
                'email' => 'successnodal@gmail.com',
                'otp'   => '0007',
                'isPaPgEnable' => "true",
            ],
        ],
        'response' => [
            'content'     => [
               [
                    'number'         => 9993,
                    'action'        => 'assistant_nodal',
                    'tags'      => ['tag1'],
                    'status'            => 'Open',
                    'subject'           => '',
                    'source'            => 2,
                    'type'              => 'Other',
                    'payment_id'        => 'FrTYsVAuCrW8Fm',
                    'refund_id'         => null,
                    'order_id'          => null,
                    'transaction_id'    => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'=> "2022-02-12T11:05:31Z",
                    'updated_at'=> "2022-02-12T11:18:25Z",
                    'requester_id'             => 11033774580,
                ],
                [
                    'number'         => 9994,
                    'tags' => ['assistant_nodal'],
                    'status'         => 'Closed',
                    'subject'        => '',
                    'source'         => 2,
                    'type' => 'Other',
                    'payment_id'     => 'FrTYsVAuCrW8Fm',
                    'refund_id'      => null,
                    'order_id'       => null,
                    'transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'=> "2022-02-12T11:05:31Z",
                    'updated_at'=> "2022-02-12T11:18:25Z",
                    'requester_id'             => 11033774580,
                ],
                [
                    'number'         => 9995,
                    'status'         => 'Waiting on Customer',
                    'tags'   => [],
                    'action' => 'nodal',
                    'subject'        => '',
                    'source'         => 2,
                    'type' => 'Other',
                    'payment_id'     => 'FrTYsVAuCrW8Fm',
                    'refund_id'      => null,
                    'order_id'       => null,
                    'transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'=> "2022-02-02T11:05:31Z",
                    'updated_at'=> "2022-02-02T11:18:25Z",
                    'requester_id'             => 11033774580,
                ],
                [
                    'number'         => 9996,
                    'status'         => 'Open',
                    'tags'   => ['assistant_nodal'],
                    'action' => 'nodal',
                    'subject'        => '',
                    'source'         => 2,
                    'type' => 'Other',
                    'payment_id'     => 'FrTYsVAuCrW8Fm',
                    'refund_id'      => null,
                    'order_id'       => null,
                    'transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'created_at'=> "2022-02-02T11:05:31Z",
                    'updated_at'=> "2022-02-02T11:18:25Z",
                    'requester_id'             => 11033774580,
                ]
            ],
            'status_code' => 200,
        ]
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
                'number'         => 3328,
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
            ],
        ],
    ],

    'testRaiseGrievanceNodalFlowForWrongAction' => [
        'request'  => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'                   => 9991,
                'group_id'             => '123',
                'email'                => 'thatemail@razorpay.com',
                'tags'                 => ['tag1'],
                'description'          => 'tag1',
                'action'               => 'nodal',
                'g_recaptcha_response' => 'test',
                'custom_fields'        => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id'     => ''
                ],
                'isPaPgEnable' => "true",
            ],
        ],
            'response'  => [
                'status_code' => 400,
                'content'     => [
                    'error' => [
                        'description' => 'BAD_REQUEST_ACTION_NOT_ALLOWED',
                        'code'        => 'BAD_REQUEST_ERROR',
                    ],
                ],
            ],
            'exception' =>
                [
                    'class'               => 'RZP\Exception\BadRequestValidationFailureException',
                    'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                ],
    ],

    'testRaiseGrievanceNodalFlowTicket'=> [
        'request'  => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'group_id' => '123',
                'email' => 'thatemail@razorpay.com',
                'tags' => ['tag1'],
                'description' => 'tag1',
                'g_recaptcha_response' => 'test',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id' => ''
                ],
                'isPaPgEnable' => "true",
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
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
            ],
        ],
    ],

    'testFetchConversationCustomerTicket' => [
        'request'  => [
            'url'     => '/freshdesk/ticket/customer/9993/conversations',
            'method'  => 'POST',
            'content' => [
                'g_recaptcha_response' => 'test'
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'body'      => "<table style=\"margin-left: auto;margin-right: auto\">  <tbody>\n<tr>    <td>      <table style=\"margin:0px auto;background: #f4f8fa;overflow: auto;padding: 0 15px; min-width: 500px; margin: 0px\">          <tbody>\n<tr style=\"border: none\">    <td style=\"padding: 10px 20px;color: #333;color: #3f3f46\">        <div></div>\n<div style=\"padding:0 5px 0 0;font-size:12px; color: #6f7071;margin-left: 33px\">Eloquent Info Solutions Private Limited</div>\n<div></div>        <img src=\"https://images.freshchat.com/30x30/fresh-chat-names/Alphabets/E.png\" style=\"width: 30px;height: 30px;border-radius: 50% 6px 50% 50%;float:left;margin-right: 3px\">        <div style=\"border-radius: 4px 20px 20px;background: #a8ddfd;max-width: 320px;padding: 12px;float: left\"><div>please resolve</div></div>        <div style=\"color: #999999; font-size: 11px; clear: left;margin-left: 33px\">04:58 PM, 07th Jul</div>    </td>\n</tr>\n<tr></tr>\n<tr style=\"border: none\">  <td style=\"padding: 10px 20px;font-size: 13.6px;color: #3f3f46\">        <div style=\"float:right\">\n<div style=\"font-size: 12px;font-weight: 500;float: right; color: #6f7071; margin-right: 30px\">Ashmitha</div>        <img src=\"https://images.freshchat.com/30x30/fresh-chat-names/Alphabets/A.png\" style=\"width: 30px;height: 30px;border-radius: 6px 50% 50% 50%;margin-left:5px;float:right;clear:right\">        <div style=\"float: right;border-radius: 20px 4px 20px 20px;background-color: #ffffff;max-width: 320px;padding: 12px\"><div>Hi</div></div>        <div style=\"color: #999999;font-size: 11px;clear: right\">05:01 PM, 07th Jul</div>\n</div>   </td>\n</tr>\n<tr>       </tr>\n</tbody>\n</table>    </td>  </tr>  <tr>    <td>      <table style=\"margin:10px auto;padding:0 20px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#333;line-height:1.4;margin: 0px;min-width: 100%\">        <tbody>\n<tr><td style=\"text-align: center\"><span style=\"font-size: 13px; color: #999\">View conversation in <a href=\"https://web.freshchat.com/a/153370844780746/open/conversation/374169896285298\" style=\"font-weight: 500; color: #999; text-decoration: none\" rel=\"noreferrer\">Freshchat</a></span></td></tr>      </tbody>\n</table>    </td>  </tr>\n</tbody>\n</table>",
                        'body_text' => "Eloquent Info Solutions Private Limited                    please resolve           04:58 PM, 07th Jul                   Ashmitha                   Hi           05:01 PM, 07th Jul                                          View conversation in Freshchat",
                    ]
                ],

            ],
        ],
    ],

    'testNotVerifiedEmailFetchConversationCustomerTicket' => [
        'request'   => [
            'url'     => '/freshdesk/ticket/customer/9993/conversations',
            'method'  => 'POST',
            'content' => [
                'g_recaptcha_response' => 'test',
                'isPaPgEnable' => "true",
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'BAD_REQUEST_EMAIL_NOT_VERIFIED',
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPostReplyCustomerTicket' => [
        'request'  => [
            'url'     => '/freshdesk/ticket/customer/9993/reply',
            'method'  => 'POST',
            'content' => [
                'body'                 => 'test reply',
                'g_recaptcha_response' => 'test'
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     =>
                [
                    'body'      => "<div>123211 fdf3</div>",
                    'body_text' => "123211 fdf3",
                ]
        ]
    ],

    'testNotVerifiedPostReplyCustomerTicket' => [
        'request'   => [
            'url'     => '/freshdesk/ticket/customer/9993/reply',
            'method'  => 'POST',
            'content' => [
                'body'                 => 'test reply',
                'g_recaptcha_response' => 'test'
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRaiseGrievanceAgainstTicketUpdateFailure' => [
        'request'   => [
            'url'     => '/freshdesk/grievance',
            'method'  => 'POST',
            'content' => [
                'id'            => 3329,
                'description'   => 'some description',
                'email'         => 'thatemail@razorpay.com',
                'custom_fields' => [
                    'cf_requester_category' => 'Customer',
                    'cf_transaction_id'     => ''
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

    'testFreshDeskInternalAddNote' => [
        'request' => [
            'url' => '/internal/fd/ticket/3331/note',
            'method' => 'POST',
            'content' => [
                'description' => 'Description',
                'private' => false
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ]
    ],

    'testFreshDeskInternalAddPrivateNote' => [
        'request' => [
            'url' => '/internal/fd/ticket/3331/note',
            'method' => 'POST',
            'content' => [
                'description' => 'Private note addition',
                'private' => true
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
            ]
        ]
    ]
];

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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_RESERVE_BALANCE_TICKET_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
];

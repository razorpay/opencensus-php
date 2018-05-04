<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPostSendInvitationToNewUser' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testTeamInvite@razorpay.com',
                'role'        => 'manager',
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testTeamInvite@razorpay.com',
                'role'        => 'manager'
            ]
        ]
    ],

    'testPostSendInvitationToExistingUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'existingInvite@razorpay.com',
                'role'        => 'manager',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'user_id'     => '1000InviteUser',
                'email'       => 'existingInvite@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testPostSendInvitationToInvitedUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testTeamInvite@razorpay.com',
                'role'        => 'manager',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invitation is already sent to this email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_INVITED,
        ],
    ],

    'testPostSendInvitationWithInvalidRole' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testTeamInvite@razorpay.com',
                'role'        => 'boss',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The given role is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_ROLE_INVALID,
        ],
    ],

    'testPostResendInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/resend',
            'method'  => 'PUT',
            'content' => [
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'email'       => 'testTeamInvite@razorpay.com',
                'role'        => 'manager',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testAcceptInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testTeamInvite@razorpay.com',
            ]
        ]
    ],

    'testRejectInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/reject',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
        ],
        'response' => [
            'content' => [
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1000InviteMerc',
                'role'        => 'manager',
                'email'       => 'reject@razorpay.com',
            ],
        ]
    ],

    'testInvalidResponseToInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/something',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected action is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3',
            'method'  => 'PATCH',
            'content' => [
                'role'  => 'finance',
            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'finance',
                'email'       => 'update@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testUpdateInvitationWithInvalidRole' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3',
            'method'  => 'PATCH',
            'content' => [
                'role'  => 'saheb',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_ROLE_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_ROLE_INVALID,
        ],
    ],

    'testUpdateDeletedInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3',
            'method'  => 'PATCH',
            'content' => [
                'role'  => 'finance',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testDeleteMerchantInvitation' => [
        'request' => [
            'url'    => '/invitations/8hd48md930kel3',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'email'       => 'delete@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ],
        ]
    ],

    'testGetPendingInvitations' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                [
                    'role'        => 'manager',
                    'email'       => 'pending1@razorpay.com',
                    'merchant_id' => '1000InviteMerc',
                ],
                [
                    'role'        => 'finance',
                    'email'       => 'pending2@razorpay.com',
                    'merchant_id' => '1000InviteMerc',
                ],
            ],
        ]
    ],

    'testGetInvitationByToken' => [
        'request' => [
            'url'     => '/invitations/token',
            'method'  => 'GET',
            'content' => [

            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'email'       => 'testTeamInvite@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testGetInvitationByInvalidToken' => [
        'request' => [
            'url'     => '/invitations/token',
            'method'  => 'GET',
            'content' => [

            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testGetInvitationsReceivedBeforeSignup' => [
        'request' => [
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'email' => "old@razorpay.com",
            ],
            'status_code' => 200,
        ],
    ],

    'testGetInvitationsReceivedPostSignup' => [
        'request' => [
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'email' => "old@razorpay.com",
            ],
            'status_code' => 200,
        ],
    ],
];

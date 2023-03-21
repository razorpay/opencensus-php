<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPostSendInvitationToNonExistingUserInX' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'admin',
                'sender_name' => 'sender_name'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'admin'
            ]
        ]
    ],

    'testPostSendInvitationToNonExistingUserInXForCARole' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'chartered_accountant',
                'sender_name' => 'sender_name'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'chartered_accountant'
            ]
        ]
    ],

    'testDraftInvitationsCreate' => [
            'request' => [
                'url'    => '/banking_axis_invitations',
                'method' => 'POST',
                'content' => [
                    'email'       => 'testnonexistentuserinvite@razorpay.com',
                    'role'        => 'admin',
                    'sender_name' => 'sender_name',
                    'merchant_id' => '100XInviteMerc',
                ],
                'server'  => [
                    'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
                ],
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100XInviteMerc',
                    'email'       => 'testnonexistentuserinvite@razorpay.com',
                    'role'        => 'admin'
                ]
            ]
        ],

    'testDraftInvitationsSendMail' => [
        'request' => [
            'url'    => '/banking_axis_invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'authorised_signatory',
                'sender_name' => 'sender_name',
                'merchant_id' => '100XInviteMerc',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'email'       => 'testnonexistentuserinvite@razorpay.com',
                'role'        => 'authorised_signatory'
            ]
        ]
    ],

    'testPostSendInvitationToNewUserInX' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteamxinvite@razorpay.com',
                'role'        => 'admin',
                'sender_name' => 'sender_name'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'email'       => 'testteamxinvite@razorpay.com',
                'role'        => 'admin'
            ]
        ]
    ],

    'testPostSendInvitationToNewUser' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager',
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager'
            ]
        ]
    ],


    'testPostSendInvitationToNewCurlecUser' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager',
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager'
            ]
        ]
    ],

    'testPostSendInvitationToExistingUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'existinginvite@razorpay.com',
                'role'        => 'manager',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'user_id'     => '1000InviteUser',
                'email'       => 'existinginvite@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testPostSendInvitationToExistingCurlecUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'existinginvite@razorpay.com',
                'role'        => 'manager',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'user_id'     => '1000InviteUser',
                'email'       => 'existinginvite@razorpay.com',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testPostSendInvitationToExistingTeamUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'role'        => 'manager',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'User with given email is already a member of the team',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER,
        ],
    ],

    'testPostSendInvitationToInvitedUser' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
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
                'email'       => 'testteaminvite@razorpay.com',
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

    'testPostSendInvitationWithOwnerRole' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'owner',
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

    'testPostSendInvitationByRBLSupervisorToValidRole' => [
        'request'  => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'existinginvite@razorpay.com',
                'role'        => 'rbl_agent',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'role'        => 'rbl_agent',
                'email'       => 'existinginvite@razorpay.com',
                'merchant_id' => '10000000000000',
            ]
        ]
    ],

    'testPostSendInvitationByRBLSupervisorToInvalidRole' => [
        'request'   => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'boss',
                'token'       => str_random(40),
                'sender_name' => 'sender_name'
            ]
        ],
        'response'  => [
            'content'     => [
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
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager',
                'merchant_id' => '1000InviteMerc',
            ]
        ]
    ],

    'testEmailDraftInvitations' => [
        'request' => [
            'url'     => '/draft_invitations/accept',
            'method'  => 'PUT',
            'content' => [
                'invitation_ids'=> [
                ],
                'sender_name'  => 'sender_name'
            ]
        ],
        'response' => [
            'content' => [
                'Success',
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
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'role'        => 'manager',
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
            ]
        ]
    ],

    'testAcceptInvitationByAlreadyExistingUserOnX' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
        ],
    ],

    'testAcceptInvitationByAlreadyExistingUserOnXWithExperimentOff' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'role'        => 'admin',
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
            ]
        ]
    ],

    'testAcceptInvitationByRestrictedMerchant' => [
        'request'   => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVITATION_ACCEPT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_ACCEPT_FAILED,
        ],
    ],

    'testAcceptInvitationForRestrictedUser' => [
        'request'   => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVITATION_ACCEPT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_ACCEPT_FAILED,
        ],
    ],

    'testRejectInvitation' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/reject',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
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
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
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

    'testAcceptRandomValidInvitation' => [
        'request' => [
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
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

    'testUpdateInvitationWithRoleArray' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3',
            'method'  => 'PATCH',
            'content' => [
                'role'  => ['finance'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The role must be a string.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
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
                    'merchant_id' => '10000000000000',
                ],
                [
                    'role'        => 'finance',
                    'email'       => 'pending2@razorpay.com',
                    'merchant_id' => '1000InviteMerc',
                ],
            ],
        ]
    ],

    'testGetPendingInvitationsForBanking' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                [
                    'role'        => 'owner',
                    'email'       => 'pending1@razorpay.com',
                    'merchant_id' => '10000000000000',
                ],
                [
                    'role'        => 'finance',
                    'email'       => 'pending2@razorpay.com',
                    'merchant_id' => '1000InviteMerc',
                ],
            ],
        ]
    ],

    'testGetPendingInvitationsWhichAreNotDraft' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                [
                    'role'        => 'manager',
                    'email'       => 'pending1@razorpay.com',
                    'merchant_id' => '10000000000000',
                    'is_draft'    => 0,
                ],
            ],
        ]
    ],

    'testGetPendingInvitationsByNonOwnerMember' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetPendingInvitationsWithDraftStateAsTrue' => [
        'request' => [
            'url'    => '/banking_axis_invitations',
            'method' => 'get',
            'content' => [
                'product' => 'primary'
            ],
        ],
        'response' => [
            'content' => [
                [
                    'role'        => 'finance',
                    'email'       => 'pending2@razorpay.com',
                    'merchant_id' => '10000000000000',
                ],
            ],
        ],
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
                'email'       => 'testteaminvite@razorpay.com',
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
            'server'  => [
                'HTTP_X-Dashboard-User-Email' => 'old@razorpay.com',
            ],
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
        'server'  => [
            'HTTP_X-Dashboard-User-Email' => 'old@razorpay.com',
        ],
        'response' => [
            'content'     => [
                'email' => "old@razorpay.com",
            ],
            'status_code' => 200,
        ],
    ],

    'testGetInvitationsSentToUpperCaseEmail' => [
        'request' => [
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'email' => "upper_case@razorpay.com",
            ],
            'status_code' => 200,
        ],
    ],

    'testPostSendInvitationForUserRestricted' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'admin',
                'sender_name' => 'sender_name'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVITATION_CREATE_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_CREATE_FAILED,
        ],
    ],

    'testPostSendInvitationByMerchantRestricted' => [
        'request' => [
            'url'     => '/invitations',
            'method'  => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'admin',
                'sender_name' => 'sender_name'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVITATION_CREATE_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVITATION_CREATE_FAILED,
        ],
    ],

    'testSendVendorPortalInvitationToNewUser' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
                'role'        => 'vendor',
                'product'     => 'banking',
            ]
        ]
    ],

    'testSendVendorPortalInvitationToExistingUser' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
                'role'        => 'vendor',
                'product'     => 'banking',
                'user_id'     => 'ExistingUserId'
            ]
        ]
    ],

    'testSendVendorPortalInviteWithoutContactId' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendVendorPortalInviteWithoutContactEmail' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_WITHOUT_EMAIL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendVendorPortalInviteToAlreadyInvitedUser' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
                'role'        => 'vendor',
                'product'     => 'banking',
                'user_id'     => 'ExistingUserId'
            ]
        ]
    ],

    'testSendVendorPortalInvitationToExistingUserWithPendingInvite' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
                'role'        => 'vendor',
                'product'     => 'banking',
                'user_id'     => 'ExistingUserId'
            ]
        ]
    ],

    'testSendVendorPortalInvitationToNewUserWithPendingInvite' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
                'role'        => 'vendor',
                'product'     => 'banking',
            ]
        ]
    ],

    'testSendVendorPortalInviteToAlreadyInvitedUserMicroServiceError' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'microservice error',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
        ],
    ],

    'testAcceptVendorPortalInvite' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'role'        => 'vendor',
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
            ]
        ]
    ],

    'testAcceptRepeatVendorPortalInvite' => [
        'request' => [
            'url'     => '/invitations/8hd48md930kel3/accept',
            'method'  => 'POST',
            'content' => [
                'user_id' => '1000InviteUser',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '1000InviteUser',
                'HTTP_X-Dashboard-User-Email' => 'testteaminvite@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'role'        => 'vendor',
                'user_id'     => '1000InviteUser',
                'merchant_id' => '1DummyMerchant',
                'email'       => 'vendorportal@razorpay.com',
            ]
        ]
    ],

    'testSendVendorPortalInvitationBadRequestException' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'microservice error',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
        ],
    ],

    'testSendVendorPortalInvitationServerErrorException' => [
        'request' => [
            'url'    => '/vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],
    'testResendVendorPortalInvitation' => [
        'request' => [
            'url'    => '/resend_vendor_portal_invitation',
            'method' => 'POST',
            'content' => [
                'contact_id' => 'cont_1000000contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '1000InviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'manager',
                'product'     => 'primary',
            ]
        ]
    ],
    'testResendVendorPortalInviteWithoutContactId' => [
        'request' => [
            'url'    => '/resend_vendor_portal_invitation',
            'method' => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPostSendXAccountingIntegrationInvitationToNewUserInX' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'finance_l1',
                'sender_name' => 'sender_name',
                'invitation_type' => 'joining_integration_invitation'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'email'       => 'testteaminvite@razorpay.com',
                'role'        => 'finance_l1'
            ]
        ]
    ],

    'testPostSendOnlyXAccountingIntegrationInvitationToNewUserInX' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'sender_name' => 'sender_name',
                'invitation_type' => 'integration_invitation'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'sampleInviteId',
                'to_email_id'       => 'testteaminvite@razorpay.com',
                'from_email_id' => 'testteamxinvite@razorpay.com',
            ]
        ]
    ],

    'testResendXAccountingIntegrationInvitationToNewUserInX' => [
        'request' => [
            'url'    => '/accounting-integrations-invite/resend',
            'method' => 'PUT',
            'content' => [
                'to_email_id'       => 'testteaminvite@razorpay.com'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100XInviteMerc',
                'to_email_id'       => 'testteaminvite@razorpay.com',
                'from_email_id' => 'testteamxinvite@razorpay.com',
            ]
        ]
    ],

    'testPostSendOnlyXAccountingIntegrationInvitationToNewUserInXFailed' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'sender_name' => 'sender_name',
                'invitation_type' => 'integration_invitation'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testPostSendOnlyXAccountingIntegrationInvitationToNewUserInXBadRequest' => [
        'request' => [
            'url'    => '/invitations',
            'method' => 'POST',
            'content' => [
                'email'       => 'testteaminvite@razorpay.com',
                'sender_name' => 'sender_name',
                'invitation_type' => 'integration_invitation'
            ],
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url')
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'not found'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCOUNTING_PAYOUTS_SERVICE_FAILED
        ],
    ]
];

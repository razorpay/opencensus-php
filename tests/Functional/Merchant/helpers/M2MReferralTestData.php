<?php

return [
    'testOauthSignup' => [
        'request'  => [
            'url'     => '/users/oauth-register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
                'utmSource'            => 'friendbuy',
                'utmMedium'            => 'referral',
                "utmCampaign"          => "Referral Test",
                "referralCode"          => "mg5xnqzn",

            ],
        ],
        'response' => [
            'content' => [
                'email' => 'hello123@gmail.com',
            ],
        ],
    ],
    'testUserRegisterVerifySignupOtpSms' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'contact_mobile'        => '8877665544',
                'captcha'               => 'faked',
                'token'                 => 'token',
                'otp'                   => '0007',
                'utmSource'            => 'friendbuy',
                'utmMedium'            => 'referral',
                "utmCampaign"          => "Referral Test",
                "referralCode"          => "mg5xnqzn",
                ],
        ],
        'response' => [
            "content" => [
                "contact_mobile"            => "8877665544",
                "signup_via_email"          => 0,
                "confirmed"                 => false,
                "email_verified"            => false,
                "contact_mobile_verified"   => true,
                "email"                     => null
            ]
        ]
    ],
    'testRewardValidationInvalidReferee' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "friend",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxp"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxp",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testRewardValidationInvalidReferrer' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "friend",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS35",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 400,
        ]
    ],
    'testRewardValidationInvalidPayload' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 400,
        ]
    ],
    'testRewardValidationInvalidSignature' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'pvoO64ufuVyINcy6KLMi6NBg8/RR97MJiBlhMnBhT60'
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "advocate",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ]
        ],
        'response' => [
            'content' =>[],
            'status_code' => 400,
        ]
    ],
    'testRewardValidationInvalidAuth' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "advocate",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ]
        ],
        'response' => [
            'content' =>[
                "error"=>
                    [
                        "code"=>"BAD_REQUEST_ERROR",
                        "description"=>"The requested URL was not found on the server.",
                        "source"=>"NA",
                        "step"=>"NA",
                        "reason"=>"NA",
                        "metadata"=>[]
                    ]
            ],
            'status_code' => 400,
        ]
    ],
    'testRewardValidationNonExistentReferral' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "friend",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testRewardValidationNotMTU' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "advocate",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ]
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testRewardValidationInvalidCoupon' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
                    'content' => [
                        "eventType"=> "mtu",
                        "recipientType"=> "advocate",
                        "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                        "event"=> [
                            "isNewCustomer"=> true,
                            "email"=> "12@c.com",
                            "customerId"=> "I0qYGdG9IGaVxz"
                        ],
                        "advocate"=> [
                            "customerId"=> "Hm9Bv6kFufFS36",
                            "email"=> "123@razorpay.com",
                            "ipAddress"=> "115.110.224.178"
                        ],
                        "actor"=> [
                            "customerId"=> "I0qYGdG9IGaVxz",
                            "email"=> "124@razorpay.com",
                            "ipAddress"=> "115.110.224.178"
                        ]
                    ]
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],

    'testSignupFromFriendBuy' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'utmSource'            => 'friendbuy',
                'utmMedium'            => 'referral',
                "utmCampaign"          => "Referral Production",
                "referralCode"          => "mg5xnqzn",
                'email'                 => 'test2@c.com',
                'password'              => 'hello1233',
                'password_confirmation' => 'hello1233',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ],
            ],
        'response' => [
            'content' => [
                'email' => 'test2@c.com',
                ]
        ]
    ],
    'testRewardValidation' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "friend",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testRewardValidationMaxReached' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "friend",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testAlreadyRewardedReferrer' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "advocate",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testAlreadyRewardedAndSeenReferrer' => [
        'request'  => [
            'url'     => '/friendbuy/reward_validation',
            'method'  => 'POST',
            'headers' =>[
                'x-friendbuy-hmac-sha256'=> 'muGWc0fftTMWwYS9Md/kjchLRtfmzq3e8bwt9W+/EwY='
            ],
            'content' => [
                "eventType"=> "mtu",
                "recipientType"=> "advocate",
                "campaignId"=> "e1466ae6-441f-43e4-88c0-36e0b0c6bc15",
                "event"=> [
                    "isNewCustomer"=> true,
                    "email"=> "12@c.com",
                    "customerId"=> "I0qYGdG9IGaVxz"
                ],
                "advocate"=> [
                    "customerId"=> "Hm9Bv6kFufFS36",
                    "email"=> "123@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ],
                "actor"=> [
                    "customerId"=> "I0qYGdG9IGaVxz",
                    "email"=> "124@razorpay.com",
                    "ipAddress"=> "115.110.224.178"
                ]
            ],
        ],
        'response' => [
            'content' =>[],
            'status_code' => 200,
        ]
    ],
    'testFetchReferralLinkWithEnabledFeature' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/onboarding/m2m_referral',
        ],
        'response' => [
            'content' => [
                'can_refer'=>true,
                "max_allowed_referrals"=>'5',
                "referral_amount_currency"=> "INR",
                "referral_amount" =>20000000
            ]
        ],
        'status_code' => 200,
    ],
    'testFetchReferralLinkWithDisabledFeature' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/onboarding/m2m_referral',
        ],
        'response' => [
            'content' => ['can_refer'=>false]
        ],
        'status_code' => 200,
    ],
    'testFetchReferralLinkFromStore' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/onboarding/m2m_referral',
        ],
        'response' => [
            'content' => [
                "max_allowed_referrals"=>'5',
                'can_refer'=>true,
                "referral_link"=> "https://fbuy.io/05c3a537-257c-48ae-a0f6-319bff3ac55f/x4skwckp?share=c4ea645a-b50d-44c4-8dea-a6ce8305b824",
                "referral_amount_currency"=> "INR",
                "referral_amount" =>20000000
            ]
        ],
        'status_code' => 200,
    ],
    'testFetchReferralDetailsFromSignup' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/m2m_referral',
        ],
        'response' => [
            'content' => [
                "referee"=>[
                    "referral_amount_currency"=> "INR",
                    "referral_amount" =>20000000
                ]
            ]
        ],
        'status_code' => 200,
    ],

];

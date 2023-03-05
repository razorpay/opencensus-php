<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testProxyRequest'                        => [
        'request'  => [
            'content' => [
                "payment_requests" => [
                    [
                        "method"      => "upi",
                        "reference16" => "123456",
                        "vpa"         => "upi_payments@ybl",
                        "base_amount" => 100099,
                        "from"        => 1618161012,
                        "to"          => 1618191012
                    ],
                    [
                        "method"      => "netbanking",
                        "reference1"  => "123456",
                        "base_amount" => 100099,
                        "from"        => 1618161012,
                        "to"          => 1618191012
                    ]
                ],
                "files"            => [
                    [
                        "file_id"       => "file_Kdtpsn5MxCCBS4",
                        "document_type" => "fir"
                    ]
                ],
            ],
            'url'     => '/cyber_helpdesk/ticket',
            'method'  => 'post',
        ],
        'response' => [

        ]
    ],
    'testSendMailToLEAFromCyberCrimeHelpdesk' => [
        'request'  => [
            'url'     => '/cybercrime_helpdesk/send_mail_to_lea',
            'method'  => 'POST',
            'content' => [
                'requester_mail'   => 'random.abc@gmail.com',
                'payment_requests' => [
                    [
                        "method"      => "upi",
                        "reference16" => "123456",
                        "vpa"         => "upi_payments@ybl",
                        "refrence1"   => "",
                        "base_amount" => 100000,
                        "from"        => 1665075143,
                        "to"          => 1665075143
                    ],
                    [
                        "method"      => "netbanking",
                        "reference16" => "",
                        "vpa"         => "upi_payments@ybl",
                        "refrence1"   => "12346",
                        "base_amount" => 100000,
                        "from"        => 1665075143,
                        "to"          => 1665075143
                    ]
                ],
                'files'            => []
            ],
        ],
        'response' => [
            'content'     => [
                'fd_ticket_id' => '123'
            ],
            'status_code' => 200,
        ],
    ],

    'createCyberHelpDeskWorkflowAction' => [
        'request'  => [
            'url'     => '/cybercrime_helpdesk/workflow_action',
            'method'  => 'POST',
            'content' => [
                'requester_mail' => 'xyz@gov.nic.in',
                'ticket_data'    => [
                    'ticket'       =>
                        [
                            [
                                'request' => [
                                    'id'   => 'abcd1234567890',
                                    'data' => [
                                        'method'      => 'upi',
                                        'reference16' => '123456',
                                        'vpa'         => 'upi_payments@ybl',
                                        'base_amount' => 100000,
                                        'from'        => 1618161012,
                                        'to'          => 1618191012
                                    ]
                                ],
                                'details' => [
                                    'payment'           => [
                                        'id'          => 'JCTRhsU4aiY0t1',
                                        'method'      => 'upi',
                                        'base_amount' => 1000,
                                        'email'       => 'customer1@gmail.com',
                                        'contact'     => '8114455062',
                                        'created_at'  => 1618191011
                                    ],
                                    'transaction'       => [
                                        'id'      => 'TCTRhsU4aiY0t1',
                                        'settled' => 0
                                    ],
                                    'merchant_details'  => [
                                        'merchant_id'      => '10000000000000',
                                        'merchant_name'    => 'Test Merchant',
                                        'contact_name'     => 'Test Merchant',
                                        'contact_email'    => 'testmerchant@gmail.com',
                                        'contact_mobile'   => '8114455061',
                                        'business_website' => 'testmerchant.com'
                                    ],
                                    'bank_account'      => [
                                        'id'               => 'bankAccount000',
                                        'beneficiary_name' => 'Test Merchant',
                                        'account_number'   => '22235678990',
                                        'ifsc_code'        => 'SBIN00001',
                                    ],
                                    'payment_analytics' => [
                                        'ip' => '120.111.22.30',
                                    ],
                                ],
                            ],
                            [
                                'request' => [
                                    'id'   => 'abcd1234567891',
                                    'data' => [
                                        'method'      => 'netbanking',
                                        'reference1'  => '123456',
                                        'base_amount' => 100000,
                                        'from'        => 1618161012,
                                        'to'          => 1618191012
                                    ],
                                ],
                                'details' => []
                            ],
                        ],
                    'file_names'   => [
                        'uniqueDoc1',
                        'uniqueDoc2',
                    ],
                    'fd_ticket_id' => 'ticket1',
                ],
                'enable_share_beneficiary_details_checkbox' => '0'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],
];

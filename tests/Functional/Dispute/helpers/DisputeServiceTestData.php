<?php

return [
    'testDisputeFetchProxyAuthViaDisputeClient' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes/disp_1000000dispute?expand[]=transaction.settlement',
        ],
        'response'  => [
            'content' => [
            ]
        ],
    ],
    'testDisputeFetchProxyAuthOlderDispute' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes/disp_1000000dispute?expand[]=transaction.settlement',
        ],
        'response'  => [
            'content' => [
                'id' => "disp_1000000dispute",
                'entity' => "dispute",
                'amount' => 1000000,
                'currency' => "INR",
                'amount_deducted' => 1000000,
                'gateway_dispute_id' => NULL,
                'reason_code' => "SOMETHING_BAD",
                'reason_description' => "Something went wrong",
                'status' => "open",
                'phase' => "chargeback",
                'comments' => NULL,
                'reason'   => [
                    'code'                  => 'KFRER_R',
                    'description'           => 'This is a serious fraud',
                    'network'               => 'Visa',
                    'gateway_code'          => '8fjf',
                    'gateway_description'   => 'Fraud on merchant side',
                ]
            ]
        ],
    ],
    'testDisputeFetchProxyAuthExperimentOff' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes/disp_1000000dispute?expand[]=transaction.settlement',
        ],
        'response'  => [
            'content' => [
                'id' => "disp_1000000dispute",
                'entity' => "dispute",
                'amount' => 1000000,
                'currency' => "INR",
                'amount_deducted' => 1000000,
                'gateway_dispute_id' => NULL,
                'reason_code' => "SOMETHING_BAD",
                'reason_description' => "Something went wrong",
                'status' => "open",
                'phase' => "chargeback",
                'comments' => NULL,
                'reason'   => [
                    'code'                  => 'KFRER_R',
                    'description'           => 'This is a serious fraud',
                    'network'               => 'Visa',
                    'gateway_code'          => '8fjf',
                    'gateway_description'   => 'Fraud on merchant side',
                ]
            ]
        ],
    ],
];

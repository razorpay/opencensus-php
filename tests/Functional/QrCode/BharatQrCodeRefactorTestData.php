<?php
return [
    'testQrStatusCheckViaRefactorFlowForMindgate' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/reminders/send/live/qr_code/qr_code_payment_status/',
        ],
        'response' => [
            'content' => [
                'success' => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testQrStatusCheckViaRefactorFlowForMintoak' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/reminders/send/live/qr_code/qr_code_payment_status/',
        ],
        'response' => [
            'content' => [
                'success' => false,
            ],
            'status_code' => 200,
        ],
    ],
];

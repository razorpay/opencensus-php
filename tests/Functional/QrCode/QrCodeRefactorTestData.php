<?php

return [
    'testQrStatusCheckViaRefactorFlow' => [
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

    'testQrStatusCheckViaRefactorFlowForFailedStatus' => [
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

    'testQrStatusCheckViaRefactorFlowForFailedStatusThroughIntegrationError' => [
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
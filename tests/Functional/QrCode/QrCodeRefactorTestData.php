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

    'testQrStatusCheckViaRefactorFlowForUpiRzpapb' => [
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

    'testQrStatusCheckViaRefactorFlowForUpiRzpapbWhenPaymentExists' => [
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

    'testQrPaymentReconForUpiRzpapbWhenPaymentDoesNotExist' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payment/callback/bharatqr/upi_rzpapb/internal',
            'content' => [
                'data' => [
                    'terminal' => [
                        'gateway'             => 'upi_rzpapb',
                        'gateway_merchant_id' => 'LiveAccountMer',
                        'vpa'                 => 'testvpa@rxairtel',
                    ],
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => '1000RandomQrId' . 'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'currency'           => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'payment' => [
                    'amount'      => 100,
                    'status'      => "captured",
                    'merchant_id' => "LiveAccountMer",
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testQrPaymentReconForUpiRzpapbWhenPaymentExists' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payment/callback/bharatqr/upi_rzpapb/internal',
            'content' => [
                'data' => [
                    'terminal' => [
                        'gateway'             => 'upi_rzpapb',
                        'gateway_merchant_id' => 'LiveAccountMer',
                        'vpa'                 => 'testvpa@rxairtel',
                    ],
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => '1000RandomQrId' . 'qrv2',
                        'npci_reference_id'  => '002002002002',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'currency'           => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'payment' => [
                    'amount'      => 100,
                    'status'      => "captured",
                    'merchant_id' => "LiveAccountMer",
                ],
            ],
            'status_code' => 200,
        ],
    ],
];

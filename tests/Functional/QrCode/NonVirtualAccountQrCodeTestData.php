<?php

return [
    'testCreateBharatQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'bharat_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateUpiQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],
];

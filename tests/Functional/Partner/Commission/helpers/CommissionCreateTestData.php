<?php

return [
    'testOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],
];

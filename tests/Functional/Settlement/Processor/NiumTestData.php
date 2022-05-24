<?php

return [
    'testNiumFileGeneration' => [
        'request' => [
            'url' => '/settlements/generate/nium',
            'method' => 'POST',
            'content' => [
                'niumMerchantId' => 'DefaultPartner'
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],
    'testNiumFileGenerationNoSettlements' => [
        'request' => [
            'url' => '/settlements/generate/nium',
            'method' => 'POST',
            'content' => [
                'niumMerchantId' => 'DefaultPartner'
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],
];

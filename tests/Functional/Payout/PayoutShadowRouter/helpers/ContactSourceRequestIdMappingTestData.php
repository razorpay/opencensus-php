<?php

return [
    'testCreateContactWithSourceRequestIdMapping' => [
        'request' => [
            'method' => 'POST',
            'url' => '/contacts',
            'content' => [
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping'
                ]
            ],
            'server' => [
                'X-Amzn-Trace-Id' => 'Root=1-507f38c4-687b7dc8a5b4c4d5e8f12345'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'contact',
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'active' => true,
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping'
                ]
            ],
            'status_code' => 201
        ]
    ],

    'testCreateContactWithSourceRequestIdMappingDisabled' => [
        'request' => [
            'method' => 'POST',
            'url' => '/contacts',
            'content' => [
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping_disabled'
                ]
            ],
            'server' => [
                'X-Amzn-Trace-Id' => 'Root=1-507f38c4-687b7dc8a5b4c4d5e8f12345'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'contact',
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'active' => true,
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping_disabled'
                ]
            ],
            'status_code' => 201
        ]
    ],

    'testCreateContactWithShadowServiceError' => [
        'request' => [
            'method' => 'POST',
            'url' => '/contacts',
            'content' => [
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping'
                ]
            ],
            'server' => [
                'X-Amzn-Trace-Id' => 'Root=1-507f38c4-687b7dc8a5b4c4d5e8f12345'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'contact',
                'name' => 'Test Contact Shadow',
                'email' => 'shadow.test@example.com',
                'contact' => '9876543210',
                'type' => 'self',
                'reference_id' => 'SHADOW_REF_123',
                'active' => true,
                'notes' => [
                    'test_type' => 'shadow_routing_test',
                    'experiment' => 'source_request_id_mapping'
                ]
            ],
            'status_code' => 201
        ]
    ],

];

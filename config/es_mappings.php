<?php

return [

    // -------------------- Index Settings ---------------------------

    // Default settings used for indexes.

    'settings' => [

        'index.mapping.total_fields.limit'  => 10000000,
        'index.mapping.depth.limit'         => 50,
        'index.mapping.nested_fields.limit' => 20,
        'number_of_shards'                  => 5,
        'number_of_replicas'                => 1,

        'analysis' => [
            'analyzer' => [
                'edge_ngram_analyzer' => [
                    'tokenizer' => 'edge_ngram_tokenizer',
                    'filter'    => [
                        'lowercase_filter',
                    ],
                ],
            ],
            'tokenizer' => [
                'edge_ngram_tokenizer' => [
                    'type'        => 'edge_ngram',
                    'min_gram'    => 2,
                    'max_gram'    => 50,
                    'token_chars' => [
                        'letter',
                        'digit',
                    ],
                ],
            ],
            'filter' => [
                'lowercase_filter' => [
                    'type' => 'lowercase',
                ],
            ],
        ]
    ],

    // -------------------- Notes Entity Default Mappings ------------

    // These entities have 'notes' field in it and so
    // 'notes_entity_mapping' would be used as base mapping array.

    'has_notes' => [
        'invoice',
        'order',
        'payment',
        'refund',
    ],

    // Any entity having 'notes' field will have followings common.

    'notes_entity_mapping' => [
        '_all' => [
            'enabled' => false
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type' => 'keyword',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'notes' => [
                'type' => 'object',
            ],
        ],
        'dynamic_templates' => [
            [
                'notes' => [
                    'path_match' => 'notes.*',
                    'mapping'    => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer',
                        'search_analyzer' => 'standard',
                        'index_options'   => 'offsets',
                    ],
                ],
            ],
        ],
    ],

    // -------------------- Entity Mappings --------------------------

    // Invoice has following additional(other than notes) field mappings

    'invoice_mapping' => [
        'properties' => [
            'type' => [
                'type'            => 'keyword',
            ],
            'status' => [
                'type'            => 'keyword',
            ],
            'receipt' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
            'customer_name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
            'customer_contact' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
            'customer_email' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
            'description' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
            'terms' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard',
                'index_options'   => 'offsets',
            ],
        ],
    ],

    'order_mapping'   => [],

    'payment_mapping' => [],

    'refund_mapping'  => [],
];

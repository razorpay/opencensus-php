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

                //
                // Refs:
                // - https://www.elastic.co/guide/en/elasticsearch/reference/1.4/analysis-custom-analyzer.html
                // - https://www.elastic.co/guide/en/elasticsearch/reference/1.4/analysis-edgengram-tokenizer.html
                //
                // For many of the text field we store their edge ngrams in index.
                //

                'edge_ngram_analyzer' => [
                    'type'      => 'custom',
                    'tokenizer' => 'edge_ngram_tokenizer',
                    'filter'    => [
                        'lowercase',
                    ],
                ],

                //
                // Generally index analysis and search analysis should be same.
                // But for better match against combination of query, we decided
                // we will index using edge ngram but search using standard.
                //
                // Now edge ngram tokenized terms on any punctuation. But standard
                // does not tokenized for a set of punctuation(set 1). So we use custom
                // standard_analyzer where it's same as standard but also replaces
                // those set 1 punctuation to '-' which will get used as word break
                // char in normal standard anaylzer.
                //
                //  This way, both index and search time analysis is consistent.
                //

                'standard_custom' => [
                    'type'        => 'custom',
                    'char_filter' => [
                        'punctuation_remap',
                    ],
                    'tokenizer'   => 'standard',
                    'filter'      => [
                        'standard',
                        'lowercase',
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
            ],
            'char_filter' => [
                'punctuation_remap' => [
                    'type'     => 'mapping',
                    'mappings' => [
                        '. => -',
                        ': => -',
                        '\' => -',
                    ],
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
        'reversal',
        'transfer',
        'virtual_account',
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
                        'search_analyzer' => 'standard_custom',
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
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'customer_name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'customer_contact' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'customer_email' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'description' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'terms' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
        ],
    ],

    'order_mapping'           => [],

    'payment_mapping'         => [],

    'refund_mapping'          => [],

    'reversal_mapping'        => [],

    'transfer_mapping'        => [],

    'virtual_account_mapping' => [],

    'merchant_mapping'        => [
        '_all' => [
            'enabled' => false
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'org_id' => [
                'type'  => 'keyword',
            ],
            'name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'email' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'billing_label' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'website' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'tag_list' => [
                'type'            => 'text',
                'analyzer'        => 'standard',
                'search_analyzer' => 'standard',
            ],
            'parent_id' => [
                'type' => 'keyword',
            ],
            'activated' => [
                'type' => 'boolean',
            ],
            'activated_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'archived_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'suspended_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'updated_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'merchant_details' => [
                'properties' => [
                    'merchant_id' => [
                        'type'  => 'keyword',
                        'index' => false,
                    ],
                    'steps_finished' => [
                        'type'  => 'keyword',
                        'index' => false,
                    ],
                    'activation_progress' => [
                        'type' => 'byte',
                    ],
                    'submitted_at' => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                        'index'  => false,
                    ],
                    'updated_at' => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                        'index'  => false,
                    ],
                ],
            ],
            'admins' => [
                'type' => 'keyword',
            ],
            'groups' => [
                'type' => 'keyword',
            ],
            'is_marketplace' => [
                'type'  => 'boolean',
                'index' => false,
            ],
            'referrer' => [
                'type'            => 'text',
                'analyzer'        => 'standard',
                'search_analyzer' => 'standard',
            ],
        ],
    ],
];

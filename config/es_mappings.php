<?php

return [

    // -------------------- Index Settings ---------------------------

    // Default settings used for indexes.

    'settings' => [

        // Following are default values in Elasticsearch 5.6 and
        // used unchanged for index creation.
        'index.mapping.total_fields.limit'  => 1000,
        'index.mapping.depth.limit'         => 20,
        'index.mapping.nested_fields.limit' => 50,

        // Following are default values in Elasticsearch 5.6 and
        // is changeable per index via CLI command.
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
                        'en_stopwords',
                    ],
                ],

                'edge_ngram_analyzer_without_token_chars' => [
                    'type'      => 'custom',
                    'tokenizer' => 'edge_ngram_tokenizer_without_token_chars',
                    'filter'    => [
                        'lowercase',
                        'en_stopwords',
                    ],
                ],

                'ngram_analyzer_digit_only' => [
                    'type'      => 'custom',
                    'tokenizer' => 'ngram_tokenizer_digit_only',
                    'filter'    => [
                        'lowercase',
                        'en_stopwords',
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
                // char in normal standard analyzer.
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
                        'en_stopwords',
                    ],
                ],

                // This is used as workaround until we use v5.1.
                // Instead of type=keyword,normalizer=lowercase we will use type=text,analyzer=lowercase_keyword.
                'lowercase_keyword' => [
                    'type'      => 'custom',
                    'tokenizer' => 'keyword',
                    'filter'    => [
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
                'edge_ngram_tokenizer_without_token_chars' => [
                    'type'        => 'edge_ngram',
                    'min_gram'    => 2,
                    'max_gram'    => 50,
                ],
                'ngram_tokenizer_digit_only' => [
                    'type'        => 'ngram',
                    'min_gram'    => 2,
                    'max_gram'    => 20,
                    'token_chars' => [
                        'digit',
                    ],
                ],
            ],
            'filter' => [
                'en_stopwords' => [
                    'type'      => 'stop',
                    'stopwords' => '_english_',
                ],
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
        'commission',
        'virtual_account',
        'qr_code',
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
            // Reference: https://www.elastic.co/guide/en/elasticsearch/reference/current/object.html
            'notes' => [
                'type'       => 'object',
                'dynamic'    => false,
                'enabled'    => true,
                'properties' => [
                    'key' => [
                        'type'     => 'text',
                        'analyzer' => 'lowercase_keyword',
                    ],
                    'value' => [
                        'type'     => 'text',
                        'analyzer' => 'lowercase_keyword',
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
            'user_id' => [
                'type'            => 'keyword',
            ],
            'subscription_id' => [
                'type'            => 'keyword',
            ],
            'currency' => [
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
            'entity_type' => [
                'type'            => 'keyword',
            ],
        ],
    ],

    'commission_mapping'      => [
        'properties' => [
            'type' => [
                'type' => 'keyword',
            ],
            'partner_id' => [
                'type' => 'keyword',
            ],
            'source_type' => [
                'type' => 'keyword',
            ],
            'source_id' => [
                'type' => 'keyword',
            ],
            'status' => [
                'type' => 'keyword',
            ],
            'transaction_id' => [
                'type' => 'keyword',
            ],
            'partner_config_id' => [
                'type' => 'keyword',
            ],
            'model' => [
                'type' => 'keyword',
            ],
            'merchant' => [
                'properties' => [
                    'id' => [
                        'type'  => 'keyword',
                    ],
                ],
            ],
        ],
    ],

    'order_mapping'           => [],

    'payment_mapping'         => [
        'properties' => [
            'recurring' => [
                'type' => 'boolean',
            ],
            'amount_transferred' => [
                'type' => 'long',
            ],
            'va_transaction_id' => [
                'type' => 'keyword',
            ],
            'status' => [
                'type' => 'keyword',
            ],
        ],
    ],

    'refund_mapping'          => [],

    'reversal_mapping'        => [],

    'transfer_mapping'        => [],

    'virtual_account_mapping' => [
        'properties' => [
            'balance_id' => [
                'type'  => 'keyword',
            ],
            'name' => [
                'type' => 'text',
            ],
            'description' => [
                'type' => 'text',
            ],
            'status' => [
                'type' => 'keyword',
            ],
            'email' => [
                'type' => 'text',
            ],
            'contact' => [
                'type' => 'text',
            ],
            'bank_account_id'=> [
                'type'=> 'keyword'
            ],
            'vpa_id'=> [
                'type'=> 'keyword'
            ],
            'qr_code_id'=> [
                'type'=> 'keyword'
            ],
            'vpa' => [
                'type'=> 'keyword'
            ],
            'account_number' => [
                'type'=> 'keyword'
            ]
        ],
    ],

    'qr_payment_mapping' => [
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
            'payment_id' => [
                'type' => 'keyword',
            ],
            'status' => [
                'type' => 'keyword',
            ],
            'qr_code_id' => [
                'type' => 'keyword',
            ],
            'provider_reference_id' => [
                'type' => 'keyword'
            ],
            'cust_email' => [
                'type' => 'keyword'
            ]
        ],
    ],

    'qr_code_mapping'         => [
        'properties' => [
            'name' => [
                'type' => 'text',
            ],
            'cust_name' => [
                'type' => 'text',
            ],
            'status' => [
                'type' => 'keyword',
            ],
            'cust_email' => [
                'type' => 'text',
            ],
            'cust_contact' => [
                'type' => 'text',
            ],
            'customer_id' => [
                'type' => 'keyword',
            ],
        ]
    ],

    'payment_page_item_mapping' => [
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'name' => [
                'type' => 'text',
            ],
            'description' => [
                'type' => 'text',
            ],
            'item_deleted_at' => [
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
            'payment_link_id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type' => 'keyword',
            ],
        ]
    ],

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
            'partner_type' => [
                'type' => 'keyword',
            ],
            'activation_source' => [
                'type' => 'keyword',
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
            'merchant_business_detail' =>[
                'properties' => [
                    'miq_sharing_date'         => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                    ],
                    'testing_credentials_date'          => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                    ],
                ],
            ],
            'merchant_detail' => [
                'properties' => [
                    'merchant_id'         => [
                        'type'  => 'keyword',
                        'index' => false,
                    ],
                    'steps_finished'      => [
                        'type'  => 'keyword',
                        'index' => false,
                    ],
                    'activation_progress' => [
                        'type' => 'byte',
                    ],
                    'activation_status'   => [
                        'type' => 'keyword',
                    ],
                    'activation_flow'     => [
                        'type' => 'keyword',
                    ],
                    'reviewer_id'         => [
                        'type' => 'keyword',
                    ],
                    'archived_at'         => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                    ],
                    'submitted_at'        => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                    ],
                    'updated_at'          => [
                        'type'   => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
                        'index'  => false,
                    ],
                    'business_type'       => [
                        'type' => 'keyword',
                    ]
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
            'balance' => [
                'type' => 'long',
            ],
            'account_code' => [
                'type' => 'keyword',
            ],
        ],
    ],

    'payment_link_mapping' => [
        '_all' => [
            'enabled' => false
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'user_id' => [
                'type'  => 'keyword',
            ],
            'status' => [
                'type'  => 'keyword',
            ],
            'status_reason' => [
                'type'  => 'keyword',
            ],
            'view_type' => [
                'type'  => 'keyword',
            ],
            'receipt' => [
                'type'  => 'keyword',
            ],
            'title' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],

    'contact_mapping' => [
        '_all' => [
            'enabled' => false
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'active' => [
                'type' => 'boolean',
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
                'fields'          => [
                    'raw' => [
                        'type'  => 'keyword',
                        'index' => 'not_analyzed'
                    ],
                    'partial_search' => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer_without_token_chars',
                        'search_analyzer' => 'keyword',
                    ]
                ]
            ],
            'contact' => [
                'type'            => 'keyword',
                'fields'          => [
                    'partial_search' => [
                        'type'            => 'text',
                        'analyzer'        => 'ngram_analyzer_digit_only',
                        'search_analyzer' => 'keyword',
                    ]
                ]
            ],
            'type' => [
                'type' => 'keyword',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],

    'payout_mapping' => [
        '_all' => [
            'enabled' => false,
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'balance_id' => [
                'type'  => 'keyword',
            ],
            'contact_type' => [
                'type'  => 'keyword',
            ],
            'contact_name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'contact_phone' => [
                'type'            => 'text',
                'analyzer'        => 'ngram_analyzer_digit_only',
                'search_analyzer' => 'keyword',
            ],
            'contact_email' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
                'fields'          => [
                    'raw' => [
                        'type'  => 'keyword',
                        'index' => 'not_analyzed'
                    ],
                    'partial_search' => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer_without_token_chars',
                        'search_analyzer' => 'keyword',
                    ]
                ]
            ],
            'fund_account_number' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer_without_token_chars',
                'search_analyzer' => 'keyword',
            ],
            'notes' => [
                'type'       => 'object',
                'dynamic'    => false,
                'properties' => [
                    'key' => [
                        'type'     => 'text',
                        'analyzer' => 'lowercase_keyword',
                    ],
                    'value' => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer',
                        'search_analyzer' => 'standard_custom',
                    ],
                ],
            ],
            'source_type' => [
                'type' => 'keyword',
            ],
            'type' => [
                'type'  => 'keyword',
            ],
            'product' => [
                'type'  => 'keyword',
            ],
            'method' => [
                'type'  => 'keyword',
            ],
            'mode' => [
                'type'     => 'text',
                'analyzer' => 'lowercase_keyword'
            ],
            'status' => [
                'type'  => 'keyword',
            ],
            'purpose' => [
                'type'  => 'keyword',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
            'reversed_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],


    'transaction_mapping' => [
        '_all' => [
            'enabled' => false,
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'balance_id' => [
                'type'  => 'keyword',
            ],
            'contact_name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'contact_phone' => [
                'type'            => 'text',
                'analyzer'        => 'ngram_analyzer_digit_only',
                'search_analyzer' => 'keyword',
            ],
            'contact_email' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
                'fields'          => [
                    'raw' => [
                        'type'  => 'keyword',
                        'index' => 'not_analyzed'
                    ],
                    'partial_search' => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer_without_token_chars',
                        'search_analyzer' => 'keyword',
                    ]
                ]
            ],
            'fund_account_number' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer_without_token_chars',
                'search_analyzer' => 'keyword',
            ],
            'notes' => [
                'type'       => 'object',
                'dynamic'    => false,
                'properties' => [
                    'key' => [
                        'type'     => 'text',
                        'analyzer' => 'lowercase_keyword',
                    ],
                    'value' => [
                        'type'            => 'text',
                        'analyzer'        => 'edge_ngram_analyzer',
                        'search_analyzer' => 'standard_custom',
                    ],
                ],
            ],
            'utr' => [
                'type'  => 'keyword',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],

    'item_mapping' => [
        '_all' => [
            'enabled' => false
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'active' => [
                'type'  => 'boolean',
            ],
            'type' => [
                'type'  => 'keyword',
            ],
            'name' => [
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
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],


    'customer_mapping' => [
        '_all' => [
            'enabled' => false,
        ],
        'properties' => [
            'id' => [
                'type' => 'keyword',
            ],
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'name' => [
                'type'            => 'text',
                'analyzer'        => 'edge_ngram_analyzer',
                'search_analyzer' => 'standard_custom',
                'index_options'   => 'offsets',
            ],
            'contact' => [
                'type'  => 'keyword',
            ],
            'email' => [
                'type'  => 'keyword',
            ],
            'gstin' => [
                'type'  => 'keyword',
            ],
            'active' => [
                'type'  => 'boolean',
            ],
            'created_at' => [
                'type'   => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
            ],
        ],
    ],

    'partner_activation_mapping' => [
        'properties' => [
            'merchant_id' => [
                'type'  => 'keyword',
            ],
            'activation_status' => [
                'type' => 'keyword',
            ],
            'reviewer_id' => [
                'type' => 'keyword',
            ],
            'merchant' => [
                'properties' => [
                    'id' => [
                        'type' => 'keyword',
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
                ],
            ],
        ],
    ],
];

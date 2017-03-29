<?php

namespace RZP\Models\Base\Es;

/**
 * Holds mappings for various things:
 * - Defaults for general data types - text, boolean, date, notes etc.
 * - Index settings
 * - Other utility methods..
 *
 */
class Mapping
{

    // Default mappings for different types of fields

    public static $keywordFieldMapping = [
        'type' => 'keyword',
    ];

    // Ref: https://www.elastic.co/guide/en/elasticsearch/reference/
    //          current/analysis-edgengram-tokenizer.html
    public static $textFieldMapping = [
        'type'            => 'text',
        'analyzer'        => 'edge_ngram_analyzer',
        'search_analyzer' => 'standard',
        'index_options'   => 'offsets',
    ];

    public static $booleanFieldMapping = [
        'type' => 'boolean',
    ];

    public static $epochFieldMapping = [
        'type'   => 'date',
        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis',
    ];

    public static $objectFieldMapping = [
        'type' => 'object',
    ];

    // Default index settings and type mappings

    public static $indexSettings = [
        // We have notes which keeps on adding new fields under notes object, so
        // we need to support a high total_fields. By default this value is 1000
        // but there is no issue in safely increasing this limit.
        'index.mapping.total_fields.limit'  => 10000000,
        // Followings index. namespaced settings are defaults, but just dropping
        // it here for reference.
        'index.mapping.depth.limit'         => 50,
        'index.mapping.nested_fields.limit' => 20,
        'number_of_shards'                  => 5,
        'number_of_replicas'                => 1,
        'analysis' => [
            'analyzer' => [
                'edge_ngram_analyzer' => [
                    'tokenizer' => 'edge_ngram_tokenizer',
                    'filter'    => ['lowercase_filter'],
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
    ];

    public static $typeMappings = [
        '_default_' => [
            // We won't be using _all field
            '_all' => [
                'enabled' => false,
            ],
            'properties' => [],
            'dynamic_templates' => [
                [
                    // All fields of notes object will have following type and
                    // analysis.
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
    ];

    /**
     * Utility: Returns mapping put with default values in case it doesn't exists
     * for a given field.
     *
     * @param array $fields
     * @param array $fieldMappings
     *
     * @return array
     */
    public static function mappings(array $fields, array $fieldMappings): array
    {
        foreach ($fields as $field)
        {
            if (isset($fieldMappings[$field]) === false)
            {
                $fieldMappings[$field] = self::$keywordFieldMapping;
            }
        }

        $mappings = self::$typeMappings;

        $mappings['_default_']['properties'] = $fieldMappings;

        return $mappings;
    }
}

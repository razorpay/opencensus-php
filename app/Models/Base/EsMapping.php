<?php

namespace RZP\Models\Base;

class EsMapping
{
    //
    // Default mappings for different types of fields
    //

    public static $keywordFieldMapping = [
        'type' => 'keyword',
    ];

    public static $textFieldMapping = [
        'type'            => 'text',
        'analyzer'        => 'edge_ngram_analyzer',
        'search_analyzer' => 'standard',
        'index_options'   => 'offsets',
    ];

    public static $booleanFieldMapping = [
        'type' => 'boolean',
    ];

    public static $dateFieldMapping = [
        'type' => 'date',
    ];

    public static $objectFieldMapping = [
        'type' => 'object',
    ];

    //
    // Default index settings and type mappings
    //

    public static $indexSettings = [
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
            'properties' => [],
            'dynamic_templates' => [
                [
                    'default' => [
                        'match_mapping_type' => 'string',
                        'mapping'            => [
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
     * Returns mapping put with default values in case it doesn't exists
     * for a given field.
     *
     * @param array $fields
     * @param array $fieldMappings
     *
     * @return array
     */
    public static function mappings(array $fields, array $fieldMappings)
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

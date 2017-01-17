<?php

namespace RZP\Models\Base;

class EsMappping
{
    public static $defaultFieldMapping = [
        'type' => 'keyword',
    ];

    public static $defaultTextFieldMapping = [
        'type'            => 'text',
        'analyzer'        => 'edge_ngram_analyzer',
        'search_analyzer' => 'standard',
        'index_options'   => 'offsets',
    ];

    public static $defaultIndexSettings = [
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

    public static $defaultTypeMappings = [
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

    public static function mappings(array $fields, array $fieldMappings)
    {
        foreach ($fields as $field)
        {
            if (isset($fieldMappings[$field]) === false)
            {
                $fieldMappings[$field] = self::$defaultFieldMapping;
            }
        }

        $mappings                            = self::$defaultTypeMappings;
        $mappings['_default_']['properties'] = $fieldMappings;

        return $mappings;
    }
}

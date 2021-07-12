<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Shop_establishment_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'entity_name'
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_suzzy' => [
                        [
                            'var' => 'artefact.details.entity_name.value',
                        ],
                        [
                            'var' => 'enrichments.ocr.details.1.entity_name.value',
                        ],
                        81,
                    ]
                ],
            ],
        ],
    ];
}

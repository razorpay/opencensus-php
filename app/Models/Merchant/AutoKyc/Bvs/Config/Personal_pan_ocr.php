<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Personal_pan_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'pan_number',
                'name'
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v2',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'equals' => [
                        [
                            'var' => 'artefact.details.pan_number.value',
                        ],
                        [
                            'var' => 'enrichments.ocr.details.1.pan_number.value',
                        ],
                    ],
                ],
            ],
            '1' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_suzzy' => [
                        [
                            'var' => 'artefact.details.name.value',
                        ],
                        [
                            'var' => 'enrichments.ocr.details.1.name.value',
                        ],
                        81,
                    ],
                ],
            ],
        ],
    ];
}

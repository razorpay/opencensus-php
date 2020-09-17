<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Personal_pan extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'name'
            ],
        ],
    ];

    protected $rule = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_wuzzy' => [
                        [
                            'var' => 'artefact.details.name.value'
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.name.value'
                        ],
                        100,
                    ],
                ],
            ],
        ],
    ];
}

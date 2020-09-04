<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class POI extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'pan_owner_name'
            ],
        ],
    ];

    protected $rule = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_match' => [
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

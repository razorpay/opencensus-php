<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Business_pan extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'name'
            ],
        ],
    ];

    protected $enrichment_v2 = [
        'online_provider' => [
            'required_fields' => [
                'name'
            ],
            'enrichment_details_fields' => [
                'online_provider.details.name.value'
            ]
        ],
    ];

    protected $fetchDetailsRule = [
        'version'    => 'v2',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def' => [
                    '===' => [
                        '1',
                        '1'
                    ]
                ]
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v2',
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
                        81,
                        [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                ],
            ],
        ],
    ];
}

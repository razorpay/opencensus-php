<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Shop_establishment_auth extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'entity_name',
                'owner_name'
            ]
        ]
    ];

    protected $rule_v2 = [
        'version'    => 'v2',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_suzzy' => [
                        [
                            'var' => 'artefact.details.owner_name.value'
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.owner_name.value'
                        ],
                        81
                    ]
                ]
            ],
            '1' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_wuzzy' => [
                        [
                            'var' => 'artefact.details.entity_name.value'
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.entity_name.value'
                        ],
                        81,
                        [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ],
                    ]
                ]
            ]
        ]
    ];
}

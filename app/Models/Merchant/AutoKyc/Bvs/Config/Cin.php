<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Cin extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'signatory_details',
                'company_name',
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v2',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  =>
                    [
                        'fuzzy_wuzzy' => [
                            [
                                'var' => 'artefact.details.company_name.value',
                            ],
                            [
                                'var' => 'enrichments.online_provider.details.company_name.value',
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
            '1' => [
                'rule_type' => 'array_comparison_rule',
                'rule_def'  => [
                    "some" => [
                        [
                        'var' => 'enrichments.online_provider.details.signatory_details',
                        ],
                        [
                            'fuzzy_suzzy' => [
                                [
                                    'var' => 'artefact.details.signatory_details.0.full_name.value',
                                ],
                                [
                                    'var' => 'each_array_element.full_name.value',
                                ],
                                81,
                            ],
                        ]
                    ]
                ],
            ]
        ],
    ];

    protected $enrichmentDetails = [
        "online_provider.details.company_sub_category.value",
        "online_provider.details.company_category.value",
        "online_provider.details.registered_address.value",
        "online_provider.details.signatory_details",
    ];
}

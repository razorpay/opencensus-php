<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;


class Gstin_self_serve extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'trade_name',
                'legal_name',
                'signatory_names',
                'primary_pin_code',
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
                        'or' => [
                            [
                                'fuzzy_suzzy' => [
                                    [
                                        'var' => 'artefact.details.legal_name.value',
                                    ],
                                    [
                                        'var' => 'enrichments.online_provider.details.legal_name.value',
                                    ],
                                    81,
                                ],
                            ],
                            [
                                'fuzzy_wuzzy' => [
                                    [
                                        'var' => 'artefact.details.trade_name.value',
                                    ],
                                    [
                                        'var' => 'enrichments.online_provider.details.trade_name.value',
                                    ],
                                    81,
                                    [
                                        "private limited",
                                        "limited liability partnership",
                                        "pvt",
                                        "ltd",
                                        "."
                                    ]
                                ]
                            ],
                        ],
                    ],
            ],
            '1' => [
                'rule_type' => 'array_comparison_rule',
                'rule_def'  => [
                    "some" => [
                        [
                            'var' => 'enrichments.online_provider.details.signatory_names',
                        ],
                        [
                            'fuzzy_suzzy' => [
                                [
                                    'var' => 'artefact.details.legal_name.value',
                                ],
                                [
                                    'var' => "each_array_element",
                                ],
                                81,
                            ],
                        ]
                    ]
                ],
            ],
            '2' => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "equals" => [
                        [
                            "var" => "artefact.details.primary_pin_code.value",
                        ],
                        [
                            "var" => "enrichments.online_provider.details.primary_pin_code.value",
                        ],
                    ],
                ],
            ],
        ],
    ];
}

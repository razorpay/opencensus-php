<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Bank_account_reg extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'account_number',
                'ifsc',
                'account_holder_names',
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
                            'var' => 'artefact.details.account_number.value',
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.account_number.value',
                        ],
                    ],
                ],
            ],
            '1' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'equals' => [
                        [
                            'var' => 'artefact.details.ifsc.value',
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.ifsc.value',
                        ],
                    ],
                ],
            ],
            '2' => [
                'rule_type' => 'array_comparison_rule',
                'rule_def'  => [
                    "or" => [
                        [
                            "some" => [
                                [
                                    'var' => 'enrichments.online_provider.details.account_holder_names',
                                ],
                                [
                                    'fuzzy_suzzy' => [
                                        [
                                            'var' => "each_array_element.value",
                                        ],
                                        [
                                            'var' => 'artefact.details.account_holder_names.0.value'
                                        ],
                                        51,
                                    ],
                                ]
                            ]
                        ],
                        [
                            "some" => [
                                [
                                    'var' => 'enrichments.online_provider.details.account_holder_names',
                                ],
                                [
                                    'fuzzy_wuzzy' => [
                                        [
                                            'var' => "each_array_element.value",
                                        ],
                                        [
                                            'var' => 'artefact.details.account_holder_names.1.value'
                                        ],
                                        51,
                                        [
                                            "private limited",
                                            "limited liability partnership",
                                            "pvt",
                                            "ltd",
                                            "."
                                        ],
                                    ],
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        ],
    ];
}

<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Llpin extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'signatory_details',
                'llp_name',
            ],
        ],
    ];

    protected $rule = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  =>
                    [
                        'fuzzy_wuzzy' => [
                            [
                                'var' => 'artefact.details.llp_name.value',
                            ],
                            [
                                'var' => 'enrichments.online_provider.details.llp_name.value',
                            ],
                            70,
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
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => 'each_array_element.full_name.value',
                                ],
                                [
                                    'var' => 'artefact.details.signatory_details.0.full_name.value',
                                ],
                                70,
                            ],
                        ]
                    ]
                ],
            ]
        ],
    ];
}

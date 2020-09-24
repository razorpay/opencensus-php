<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Llpin extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'signatory_details',
                'company_name',
                'registered_address',
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
                'rule_type' => 'array_comparision_rule',
                'rule_def'  => [
                    "some" => [
                        'enrichments.online_provider.details.signatory_details.value',
                        [
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => 'full_name.value',
                                ],
                                [
                                    'var' => 'artefact.details.signatory_name.value',
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

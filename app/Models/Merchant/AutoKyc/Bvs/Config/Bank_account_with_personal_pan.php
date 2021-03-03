<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Bank_account_with_personal_pan extends BaseConfig
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
                'rule_type' => 'array_comparison_rule',
                'rule_def'  => [
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
                                81,
                            ],
                        ]
                    ]
                ],
            ]
        ],
    ];
}

<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Bank_account_with_business_pan extends Bank_account
{
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
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => "each_array_element.value",
                                ],
                                [
                                    'var' => 'artefact.details.account_holder_names.0.value'
                                ],
                                81,
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
            ]
        ],
    ];
}

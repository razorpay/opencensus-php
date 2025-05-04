<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Bank_account_my extends BaseConfig
{

    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'creditor_name'
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    'fuzzy_wuzzy' => [
                        [
                            'var' => 'artefact.details.data.creditor_name.value',
                        ],
                        [
                            'var' => 'enrichments.online_provider.details.data.creditor_name.value',
                        ],
                        81,
                    ],
                ],
            ],
        ],
    ];
}

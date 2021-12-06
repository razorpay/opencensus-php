<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Common_manual_verification extends BaseConfig
{
    protected $enrichment = [
        'internal_ops' => [
            'required_fields' => [
                "status"
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
                        'equals' => [
                            [
                                'var' => 'enrichments.internal_ops.details.data.status.value',
                            ],
                            "activated"
                        ],
                    ],
            ],
        ],
    ];
}

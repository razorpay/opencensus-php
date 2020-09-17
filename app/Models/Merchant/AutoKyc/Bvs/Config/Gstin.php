<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Gstin extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'trade_name',
                'legal_name',
                'signatory_names',
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
                        'or' => [
                            [
                                'fuzzy_wuzzy' => [
                                    [
                                        'var' => 'artefact.details.promoter_pan_name.value',
                                    ],
                                    [
                                        'var' => 'enrichments.online_provider.details.legal_name.value',
                                    ],
                                    70,
                                ],
                            ],
                            [
                                'fuzzy_wuzzy' => [
                                    [
                                        'var' => 'artefact.details.company_name.value',
                                    ],
                                    [
                                        'var' => 'enrichments.online_provider.details.trade_name.value',
                                    ],
                                    70,
                                ]
                            ],
                        ],
                    ],
            ],
            '1' => [
                'rule_type' => 'array_comparision_rule',
                'rule_def'  => [
                    "some" => [
                        'enrichments.online_provider.details.signatory_names.value',
                        [
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => '',
                                ],
                                [
                                    'var' => 'artefact.details.promoter_pan_name.value',
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

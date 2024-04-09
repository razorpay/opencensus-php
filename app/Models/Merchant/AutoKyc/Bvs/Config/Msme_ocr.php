<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Msme_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                "trade_name",
                "signatory_name"
            ],
        ],
    ];

    protected $rule_v2 = [
        'version'    => 'v1',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def'  => [
                    "or" => [
                        [
                            'fuzzy_suzzy' => [
                                [
                                    
                                    'var' => 'artefact.details.trade_name.value',
                                ],
                                [
                                    'var' => 'enrichments.ocr.details.1.trade_name.value',
                                ],
                                81,
                            ],
                        ],
                        [
                            'fuzzy_suzzy' => [
                                [
                                    'var' => 'artefact.details.signatory_name.value',
                                ],
                                [
                                    'var' => 'enrichments.ocr.details.1.trade_name.value',
                                ],
                                81,
                            ],
                        ]
                    ]
                ],
            ],
        ],
    ];
}

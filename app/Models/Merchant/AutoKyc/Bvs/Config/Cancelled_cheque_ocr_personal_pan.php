<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Cancelled_cheque_ocr_personal_pan extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
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
                            'var' => 'enrichments.ocr.details.1.account_number.value',
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
                            'var' => 'enrichments.ocr.details.1.ifsc.value',
                        ],
                    ],
                ],
            ],
            '2' => [
                'rule_type' => 'array_comparison_rule',
                'rule_def'  => [
                    "some" => [
                        [
                            'var' => 'enrichments.ocr.details.1.account_holder_names',
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

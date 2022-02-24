<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Trust_society_ngo_business_certificate_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'trade_names',
            ],
        ],
    ];

    protected $rule_v2    = [
        "version"=> "v1",
        "rules_list"=> [
            "0"=> [
                "rule_type"=> "array_comparison_rule",
                "rule_def"=> [
                    "some" => [
                        [
                            "var" => "enrichments.ocr.details.1.trade_names"
                        ],
                        [
                            "fuzzy_wuzzy"=> [
                                [
                                    "var"=> "artefact.details.business_name.value"
                                ],
                                [
                                    "var"=> "each_array_element.value"
                                ],
                                81,
                                [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
}

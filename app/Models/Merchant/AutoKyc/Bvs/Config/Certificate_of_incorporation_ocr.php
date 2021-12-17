<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Certificate_of_incorporation_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'trade_name',
            ],
        ],
    ];

    protected $rule_v2    = [
        "version"=> "v1",
        "rules_list"=> [
            "0"=> [
                "rule_type"=> "string_comparison_rule",
                "rule_def"=> [
                    "fuzzy_wuzzy"=> [
                        [
                            "var"=> "artefact.details.trade_name.value"
                        ],
                        [
                            "var"=> "enrichments.ocr.details.1.trade_name.value"
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
    ];
}

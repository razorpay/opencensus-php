<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Partnership_deed_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'business_name',
                'name_of_partners'
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
                            "var"=> "artefact.details.business_name.value"
                        ],
                        [
                            "var"=> "enrichments.ocr.details.1.business_name.value"
                        ],
                        60,
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

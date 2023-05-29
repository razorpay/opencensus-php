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
            "0" => [
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
            ],
            "1" => [
                "rule_type"=> "string_comparison_rule",
                "rule_def"=> [
                    "any"=> [
                        [
                            "var"=> "enrichments.ocr.details.1.name_of_partners"
                        ],
                        [
                            "var"=> "artefact.details.name_of_partners"
                        ],
                        [
                            "fuzzy_suzzy"=> [
                                [
                                    "var"=> "each_array1_element"
                                ],
                                [
                                    "var"=> "each_array2_element"
                                ],
                                60
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
}

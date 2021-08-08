<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Gst_in_ocr extends BaseConfig
{
    protected $enrichment = [
        'ocr' => [
            'required_fields' => [
                'trade_name',
                'legal_name'
            ],
        ],
    ];

    protected $rule_v2    = [
        "version"=> "v2",
        "rules_list"=> [
            "0"=> [
                "rule_type"=> "string_comparison_rule",
                "rule_def"=> [
                    "or"=> [
                        [
                            "fuzzy_suzzy"=> [
                                [
                                    "var"=> "artefact.details.legal_name.value"
                                ],
                                [
                                    "var"=> "enrichments.ocr.details.1.legal_name.value"
                                ],
                                81
                            ]
                        ],
                        [
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
                        ],
                        [
                            "fuzzy_suzzy"=> [
                                [
                                    "var"=> "artefact.details.trade_name.value"
                                ],
                                [
                                    "var"=> "enrichments.ocr.details.1.legal_name.value"
                                ],
                                81
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
}

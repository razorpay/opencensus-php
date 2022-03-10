<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Aadhaar_front_and_back extends BaseConfig
{
    protected $enrichment = [
        "ocr" => [
            "required_fields" => [
                "name",
            ],
        ],
    ];

    protected $rule_v2 = [
        "version" => "v1",
        "rules_list" => [
            "0" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "fuzzy_suzzy" => [
                        [
                            "var" => "artefact.details.name.value",
                        ],
                        [
                            "var" => "enrichments.ocr.details.3.name.value",
                        ],
                        81
                    ],
                ],
            ],
            "1" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "fuzzy_suzzy" => [
                        [
                            "var" => "enrichments.ocr.details.2.aadhaar_number.value",
                        ],
                        [
                            "var" => "enrichments.ocr.details.3.aadhaar_number.value",
                        ],
                        100
                    ],
                ],
            ],
        ],
    ];

}

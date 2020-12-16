<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Aadhaar extends BaseConfig
{
    protected $enrichment = [
        "ocr" => [
            "required_fields" => [
                "name",
            ],
        ],
    ];

    protected $rule_v2 = [
        "version"    => "v2",
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
        ],
    ];
}

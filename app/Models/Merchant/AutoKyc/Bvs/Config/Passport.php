<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Passport extends BaseConfig
{
    protected $enrichment = [
        "ocr" => [
            "required_fields" => [
                "name",
            ],
        ],
    ];

    protected $rule = [
        "version"    => "v1",
        "rules_list" => [
            "0" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "fuzzy_wuzzy" => [
                        [
                            "var" => "artefact.details.name.value",
                        ],
                        [
                            "var" => "enrichments.ocr.details.1.name.value",
                        ],
                        70
                    ],
                ],
            ],
        ],
    ];
}

<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class POA extends BaseConfig
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
                            "var" => "enrichments.ocr.details.3.name.value",
                        ],
                        70
                    ],
                ],
            ],
        ],
    ];
}

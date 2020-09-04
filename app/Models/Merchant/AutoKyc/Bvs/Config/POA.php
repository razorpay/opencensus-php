<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class POA extends BaseConfig
{
    private $enrichment = [
        "ocr" => [
            "required_fields" => [
                "name",
            ],
        ],
    ];

    private $rule = [
        "version"   => "v1",
        "rule_list" => [
            "0" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "equals" => [
                        [
                            "var" => "artefact.details.name.value",
                        ],
                        [
                            "var" => "enrichments.ocr.details.1.name.value",
                        ]
                    ],
                ],
            ],
            "1" => [
                "rule_type" => "numeric_rule",
                "rule_def"  => [
                    ">" => [
                        [
                            "var" => "enrichments.ocr.details.1.name.score",
                        ],
                        0.97
                    ],
                ],
            ],
        ],
    ];
}

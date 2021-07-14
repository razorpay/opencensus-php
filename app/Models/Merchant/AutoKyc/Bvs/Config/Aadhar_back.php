<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Aadhar_back extends BaseConfig
{
    protected $enrichment = [
        "ocr" => [
            "required_fields" => [
                "name",
            ],
        ],
    ];

    protected $rule_v2 = [
        "version"    => "v1",
        "rules_list" => [
            "0" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "equals" => [
                        "dummy_value",
                        "dummy_value"
                    ],
                ],
            ],
        ],
    ];
}

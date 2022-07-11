<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Gstin_with_business_pan_for_no_doc extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'gstin.trade_name',
                'gstr.compliance_status.is_any_delay',
                'gstr.compliance_status.is_defaulter'
            ],
        ],
    ];

    protected $rule_v2    = [
        "version"    => "v3",
        "rules_list" => [
            "0" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "or" => [
                        [
                            "fuzzy_wuzzy" => [
                                [
                                    "var" => "artefact.details.trade_name.value"
                                ],
                                [
                                    "var" => "enrichments.online_provider.details.trade_name.value"
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
                            "fuzzy_suzzy" => [
                                [
                                    "var" => "artefact.details.trade_name.value"
                                ],
                                [
                                    "var" => "enrichments.online_provider.details.trade_name.value"
                                ],
                                81
                            ]
                        ]
                    ]
                ]
            ],
            "1" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "===" => [
                        [
                            "var"=> "artefact.details.compliance_status.is_any_delay.value"
                        ],
                        [
                            "var"=> "enrichments.online_provider.details.compliance_status.is_any_delay.value"
                        ]
                    ]
                ]
            ],
            "2" => [
                "rule_type" => "string_comparison_rule",
                "rule_def"  => [
                    "===" => [
                        [
                            "var"=> "artefact.details.compliance_status.is_defaulter.value"
                        ],
                        [
                            "var"=> "enrichments.online_provider.details.compliance_status.is_defaulter.value"
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected $enrichmentDetails = [
        "online_provider.details.primary_address.value",
    ];
}

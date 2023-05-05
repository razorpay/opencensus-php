<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class Gstin extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'trade_name',
                'legal_name',
                'signatory_names',
            ],
        ],
    ];

    protected $enrichment_v2 = [
        'online_provider' => [
            'required_fields' => [
                'gstin.aggregate_turnover',
                'gstin.gross_total_income'
            ],
            'enrichment_details_fields' => [
                "online_provider.details.trade_name.value",
                "online_provider.details.legal_name.value",
                "online_provider.details.aggregate_turnover",
                "online_provider.details.gross_total_income",
                "online_provider.details.registration_date.value",
            ]
        ]
    ];

    protected $fetchDetailsRule = [
        'version'    => 'v2',
        'rules_list' => [
            '0' => [
                'rule_type' => 'string_comparison_rule',
                'rule_def' => [
                    '===' => [
                        '1',
                        '1'
                    ]
                ]
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
                            "fuzzy_suzzy" => [
                                [
                                    "var" => "artefact.details.legal_name.value"
                                ],
                                [
                                    "var" => "enrichments.online_provider.details.legal_name.value"
                                ],
                                81
                            ]
                        ],
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
                                    "var" => "enrichments.online_provider.details.legal_name.value"
                                ],
                                81
                            ]
                        ]
                    ]
                ]
            ],
            "1" => [
                "rule_type" => "array_comparison_rule",
                "rule_def"  => [
                    "some" => [
                        [
                            "var" => "enrichments.online_provider.details.signatory_names"
                        ],
                        [
                            "fuzzy_wuzzy" => [
                                [
                                    "var" => "each_array_element"
                                ],
                                [
                                    "var" => "artefact.details.legal_name.value"
                                ],
                                81
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected $enrichmentDetails = [
        "online_provider.details.primary_address.value",
        "online_provider.details.trade_name.value",
        "online_provider.details.gstin.value",
        "online_provider.details.status.value",
        "online_provider.details.legal_name.value",
    ];
}

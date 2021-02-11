<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;


use DeepCopy\DeepCopy;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class Aadhaar_with_pan extends BaseConfig
{
    protected $enrichment = [
        "probe_provider" => [
            "probe_id"  => "",
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
                    "fuzzy_suzzy" => [
                        [
                            "var" => "artefact.details.name.value",
                        ],
                        [
                            "var" => "enrichments.probe_provider.details.name.value",
                        ],
                        81
                    ],
                ],
            ],
        ],
    ];

    /**
     * @param array $input
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichment()
    {
        assertTrue(empty($this->enrichment) === false);

        $clonedEnrichment = (new DeepCopy)->copy($this->enrichment);

        $clonedEnrichment["probe_provider"]["probe_id"] = $this->input[Constant::PROBE_ID];

        return $clonedEnrichment;
    }
}

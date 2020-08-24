<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\poi;

use RZP\Models\Merchant\AutoKyc\Bvs\Rules;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Enrichment;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseProcessor;
use RZP\Models\Merchant\Detail\Constants as DEConstant;

class PoiProcessor extends BaseProcessor
{
    /**
     * @return array
     */
    public function GetArtefact(): array
    {
        $artefactDetails = [
            Constant::PAN_NUMBER => $this->input[DEConstant::PAN_NUMBER],
            Constant::NAME       => $this->input[DEConstant::PROMOTER_PAN_NAME]
        ];

        $artefact = $this->getOwnerInput();

        $artefact[Constant::TYPE] = Constant::PERSONAL_PAN;

        $artefact[Constant::IDENTIFIER] = $this->input[DEConstant::PAN_NUMBER];

        $artefact[Constant::DETAILS] = $artefactDetails;

        return $artefact;
    }

    /**
     * @return array
     */
    public function GetEnrichments(): array
    {
        return [
            'online_provider' => [
                'required_fields' => [
                    'pan_owner_name'
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function GetRules(): array
    {
        return [
            'version'    => 'v1',
            'rules_list' => [
                '0' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def'  => [
                        'fuzzy_match' => [
                            [
                                'var' => 'artefact.details.name.value'
                            ],
                            [
                                'var' => 'enrichments.online_provider.details.name.value'
                            ]
                        ]
                    ],
                ],
            ],
        ];
    }
}

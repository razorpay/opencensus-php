<?php

namespace RZP\Models\Merchant\Stakeholder\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Stakeholder\Core;
use Selective\Transformer\ArrayTransformer;

class MerchantsTransformer extends Base\Transformer
{
    //Format of Each item in the rules array
    // input column name  1   => [
    //            [
    //                "column"    => output column name 1,
    //                'condition' => [ //used for row to column mapping - all are and conditions if met we will choose output column name 1 for input column name  1
    //                    input column name 1   => value 1,
    //                    input column name 2   => value 2,
    //             ],
    //                'function' => function name 1 // this is used for data conversion
    //            ]

    protected $rules = [
        'id'                             => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'business_identity.promoter_pan_name'   => [
            [
                "column" => 'name'
            ]
        ],
        'business_identity.promoter_pan' => [
            [
                "column" => 'poi_identification_number'
            ]
        ],
        'metadata.identity_proof_manual_submission' => [
            [
                "column" => 'aadhaar_linked',
                'function' => 'mapIdentityProofManualSubmission'
            ]
        ]
    ];

    // Mapping array to invert values
    public const IDENTITY_PROOF_MANUAL_SUBMISSION_MAPPING = [
        true => false,
        false => true,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

    }

    protected function registerFilters(ArrayTransformer $transformer)
    {
        $transformer->registerFilter(
            'mapIdentityProofManualSubmission',
            function($value) {
                return self::IDENTITY_PROOF_MANUAL_SUBMISSION_MAPPING[$value] ?? $value;
            }
        );
    }
}

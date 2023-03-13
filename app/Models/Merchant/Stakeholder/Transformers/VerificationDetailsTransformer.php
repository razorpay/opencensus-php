<?php

namespace RZP\Models\Merchant\Stakeholder\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Stakeholder\Core;
use Selective\Transformer\ArrayTransformer;

class VerificationDetailsTransformer extends Base\Transformer
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
        'merchant_id'             => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'verification_status'     => [
            [
                "column"    => 'aadhaar_esign_status',
                'condition' => [
                    'artefact_type'     => 'aadhaar_esign',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'aadhaar_verification_with_pan_status',
                'condition' => [
                    'artefact_type'     => 'aadhaar_esign',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ]
        ],
        'metadata.aadhaar_pin'    => [
            [
                "column" => 'aadhaar_pin'
            ]
        ],
        'metadata.aadhaar_linked' => [
            [
                "column" => 'aadhaar_linked'
            ]
        ],
        'metadata.bvs_probe_id'   => [
            [
                "column" => 'bvs_probe_id'
            ]
        ],
        'metadata'                => [
            [
                "column" => 'verification_metadata'
            ]
        ]
    ];

    public const VERIFICATION_STATUS_MAPPING = [
        "verified"          => "verified",
        "incorrect_details" => "incorrect_details",
        "not_matched"       => "not_matched",
        "failed"            => "failed",
        "captured"          => "initiated"
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    protected function registerFilters(ArrayTransformer $transformer)
    {
        $transformer->registerFilter(
            'mapVerificationStatus',
            function($value) {
                return self::VERIFICATION_STATUS_MAPPING[$value] ?? $value;
            }
        );
    }
}

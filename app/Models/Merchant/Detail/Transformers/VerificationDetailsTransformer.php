<?php

namespace RZP\Models\Merchant\Detail\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Detail\Core;
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
        'merchant_id'         => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'verification_status' => [
            [
                "column"    => 'poi_verification_status',
                'condition' => [
                    'artefact_type'     => 'personal_pan',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'company_pan_verification_status',
                'condition' => [
                    'artefact_type'     => 'business_pan',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'poa_verification_status',
                'condition' => [
                    'artefact_type'     => 'aadhaar',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'bank_details_verification_status',
                'condition' => [
                    'artefact_type'     => 'bank_account',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'gstin_verification_status',
                'condition' => [
                    'artefact_type'     => 'gstin',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'cin_verification_status',
                'condition' => [
                    'artefact_type'     => 'cin',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'shop_establishment_verification_status',
                'condition' => [
                    'artefact_type'     => 'shop_establishment',
                    'verification_unit' => 'auth'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'personal_pan_doc_verification_status',
                'condition' => [
                    'artefact_type'     => 'personal_pan',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'company_pan_doc_verification_status',
                'condition' => [
                    'artefact_type'     => 'business_pan',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'msme_doc_verification_status',
                'condition' => [
                    'artefact_type'     => 'msme',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ],
            [
                "column"    => 'bank_details_doc_verification_status',
                'condition' => [
                    'artefact_type'     => 'bank_account',
                    'verification_unit' => 'ocr'
                ],
                "function"  => 'mapVerificationStatus'
            ]
        ],
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

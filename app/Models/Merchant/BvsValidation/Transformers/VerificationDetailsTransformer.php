<?php

namespace RZP\Models\Merchant\BvsValidation\Transformers;

use RZP\Base;
use RZP\Models\Merchant\BvsValidation\Core;
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
        'merchant_id'                  => [
            [
                "column" => 'owner_id'
            ]
        ],
        'artefact_type'                => [
            [
                "column"   => 'artefact_type',
                "function" => 'mapArtefactType'
            ]
        ],
        'verification_id'              => [
            [
                "column" => 'validation_id'
            ]
        ],
        'verification_unit'            => [
            [
                "column"   => 'validation_unit',
                "function" => 'mapVerificationUnit'
            ]
        ],
        'verification_status'          => [
            [
                "column"   => 'validation_status',
                "function" => 'mapVerificationStatus'
            ]
        ],
        'metadata.error_code'          => [
            [
                "column" => 'error_code'
            ]
        ],
        'metadata.error_description'   => [
            [
                "column" => 'error_description'
            ]
        ],
        'metadata.rule_execution_list' => [
            [
                "column" => 'rule_execution_list'
            ]
        ],
        'metadata.fuzzy_score'         => [
            [
                "column" => 'fuzzy_score'
            ]
        ],
    ];

    public const VERIFICATION_UNIT_VALIDATION_UNIT_MAPPING = [
        "auth" => "identifier",
        "ocr"  => "proof"
    ];

    public const ARTEFACT_TYPE_MAPPING = [
        "aadhaar_front"                          => "aadhaar",
        "aadhaar_back"                           => "aadhaar",
        "aadhaar"                                => "aadhaar",
        "personal_pan"                           => "personal_pan",
        "business_pan"                           => "business_pan",
        "voters_id"                              => "voters_id",
        "passport"                               => "passport",
        "cin"                                    => "cin",
        "gstin"                                  => "gstin",
        "gst_certificate"                        => "gstin",
        "bank_account"                           => "bank_account",
        "shop_establishment"                     => "shop_establishment",
        "msme"                                   => "msme",
        "partnership_deed"                       => "partnership_deed",
        "certificate_of_incorporation"           => "certificate_of_incorporation",
        "trust_society_ngo_business_certificate" => "trust_society_ngo_business_certificate",
        "llp_deed"                               => "llp_deed",
    ];

    public const VERIFICATION_STATUS_MAPPING = [
        "verified"          => "success",
        "incorrect_details" => "failed",
        "not_matched"       => "failed",
        "failed"            => "failed",
        "captured"          => "captured"
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    protected function registerFilters(ArrayTransformer $transformer)
    {
        $transformer->registerFilter(
            'mapArtefactType',
            function($value) {
                return self::ARTEFACT_TYPE_MAPPING[$value] ?? $value;
            }
        );

        $transformer->registerFilter(
            'mapVerificationUnit',
            function($value) {
                return self::VERIFICATION_UNIT_VALIDATION_UNIT_MAPPING[$value] ?? $value;
            }
        );

        $transformer->registerFilter(
            'mapVerificationStatus',
            function($value) {
                return self::VERIFICATION_STATUS_MAPPING[$value] ?? $value;
            }
        );
    }
}

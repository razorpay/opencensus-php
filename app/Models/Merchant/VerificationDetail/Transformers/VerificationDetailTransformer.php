<?php

namespace RZP\Models\Merchant\VerificationDetail\Transformers;

use RZP\Base;
use Selective\Transformer\ArrayTransformer;
use RZP\Models\Merchant\VerificationDetail\Core;

class VerificationDetailTransformer extends Base\Transformer
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
        'artefact_type'       => [
            [
                "column" => 'artefact_type'
            ]
        ],
        'verification_unit'   => [
            [
                "column"   => 'artefact_identifier',
                "function" => 'mapVerificationUnit'
            ]
        ],
        'verification_status' => [
            [
                "column"   => 'status',
                "function" => 'mapVerificationStatus'
            ]
        ],
    ];

    public const VERIFICATION_UNIT_VALIDATION_UNIT_MAPPING = [
        "auth" => "number",
        "ocr"  => "doc"
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

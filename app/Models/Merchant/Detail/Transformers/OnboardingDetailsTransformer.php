<?php

namespace RZP\Models\Merchant\Detail\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Detail\Core;

class OnboardingDetailsTransformer extends Base\Transformer
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
        'merchant_id'                   => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'activation_progress'           => [
            [
                "column" => 'activation_progress'
            ]
        ],
        'locked'                        => [
            [
                "column" => 'locked'
            ]
        ],
        'activation_status'             => [
            [
                "column" => 'activation_status'
            ]
        ],
        'activation_flow'               => [
            [
                "column" => 'activation_flow'
            ]
        ],
        'international_activation_flow' => [
            [
                "column" => 'international_activation_flow'
            ]
        ],
        'submitted'                     => [
            [
                "column" => 'submitted'
            ]
        ],
        'submitted_at'                  => [
            [
                "column" => 'submitted_at'
            ]
        ],
        'activation_form_milestone'     => [
            [
                "column" => 'activation_form_milestone'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

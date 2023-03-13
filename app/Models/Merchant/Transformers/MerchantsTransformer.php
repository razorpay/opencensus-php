<?php

namespace RZP\Models\Merchant\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Core;

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
                "column" => 'id'
            ]
        ],
        'stakeholder.stakeholder_name'   => [
            [
                "column" => 'name'
            ]
        ],
        'stakeholder.stakeholder_email'  => [
            [
                "column" => 'email'
            ]
        ],
        'business_details.billing_label' => [
            [
                "column" => 'billing_label'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

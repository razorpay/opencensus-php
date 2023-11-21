<?php

namespace RZP\Models\User\Transformers;

use RZP\Base;
use RZP\Models\User\Core;

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
        'stakeholder.stakeholder_mobile' => [
            [
                "column" => 'contact_mobile'
            ]
        ],
    ];


    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

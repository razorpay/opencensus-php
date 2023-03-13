<?php

namespace RZP\Models\ClarificationDetail\Transformers;

use RZP\Base;
use RZP\Models\ClarificationDetail\Core;

class ClarificationDetailsTransformer extends Base\Transformer
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
        'id'                  => [
            [
                "column" => 'id'
            ]
        ],
        'merchant_id'                  => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'status'             => [
            [
                "column" => 'status'
            ]
        ],
        'metadata' => [
            [
                "column" => 'metadata'
            ]
        ],
        'group_name' => [
            [
                "column" => 'group_name'
            ]
        ],
        'comment_data' => [
            [
                "column" => 'comment_data'
            ]
        ],
        'message_from' => [
            [
                "column" => 'message_from'
            ]
        ],
        'field_details' => [
            [
                "column" => 'field_details'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

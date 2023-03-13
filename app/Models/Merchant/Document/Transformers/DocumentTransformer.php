<?php

namespace RZP\Models\Merchant\Document\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Document\Core;

class DocumentTransformer extends Base\Transformer
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
        'merchant_id'        => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'deleted_at'         => [
            [
                "column" => 'deleted_at',
            ],
        ],
        'document_type'      => [
            [
                "column" => 'document_type',
            ],
        ],
        'file_store_id'      => [
            [
                "column" => 'file_store_id',
            ],
        ],
        'upload_by_admin_id' => [
            [
                "column" => 'upload_by_admin_id',
            ],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

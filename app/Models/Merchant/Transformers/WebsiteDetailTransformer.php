<?php

namespace RZP\Models\Merchant\Transformers;

use RZP\Models\Merchant\Core;
use RZP\Base;

class WebsiteDetailTransformer extends Base\Transformer
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
                "column" => 'id'
            ]
        ],
        'business_website'                      => [
            [
                "column" => 'website'
            ]
        ],
        'metadata.whitelisted_domains' => [
            [
                "column" => 'whitelisted_domains'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

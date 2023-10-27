<?php

namespace RZP\Models\Merchant\Website\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Website\Core;

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
        'id' => [
            [
                "column" => 'id'
            ]
        ],
        'merchant_id' => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'status' => [
            [
                "column" => 'status'
            ]
        ],
        'grace_period' => [
            [
                "column" => 'grace_period'
            ]
        ],
        'send_communication' => [
            [
                "column" => 'send_communication'
            ]
        ],
        'audit_id' => [
            [
                "column" => 'audit_id'
            ]
        ],
        'metadata.deliverable_type' => [
            [
                "column" => 'deliverable_type'
            ]
        ],
        'metadata.shipping_period' => [
            [
                "column" => 'shipping_period'
            ]
        ],
        'metadata.refund_request_period' => [
            [
                "column" => 'refund_request_period'
            ]
        ],
        'metadata.refund_process_period' => [
            [
                "column" => 'refund_process_period'
            ]
        ],
        'metadata.warranty_period' => [
            [
                "column" => 'warranty_period'
            ]
        ],
        'metadata.additional_data' => [
            [
                "column" => 'additional_data'
            ]
        ],
        'metadata.merchant_website_policy_details' => [
            [
                "column" => 'merchant_website_details'
            ]
        ],
        'metadata.admin_website_policy_details' => [
            [
                "column" => 'admin_website_details'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

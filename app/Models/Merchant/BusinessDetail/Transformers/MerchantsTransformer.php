<?php

namespace RZP\Models\Merchant\BusinessDetail\Transformers;

use RZP\Base;
use RZP\Models\Merchant\BusinessDetail\Core;

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
        'id'                                             => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'business_details.business_parent_category'      => [
            [
                "column" => 'business_parent_category'
            ]
        ],
        'business_details.blacklisted_products_category' => [
            [
                "column" => 'blacklisted_products_category'
            ]
        ],
        'payment_preference.physical_store'              => [
            [
                "column" => 'website_details.physical_store'
            ]
        ],
        'payment_preference.social_media'                => [
            [
                "column" => 'website_details.social_media'
            ]
        ],
        'payment_preference.website_present'             => [
            [
                "column" => 'website_details.website_present'
            ]
        ],
        'payment_preference.android_app_present'         => [
            [
                "column" => 'website_details.android_app_present'
            ]
        ],
        'payment_preference.ios_app_present'             => [
            [
                "column" => 'website_details.ios_app_present'
            ]
        ],
        'payment_preference.others_present'              => [
            [
                "column" => 'website_details.others_present'
            ]
        ],
        'payment_preference.others'                      => [
            [
                "column" => 'website_details.others'
            ]
        ],
    ];


    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

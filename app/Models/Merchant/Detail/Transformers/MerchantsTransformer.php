<?php

namespace RZP\Models\Merchant\Detail\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Detail\Core;
use Selective\Transformer\ArrayTransformer;

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
        'id'                                           => [
            [
                "column" => 'merchant_id'
            ]
        ],
        'stakeholder.stakeholder_name'                 => [
            [
                "column" => 'contact_name'
            ]
        ],
        'stakeholder.stakeholder_email'                => [
            [
                "column" => 'contact_email'
            ]
        ],
        'stakeholder.stakeholder_mobile'               => [
            [
                "column" => 'contact_mobile'
            ]
        ],
        'business_details.business_type'               => [
            [
                "column"   => 'business_type',
                "function" => 'mapBusinessType'
            ]
        ],
        'business_details.company_pan_name'            => [
            [
                "column" => 'company_pan_name',
            ]
        ],
        'business_details.business_name'               => [
            [
                "column" => 'business_name'
            ]
        ],
        'business_details.business_description'        => [
            [
                "column" => 'business_description'
            ]
        ],
        'business_details.billing_label'               => [
            [
                "column" => 'business_dba'
            ]
        ],
        'business_details.business_registered_address' => [
            [
                "column" => 'business_registered_address'
            ]
        ],
        'business_details.business_registered_state'   => [
            [
                "column" => 'business_registered_state'
            ]
        ],
        'business_details.business_registered_city'    => [
            [
                "column" => 'business_registered_city'
            ]
        ],
        'business_details.business_registered_pin'     => [
            [
                "column" => 'business_registered_pin'
            ]
        ],
        'business_details.business_operation_address'  => [
            [
                "column" => 'business_operation_address'
            ]
        ],
        'business_details.business_operation_state'    => [
            [
                "column" => 'business_operation_state'
            ]
        ],
        'business_details.business_operation_city'     => [
            [
                "column" => 'business_operation_city'
            ]
        ],
        'business_details.business_operation_pin'      => [
            [
                "column" => 'business_operation_pin'
            ]
        ],
        'business_identity.gstin'                      => [
            [
                "column" => 'gstin'
            ]
        ],
        'business_identity.company_cin'                => [
            [
                "column" => 'company_cin'
            ]
        ],
        'business_identity.llp_in'                     => [
            [
                "column" => 'company_cin'
            ]
        ],
        'business_identity.company_pan'                => [
            [
                "column" => 'company_pan'
            ]
        ],
        'business_identity.business_category'          => [
            [
                "column" => 'business_category'
            ]
        ],
        'business_identity.business_subcategory'       => [
            [
                "column" => 'business_subcategory'
            ]
        ],
        'business_identity.business_model'             => [
            [
                "column" => 'business_model'
            ]
        ],
        'business_identity.promoter_pan'               => [
            [
                "column" => 'promoter_pan'
            ]
        ],
        'business_identity.promoter_pan_name'          => [
            [
                "column" => 'promoter_pan_name'
            ]
        ],
        'business_identity.bank_account_number'        => [
            [
                "column" => 'bank_account_number'
            ]
        ],
        'business_identity.bank_account_name'          => [
            [
                "column" => 'bank_account_name'
            ]
        ],
        'business_identity.bank_branch_ifsc'           => [
            [
                "column" => 'bank_branch_ifsc'
            ]
        ],
        'business_identity.shop_establishment_number'  => [
            [
                "column" => 'shop_establishment_number'
            ]
        ],
    ];


    public const BUSINESS_TYPE_MAPPING = [
        "proprietorship"         => 1,
        "individual"             => 2,
        "partnership"            => 3,
        "private_limited"        => 4,
        "public_limited"         => 5,
        "llp"                    => 6,
        "ngo"                    => 7,
        "educational_institutes" => 8,
        "trust"                  => 9,
        "society"                => 10,
        "not_yet_registered"     => 11,
        "other"                  => 12,
        "huf"                    => 13
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    protected function registerFilters(ArrayTransformer $transformer)
    {
        $transformer->registerFilter(
            'mapBusinessType',
            function($value) {
                return self::BUSINESS_TYPE_MAPPING[$value] ?? $value;
            }
        );
    }
}

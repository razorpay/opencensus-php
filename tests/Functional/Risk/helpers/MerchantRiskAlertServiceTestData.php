<?php

return [
    'testRasSignupFraudMerchant' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'activation_form_milestone'   => 'L1',
                'company_cin'                 => 'U65999KA2018PTC114468',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'activation_form_milestone'   => 'L1',
                'company_cin'                 => 'U65999KA2018PTC114468',
                'promoter_pan'                => 'ABCPE0000Z',
                'gstin'                       => null,
                'p_gstin'                     => null,
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'archived'                    => 0,
                'submitted_at'                => null,
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'can_submit'                  => false,
                'activated'                   => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testGetMerchantDetails' => [
        'request'     => [
            'method'  => 'get',
            'url'     => '/merchant_risk_alerts/merchant/10000000000000/details',
        ],
        'response'    => [
            'content' => [],
        ],
        'status_code' => 200,
    ]
];

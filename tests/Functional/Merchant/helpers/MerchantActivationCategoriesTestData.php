<?php

namespace RZP\Tests\Functional\Merchant\helpers;

return [
    'testMerchantActivationCategoriesResponse' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/merchant/activation/business_category_details'
        ],
        'response' => [
            'content' => [ ],
            'status_code' => 200,
        ]
    ],
];

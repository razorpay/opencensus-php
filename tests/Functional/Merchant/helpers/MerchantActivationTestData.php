<?php

namespace RZP\Tests\Functional\Merchant\helpers;

return [
    'testMerchantActivationCategoriesResponse' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/merchant/activation/business_category_details'
        ],
        'response' => [
            'content' => array (
                'financial_services' =>
                    array (
                        'description' => 'Financial Services',
                        'subcategories' =>
                            array (
                                'mutual_fund' =>
                                    array (
                                        'category' => 6211,
                                        'description' => 'Mutual Fund',
                                        'category2' => 'Mutual funds',
                                        'activation_category' => 'greylist',
                                    ),
                                'lending' =>
                                    array (
                                        'category' => 6012,
                                        'description' => 'Lending',
                                        'category2' => 'Lending',
                                        'activation_category' => 'greylist',
                                    )
                    ))),
            'status_code' => 200
        ]
    ],
];

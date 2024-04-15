<?php

return [
    'testGetInternalAccountConfigForCheckout' => [
        'request' => [
            'url' => '/internal/account/config/checkout',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'name' => 'Tester 2',
                'brand_color' => '#123456',
                'logo_url' => '/logos/random_image_original.png',
                'display_name' => 'Tester Account 2',
                'fee_bearer' => 'platform',
                'billing_label' => 'Tester 2',
                'international' => false,
                'category' => '5945',
                'activated' => false,
                'country_code' => 'IN',
                'partnership_url' => 'https://dummycdn.razorpay.com/logos/partnership.png',
                'live' => false,
                'org_id' => '100000razorpay',
                'language_code' => 'en',
                'checkout_logo_size_image_url' => 'https://dummycdn.razorpay.com/logos/random_image_original_medium.png',
                'is_fee_bearer' => false,
                'brand_name' => 'Tester 2',
                'currency' => 'INR',
                'category_name' => 'ecommerce',
                'email' => 'test@gmail.com',
            ],
        ],
    ],
    'testGetInternalFetchConfig' => [
        'request' => [
            'url' => '/internal/account/config',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'brand_color' => '#123456',
                'logo_url' => '/logos/random_image_original.png',
                'display_name' => 'Tester Account 2',
                'invoice_logo'=>'/logos/random_image_url.png',
                'category' => '5945',
                'org_id' => 'NMt44KU8fXpHdB',
                'custom_org_branding' => true,
                'business_registered_address'=>'B-85 Bais godam industrial area Bangalore',
                'business_registered_address_text' => 'B-85 Bais godam industrial area Bangalore, Koramangala, Bangalore, India - 560029'
            ],
        ],
    ],
];

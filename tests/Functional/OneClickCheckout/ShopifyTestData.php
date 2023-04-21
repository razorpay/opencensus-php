<?php

return [
    'testCreateOrderAndGetPreferences' => [
        'request'  => [
            'content' => [
                'merchant_id'       => '10000000000000',
                'checkout'          => [
                    'id'           => 'Z2lkOi8vc2hvcGlmeS9DaGVja291dC8zYTEyNGMxMDM2MDJlNThiMjdkNzA1NTdmOTM4NmJhMz9rZXk9NWU5OWI5YTZiNzBiZjJiNDljOGRlNjgyN2RkY2VlZWY=',
                    "totalPrice"   => "400.00",
                    "totalPriceV2" => [
                        "amount"       => "400.0",
                        "currencyCode" => "INR",
                    ],
                    "lineItems" => [
                        "pageInfo" => [
                            "hasNextPage"     => false,
                            "hasPreviousPage" => false,
                        ],
                        "edges"    => [
                            [
                                "cursor" => "eyJsYXN0X2lkIjo0MTk4OTMxODMxMjEwMzAsImxhc3RfdmFsdWUiOjQxOTg5MzE4MzEyMTAzMH0=",
                                "node"   => [
                                    "id"                  => "Z2lkOi8vc2hvcGlmeS9DaGVja291dExpbmVJdGVtLzQxOTg5MzE4MzEyMTAzMD9jaGVja291dD0zYTEyNGMxMDM2MDJlNThiMjdkNzA1NTdmOTM4NmJhMw==",
                                    "title"               => "Product Variant - T Shirt",
                                    "quantity"            => 1,
                                    "variant"             => [
                                        "id"      => "Z2lkOi8vc2hvcGlmeS9Qcm9kdWN0VmFyaWFudC80MTk4OTMxODMxMjEwMw==",
                                        "weight"  => 0,
                                        "price"   => "400.00",
                                        "image"   => [
                                            "id"  => "Z2lkOi8vc2hvcGlmeS9Qcm9kdWN0SW1hZ2UvMzMyNTE0NzA2MzkyNzE=",
                                            "url" => "https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306",
                                        ],
                                        "sku"     => "P0006S",
                                        "title"   => "Black / Medium",
                                        "priceV2" => [
                                            "amount"       => "400.0",
                                            "currencyCode" => "INR",
                                        ],
                                        "product" => [
                                            "id"          => "Z2lkOi8vc2hvcGlmeS9Qcm9kdWN0LzcyNTQ5NTM0NTk4Nzk=",
                                            "handle"      => "product-variant-t-shirt",
                                            "title"       => "Product Variant - T Shirt",
                                            "description" => "",
                                        ],
                                    ],
                                    "customAttributes"    => [],
                                    "discountAllocations" => [],
                                ],
                            ],
                        ],
                    ],

                ],
                'cart'              => [
                    'token' => '219c69df571b5865e0e8a8c279da5305',
                    'items' => [
                        [
                            'id'                               => 41989318312103,
                            'properties'                       => [],
                            'quantity'                         => 1,
                            'variant_id'                       => 41989318312103,
                            'key'                              => '41989318312103=>b70dd2f13e37f70964db4d2e3975d2f2',
                            'title'                            => 'Product Variant - T Shirt - Black / Medium',
                            'price'                            => 40000,
                            'original_price'                   => 40000,
                            'discounted_price'                 => 40000,
                            'line_price'                       => 40000,
                            'original_line_price'              => 40000,
                            'total_discount'                   => 0,
                            'discounts'                        => [],
                            'sku'                              => 'P0006S',
                            'grams'                            => 0,
                            'vendor'                           => 'myprivatedevstore2',
                            'taxable'                          => true,
                            'product_id'                       => 7254953459879,
                            'product_has_only_default_variant' => false,
                            'gift_card'                        => false,
                            'final_price'                      => 40000,
                            'final_line_price'                 => 40000,
                            'url'                              => '/products/product-variant-t-shirt?variant=41989318312103',
                            'featured_image'                   => [
                                'aspect_ratio' => 0.75,
                                'alt'          => 'Product Variant - T Shirt',
                                'height'       => 1024,
                                'url'          => 'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
                                'width'        => 768,
                            ],
                            'image'                            => 'https://cdn.shopify.com/s/files/1/0601/4173/2007/products/Tshirt-Black.webp?v=1657629306',
                            'handle'                           => 'product-variant-t-shirt',
                            'requires_shipping'                => true,
                            'product_type'                     => '',
                            'product_title'                    => 'Product Variant - T Shirt',
                            'product_description'              => '',
                            'variant_title'                    => 'Black / Medium',
                            'variant_options'                  => [
                                'Black',
                                'Medium',
                            ],
                            'options_with_values'              => [
                                [
                                    'name'  => 'Color',
                                    'value' => 'Black',
                                ],
                                [
                                    'name'  => 'Size',
                                    'value' => 'Medium',
                                ],
                            ],
                            'line_level_discount_allocations'  => [],
                            'line_level_total_discount'        => 0,
                        ],
                    ],

                ],
                'preference_params' => [
                    'send_preferences' => true,
                    'currency'         => [
                        'INR',
                    ],
                ],
            ],
            'method'  => 'POST',
            'url'     => '/1cc/shopify/order',
        ],
        'response' => [
            'content' => [
                'preferences' => [
                    'order' => [
                        'amount' => 40000,
                        'currency' => "INR",
                    ],
                    'features' => [
                        'one_click_checkout' => true,
                    ]
                ],
            ],
        ],
    ],

    'testFetchMetaFieldsApi' => [
        'request'  => [
            'url'    => '/1cc/admin/merchants/10000000000000/shopify/metafields',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'meta_fields' => [
                    [
                        'id'    => 'gid://shopify/Metafield/28810825924884',
                        'key'   => 'test_key',
                        'value' => 'test_value',
                        'type'  => 'string',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateMetaFieldsApi' => [
        'request'  => [
            'url'     => '/1cc/admin/merchants/10000000000000/shopify/metafields',
            'method'  => 'POST',
            'content' => [
                'namespace'  => 'magic_checkout',
                'metafields' => [
                    'key'   => 'test_key',
                    'value' => 'test_value',
                    'type'  => 'string',
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'errors' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchShopifyStoreThemes' => [
        'request'  => [
            'url'    => '/1cc/admin/merchants/10000000000000/shopify/themes',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'shop_id' => 'random-shop',
                'themes'  => [
                    [
                        'id'                   => 138358325524,
                        'name'                 => 'Dawn',
                        'created_at'           => '2022-11-18T00:53:52+05:30',
                        'updated_at'           => '2023-04-14T23:48:41+05:30',
                        'role'                 => 'main',
                        'theme_store_id'       => 887,
                        'previewable'          => true,
                        'processing'           => false,
                        'admin_graphql_api_id' => 'gid://shopify/Theme/138358325524',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testInsertShopifySnippetApi' => [
        'request'  => [
            'url'     => '/1cc/admin/merchants/10000000000000/shopify/snippets/insert',
            'method'  => 'PUT',
            'content' => [
                'theme_id' => '140116427028',
                'asset'    => [
                    'key'   => 'snippets/razorpay-magic-test.liquid',
                    'value' => 'test_value',
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testRenderMagicSnippetApi' => [
        'request'  => [
            'url'     => '/1cc/admin/merchants/10000000000000/shopify/snippets/render',
            'method'  => 'PUT',
            'content' => [
                'theme_id' => '140116427028',
            ],
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],
];

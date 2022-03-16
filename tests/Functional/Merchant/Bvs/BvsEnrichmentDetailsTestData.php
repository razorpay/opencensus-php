<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

return [
    'testFetchEnrichmentDetails' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/enhancedactivation/10000000000000'
        ],
        'response' => [
            'content' => [
                    'llpin' => [
                        'validation_id' => 'IuZndF1UeIEHOP',
                        'enrichment_details_fields' => [
                            'online_provider' => [
                                'details' => [
                                    'account_holder_names' => [
                                        ['score' => 0, 'value' => 'name 1'],
                                        ['score' => 0, 'value' => 'name 2']
                                    ],
                                    'account_status'    => ['value' => 'active',]
                                ]
                            ]
                        ]
                    ],
                    'gstin' => [
                        'validation_id' => 'Ity9sdi70CJOJI',
                        'enrichment_details_fields' => [
                            'online_provider' => [
                                'details' => [
                                    'account_holder_names' => [
                                        ['score' => 0, 'value' => 'name 1'],
                                        ['score' => 0, 'value' => 'name 2']
                                    ],
                                    'account_status'       => ['value' => 'active',]
                                ]
                            ]
                        ]
                    ]
            ]
        ]
    ]
];


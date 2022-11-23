<?php

namespace RZP\Services\Mock;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Services\MasterOnboardingService as MOBService;

class MasterOnboardingService
{
    public function sendRequestAndParseResponse(string $path, string $method, array $data = [], bool $isAdmin = true, array $headers = [])
    {
        $path = (new MOBService)->getPathWithQueryString($method, $path, $data);

        $result = [];

        if (($path === 'intents/intent00000001/application/apply') and
            ($method === 'POST')) {
            $result = [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'application' =>
                        [
                            'id' => 'application001',
                        ],
                    'product_bundle' =>
                        [
                            'id' => 'prodBundle0001',
                            'name' => 'ca_cc_product_bundle',
                            'line_items' => [
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'ca',
                                    'ranking' => '1'
                                ],
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'cc',
                                    'ranking' => '2'
                                ]
                            ]
                        ]
                ],
            ];
        } else if (($path === 'applications/application001?merchant_id=10000000000000&service=x') and
                  ($method === 'GET')) {
            $result = [
                'application' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'obs_workflow_id' => 'obsWorkflowId1',
                    'status' => 'created',
                    'kyc_applications' => [
                        [
                            'id' => 'kycApp00000001',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'ca',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID001',
                            'metadata' => null
                        ],
                        [
                            'id' => 'kycApp00000002',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'cc',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID002',
                            'metadata' => null
                        ]
                    ]
                ]
            ];
        } else if (($path === 'applications?merchant_id=10000000000000&service=x&offset=0&limit=2') and
                  ($method === 'GET')) {
            $result = [
                'count' => 2,
                'entity' => 'application',
                'items' => [
                    [
                        'id' => 'application002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId2',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000003',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000004',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ],
                    [
                        'id' => 'application001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId1',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000001',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000002',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ]
                ]
            ];
        } else if (($path === 'intents') and
                   ($method === 'POST')) {
            $result = [
                'intent' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => []
                ]
            ];
        } else if (($path === 'intents/intent00000001?merchant_id=10000000000000&service=x') and
                  ($method === 'GET')) {
            $result = [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' =>'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => [
                        'id' => 'prodBundle0001',
                        'name' => 'ca_cc_product_bundle',
                        'line_items' => [
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'ca',
                                'ranking' => 1
                            ],
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'cc',
                                'ranking' => 2
                            ]
                        ]
                    ]
                ]
            ];
        } else if (($path === 'intents?merchant_id=10000000000000&service=x&limit=2&offset=0') and
                   ($method === 'GET')) {
            $result = [
                'count' => 2,
                'entity' => 'Intent',
                'items' => [
                    [
                        'id' => 'intent00000002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ],
                    [
                        'id' => 'intent00000001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ]
                ]
            ];
        } else if (($path === 'get_workflow/IfyrSDmvEmnA0N') and
            ($method === 'GET')) {
            $result = [
              'id' => 'IfyrSDmvEmnA0N'
            ];
        } else if (($path === 'save_workflow') and
            ($method === 'POST')) {
            $result = [
                'id' => 'IfyrSDmvEmnA0N'
            ];
        }

        return $result;
    }

    public function mobMigration(array $input): array
    {
        return $input;
    }
}

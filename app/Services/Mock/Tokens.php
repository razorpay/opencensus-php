<?php

namespace RZP\Services\Mock;

use RZP\Exception;
use RZP\Services\Tokens as BaseTokens;

class Tokens extends BaseTokens {
    public function fetchTokensInternal($input)
    {
        if (isset($input['token']) && $input['token'] == '101externaltok')
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'input'         => array_keys($input),
                ]);
        }

        return [
            'code'     => 200,
            'body'     => [
                'data' => [
                    [
                        'id'                        => !empty($input['id']) ? substr($input['id'], -14) : 'abcdeferhthjkt',
                        'token'                     => $input['token'] ?? 'token000000001',
                        'method'                    => $input['method'] ?? 'upi',
                        'merchant_id'               => '10000merchant1',
                        'customer_id'               => $input['cutomer_id'] ?? 'dummy_customer',
                        'namespace'                 => 'upi',
                        'terminal_id'               => 'term1234567890',
                        'expired_at'                => 1234567890,
                        'created_at'                => 1234567890,
                        'updated_at'                => 1234567890,
                        'entity_id'                 => 'upiEntityId001',
                        'status'                    => 'created',
                        'notes'                     => '{"emandate_configs":{"cooldown_period":1707881400,"retry_attempts":1,"last_updated_month":"Feb","last_updated_on":"2024-02-11 13:39:34"}}',
                        'error_code'                => 'ERROR_CODE',
                        'error_description'         => 'no_error_present',
                        'vpa'                       => ["username" => "api_user", "handle" => "api@okhdfc"]
                    ],
                ],
            ],
        ];
    }

    public function fetchTokenByIdsInternal($input)
    {
        return [
            'code'     => 200,
            'body'     => [
                'data' => [
                    [
                        'id'                        => $input['id'][0] ?? 'abcdeferhthjkt',
                        'token'                     => 'token000000001',
                        'merchant_id'               => 'testMerchant01',
                        'customer_id'               => $input['customer_id'] ?? 'testCustomer01',
                        'method'                    => 'upi',
                        'created_at'                => 1708240419,
                        'updated_at'                => 1708240419,
                        'expired_at'                => 1707673744,
                    ],
                ],
            ],
        ];
    }

    public function fetchCustomerTokensInternal($input)
    {
        if (isset($input['token']) && $input['token'] == '101externaltok')
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'input'         => array_keys($input),
                ]);
        }

        return [
            'code'     => 200,
            'body'     =>
                [
                    'data' =>
                        [
                            [
                                'token'                     => '100001upitoken',
                                'id'                        => 'random10000000',
                                'merchant_id'               => '10000000000000',
                                'method'                    => 'upi',
                                'namespace'                 => 'upi',
                                'entity_id'                 => 'upiEntityId001',
                                'vpa'                       => ["username" => "api_user", "handle" => "oksbi", "name" => "satyanand prasad", "status" => "valid", "received_at" => 1709615226]
                            ],
                        ],
                ]
        ];
    }

}

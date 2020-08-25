<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class IntentData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function wallet_phonepe($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => 'SUCCESS',
                    'status' => 'authorization_successful',
                    '_raw' => '{\"success\":true,\"received\":true,\"message\":\"Your request has been successfully completed.\",\"code\":\"SUCCESS\",\"type\":\"intent\"}',
                    'received' => true,
                    'success' => true,
                    'message' => 'Your request has been successfully completed.',
                    'type' => 'intent'
                ],
            'next' => [
                'redirect' => [
                    'content' => [],
                    'method' => 'post',
                    'url' => 'upi://pay?test=test1',
                ]
            ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [],
            'next' => [
                'redirect' => [
                    'content' => [],
                    'method' => 'post',
                    'url' => 'upi://pay?test=test1',
                ]
            ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}

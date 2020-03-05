<?php

use RZP\Error\ErrorCode;

return

[
    'testFailureTypeformWebhookConsumptionSecurity' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/typeform/webhook_consumption',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied'
                ],
            ],
            'status_code' => 400,
        ],
    ],
    'testSuccessTypeformWebhookConsumptionSecurity' => [
        'request'     => [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' =>  'sha256=NWZhZjg3ZWQ5MWU1YjgxODJhNWY3ZDQyM2IwNWUyZjk1MjVmYjI2NWM4ZDkzYzA2NDI0ZTQ5YjJjMTNiMmIwNg=='
            ],
            'method'  => 'POST',
            'url'     => '/typeform/webhook_consumption',
            'content' => [
            ],
        ],
        'response'    => [
            'content' => [
                'authorization' => 'cleared'
            ],
        ],
        'status_code' => 200,
    ],


];

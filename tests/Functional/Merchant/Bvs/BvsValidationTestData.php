<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

return [
    'testCreateBvsValidationPoi' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'promoter_pan_name' => 'Test123',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],
];

<?php

namespace RZP\Tests\Functional\QrPayment;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code'
        ],
    ],
];

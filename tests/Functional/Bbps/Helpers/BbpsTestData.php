<?php

namespace RZP\Tests\Functional\Bbps;

return [

    'testFetchBbpsIframeUrl' => [
        'request'  => [
            'url'     => '/bbps_bill_payments',
            'method'  => 'get',
            'content' => [],
        ],

        'response' => [
            'content' => [
                'iframe_embed_url' => 'https://www.wikipedia.org',
            ],
        ],
    ]
];

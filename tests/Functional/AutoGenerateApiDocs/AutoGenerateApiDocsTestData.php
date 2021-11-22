<?php

namespace RZP\Tests\Functional\AutoGenerateApiDocs;

return [

    'testAutoGeneratesApiDocs' => [
        'request'  => [
            'url'     => '/bbps_bill_payments',
            'method'  => 'get',
            'content' => [],
            'headers' => ['auto-generate-api-docs-dir' => './_docstest/' ]
        ],

        'response' => [
            'content' => [
                'iframe_embed_url' => 'https://www.wikipedia.org',
            ],
        ],
    ]
];

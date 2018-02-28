<?php

namespace RZP\Tests\Unit\Request\Helpers;

return [

    // Set of sample settings
    'settings' => [
        [
            [],
            [],
        ],
    ],

    //
    // Per available request case, lists:
    // - what id settings key is expected to be used
    // - what throttle key is expected to be used
    // - what throttle values is expected to be used, per settings available(above ^)
    //

    'publicRouteWhenKeyInHeaders' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_get_status:test:public:0::10000000000000:1.1.1.1',
        'settings' => [
            [2, 1, 30],
        ],
    ],

    'privateRoute' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_fetch_multiple:test:private:0::10000000000000:',
        'settings' => [
            [2, 1, 30],
        ],
    ],
];

<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
     'testGetIntegrationUrlServiceMethod'  => [
            'request'  => [
                'method' => 'GET',
                'url'    => '/accounting-payouts/integration/quickbooks',
            ],
            'response' => [
                'content' => []
            ]
     ],

     'testInitiateIntegrationServiceMethod'  => [
            'request'  => [
                'method' => 'POST',
                'url'    => '/accounting-payouts/integration/quickbooks',
            ],
            'response' => [
                'content' => []
            ]
    ],

     'testIntegrationStatusServiceMethod'  => [
            'request'  => [
                'method' => 'GET',
                'url'    => '/accounting-payouts/integration/status',
            ],
            'response' => [
                'content' => []
            ]
     ],

     'testIntegrationStatusAppServiceMethod'  => [
             'request'  => [
                 'method' => 'GET',
                 'url'    => '/accounting-payouts/integration/quickbooks/status',
             ],
             'response' => [
                 'content' => []
             ]
     ],

    'testCallbackServiceMethod'  => [
            'request'  => [
                'method' => 'GET',
                'url'    => '/accounting-payouts/callback',
            ],
            'response' => [
                'content' => []
            ]
        ],

    'testAppCredentialsServiceMethod'  => [
           'request'  => [
               'method' => 'POST',
               'url'    => '/accounting-payouts/appcredentials/quickbooks',
           ],
           'response' => [
               'content' => []
           ]
    ],

     'testDeleteServiceMethod'  => [
             'request'  => [
                 'method' => 'POST',
                 'url'    => '/accounting-payouts/delete/quickbooks',
             ],
             'response' => [
                 'content' => []
             ]
     ],

    'testSyncStatusServiceMethod'  => [
           'request'  => [
               'method' => 'GET',
               'url'    => '/accounting-payouts/sync/quickbooks',
           ],
           'response' => [
               'content' => []
           ]
    ],

    'testSyncServiceMethod'  => [
         'request'  => [
             'method' => 'POST',
             'url'    => '/accounting-payouts/sync/quickbooks',
         ],
         'response' => [
             'content' => []
         ]
    ],

    'testWaitlistServiceMethod'  => [
         'request'  => [
             'method' => 'POST',
             'url'    => '/accounting-payouts/waitlist/quickbooks',
         ],
         'response' => [
             'content' => []
         ]
    ],

    'testIntegrationStatusForViewOnlyUsers'  => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/status',
         ],
        'response' => [
            'content' => []
         ]
    ],

    'testSyncStatusForViewOnlyUsers'  => [
         'request'  => [
           'method' => 'GET',
           'url'    => '/accounting-payouts/sync/quickbooks',
         ],
         'response' => [
           'content' => []
         ]
    ],
];

<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testTruecallerCallbackWithValidData' => [
        'request' => [
            'successContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
            	'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
            'userRejectedContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'user_rejected',
            ],
            'usedAnotherNumberContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'use_another_number',
            ],
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testTruecallerCallbackWithInvalidData' => [
        'request' => [
            'contentWithoutRequestId' => [],
            'contentWithoutAccessToken' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
            ],
            'contentWithoutEndpoint' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
            ],
        ],
    ],

    'testVerifyTruecallerRequestWithInvalidData' => [
        'request' => [
            'contentWithoutRequestId' => [],
            'contentWithInvalidRequestId' => [
                'request_id' => 'invalid_request_example'
            ]
        ]
    ],

    'testVerifyTruecallerRequestForSucessResponse' => [
        'request' => [
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ]
    ],

    'testVerifyTruecallerRequestForRejectedResponse' => [
        'request' => [
            'userRejectedContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'user_rejected'
            ],
            'usedAnotherNumberContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'use_another_number'
            ],
        ]
    ],

    'testVerifyTruecallerRequestForErrorResponse' => [
        'request' => [
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ]
    ],

    'testCreateTruecallerAuthRequestInternal' => [
        'request' => [
            'url'       => '/internal/customers/truecaller/auth',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'status' => 'active',
                'truecaller_status' => null,
                'context' => '10000000000000',
                'service' => 'checkout',
            ],
        ]
    ]
];

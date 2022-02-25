<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testListVendorInvoices' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetVendorInvoice' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/invoices/invoice1',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testListTdsCategories' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/tds-categories',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetInvoiceSignedUrl' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/invoice-signed-url/file1',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testListVendorPortalInvites' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invites',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreate' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testUploadInvoice' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/upload-invoice',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetOcrData' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/get-ocr-data/ocr1',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorFetchUser' => [
        'request' => [
            'url'    => '/users/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner',
                    ],
                ],
                'invitations'             => [
                ],
                'settings'                => [
                ],
            ],
        ],
    ],

    'testVendorEditUser' => [
        'request' => [
            'url'     => '/users',
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'name'           => 'Updated Name',
                'contact_mobile' => '123456789',
            ],
        ],
        'response' => [
            'content' => [
                'name'           => 'Updated Name',
                'contact_mobile' => '123456789',
            ],
        ],
    ],

    'testGetVendorPreferences' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/preferences',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testUpdateVendorPreferences' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => 'VendPortalUser',
            ],
            'url'    => '/vendor-portal/invite/invite1/preferences',
        ],
        'response' => [
            'content' => []
        ]
    ],
];

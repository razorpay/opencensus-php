<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateOptionsForMerchant' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => null,
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Test first amount"}}}'
            ],
        ],
    ],

    'testCreateOptionsForMerchantWithReferenceId' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => 'Ddl2pnoDXM59rH',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => 'Ddl2pnoDXM59rH',
                'scope'           => 'entity',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Test first amount"}}}'
            ],
        ],
    ],

    'testCreateOptionsForMerchantWithDefaultNamespaceAndServiceType' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => null,
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Test first amount"}}}'
            ],
        ],
    ],

    'testDuplicateCreateOptionsForMerchant' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Entry with field namespace=payment_links already exists for merchant. You may want to update or delete existing'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDuplicateCreateOptionsForServiceAndReferenceId' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => 'Ddl2pnoDXM59rH',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Entry with fields namespace=payment_links and service_type=invoices and reference_id=Ddl2pnoDXM59rH already exists for merchant.'.
                                            'You may want to update or delete existing'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNoOptionsSentOnCreate' => [
        'request'  => [
            'url'     => '/options',
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'The options field is required.'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsFetchById1' => [
        'request'  => [
            'url'     => '/options/opt_Ddl2qTGP3uHL2O',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Some first amount"}}}'
            ]
        ]
    ],

    'testOptionsFetchById2' => [
        'request'  => [
            'url'     => '/options/opt_Ddl2qTGP3uHL2O',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => 'Ddl2pnoDXM59rH',
                'scope'           => 'entity',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Some first amount"}}}'
            ]
        ]
    ],

    'testOptionsFetchByNamespaceAndService' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'defaultOptions'   => [],
                'merchantOptions'  => [],
                'serviceOptions'   => [],
                'mergedOptions'    => []
            ]
        ]
    ],

    'testOptionsFetchByInvalidNamespace' => [
        'request'  => [
            'url'     => '/options/some_wrong_name/invoices',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Namespace some_wrong_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsFetchByInvalidService' => [
        'request'  => [
            'url'     => '/options/payment_links/some_wrong_name',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Service some_wrong_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsDeleteByIdSuccess' => [
        'request'  => [
            'url'     => '/options/opt_Ddl2qTGP3uHL2O',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'id'         => 'opt_Ddl2qTGP3uHL2O',
                'deleted'    => true
            ]
        ]
    ],

    'testOptionsDeleteByIdFailure' => [
        'request'  => [
            'url'     => '/options/opt_Ddl2qTGP3uHL2O',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'The id provided does not exist'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testOptionsPatchById' => [
        'request'  => [
            'url'     => '/options/opt_Ddl2qTGP3uHL2O',
            'method'  => 'patch',
            'content' => [
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Modify this field value'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Modify this field value"}}}'
            ]
        ]
    ],

    'testCreateOptionsForMerchantAdmin' => [
        'request'  => [
            'url'     => '/options/100DemoAccount',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'reference_id'    => null,
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Test first amount"}}}',
                'merchant_id'     => '100DemoAccount'
            ],
        ],
    ],

    'testOptionsPatchAdmin' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/100DemoAccount',
            'method'  => 'patch',
            'content' => [
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Modify this field value'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'scope'           => 'global',
                'options_json'    => '{"checkout":{"label":{"min_amount":"Modify this field value"}}}',
                'merchant_id'     => '100DemoAccount'
            ]
        ]
    ],

    'testOptionsFetchByAdmin' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/100DemoAccount',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'defaultOptions'   => [],
                'merchantOptions'  => [],
                'serviceOptions'   => [],
                'mergedOptions'    => []
            ]
        ]
    ],

    'testOptionsFetchByAdminFailure1' => [
        'request'  => [
            'url'     => '/options/wrong_namespace_name/invoices/100DemoAccount',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Namespace wrong_namespace_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsFetchByAdminFailure2' => [
        'request'  => [
            'url'     => '/options/payment_links/wrong_service_name/100DemoAccount',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Service wrong_service_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsFetchByAdminFailure3' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/wrong_merchant_name',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'error'           => [
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testOptionsDeleteSuccessAdmin' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/100DemoAccount',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'id'         => 'opt_Ddl2qTGP3uHL2O',
                'deleted'    => true
            ]
        ]
    ],

    'testOptionsDeleteByAdminFailure1' => [
        'request'  => [
            'url'     => '/options/wrong_namespace_name/invoices/100DemoAccount',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Namespace wrong_namespace_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsDeleteByAdminFailure2' => [
        'request'  => [
            'url'     => '/options/payment_links/wrong_service_name/100DemoAccount',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Service wrong_service_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsDeleteByAdminFailure3' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/wrong_merchant_name',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'error'           => [
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],


    'testOptionsUpdateByAdminFailure1' => [
        'request'  => [
            'url'     => '/options/wrong_namespace_name/invoices/100DemoAccount',
            'method'  => 'patch'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Namespace wrong_namespace_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsUpdateByAdminFailure2' => [
        'request'  => [
            'url'     => '/options/payment_links/wrong_service_name/100DemoAccount',
            'method'  => 'patch'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Service wrong_service_name is not valid'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsUpdateByAdminFailure3' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/wrong_merchant_name',
            'method'  => 'patch'
        ],
        'response' => [
            'content' => [
                'error'           => [
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testOptionsUpdateByAdminForMissingEntityFailure' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/100DemoAccount',
            'method'  => 'patch'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'No entity with namespace=payment_links, service=invoices and merchant=100DemoAccount found'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOptionsDeleteByAdminForMissingEntityFailure' => [
        'request'  => [
            'url'     => '/options/payment_links/invoices/100DemoAccount',
            'method'  => 'delete'
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'No entity with namespace=payment_links, service=invoices and merchant=100DemoAccount found'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDuplicateCreateOptionsForMerchantAdmin' => [
        'request'  => [
            'url'     => '/options/100DemoAccount',
            'method'  => 'post',
            'content' => [
                'namespace'       => 'payment_links',
                'service_type'    => 'invoices',
                'options'         => [
                    'checkout'    => [
                        'label'   => [
                            'min_amount'  => 'Test first amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error'           => [
                    'code'        		=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Entry with field namespace=payment_links already exists for merchant. You may want to update or delete existing'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];

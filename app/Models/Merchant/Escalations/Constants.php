<?php


namespace RZP\Models\Merchant\Escalations;


use phpDocumentor\Reflection\Types\Self_;
use RZP\Models\Merchant\Detail\Status;
use RZP\Notifications\Onboarding\Events;

use RZP\Models\Merchant\Detail\Entity as DEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Constants as DConstants;
use RZP\Models\Merchant\Account\Constants as AccountConstants;
use RZP\Models\Merchant\Escalations\Actions\Handlers\EscalationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\FundsOnHoldHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\MerchantTagsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\CommunicationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\NoDocLimitWarnHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\DisablePaymentsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\NoDocLimitHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\InstantActivationV2ApiLimitWarnHandler;

class Constants
{
    const SIGNUP_ATTRIBUTION_CRON_CACHE_KEY                 = 'signup_attribution_cron_timestamp';
    const WEB_ATTRIBUTION_FIRST_TOUCH_CRON_CACHE_KEY        = 'web_attribution_first_touch_cron_timestamp';
    const WEB_ATTRIBUTION_CRON_CACHE_KEY                    = 'web_attribution_cron_timestamp';
    const TRANSACTION_CRON_CACHE_KEY                        = 'onboarding_transaction_cron_timestamp';
    const ESCALATION_CACHE_KEY                              = 'onboarding_escalation_timestamp';
    const SEGMENT_MTU_CACHE_KEY                             = 'onboarding_segment_mtu_timestamp';
    const NO_DOC_ESCALATION_CACHE_KEY                       = 'no_doc_onboarding_escalation_timestamp';
    const BANKING_ORG_ESCALATION_CACHE_KEY                  = 'banking_org_onboarding_escalation_timestamp';
    const START_TIME                                        = 'start_time';
    const END_TIME                                          = 'end_time';
    // request payload constants
    const TIME_BOUND = 'time_bound';

    const TIME_BOUND_THRESHOLD = 3; // in months

    // Escalation triggered to
    const MERCHANT = 'merchant';
    const ADMIN    = 'admin';

    const TO            = 'to';
    const CONDITIONS    = 'conditions';
    const ACTIONS       = 'actions';
    const ENABLE_ACTION = 'enable_action';
    const DESCRIPTION   = 'description';
    const HANDLER       = 'handler';
    const PARAMS        = 'params';
    const MILESTONE     = 'milestone';
    const ENABLE        = 'enable';
    const CURRENT_GMV   = 'current_gmv';
    const SOFT_LIMIT_LEVEL_1        = 'soft_limit_level_1';
    const HARD_LIMIT_LEVEL_1        = 'hard_limit_level_1';
    const CMMA_SOFT_LIMIT_BREACH    = 'SOFT-LIMIT-BREACH-LV1';
    const CMMA_HARD_LIMIT_BREACH    = 'HARD-LIMIT-BREACH-LV1';
    const AUTO_KYC_FAILURE_TRIGGER  = 'AUTO-KYC-FAILURE';
    const AMP           = "AMP";
    const ACTIVATION    = 'activation';
    const CMMA_ROUTE    = 'twirp/rzp.cmma.process.v1.ProcessManagementService/CreateProcessInstance';
    const UNDEFINED     = 'undefined';

    //Escalation Types
    const PAYMENT_BREACH    = 'payment_breach';
    const SETTLEMENT_BREACH = 'settlement_breach';

    const PAYMENTS_ESCALATION = 'payments_escalation';
    const NO_DOC_PAYMENTS_ESCALATION = 'no_doc_payments_escalation';
    const BANKING_ORG_PAYMENTS_ESCALATION = 'banking_org_payments_escalation';

    const KEY = 'key';
    const INTERVAL = 'interval';
    const ALLOWED_OPEN_STATUSES = 'allowed_open_statuses';

    const escalationsParamsMap = [
        self::PAYMENTS_ESCALATION => [
            self::KEY                       => self::ESCALATION_CACHE_KEY,
            self::ALLOWED_OPEN_STATUSES     => Status::MERCHANT_OPEN_STATUSES,
            self::INTERVAL                  => 300
        ],
        self::NO_DOC_PAYMENTS_ESCALATION => [
            self::KEY                       => self::NO_DOC_ESCALATION_CACHE_KEY,
            self::ALLOWED_OPEN_STATUSES     => Status::MERCHANT_NO_DOC_OPEN_STATUSES,
            self::INTERVAL                  => 1800
        ],
        self::BANKING_ORG_PAYMENTS_ESCALATION => [
            self::KEY                       => self::BANKING_ORG_ESCALATION_CACHE_KEY,
            self::ALLOWED_OPEN_STATUSES     => Status::MERCHANT_OPEN_STATUSES,
            self::INTERVAL                  => 300
        ],
    ];

    // Retention Period on Pinot
    const RETENTION_PERIOD = 10; // in days

    const HYBRID_DATA_QUERYING = 'hybrid_data_querying';

    const HYBRID_DATA_QUERYING_SPLITZ_EXPERIMENT_ID = 'hybrid_data_querying_splitz_experiment_id';

    // This is required to optimise DB query. We ignore all merchants with GMV below this threshold
    const LOWEST_PAYMENTS_THRESHOLD = 00; // in paisa
    const IS_NOT_NULL               = 'is not null';
    const IS_NULL                   = 'is null';
    const TRUE                      = 'true';
    const OPEN_STATUS_CONDITION     = [
        DEntity::ACTIVATION_STATUS => Status::MERCHANT_OPEN_STATUSES
    ];

    // hard limit for sub-merchant no-doc onboarding
    const HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY    = 5000000;  // 50 k
    const HARD_LIMIT_KYC_PENDING_THRESHOLD_3_WAY    = 50000000; // 5 lakhs
    const NO_DOC_P90_GMV                            = 'no_doc_p90_gmv';
    const NO_DOC_P91_GMV                            = 'no_doc_p91_gmv';
    const HARD_LIMIT_NO_DOC                         = 'hard_limit_no_doc';

    // keys
    const CMMA_PROCESS_ID_KEY = 'app.cmma_escalation_process_id';
    const CMMA_NEW_PROCESS_ID_KEY = 'app.cmma_escalation_new_process_id';
    const CMMA_EXPERIMENT_ID_KEY = 'app.cmma_limit_breach_trigger_experiment_id';
    const CMMA_SOFT_LIMIT_EXPERIMENT_ID = 'app.cmma_soft_limit_breach_trigger_experiment_id';
    const CMMA_AMP_EXPERIMENT_ID = 'app.cmma_amp_trigger_experiment_id';
    const CMMA_NEW_EXPERIMENT_ID_KEY = 'app.cmma_limit_breach_trigger_new_experiment_id';
    const CMMA_AUTO_KYC_FAILURE_EXPERIMENT_ID = 'app.cmma_auto_kyc_failure_trigger_experiment_id';

    const TAG          = 'tag';

    const SOFT_LIMIT_IA_V2              = 'soft_limit_ia_v2';
    const HARD_LIMIT_IA_V2              = 'hard_limit_ia_v2';

    const DEFAULT_ESCALATION_TRANSACTION_LIMIT_FOR_KYC_PENDING = 5000000;
    const THRESHOLD_BEFORE_TRANSACTION_LIMIT_FOR_KYC_PENDING = 1500000;
    const THRESHOLD_AFTER_TRANSACTION_LIMIT_FOR_KYC_PENDING = 1000000000;
    const ASSIGN_CUSTOM_HARD_LIMIT = "assign_custom_hardlimit";
    const CUSTOM_TRANSACTION_LIMIT_FOR_KYC_PENDING = "custom_transaction_limit_for_kyc_pending";
    const NAME = 'name';
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID = 'entity_id';
    const ORG_ID = "org_id";

    const PAYMENTS_ESCALATION_MATRIX = [
        0          => [
            [
                self::DESCRIPTION => "transacted after L1",
                self::TO          => self::ADMIN,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => MerchantTagsHandler::class
                    ]
                ],
            ]
        ],
        100000     => [
            [
                self::DESCRIPTION => "soft limit breach on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS                  => Status::ACTIVATED_MCC_PENDING,
                    DEntity::BUSINESS_WEBSITE                   => self::IS_NOT_NULL,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'soft_limit_level_1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
        ],
        500000     => [
            [
                self::DESCRIPTION => "payments breach of 5k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        1000000    => [
            [
                self::DESCRIPTION => "payments breach of 10k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        1500000    => [
            [
                self::DESCRIPTION => "payments breach of 15k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED
                        ]
                    ],
                    [
                        self::HANDLER => DisablePaymentsHandler::class,
                    ]
                ]
            ],
            [
                self::DESCRIPTION => "hard limit breach on activated mcc pending",
                self::TO          => self::ADMIN,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES
                ],
                self::MILESTONE   => 'hard_limit_level_1',
                self::ACTIONS     => [],  // This is getting escalated from v1 so disabling actions from here.
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "1 days after hard limit breach on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES,
                ],
                self::MILESTONE   => 'hard_limit_level_2',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "funds on hold on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES,
                ],
                self::MILESTONE   => 'hard_limit_level_4',
                self::ACTIONS     => [],
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "reminder funds on hold for activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::NEEDS_CLARIFICATION,
                ],
                self::MILESTONE   => 'funds_on_hold_reminder',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::FUNDS_ON_HOLD_REMINDER
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
        ],
        5000000 => [
            [
                self::DESCRIPTION => "funds on hold on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::MERCHANT_L2_OPEN_STATUSES,
                    'action_state'              => Status::ACTIVATED_MCC_PENDING
                ],
                self::MILESTONE   => 'hard_limit_level_4',
                self::ACTIONS     => [
                    [
                        self::HANDLER => FundsOnHoldHandler::class,
                    ],
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::FUNDS_ON_HOLD
                        ]
                    ],
                    [
                        self::HANDLER => EscalationHandler::class,
                    ]
                ],
            ]
        ],
        1000000000 => [
            [
                self::DESCRIPTION => "payments breach of 1cr after L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L2_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L2',
                self::ACTIONS     => [
                    [
                        self::HANDLER => DisablePaymentsHandler::class,
                    ]
                ]
            ]
        ]
    ];

    const BANKING_ORG_PAYMENTS_ESCALATION_MATRIX = [
        0          => [
            [
                self::DESCRIPTION => "transacted after L1",
                self::TO          => self::ADMIN,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => MerchantTagsHandler::class
                    ]
                ],
            ]
        ],
        100000     => [
            [
                self::DESCRIPTION => "soft limit breach on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS                  => Status::ACTIVATED_MCC_PENDING,
                    DEntity::BUSINESS_WEBSITE                   => self::IS_NOT_NULL,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'soft_limit_level_1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
        ],
        500000     => [
            [
                self::DESCRIPTION => "payments breach of 5k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        1000000    => [
            [
                self::DESCRIPTION => "payments breach of 10k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        self::THRESHOLD_BEFORE_TRANSACTION_LIMIT_FOR_KYC_PENDING    => [
            [
                self::DESCRIPTION => "payments breach of 15k after L1, before L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L1',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED
                        ]
                    ],
                    [
                        self::HANDLER => DisablePaymentsHandler::class,
                    ]
                ]
            ],
            [
                self::DESCRIPTION => "hard limit breach on activated mcc pending",
                self::TO          => self::ADMIN,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES
                ],
                self::MILESTONE   => 'hard_limit_level_1',
                self::ACTIONS     => [],  // This is getting escalated from v1 so disabling actions from here.
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "1 days after hard limit breach on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES,
                ],
                self::MILESTONE   => 'hard_limit_level_2',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "funds on hold on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::MERCHANT_L2_OPEN_STATUSES,
                ],
                self::MILESTONE   => 'hard_limit_level_4',
                self::ACTIONS     => [],
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "reminder funds on hold for activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::NEEDS_CLARIFICATION,
                ],
                self::MILESTONE   => 'funds_on_hold_reminder',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::FUNDS_ON_HOLD_REMINDER
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
        ],
        self::DEFAULT_ESCALATION_TRANSACTION_LIMIT_FOR_KYC_PENDING => [
            [
                self::DESCRIPTION => "funds on hold on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::MERCHANT_L2_OPEN_STATUSES,
                    'action_state'              => Status::ACTIVATED_MCC_PENDING
                ],
                self::MILESTONE   => 'hard_limit_level_4',
                self::ACTIONS     => [
                    [
                        self::HANDLER => FundsOnHoldHandler::class,
                    ],
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::FUNDS_ON_HOLD
                        ]
                    ],
                    [
                        self::HANDLER => EscalationHandler::class,
                    ]
                ],
            ]
        ],
        self::THRESHOLD_AFTER_TRANSACTION_LIMIT_FOR_KYC_PENDING => [
            [
                self::DESCRIPTION => "payments breach of 1cr after L2",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE          => DConstants::L2_SUBMISSION,
                    DEntity::ACTIVATION_STATUS                  => Status::MERCHANT_OPEN_STATUSES,
                    FeatureConstants::DISABLED_FEATURE          => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => 'L2',
                self::ACTIONS     => [
                    [
                        self::HANDLER => DisablePaymentsHandler::class,
                    ]
                ]
            ]
        ]
    ];
    /**
     * This config is based on milestone as key instead of threshold, since for no_doc onboarded merchants the gmv limit threshold can vary.
     * The config format here is similar to the payment escalation matrix config as given above
     */
    const NO_DOC_PAYMENTS_ESCALATION_MATRIX = [
        self::NO_DOC_P90_GMV => [
            [
                self::DESCRIPTION => "gmv limit 90% reached warning for no-doc onboarded merchant",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::MERCHANT_NO_DOC_OPEN_STATUSES,
                    FeatureConstants::FEATURE   => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => self::NO_DOC_P90_GMV,
                self::ACTIONS     => [
                    [
                        self::HANDLER   => NoDocLimitWarnHandler::class,
                        self::PARAMS    => [
                            self::MILESTONE   => self::NO_DOC_P90_GMV,
                            Entity::THRESHOLD => null
                        ]
                    ]
                ],
                self::ENABLE    => false
            ]
        ],
        self::NO_DOC_P91_GMV => [
            [
                self::DESCRIPTION => "gmv limit 91% reached warning for no-doc onboarded merchant",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::MERCHANT_NO_DOC_OPEN_STATUSES,
                    FeatureConstants::FEATURE   => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => self::NO_DOC_P91_GMV,
                self::ACTIONS     => [
                    [
                        self::HANDLER   => NoDocLimitWarnHandler::class,
                        self::PARAMS    => [
                            self::MILESTONE   => self::NO_DOC_P91_GMV,
                            Entity::THRESHOLD => null
                        ]
                    ]
                ],
                self::ENABLE    => false
            ]
        ],
        self::HARD_LIMIT_NO_DOC => [
            [
                self::DESCRIPTION => "gmv limit breach for no-doc onboarded merchant",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::MERCHANT_NO_DOC_OPEN_STATUSES,
                    FeatureConstants::FEATURE   => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => self::HARD_LIMIT_NO_DOC,
                self::ACTIONS     => [
                    [
                        self::HANDLER   => NoDocLimitHandler::class,
                        self::PARAMS    => [
                            self::MILESTONE   => self::HARD_LIMIT_NO_DOC,
                            Entity::THRESHOLD => null
                        ]
                    ]
                ],
                self::ENABLE    => false
            ]
        ]
    ];

    const SOFT_LIMIT_IA_V2_API = [
        [
            self::DESCRIPTION   => 'soft limit breach on instant activation',
            self::TO            => self::MERCHANT,
            self::CONDITIONS    => [
                DEntity::ACTIVATION_STATUS => Status::INSTANTLY_ACTIVATED,
                self::TAG                  => 'Instant_activation_subm'
            ],
            self::MILESTONE => self::SOFT_LIMIT_IA_V2,
            self::ACTIONS       => [
                [
                    self::HANDLER => InstantActivationV2ApiLimitWarnHandler::class,
                    self::PARAMS  => [
                        self::MILESTONE => self::SOFT_LIMIT_IA_V2
                    ]
                ]
            ]
        ]
    ];

    const HARD_LIMIT_IA_V2_API = [
        [
            self::DESCRIPTION   => '15k limit breach on instant activation',
            self::TO            => self::MERCHANT,
            self::CONDITIONS    => [
                DEntity::ACTIVATION_STATUS => Status::INSTANTLY_ACTIVATED,
                self::TAG                  => 'Instant_activation_subm'
            ],
            self::MILESTONE     => self::HARD_LIMIT_IA_V2,
            self::ACTIONS       => [
                [
                    self::HANDLER   => InstantActivationV2ApiLimitWarnHandler::class,
                    self::PARAMS    => [
                        self::MILESTONE => self::HARD_LIMIT_IA_V2
                    ]
                ]
            ]
        ]
    ];

    const INSTANT_ACTIVATION_V2_API_ESCALATION_MATRIX = [
        500000      => self::SOFT_LIMIT_IA_V2_API,
        1000000     => self::SOFT_LIMIT_IA_V2_API,
        1500000     => self::HARD_LIMIT_IA_V2_API,
    ];

}

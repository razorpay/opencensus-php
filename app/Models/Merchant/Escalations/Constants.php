<?php


namespace RZP\Models\Merchant\Escalations;


use RZP\Models\Merchant\Detail\Status;
use RZP\Notifications\Onboarding\Events;

use RZP\Models\Merchant\Detail\Entity as DEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Constants as DConstants;
use RZP\Models\Merchant\Escalations\Actions\Handlers\EscalationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\FundsOnHoldHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\MerchantTagsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\CommunicationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\DisablePaymentsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\NoDocLimitHandler;

class Constants
{
    const WEB_ATTRIBUTION_FIRST_TOUCH_CRON_CACHE_KEY        = 'web_attribution_first_touch_cron_timestamp';
    const WEB_ATTRIBUTION_CRON_CACHE_KEY                    = 'web_attribution_cron_timestamp';
    const TRANSACTION_CRON_CACHE_KEY                        = 'onboarding_transaction_cron_timestamp';
    const ESCALATION_CACHE_KEY                              = 'onboarding_escalation_timestamp';
    const SEGMENT_MTU_CACHE_KEY                             = 'onboarding_segment_mtu_timestamp';
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

    //Escalation Types
    const PAYMENT_BREACH    = 'payment_breach';
    const SETTLEMENT_BREACH = 'settlement_breach';

    // This is required to optimise DB query. We ignore all merchants with GMV below this threshold
    const LOWEST_PAYMENTS_THRESHOLD = 00; // in paisa
    const IS_NOT_NULL               = 'is not null';
    const IS_NULL                   = 'is null';
    const TRUE                      = 'true';
    const OPEN_STATUS_CONDITION     = [
        DEntity::ACTIVATION_STATUS => Status::OPEN_STATUSES
    ];

    // hard limit for sub-merchant no-doc onboarding
    const HARD_LIMIT_KYC_PENDING_THRESHOLD          = 5000000;
    const HARD_LIMIT_NO_DOC                         = 'hard_limit_no_doc';

    const PAYMENTS_ESCALATION_MATRIX = [
        0          => [
            [
                self::DESCRIPTION => "transacted after L1",
                self::TO          => self::ADMIN,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_FORM_MILESTONE => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS         => Status::OPEN_STATUSES
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
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
                    DEntity::BUSINESS_WEBSITE  => self::IS_NOT_NULL
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
                    DEntity::ACTIVATION_FORM_MILESTONE => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS         => Status::OPEN_STATUSES
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
                    DEntity::ACTIVATION_FORM_MILESTONE => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS         => Status::OPEN_STATUSES
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
                    DEntity::ACTIVATION_FORM_MILESTONE => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS         => Status::OPEN_STATUSES
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
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING
                ],
                self::MILESTONE   => 'hard_limit_level_1',
                self::ACTIONS     => [],  // This is getting escalated from v1 so disabling actions from here.
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "1 days after hard limit breach on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
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
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
                ],
                self::MILESTONE   => 'hard_limit_level_4',
                self::ACTIONS     => [
                    [
                        self::HANDLER => CommunicationHandler::class,
                        self::PARAMS  => [
                            'event' => Events::FUNDS_ON_HOLD
                        ]
                    ],
                ],
                self::ENABLE      => false
            ],
            [
                self::DESCRIPTION => "reminder funds on hold for activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
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
                self::DESCRIPTION => "hard limit breach on activated kyc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS  => Status::ACTIVATED_KYC_PENDING,
                    FeatureConstants::FEATURE   => FeatureConstants::NO_DOC_ONBOARDING
                ],
                self::MILESTONE   => self::HARD_LIMIT_NO_DOC,
                self::ACTIONS     => [
                    [
                        self::HANDLER   => NoDocLimitHandler::class,
                        self::PARAMS    => [
                            self::MILESTONE   => self::HARD_LIMIT_NO_DOC,
                            Entity::THRESHOLD => 5000000
                        ]
                    ]
                ],
                self::ENABLE    => false
            ]
        ],
        10000000 => [
            [
                self::DESCRIPTION => "funds on hold on activated mcc pending",
                self::TO          => self::MERCHANT,
                self::CONDITIONS  => [
                    DEntity::ACTIVATION_STATUS         => Status::ACTIVATED_MCC_PENDING,
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
                    DEntity::ACTIVATION_FORM_MILESTONE => DConstants::L2_SUBMISSION,
                    DEntity::ACTIVATION_STATUS         => Status::OPEN_STATUSES
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
}

<?php


namespace RZP\Models\Merchant\Escalations;


use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity as DEntity;
use RZP\Models\Merchant\Detail\Constants as DConstants;
use RZP\Models\Merchant\Escalations\Actions\Handlers\CommunicationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\DisablePaymentsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\MerchantTagsHandler;
use RZP\Notifications\Onboarding\Events;

class Constants
{
    const ESCALATION_CACHE_KEY = 'onboarding_escalation_timestamp';

    // request payload constants
    const TIME_BOUND    =   'time_bound';

    const TIME_BOUND_THRESHOLD = 3; // in months

    // Escalation triggered to
    const MERCHANT          = 'merchant';
    const ADMIN             = 'admin';

    const TO                = 'to';
    const CONDITIONS        = 'conditions';
    const ACTIONS           = 'actions';
    const ENABLE_ACTION     = 'enable_action';
    const DESCRIPTION       = 'description';
    const HANDLER           = 'handler';
    const PARAMS            = 'params';
    const MILESTONE         = 'milestone';
    const ENABLE            = 'enable';

    //Escalation Types
    const PAYMENT_BREACH    = 'payment_breach';
    const SETTLEMENT_BREACH = 'settlement_breach';

    // This is required to optimise DB query. We ignore all merchants with GMV below this threshold
    const LOWEST_PAYMENTS_THRESHOLD = 00; // in paisa

    const OPEN_STATUS_CONDITION = [
        DEntity::ACTIVATION_STATUS  => Status::OPEN_STATUSES
    ];

    const PAYMENTS_ESCALATION_MATRIX = [
        0             => [
            [
                self::DESCRIPTION   => "transacted after L1",
                self::TO            => self::ADMIN,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_FORM_MILESTONE  => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS          => Status::OPEN_STATUSES
                ],
                self::MILESTONE     => 'L1',
                self::ACTIONS       => [
                    [
                        self::HANDLER   => MerchantTagsHandler::class
                    ]
                ],
            ]
        ],
        500000        => [
            [
                self::DESCRIPTION   => "payments breach of 5k after L1, before L2",
                self::TO            => self::MERCHANT,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_FORM_MILESTONE  => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS          => Status::OPEN_STATUSES
                ],
                self::MILESTONE     => 'L1',
                self::ACTIONS       => [
                    [
                        self::HANDLER   => CommunicationHandler::class,
                        self::PARAMS    => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        1000000       => [
            [
                self::DESCRIPTION   => "payments breach of 10k after L1, before L2",
                self::TO            => self::MERCHANT,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_FORM_MILESTONE  => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS          => Status::OPEN_STATUSES
                ],
                self::MILESTONE     => 'L1',
                self::ACTIONS       => [
                    [
                        self::HANDLER   => CommunicationHandler::class,
                        self::PARAMS    => [
                            'event' => Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION
                        ]
                    ]
                ]
            ]
        ],
        1500000       => [
            [
                self::DESCRIPTION   => "payments breach of 15k after L1, before L2",
                self::TO            => self::MERCHANT,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_FORM_MILESTONE  => DConstants::L1_SUBMISSION,
                    DEntity::ACTIVATION_STATUS          => Status::OPEN_STATUSES
                ],
                self::MILESTONE     => 'L1',
                self::ACTIONS       => [
                    [
                        self::HANDLER   => CommunicationHandler::class,
                        self::PARAMS    => [
                            'event' => Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED
                        ]
                    ],
                    [
                        self::HANDLER   => DisablePaymentsHandler::class,
                    ]
                ]
            ],
            [
                self::DESCRIPTION   => "hard limit breach on activated mcc pending",
                self::TO            => self::ADMIN,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_STATUS          => Status::ACTIVATED_MCC_PENDING
                ],
                self::MILESTONE     => 'hard_limit_level_1',
                self::ACTIONS       => [],  // This is getting escalated from v1 so diabling actions from here.
                self::ENABLE        => false
            ],
            [
                self::DESCRIPTION   => "5 days after hard limit breach on activated mcc pending",
                self::TO            => self::ADMIN,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_STATUS          => Status::ACTIVATED_MCC_PENDING
                ],
                self::MILESTONE     => 'hard_limit_level_4',
                self::ACTIONS       => [],  // This is getting escalated from v1 so diabling actions from here.
                self::ENABLE        => false
            ]
        ],
        1000000000    => [
            [
                self::DESCRIPTION   => "payments breach of 1cr after L2",
                self::TO            => self::MERCHANT,
                self::CONDITIONS    => [
                    DEntity::ACTIVATION_FORM_MILESTONE  => DConstants::L2_SUBMISSION,
                    DEntity::ACTIVATION_STATUS          => Status::OPEN_STATUSES
                ],
                self::MILESTONE     => 'L2',
                self::ACTIONS       => [
                    [
                        self::HANDLER   => DisablePaymentsHandler::class,
                    ]
                ]
            ]
        ]
    ];

}

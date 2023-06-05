<?php

namespace RZP\Models\Merchant\AutoKyc\Escalations;

class Constants
{
    const SOFT_LIMIT = 'soft_limit';
    const HARD_LIMIT = 'hard_limit';
    const AMP = 'AMP';
    const AUTO_KYC_FAILURE = 'AUTO_KYC_FAILURE';

    const FOH_REMOVAL = 'FOH_REMOVAL';

    // adding constant as a tag for workflow creation for settlement clearance
    const ONBOARDING_REJECTED_SETTLEMENT_CLEARANCE = 'ONBOARDING_REJECTED_SETTLEMENT_CLEARANCE';

    // available escalation types
    const ESCALATION_TYPES = [
        self::SOFT_LIMIT,
        self::HARD_LIMIT
    ];

    const EMAIL         = 'email';
    const WORKFLOW      = 'workflow';
    const ESCALATION_V2 = 'ESCALATION_V2';

    const ESCALATION_METHODS = [
        self::EMAIL,
        self::WORKFLOW,
        self::ESCALATION_V2
    ];

    /**
     * config to store:
     * - all levels for a given escalation type
     * - what escalation method to use for which level
     * - duration: when to trigger the given escalation from previous escalation
     */
    const ESCALATION_CONFIG = [
        self::SOFT_LIMIT => [
            1 => [
                'method'    => self::WORKFLOW,
                'milestone' => 'soft_limit_level_1'
                // duration is basically zero. Since its a 1st escalation
            ],
            2 => [
                'method'   => self::EMAIL,
                'duration' => 2880 //in minutes [2 days after 1st escalation]
            ],
            3 => [
                'method'   => self::EMAIL,
                'duration' => 7200 // in minutes [5 days after 2nd escalation]
            ],
            4 => [
                'method'   => self::EMAIL,
                'duration' => 14400 // in minutes [10 days after 3rd escalation]
            ]
        ],
        self::HARD_LIMIT => [
            1 => [
                'method' => self::EMAIL,
                // duration is basically zero. Since its a 1st escalation
            ],
            2 => [
                'method'    => self::ESCALATION_V2,
                'milestone' => 'hard_limit_level_2',
                'duration'  => 1440 // in minutes [24 hrs after 1st escalation]
            ],
            3 => [
                'method'   => self::EMAIL,
                'duration' => 1440 // in minutes [48 hrs after 1st escalation]
            ],
            // ineffective
            4 => [
                'method'    => self::ESCALATION_V2,
                'milestone' => 'hard_limit_level_4',//funds on hold
                'duration'  => 4320 // in minutes [3 days after previous escalation, 5 days after 1st]
            ],
            5 => [
                'method'    => self::ESCALATION_V2,
                'milestone' => 'funds_on_hold_reminder',//funds on hold reminder 1
                'duration'  => 10080 // in minutes [7 days after previous escalation, 12 days after 1st]
            ],
            6 => [
                'method'    => self::ESCALATION_V2,
                'milestone' => 'funds_on_hold_reminder',//funds on hold reminder 2
                'duration'  => 10080 // in minutes [7 days after previous escalation, 19 days after 1st]
            ],
        ]
    ];

    /**
     * Map to store all escalations type that are of higher type than the key
     */
    const HIGHER_ESCALATION_TYPE_MAP = [
        self::SOFT_LIMIT => [
            self::HARD_LIMIT
        ],
        self::HARD_LIMIT => []
    ];

    /**
     * Map to store all escalations type that are of lower type than the key
     */
    const LOWER_ESCALATION_TYPE_MAP = [
        self::SOFT_LIMIT => [],
        self::HARD_LIMIT => [
            self::SOFT_LIMIT,
        ]
    ];

    /**
     * Developer email list
     * NOTE: ops team email list is fetched form env.
     */
    const ADMIN_EMAIL_LIST = [
        'suhas.ghule@razorpay.com'
    ];

    const HARD_LIMIT_MCC_PENDING_THRESHOLD = 'HARD_LIMIT_MCC_PENDING_THRESHOLD';
    const SOFT_LIMIT_MCC_PENDING_THRESHOLD = 'SOFT_LIMIT_MCC_PENDING_THRESHOLD';

    const HOLD_FUNDS_REASON_FOR_LIMIT_BREACH = 'GMV hard limit breached for the merchant.';
}

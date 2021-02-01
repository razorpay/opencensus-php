<?php

namespace RZP\Models\Merchant\AutoKyc\Escalations;

class Constants
{
    const SOFT_LIMIT    = 'soft_limit';
    const HARD_LIMIT    = 'hard_limit';

    // available escalation types
    const ESCALATION_TYPES  = [
        self::SOFT_LIMIT,
        self::HARD_LIMIT
    ];

    const EMAIL     = 'email';
    const WORKFLOW  = 'workflow';

    const ESCALATION_METHODS = [
        self::EMAIL,
        self::WORKFLOW
    ];

    /**
     * config to store:
     * - all levels for a given escalation type
     * - what escalation method to use for which level
     * - duration: when to trigger the given escalation from previous escalation
     */
    const ESCALATION_CONFIG = [
        self::SOFT_LIMIT    => [
            1   => [
                'method'    => self::WORKFLOW,
                // duration is basically zero. Since its a 1st escalation
            ],
            2   => [
                'method'    => self::EMAIL,
                'duration'  => 2880 //in minutes [2 days after 1st escalation]
            ],
            3   => [
                'method'    => self::EMAIL,
                'duration'  => 7200 // in minutes [5 days after 2nd escalation]
            ],
            4   => [
                'method'    => self::EMAIL,
                'duration'  => 14400 // in minutes [10 days after 3rd escalation]
            ]
        ],
        self::HARD_LIMIT    => [
            1   => [
                'method'    => self::EMAIL,
                // duration is basically zero. Since its a 1st escalation
            ],
            2   => [
                'method'    => self::EMAIL,
                'duration'  => 1440 // in minutes [24 hrs after 1st escalation]
            ],
            3   => [
                'method'    => self::EMAIL,
                'duration'  => 1440 // in minutes [48 hrs after 1st escalation]
            ]
        ]
    ];

    /**
     * Map to store all escalations type that are of higher type than the key
     */
    const HIGHER_ESCALATION_TYPE_MAP = [
        self::SOFT_LIMIT    => [
            self::HARD_LIMIT
        ],
        self::HARD_LIMIT    => []
    ];

    /**
     * Map to store all escalations type that are of lower type than the key
     */
    const LOWER_ESCALATION_TYPE_MAP = [
        self::SOFT_LIMIT    => [],
        self::HARD_LIMIT    => [
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
}

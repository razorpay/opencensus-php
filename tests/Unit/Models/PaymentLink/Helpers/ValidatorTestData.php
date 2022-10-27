<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaymentLink\Entity as E;
use RZP\Models\PaymentLink\DonationGoalTrackerType;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * <method_name> => ["test message" => [...attributes]]
 */
return [
    "testValidateGoalTracker" => [
        "Valid Case should pass" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ]
        ],
        "No setting provided should pass" => [
            [],
        ],
        "No goal tracker provided in setting should pass" => [
            [
                E::SETTINGS    => []
            ]
        ],
        "RANDOM Tracker type should throw exception"    => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => "RANDOM_TYPE",
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            "Not a valid Tracker Type: RANDOM_TYPE",
        ],
        "Tracker type is required" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The tracker type field is required.',
        ],
        "Goal is active other than 0 or 1 should throw exception" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "11",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The selected is active is invalid.'
        ],
        "Goal is_active is required" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The is active field is required.'
        ],
        "Meta data is required" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data field is required.'
        ],
        "Meta data is required and cannot be null" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE     => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE   => "1",
                        E::META_DATA        => null
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data field is required.'
        ],
        "Valid amount based tracker type" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT             => "10000"
                        ]
                    ]
                ]
            ]
        ],
        "Goal amount required when tracker type is " . DonationGoalTrackerType::DONATION_AMOUNT_BASED => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.goal amount field is required when tracker type is donation_amount_based.'
        ],
        "Goal amount should be numeric" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "AMOUNT"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The goal amount must be an integer.'
        ],
        "Goal amount should be exeed supported value" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "429496729512313131313213"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The goal amount must be an integer.'
        ],
        "Goal amount should adhere to minimum amount in paise for RS 10" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "10"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The goal amount must be atleast INR 1.00'
        ],
        "Goal amount should adhere to minimum amount in paise for RS 99" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "99"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The goal amount must be atleast INR 1.00'
        ],
        "Goal amount should adhere to minimum amount in paise for RS 100" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "100"
                        ]
                    ]
                ]
            ],
        ],
        "Goal end in past should throw exception" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(-10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'goal_end_timestamp should be at least 30 minutes after current time.'
        ],
        "Goal end in 1 sec past should throw exception" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addSeconds(-1)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'goal_end_timestamp should be at least 30 minutes after current time.'
        ],
        "Goal end 1 Hr in future should pass" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addHours(3)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
        ],
        "Goal end in future should pass" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
        ],
        "Goal end is required when display days left is 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.goal end timestamp field is required when meta data.display days left is 1.'
        ],
        "Goal end is not required when display days left is 0" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "0",
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
        ],
        "Goal end should be a valid integer time stamp" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => "asdadad",
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'goal_end_timestamp must be an integer.'
        ],
        "Display Days left is required" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.display days left field is required.'
        ],
        "Display Days left should be either 0 or 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "1",
                            E::DISPLAY_DAYS_LEFT        => "11",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The selected display days left is invalid.'
        ],
        "Display supporter count is required" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.display supporter count field is required.'
        ],
        "Display supporter count should be either 0 or 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SUPPORTER_COUNT  => "11",
                            E::DISPLAY_DAYS_LEFT        => "1",
                            E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                            E::GOAL_AMOUNT              => "1000"
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The selected display supporter count is invalid.'
        ],
        "Goal amount not required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "100",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
        ],
        "Available units not required when ". E::DISPLAY_AVAILABLE_UNITS. " is 0" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_AVAILABLE_UNITS => "0",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
        ],
        "Available units required when ". E::DISPLAY_AVAILABLE_UNITS. " is 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.available units field is required when meta data.display available units is 1.'
        ],
        "Available units should be greater cannot be less than 0" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "-1",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The available units must be at least 1.'
        ],
        "Available units should be greater cannot be equal to 0" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "0",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The available units must be at least 1.'
        ],
        "Display available unit is required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.display available units field is required when tracker type is donation_supporter_based.'
        ],
        "Display available unit should be either 0 or 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "11",
                            E::DISPLAY_AVAILABLE_UNITS => "11",
                            E::DISPLAY_SOLD_UNITS      => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The selected display available units is invalid.'
        ],
        "Display sold units is required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "11",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The meta data.display sold units field is required when tracker type is donation_supporter_based.'
        ],
        "Display supporter sold units be either 0 or 1" => [
            [
                E::SETTINGS    => [
                    E::GOAL_TRACKER    => [
                        E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                        E::GOAL_IS_ACTIVE  => "1",
                        E::META_DATA       => [
                            E::AVALIABLE_UNITS         => "11",
                            E::DISPLAY_AVAILABLE_UNITS => "1",
                            E::DISPLAY_SOLD_UNITS      => "11",
                            E::DISPLAY_SUPPORTER_COUNT => "1",
                            E::DISPLAY_DAYS_LEFT       => "1",
                            E::GOAL_END_TIMESTAMP      => (string) Carbon::now(Timezone::IST)->addDays(10)->getTimestamp(),
                        ]
                    ]
                ]
            ],
            BadRequestValidationFailureException::class,
            'The selected display sold units is invalid.'
        ],
    ],

    "testGeneralValidateMethods"    => [
        'Empty slug in validateSlug'                => ['validateSlug', " ", true],
        'Dollar sign slug in validateSlug'          => ['validateSlug', '$slug', true],
        'Normal Valid slug in validateSlug'         => ['validateSlug', 'slug', false],
        'Invalid slug with space in validateSlug'   => ['validateSlug', 'slug slug', true],
        'Valid slug with _ in validateSlug'         => ['validateSlug', 'slug_slug', false],
        'Valid slug with - in validateSlug'         => ['validateSlug', 'slug-slug', false],
        'Invalid slug with a dot in validateSlug'   => ['validateSlug', 'slug.slug', true],
        'Valid slug with - and int in validateSlug' => ['validateSlug', 'slug-slug111', false],
        'Valid slug with only int in validateSlug'  => ['validateSlug', '12312313131', false],

        'Valid slug with only int and _ in validateSlug'    => ['validateSlug', '12312_13131', false],
        'Valid slug with only int and - in validateSlug'    => ['validateSlug', '12312-13131', false],
        'Invalid slug with only int and . in validateSlug'  => ['validateSlug', '12312.13131', true],

        'Timestamp less than 15 minutes in validateExpireBy'    => ['validateExpireBy', Carbon::now(Timezone::IST)->getTimestamp(), true],
        'Timestamp more than 15 minutes in validateExpireBy'    => ['validateExpireBy', Carbon::now(Timezone::IST)->addDay()->getTimestamp(), false],
    ],

    "testValidateTimesPayable"  => [
        "Times Payble less then the actual value should throw exception"        => [1, true],
        "Times Payble greater then the actual value should not throw exception" => [10, false],
        "Times Payble with null value should not throw exception"               => [10, false],
    ],

    "testValidateAmount"  => [
        "Validate Amount with larger than limit should throw exception"     => [50000001, true],
        "Validate Amount with less than limit should not throw exception"   => [1, false],
        "Validate Amount with null should not throw exception"              => [null, false],
    ],

    "testValidateSettings"  => [
        "Validate Settings With Empty AllowMultipleUnits Should Throw Exception"    => [
            [
                E::AMOUNT   => 0,
                E::SETTINGS => [
                    E::ALLOW_MULTIPLE_UNITS  => true
                ],
            ],
            "amount is required with settings.allow_multiple_units."
        ],
        "Validate Settings With Empty Extra Settings Should Throw Exception"    => [
            [
                E::SETTINGS => [
                    "some_setting"  => false
                ]
            ],
            'Extra settings keys must not be sent - ' . implode(', ', ["some_setting"]) . '.'
        ]
    ],

    "testValidateTimesPayableForActivation" => [
        "Times payable less than a value should throw exception"        =>  [1, true],
        "Times payable equal to a value should throw exception"         =>  [3, true],
        "Times payable greater than a value should not throw exception" =>  [10, false],
        "Times payable with null value should not throw exception"      =>  [null, false],
    ],

    "testValidateMinAmount" => [
        "Min Amount of negative value throws error" => [-1, true],
        "Min Amount of 50 paise throws error"       => [50, true],
        "Min Amount of 99 paise throws error"       => [99, true],
        "Min Amount of 100 paise no error thrown"   => [100, false],
        "Min Amount of 200 paise no error thrown"   => [200, false],
    ],

    "testValidateDescription" => [
        "Youtube url desktop"                       => ["https://www.youtube.com/watch?v=BgP9tzt9_Z8", true],
        "Youtube url desktop with query params"     => ["https://www.youtube.com/watch?v=BgP9tzt9_Z8", true],
        "Youtube short url"                         => ["https://youtu.be/BgP9tzt9_Z8", true],
        "Youtube short url with query params"       => ["https://youtu.be/BgP9tzt9_Z8?t=2", true],
        "Vimeo Video"                               => ["https://vimeo.com/704606471", true],
        "Vimeo Video with query params"             => ["https://vimeo.com/704606471#t=120s", true],
        "False url 1"                               => ["https://vimeo.com/2539?@poc-demos.000webhostapp.com/embed_fake_page.php/#.", true],
        "False url 2"                               => ["https://www.youtube.com/watch?v=98jxd@poc-demos.000webhostapp.com/embed_fake_page.php/#.", true],
        "False url 3"                               => ["https://vimeo.com?@poc-demos.000webhostapp.com/embed_fake_page.php/#.", true],
        "False url 4"                               => ["https://vimeo.com/253989945?@poc-demos.000webhostapp.com/embed_fake_page.php/#.", true],
        "False url 5"                               => ["https://youtu.be@poc-demos.000webhostapp.com/embed_fake_page.php", false],
        "False url 6"                               => ["https://youtu.be:abcd@poc-demos.000webhostapp.com/embed_fake_page.php", false],
        "False url 7"                               => ["https://www.youtube.com@poc-demos.000webhostapp.com/embed_fake_page.php", false],
        "False url 8"                               => ["https://www.youtube.com:abcd@poc-demos.000webhostapp.com/embed_fake_page.php", false],

    ]
];

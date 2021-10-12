<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaymentLink\Entity as E;
use RZP\Models\PaymentLink\DonationGoalTrackerType;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * <method_name> => [[input1], [input2]]
 */
return [
    "testValidateGoalTracker" => [
        "Valid Case should pass" => [
            [
                "item"  => [
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
        ],
        "No setting provided should pass" => [
            ["item"=> []],
        ],
        "No goal tracker provided in setting should pass" => [
            [
                "item"=> [
                    E::SETTINGS    => []
                ]
            ],
        ],
        "RANDOM Tracker type should throw exception"    => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => "Not a valid Tracker Type: RANDOM_TYPE"
            ]
        ],
        "Tracker type is required" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The tracker type field is required.'
            ]
        ],
        "Goal is active other than 0 or 1 should throw exception" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The selected is active is invalid.'
            ]
        ],
        "Goal is_active is required" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The is active field is required.'
            ]
        ],
        "Meta data is required" => [
            [
                "item" => [
                    E::SETTINGS    => [
                        E::GOAL_TRACKER    => [
                            E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                            E::GOAL_IS_ACTIVE  => "1",
                        ]
                    ]
                ],
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data field is required.'
            ],
        ],
        "Meta data is required and cannot be null" => [
            [
                "item" => [
                    E::SETTINGS    => [
                        E::GOAL_TRACKER    => [
                            E::TRACKER_TYPE     => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                            E::GOAL_IS_ACTIVE   => "1",
                            E::META_DATA        => null
                        ]
                    ]
                ],
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data field is required.'
            ],
        ],
        "Valid amount based tracker type" => [
            [
                "item" => [
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
                ],
            ],
        ],
        "Goal amount required when tracker type is " . DonationGoalTrackerType::DONATION_AMOUNT_BASED => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.goal amount field is required when tracker type is donation_amount_based.'
            ],
        ],
        "Goal amount should be numeric" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The goal amount must be valid integer between 0 and 4294967295.'
            ],
        ],
        "Goal amount should be exeed supported value" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The goal amount must be valid integer between 0 and 4294967295.'
            ],
        ],
        "Goal amount should adhere to minimum amount in paise for RS 10" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The goal amount must be atleast INR 1.00'
            ],
        ],
        "Goal amount should adhere to minimum amount in paise for RS 99" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The goal amount must be atleast INR 1.00'
            ],
        ],
        "Goal amount should adhere to minimum amount in paise for RS 100" => [
            [
                "item" => [
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
        ],
        "Goal end in past should throw exception" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'goal_end_timestamp should be at least 30 minutes after current time.'
            ],
        ],
        "Goal end in 1 sec past should throw exception" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'goal_end_timestamp should be at least 30 minutes after current time.'
            ],
        ],
        "Goal end 1 Hr in future should pass" => [
            [
                "item" => [
                    E::SETTINGS    => [
                        E::GOAL_TRACKER    => [
                            E::TRACKER_TYPE    => DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                            E::GOAL_IS_ACTIVE  => "1",
                            E::META_DATA       => [
                                E::DISPLAY_SUPPORTER_COUNT  => "1",
                                E::DISPLAY_DAYS_LEFT        => "1",
                                E::GOAL_END_TIMESTAMP       => (string) Carbon::now(Timezone::IST)->addHours(1)->getTimestamp(),
                                E::GOAL_AMOUNT              => "1000"
                            ]
                        ]
                    ]
                ],
            ],
        ],
        "Goal end in future should pass" => [
            [
                "item" => [
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
        ],
        "Goal end is required when display days left is 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.goal end timestamp field is required when meta data.display days left is 1.'
            ],
        ],
        "Goal end is not required when display days left is 0" => [
            [
                "item" => [
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
        ],
        "Goal end should be a valid integer time stamp" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'goal_end_timestamp must be an integer.'
            ],
        ],
        "Display Days left is required" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.display days left field is required.'
            ],
        ],
        "Display Days left should be either 0 or 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The selected display days left is invalid.'
            ],
        ],
        "Display supporter count is required" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.display supporter count field is required.'
            ],
        ],
        "Display supporter count should be either 0 or 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The selected display supporter count is invalid.'
            ],
        ],
        "Goal amount not required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                "item" => [
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
            ]
        ],
        "Available units not required when ". E::DISPLAY_AVAILABLE_UNITS. " is 0" => [
            [
                "item" => [
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
            ]
        ],
        "Available units required when ". E::DISPLAY_AVAILABLE_UNITS. " is 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.available units field is required when meta data.display available units is 1.'
            ]
        ],
        "Available units should be greater cannot be less than 0" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The available units must be at least 1.'
            ]
        ],
        "Available units should be greater cannot be equal to 0" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The available units must be at least 1.'
            ]
        ],
        "Display available unit is required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.display available units field is required when tracker type is donation_supporter_based.'
            ],
        ],
        "Display available unit should be either 0 or 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The selected display available units is invalid.'
            ],
        ],
        "Display sold units is required when tracker type is ". DonationGoalTrackerType::DONATION_SUPPORTER_BASED => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The meta data.display sold units field is required when tracker type is donation_supporter_based.'
            ],
        ],
        "Display supporter sold units be either 0 or 1" => [
            [
                "item" => [
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
                "exception_class"   => BadRequestValidationFailureException::class,
                "exception_message" => 'The selected display sold units is invalid.'
            ],
        ],
    ],
];

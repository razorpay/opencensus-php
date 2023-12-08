<?php


namespace RZP\Models\Emi;


class ProcessingFeePlan
{
    const DEFAULT = 'default';

    const FIXED = 'fixed';

    const COMBINATION = 'combination';

    const PERCENTAGE = 'percentage';

    const TYPE = 'type';

    const AMOUNT = 'amount';

    const MIN_AMOUNT = 'min_amount';

    protected static $plan = [
        CreditEmiProvider::UTIB => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::COMBINATION,
                    self::PERCENTAGE => 1,
                    self::AMOUNT => 10000
                ]
            ]
        ],
        CreditEmiProvider::ICIC => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::KKBK => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ],
            Type::DEBIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 29900
                ]
            ]
        ],
        CreditEmiProvider::INDB => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ],
            Type::DEBIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::YESB => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::PERCENTAGE,
                    self::PERCENTAGE => 1
                ]
            ]
        ],
        CreditEmiProvider::RATN => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::AMEX => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::SBIN => [
            Type::CREDIT => [
                '6' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900,
                    self::MIN_AMOUNT => 1250000
                ],
                '9' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900,
                    self::MIN_AMOUNT => 900000
                ],
                '12' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900,
                    self::MIN_AMOUNT => 700000
                ],
                '18' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900,
                    self::MIN_AMOUNT => 1000000
                ],
                '24' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900,
                    self::MIN_AMOUNT => 1000000

                ],
                self:: DEFAULT => []
            ]
        ],
        CreditEmiProvider::HDFC => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ],
            Type::DEBIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::CITI => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::COMBINATION,
                    self::PERCENTAGE => 1,
                    self::AMOUNT => 10000
                ]
            ]
        ],
        CreditEmiProvider::HSBC => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900
                ]
            ]
        ]
    ];


    public function getProcessingFeePlan(string $issuer, string $cardType, string $duration, int $amount): array
    {
        if ((!isset(self:: $plan[$issuer])) || (!isset(self:: $plan[$issuer][$cardType])))
        {
            return [];
        }

        if (!isset(self:: $plan[$issuer][$cardType][$duration]))
        {
            $duration = self::DEFAULT;
        }

        if (isset(self:: $plan[$issuer][$cardType][$duration]['min_amount'])
            and ($amount < self:: $plan[$issuer][$cardType][$duration]['min_amount']))
        {
            return [];
        }

        return self:: $plan[$issuer][$cardType][$duration];
    }

}

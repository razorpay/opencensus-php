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
        CreditEmiProvider::SBIN => [
            Type::CREDIT => [
                '6' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900
                ],
                '9' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900
                ],
                '12' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900
                ],
                '18' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ],
                '24' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
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
    ];


    public function getProcessingFeePlan(string $issuer, string $cardType, string $duration): array
    {
        if ((!isset(self:: $plan[$issuer])) || (!isset(self:: $plan[$issuer][$cardType])))
            return [];

        if (!isset(self:: $plan[$issuer][$cardType][$duration]))
            $duration = self::DEFAULT;

        return self:: $plan[$issuer][$cardType][$duration];
    }

}

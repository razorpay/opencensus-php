<?php


namespace RZP\Models\Emi;

use App;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;


class ProcessingFeePlan
{
    /** @var App */
    protected $app;

    /** @var Trace  */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    const DEFAULT = 'default';

    const FIXED = 'fixed';

    const COMBINATION = 'combination';

    const PERCENTAGE = 'percentage';

    const TYPE = 'type';

    const AMOUNT = 'amount';

    const MIN_AMOUNT = 'min_amount';

    const V2_ISSUERS = [
        CreditEmiProvider::KKBK,
        CreditEmiProvider::IDFB,
        CreditEmiProvider::YESB,
    ];

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
            ],
            Type::DEBIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]

        ],
        CreditEmiProvider::SCBL => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::PERCENTAGE,
                    self::PERCENTAGE => 1
                ]
            ]
        ],
        CreditEmiProvider::AUBL => [
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
        CreditEmiProvider::KKBK.'v2' => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 24900
                ]
            ]
        ],
        CreditEmiProvider::INDB => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 24900
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
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900
                ]
            ]
        ],
        CreditEmiProvider::YESB.'v2' => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 29900
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
                    self::MIN_AMOUNT => 1600000
                ],
                '9' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900,
                    self::MIN_AMOUNT => 1100000
                ],
                '12' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 9900,
                    self::MIN_AMOUNT => 850000
                ],
                '18' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900,
                    self::MIN_AMOUNT => 1750000
                ],
                '24' => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 19900,
                    self::MIN_AMOUNT => 1600000

                ],
                self:: DEFAULT => []
            ]
        ],
        CreditEmiProvider::HDFC => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 29900
                ]
            ],
            Type::DEBIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 29900
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
        ],
        CreditEmiProvider::IDFB.'v2' => [
            Type::CREDIT => [
                self:: DEFAULT => [
                    self::TYPE => self::FIXED,
                    self::AMOUNT => 24900
                ]
            ]
        ],
    ];


    public function getProcessingFeePlan(string $issuer, string $cardType, string $duration, int $amount, string $merchantId = null): array
    {
        if (in_array($issuer, self::V2_ISSUERS) && $cardType == Type::CREDIT) {
            $issuer = $this->getIssuer($merchantId, $issuer);
        }

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

    private function getIssuer(?string $merchantId, string $issuer)
    {
        if ($merchantId === null || $merchantId === '') {
            return $issuer;
        }

        $experimentRequest = $this->fillExperimentData(
            $merchantId,
            'app.is_' . strtolower($issuer) . '_v2_emi_plans_experiment_id'
        );

        try
        {
            $expResult = (new SplitzExperimentEvaluator())->evaluateExperiment($experimentRequest);

            $isExperimentEnabled = ($expResult['variant'] === 'variant_on');

            if($isExperimentEnabled)
            {
                $issuer = $issuer.'v2';
            }

            return $issuer;

        }catch (\Throwable $e) {

            $this->trace->error(
                TraceCode::IS_EMI_V2_PLANS_RAZORX_ERROR,
                [
                    'type'         => 'is' . strtoupper($issuer) . 'v2EmiExperimentEnabled',
                    'errorMessage' => $e->getMessage()
                ]
            );
        }

    }

    private function fillExperimentData(
        string $experimentEntityId,
        string $experimentIdVariable
    )
    {
        $expData = [
            'id'            => $experimentEntityId,
            'experiment_id' => $this->app['config']->get($experimentIdVariable)
        ];

        return $expData;
    }

}

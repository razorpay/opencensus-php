<?php

namespace RZP\Tests\Functional\Refund;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart\Entity as MozartEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\UpiMetadata\Entity as UpiMetadataEntity;
use RZP\Models\Payment\PaymentMeta\Entity as PaymentMetaEntity;

class ScroogeFetchEntitiesTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ScroogeFetchEntitiesTestData.php';

        parent::setUp();
    }

    // Test scrooge fetch entities
    public function testScroogeFetchEntities()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->refundPayment($payment['id'], 200);

        $refund1 = $this->getLastEntity('refund', true);

        $this->refundPayment($payment['id'], 300);

        $refund2 = $this->getLastEntity('refund', true);

        // Internal auth
        $this->ba->appAuth('rzp_test','');

        $subTestArgs = [
            'refund1' => $refund1,
            'refund2' => $refund2,
            'payment' => $payment,
        ];

        // To test more cases add a new function with prefix scroogeFetchEntitiesSubTest appended by test number
        // this function is expected to return input and expected out for the test
        // sequence of tests might matter here since db edits can happen in each sub test which are not undone
        $subTests = 50;

        for ($i = 1; $i <= $subTests; $i++)
        {
            $func = 'scroogeFetchEntitiesSubTest' . $i;

            if (method_exists($this, $func))
            {
                list($input, $expectedOutput) = $this->$func($subTestArgs);

                $this->testData['callScroogeFetchEntities']['request']['content'] = $input;

                $response = $this->runRequestResponseFlow($this->testData['callScroogeFetchEntities']);

                $this->assertEquals($expectedOutput, $response);
            }
            else
            {
                break;
            }
        }
    }

    public function scroogeFetchEntitiesSubTest1($subTestArgs) : array
    {
        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
                substr($subTestArgs['refund2']['id'], 5),
            ],
            'payment' => ['gateway_captured'],
            'refund' => ['base_amount', 'attempts'],
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'refund' => [
                        'base_amount' => 200,
                        'attempts' => 1
                    ],
                    'payment' => [
                        'gateway_captured' => TRUE
                    ]
                ]
            ],
            substr($subTestArgs['refund2']['id'], 5) => [
                'entities' => [
                    'refund' => [
                        'base_amount' => 300,
                        'attempts' => 1
                    ],
                    'payment' => [
                        'gateway_captured' => TRUE
                    ]
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest2($subTestArgs) : array
    {
        $this->fixtures->payment->edit(substr($subTestArgs['refund1']['payment_id'], 4), ['bank' => NULL]);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
                substr($subTestArgs['refund2']['id'], 5),
            ],
            'extra_data' => ['ifsc_code'],
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => NULL
                ]
            ],
            substr($subTestArgs['refund2']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => NULL
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest3($subTestArgs) : array
    {
        $this->fixtures->payment->edit(substr($subTestArgs['refund1']['payment_id'], 4), ['bank' => 'HDFC']);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
                substr($subTestArgs['refund2']['id'], 5),
            ],
            'extra_data' => ['ifsc_code'],
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => 'HDFC0000001'
                ]
            ],
            substr($subTestArgs['refund2']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => 'HDFC0000001'
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest4($subTestArgs) : array
    {
        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
                substr($subTestArgs['refund2']['id'], 5),
            ],
            'entities' => [
                'card' => [
                    'iin',
                    'network'
                ],
                'terminal' => [
                    'gateway_acquirer',
                    'category'
                ]
            ],
            'extra_data' => [
                'is_fta_only_refund'
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'card' => [
                        'iin' => '401200',
                        'network' => 'Visa'
                    ],
                    'terminal' => [
                        'gateway_acquirer' => 'hdfc',
                        'category' => NULL
                    ]
                ],
                'extra_data' => [
                    'is_fta_only_refund' => false
                ]
            ],
            substr($subTestArgs['refund2']['id'], 5) => [
                'entities' => [
                    'card' => [
                        'iin' => '401200',
                        'network' => 'Visa'
                    ],
                    'terminal' => [
                        'gateway_acquirer' => 'hdfc',
                        'category' => NULL
                    ]
                ],
                'extra_data' => [
                    'is_fta_only_refund' => false
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest5($subTestArgs) : array
    {
        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5)
            ],
            'entities' => [
                'upi_metadata' => [
                    'type',
                    'provider'
                ]
            ],
            'extra_data' => [
                'is_fta_only_refund'
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'upi_metadata' => [
                        'type' => NULL,
                        'provider' => NULL
                    ]
                ],
                'extra_data' => [
                    'is_fta_only_refund' => false
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest6($subTestArgs) : array
    {
        $upiMetadata = new UpiMetadataEntity();

        $upiMetadata->build([
            UpiMetadataEntity::TYPE => 'otm',
            UpiMetadataEntity::FLOW => 'collect',
            UpiMetadataEntity::VPA => 'abc@okhdfcbank',
            UpiMetadataEntity::START_TIME  => Carbon::now()->getTimestamp(),
            UpiMetadataEntity::END_TIME    => Carbon::now()->addDays(2)->getTimestamp(),
            UpiMetadataEntity::EXPIRY_TIME => 5
        ]);

        $upiMetadata->forceFill([
            UpiMetadataEntity::PAYMENT_ID => substr($subTestArgs['refund1']['payment_id'], 4)
        ]);

        $upiMetadata->save();

        $this->fixtures->payment->edit(substr($subTestArgs['refund1']['payment_id'], 4), [
            'method' => 'upi',
            'gateway' => 'upi_mindgate',
        ]);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5)
            ],
            'entities' => [
                'upi_metadata' => [
                    'type',
                    'provider'
                ]
            ],
            'extra_data' => [
                'is_fta_only_refund'
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'upi_metadata' => [
                        'type' => 'otm',
                        'provider' => NULL
                    ]
                ],
                'extra_data' => [
                    'is_fta_only_refund' => true
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest7($subTestArgs) : array
    {
        $mozart = new MozartEntity();

        $mozart->build([
            MozartEntity::AMOUNT => $subTestArgs['refund1']['amount'],
            MozartEntity::RAW => '{"data": {"transaction": {"id": "90c8ec9b", "items": [{"sku": "Eyp2nbXQAsYxOX"}], "status": "CLAIMED"}}, "status": "payment_successful", "success": true}'
        ]);

        $mozart->forceFill([
            MozartEntity::ACTION => 'authorize',
            MozartEntity::GATEWAY => 'hdfc',
            MozartEntity::PAYMENT_ID => substr($subTestArgs['refund1']['payment_id'], 4)
        ]);

        $mozart->save();

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5)
            ],
            'entities' => [
                'gateway_entity' => [
                    'mozart' => [
                        'authorize' => [
                            'status',
                            'data.transaction.id',
                            'app',
                            'data.transaction.items',
                        ]
                    ]
                ]
            ],
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'gateway_entity' => [
                        'mozart' => [
                            'authorize' => [
                                'status' => 'payment_successful',
                                'data.transaction.id' => '90c8ec9b',
                                'app' => '',
                                'data.transaction.items' => [["sku" => "Eyp2nbXQAsYxOX"]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest8($subTestArgs) : array
    {
        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5)
            ],
            'entities' => [
                'gateway_entity' => [
                    'mozart' => [
                        'authorize' => [
                            'status',
                        ],
                        'capture' => [
                            'status',
                        ]
                    ]
                ]
            ],
        ];

        $expectedOutput = [];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest9($subTestArgs) : array
    {
        $this->fixtures->payment->edit(substr($subTestArgs['refund1']['payment_id'], 4), [
            'method' => 'bank_transfer'
        ]);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
                substr($subTestArgs['refund2']['id'], 5),
            ],
            'extra_data' => ['ifsc_code', 'is_fta_only_refund'],
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => 'HDFC0000001',
                    'is_fta_only_refund' => true
                ]
            ],
            substr($subTestArgs['refund2']['id'], 5) => [
                'extra_data' => [
                    'ifsc_code' => 'HDFC0000001',
                    'is_fta_only_refund' => true
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    // test iin fetch for payment card
    public function scroogeFetchEntitiesSubTest10($subTestArgs) : array
    {
        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
            ],
            'payment' => ['card_id'],
            'entities' => [
                'card' => ['iin'],
                'iin'  => ['iin', 'type'],
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'iin' => [
                        'iin'  => '401200',
                        'type' => 'credit'
                    ],
                    'card' => [
                        'iin' => '401200',
                    ],
                    'payment' => [
                        'card_id' => substr($subTestArgs['payment']['card_id'], 5)
                    ]
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    // test iin fetch for payment card when iin relation doesnt exist
    public function scroogeFetchEntitiesSubTest11($subTestArgs) : array
    {
        $this->fixtures->card->edit(substr($subTestArgs['payment']['card_id'], 5), [
            'iin' => '998761'
        ]);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
            ],
            'payment' => ['card_id'],
            'entities' => [
                'card' => ['iin'],
                'iin'  => ['iin', 'type'],
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'iin' => [
                        'iin'  => NULL,
                        'type' => NULL
                    ],
                    'card' => [
                        'iin' => '998761',
                    ],
                    'payment' => [
                        'card_id' => substr($subTestArgs['payment']['card_id'], 5)
                    ]
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }

    // test fta_data fetch
    public function scroogeFetchEntitiesSubTest12($subTestArgs) : array
    {
        // set back from previous test edit
        $this->fixtures->card->edit(substr($subTestArgs['payment']['card_id'], 5), [
            'iin' => '401200'
        ]);

        $this->fixtures->payment->edit(substr($subTestArgs['refund1']['payment_id'], 4), [
            'method'    => 'upi',
            'gateway'   => 'upi_mindgate',
            'recurring' => TRUE,
            'vpa'       => 'abc@rzp'
        ]);

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5),
            ],
            'payment' => ['method', 'is_dcc'],
            'extra_data' => [
                'fta_data',
            ]
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'payment' => [
                        'method' => 'upi',
                        'is_dcc' => false,
                    ]
                ],
                'extra_data' => [
                    'fta_data' => [
                        'error' => NULL,
                        'fta_data' => [
                            'vpa' => [
                                'address' => 'abc@rzp'
                            ]
                        ]
                    ]
                ],
            ]
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesSubTest13($subTestArgs) : array
    {
        $paymentMeta = new PaymentMetaEntity();

        $paymentMeta->build([
            PaymentMetaEntity::GATEWAY_AMOUNT => 30,
            PaymentMetaEntity::GATEWAY_CURRENCY => 'USD',
            PaymentMetaEntity::PAYMENT_ID => substr($subTestArgs['refund1']['payment_id'], 4)
        ]);

        $paymentMeta->save();

        $input = [
            'refund_ids' => [
                substr($subTestArgs['refund1']['id'], 5)
            ],
            'payment' => ['is_dcc', 'currency', 'gateway_captured']
        ];

        $expectedOutput = [
            substr($subTestArgs['refund1']['id'], 5) => [
                'entities' => [
                    'payment' => [
                        'currency' => 'INR',
                        'is_dcc' => true,
                        'gateway_captured' => true,
                    ]
                ]
            ]
        ];

        return [$input, $expectedOutput];
    }
}

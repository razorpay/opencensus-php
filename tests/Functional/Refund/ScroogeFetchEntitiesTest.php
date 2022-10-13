<?php

namespace RZP\Tests\Functional\Refund;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart\Entity as MozartEntity;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;
use RZP\Models\Payment\UpiMetadata\Entity as UpiMetadataEntity;
use RZP\Models\Payment\PaymentMeta\Entity as PaymentMetaEntity;

class ScroogeFetchEntitiesTest extends TestCase
{
    use PaymentTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ScroogeFetchEntitiesTestData.php';

        parent::setUp();
    }

    // Test scrooge fetch entities v2
    public function testScroogeFetchEntitiesV2()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $payment2 = $this->defaultAuthPayment();

        $payment2 = $this->capturePayment($payment2['id'], $payment2['amount']);

        // Internal auth
        $this->ba->scroogeAuth();

        $subTestArgs = [
            'payment'  => $payment,
            'payment2' => $payment2
        ];

        // To test more cases add a new function with prefix scroogeFetchEntitiesSubTest appended by test number
        // this function is expected to return input and expected out for the test
        // sequence of tests might matter here since db edits can happen in each sub test which are not undone
        $subTests = 50;

        for ($i = 1; $i <= $subTests; $i++)
        {
            $func = 'scroogeFetchEntitiesV2SubTest' . $i;

            if (method_exists($this, $func)) {
                list($input, $expectedOutput) = $this->$func($subTestArgs);

                $this->testData['callScroogeFetchEntitiesV2']['request']['content'] = $input;


                $response = $this->runRequestResponseFlow($this->testData['callScroogeFetchEntitiesV2']);

                $expectedOutput = Arr::dot($expectedOutput);

                $response = Arr::dot($response);

                foreach ($expectedOutput as $key => $val)
                {
                    $this->assertEquals($val, $response[$key]);
                }
            }
            else
            {
                break;
            }
        }
    }

    // test various entities fetch
    public function scroogeFetchEntitiesV2SubTest1($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);
        $paymentId2 = substr($subTestArgs['payment2']['id'], 4);
        $cardId = substr($subTestArgs['payment']['card_id'], 5);
        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a',
            ['gateway_secure_secret' => 'sample_secret_code',
            'gateway_secure_secret2' => 'sample_secret_code2',
            'gateway_terminal_password' => 'sample_terminal_password']
        );

        $this->fixtures->create('token', [
            'id' => 'IMxXhFhCPcU49R',
            'status' => 'active',
            'card_id' => $cardId
        ]);
        $this->fixtures->create('token', [
            'id' => 'IMxXhFhCPcU49S',
            'status' => 'deactivated'
        ]);

        $this->fixtures->edit('payment', $paymentId, [
            'token_id' => 'IMxXhFhCPcU49R'
        ]);

        $this->fixtures->edit('payment', $paymentId2, [
            'global_token_id' => 'IMxXhFhCPcU49S'
        ]);

        $input = [
            'payment_ids' => [
                substr($subTestArgs['payment']['id'], 4),
                substr($subTestArgs['payment2']['id'], 4),
            ],
            'entities' => ['payment', 'card', 'terminal', 'upi_metadata', "token", "token_card"],
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'payment' => [
                        'data' =>  [
                            'merchant_id' => '10000000000000',
                            'amount' => '50000',
                            'amount_unrefunded' => '50000',
                            'currency' => 'INR',
                            'currency_conversion_rate' => '1',
                            'is_upi_otm' => false,
                            'status' => 'captured',
                        ],
                        'error' => NULL,
                    ],
                    'card' => [
                        'data' =>  [
                            'merchant_id' => '10000000000000',
                            'iin' => '401200',
                            'last4' => '3335',
                            'vault_token'=>'NDAxMjAwMTAzODQ0MzMzNQ==',
                        ],
                        'error' => NULL,
                    ],
                    'terminal' => [
                        'data' =>  [
                            'gateway' => 'hdfc',
                            'gateway_acquirer' => 'hdfc',
                            'gateway_secure_secret' => 'sample_secret_code',
                            'gateway_secure_secret2' => 'sample_secret_code2',
                            'gateway_terminal_password' => 'sample_terminal_password',
                        ],
                        'error' => NULL,
                    ],
                    'upi_metadata' => [
                        'data' => NULL,
                        'error' => NULL,
                    ],
                    'token' => [
                        'data' => [
                            'status' => 'active'
                        ],
                        'error' => NULL,
                    ],
                    'token_card' => [
                        'data' => [
                            'vault' => 'rzpvault'
                        ],
                        'error' => NULL,
                    ]
                ],
            ],
            $paymentId2 => [
                'entities' => [
                    'payment' => [
                        'data' =>  [
                            'merchant_id' => '10000000000000',
                            'amount' => '50000',
                            'amount_unrefunded' => '50000',
                            'currency' => 'INR',
                            'currency_conversion_rate' => '1',
                            'is_upi_otm' => false,
                            'status' => 'captured',
                        ],
                        'error' => NULL,
                    ],
                    'card' => [
                        'data' =>  [
                            'merchant_id' => '10000000000000',
                            'iin' => '401200',
                            'last4' => '3335',
                            'vault_token'=>'NDAxMjAwMTAzODQ0MzMzNQ==',
                        ],
                        'error' => NULL,
                    ],
                    'terminal' => [
                        'data' =>  [
                            'gateway' => 'hdfc',
                            'gateway_acquirer' => 'hdfc',
                        ],
                        'error' => NULL,
                    ],
                    'upi_metadata' => [
                        'data' => NULL,
                        'error' => NULL,
                    ],
                    'token' => [
                        'data' => [
                            'status' => 'deactivated'
                        ],
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test failed extra_data ifsc_code
    public function scroogeFetchEntitiesV2SubTest2($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $this->fixtures->payment->edit($paymentId, ['bank' => NULL]);

        $input = [
            'payment_ids' => [
                substr($subTestArgs['payment']['id'], 4),
            ],
            'extra_data' => ['ifsc_code'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'ifsc_code' => [
                        'data' => NULL,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test success extra data ifsc code fetch
    public function scroogeFetchEntitiesV2SubTest3($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $this->fixtures->payment->edit($paymentId, ['bank' => 'hdfc']);

        $input = [
            'payment_ids' => [
                $paymentId,
            ],
            'extra_data' => ['ifsc_code'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'ifsc_code' => [
                        'data' => 'HDFC0000001',
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test is_fta_only_refund failing
    public function scroogeFetchEntitiesV2SubTest4($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'card',
                'terminal'
            ],
            'extra_data' => [
                'is_fta_only_refund'
            ]
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'card' => [
                        'data' => [
                            'iin' => '401200',
                            'network' => 'Visa',
                            ],
                        'error' => NULL
                    ],
                    'terminal' => [
                        'data' => [
                            'gateway_acquirer' => 'hdfc',
                            'category' => NULL
                        ],
                        'error' => NULL

                    ]
                ],
                'extra_data' => [
                    'is_fta_only_refund' => [
                        'data' => false,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test upi metadata fetch and hence, is_fta_only_refund
    public function scroogeFetchEntitiesV2SubTest5($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $upiMetadata = new UpiMetadataEntity();

        $upiMetadata->build([
            UpiMetadataEntity::TYPE => 'otm',
            UpiMetadataEntity::FLOW => 'collect',
            UpiMetadataEntity::VPA => 'abc@okhdfcbank',
            UpiMetadataEntity::START_TIME => Carbon::now()->getTimestamp(),
            UpiMetadataEntity::END_TIME => Carbon::now()->addDays(2)->getTimestamp(),
            UpiMetadataEntity::EXPIRY_TIME => 5
        ]);

        $upiMetadata->forceFill([
            UpiMetadataEntity::PAYMENT_ID => $paymentId
        ]);

        $upiMetadata->save();

        $this->fixtures->payment->edit($paymentId, [
            'method' => 'upi',
            'gateway' => 'upi_mindgate',
        ]);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'upi_metadata'
            ],
            'extra_data' => [
                'is_fta_only_refund'
            ]
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'upi_metadata' => [
                        'data' => [
                            'type' => 'otm',
                            'provider' => NULL,
                        ],
                        'error' => NULL
                    ],
                ],
                'extra_data' => [
                    'is_fta_only_refund' => [
                        'data' => true,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test gateway entity fetch
    public function scroogeFetchEntitiesV2SubTest6($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $mozart = new MozartEntity();

        $mozart->build([
            MozartEntity::AMOUNT => '100',
            MozartEntity::RAW => '{"data": {"transaction": {"id": "90c8ec9b", "items": [{"sku": "Eyp2nbXQAsYxOX"}], "status": "CLAIMED"}}, "status": "payment_successful", "success": true}'
        ]);

        $mozart->forceFill([
            MozartEntity::ACTION => 'authorize',
            MozartEntity::GATEWAY => 'hdfc',
            MozartEntity::PAYMENT_ID => $paymentId,
        ]);

        $mozart->save();

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'gateway_entity.mozart.authorize',
                'gateway_entity.mozart.capture'
            ],
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'gateway_entity.mozart.authorize' => [
                        'data' => [
                            'status' => 'payment_successful',
                            'data.transaction.id' => '90c8ec9b',
                            'data.transaction.items' => [["sku" => "Eyp2nbXQAsYxOX"]]
                        ],
                        'error' => NULL,
                    ],
                    'gateway_entity.mozart.capture' => [
                        'data' => NULL,
                        'error' => 'FETCH_ENTITIES_ERROR',
                    ],
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    public function scroogeFetchEntitiesV2SubTest7($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $this->fixtures->payment->edit($paymentId,  [
           'method' => 'bank_transfer'
        ]);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'extra_data' => ['ifsc_code', 'is_fta_only_refund'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'ifsc_code' => [
                        'data' => 'HDFC0000001',
                        'error' => NULL,
                    ],
                    'is_fta_only_refund' => [
                        'data' => true,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test successful iin entity fetch
    public function scroogeFetchEntitiesV2SubTest8($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $paymentEntity = $this->getDbEntityById('payment', $paymentId);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'payment', 'card', 'iin'
            ],
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'card' => [
                        'data' => [
                            'iin' => '401200',
                        ],
                        'error' => NULL
                    ],
                    'iin' => [
                        'data' => [
                            'iin' => '401200',
                            'type' => 'credit'
                        ],
                        'error' => NULL
                    ],
                    'payment' => [
                        'data' => [ 'card_id' => $paymentEntity['card_id']],
                        'error' => NULL
                    ],
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test failed iin entity fetch
    public function scroogeFetchEntitiesV2SubTest9($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $paymentEntity = $this->getDbEntityById('payment', $paymentId);

        $this->fixtures->card->edit($paymentEntity['card_id'], [
            'iin' => '998761'
        ]);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'payment', 'card', 'iin'
            ],
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'card' => [
                        'data' => [
                            'iin' => '998761',
                        ],
                        'error' => NULL
                    ],
                    'iin' => [
                        'data' => NULL,
                        'error' => NULL,
                    ],
                    'payment' => [
                        'data' => [ 'card_id' => $paymentEntity['card_id']],
                        'error' => NULL
                    ],
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test extra_data merchant features
    public function scroogeFetchEntitiesV2SubTest10($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $this->fixtures->merchant->addFeatures(['google_play_cards', 'disable_instant_refunds']);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'extra_data' => ['merchant_features'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'merchant_features' => [
                        'data' => ['google_play_cards', 'disable_instant_refunds'],
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }


    // test extra_data count of open non fraud disputes
    public function scroogeFetchEntitiesV2SubTest11($subTestArgs): array
    {
        $dispute = new DisputeEntity;

        $createdAt=Carbon::now()->getTimestamp();
        $capturedAt=Carbon::now()->getTimestamp();

        $payment = $this->fixtures->create('payment:captured',
            [
                'captured_at' => $capturedAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10]);

        $paymentId = $payment['id'];

        $dispute->payment()->associate($payment);

        $merchant = $this->fixtures->create('merchant');

        $dispute->merchant()->associate($merchant);

        $reason = $this->fixtures->create('dispute_reason');

        $dispute->associateReason($reason);

        $dispute->build([
            DisputeEntity::GATEWAY_DISPUTE_ID  => '4342frf34r',
            DisputeEntity::PHASE => 'Chargeback',
            DisputeEntity::RAISED_ON => Carbon::now()->getTimestamp(),
            DisputeEntity::EXPIRES_ON=> Carbon::now()->addDays(2)->getTimestamp(),
            DisputeEntity::GATEWAY_AMOUNT => 10000,
            DisputeEntity::GATEWAY_CURRENCY => 'INR',
            DisputeEntity::DEDUCT_AT_ONSET => 0,
            DisputeEntity::REASON_ID => 'NotAvailable00',
            DisputeEntity::SKIP_EMAIL => true,
        ]);

        $dispute->forceFill([
            DisputeEntity::STATUS   => 'open',
            DisputeEntity::MERCHANT_ID   => '10000000000000',
        ]);

        $dispute->save();

        $input = [
            'payment_ids' => [
                $paymentId
            ],

            'extra_data' => ['count_of_open_non_fraud_disputes'],
        ];


        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'count_of_open_non_fraud_disputes' => [
                        'data' => '1',
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test extra_data is_iin_prepaid
    public function scroogeFetchEntitiesV2SubTest12($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);
        $paymentEntity = $this->getDbEntityById('payment', $paymentId);

        $this->fixtures->card->edit($paymentEntity['card_id'], [
            'iin' => '222688'
        ]);

        $this->fixtures->create('iin', [
            'iin'       => '222688',
            'issuer'    => 'RATN',
            'type' => 'credit',
            'recurring' => 1,
        ]);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'extra_data' => ['is_iin_prepaid'],
        ];

        $expectedOutput = [
            $paymentId => [

                'extra_data' => [
                    'is_iin_prepaid' => [
                        'data' => true,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test extra_data card_has_supported_issuer
    public function scroogeFetchEntitiesV2SubTest13($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        //issuer was already set to RATN in previous test, which is a supported one

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'extra_data' => ['card_has_supported_issuer'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'card_has_supported_issuer' => [
                        'data' => true,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // test fta_data fetch
    public function scroogeFetchEntitiesV2SubTest14($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $paymentEntity = $this->getDbEntityById('payment', $paymentId);

        // set back from previous test edit
        $this->fixtures->card->edit($paymentEntity['card_id'], [
            'iin' => '401200'
        ]);

        $this->fixtures->payment->edit($paymentId, [
            'method' => 'upi',
            'gateway' => 'upi_mindgate',
            'recurring' => TRUE,
            'vpa' => 'abc@rzp',
            'card_id' => NULL,
        ]);

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'entities' => [
                'payment'
            ],
            'extra_data' => [
                'fta_data'
            ]
        ];

        $expectedOutput = [
            $paymentId => [
                'entities' => [
                    'payment' => [
                        'data' => [
                            'method' => 'upi',
                        ],
                        'error' => NULL
                    ],
                ],
                'extra_data' => [
                    'fta_data' => [
                         'data' => [
                                    'vpa' => 'abc@rzp'
                         ],
                        'error' => NULL
                    ],
                ],
            ]
        ];

        return [$input, $expectedOutput];
    }

    // test various extra data fetch on bank_transfer entity
    public function scroogeFetchEntitiesV2SubTest15($subTestArgs): array
    {
        $paymentId = substr($subTestArgs['payment']['id'], 4);

        $paymentEntity = $this->getDbEntityById('payment', $paymentId);

        $merchantEntity = $this->getDbEntityById('merchant', $paymentEntity['merchant_id']);

        $this->fixtures->payment->edit($paymentId, ['method' => 'bank_transfer']);

        $bankTransfer = new BankTransferEntity();

        $bankTransfer->payment()->associate($paymentEntity);

        $bankTransfer->merchant()->associate($merchantEntity);

        $time=Carbon::now()->getTimestamp();


        $bankTransfer->build([
            BankTransferEntity::PAYEE_ACCOUNT => '2224440041626905',
            BankTransferEntity::PAYEE_IFSC => 'HDFC0000001',
            'transaction_id' => $paymentEntity['transaction_id'],
            BankTransferEntity::AMOUNT => '100',
            BankTransferEntity::MODE => 'IMPS',
            BankTransferEntity::TIME => $time,
            BankTransferEntity::PAYER_IFSC => 'HDFC0000001',

        ]);

        $bankTransfer->forceFill([
            BankTransferEntity::GATEWAY => 'hdfc',
            BankTransferEntity::UTR => '124802075266',
        ]);

        $bankTransfer->save();

        $input = [
            'payment_ids' => [
                $paymentId
            ],
            'extra_data' => ['payment_utr', 'payer_bank_account'],
        ];

        $expectedOutput = [
            $paymentId => [
                'extra_data' => [
                    'payment_utr' => [
                        'data' => '124802075266',
                        'error' => NULL,
                    ],
                    'payer_bank_account' => [
                        'data' => NULL,
                        'error' => NULL,
                    ]
                ],
            ],
        ];

        return [$input, $expectedOutput];
    }

    // Test scrooge fetch public entities
    public function testScroogeFetchPublicEntities()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $payment['id'] = substr($payment['id'], 4);

        // Internal auth
        $this->ba->scroogeAuth();

        $input = [
            "public_entities" => [
                [
                    "entity_id" => "JR4i3Gv63iiTkJ",
                    "entity_type" => "transaction",
                    "expand" => ["settlement"],
                ],
                [
                    "entity_id" => "TestRefund0001",
                    "entity_type" => "refund",
                    "expand" => ["transaction.settlement"]
                ],
                [
                    "entity_id" => $payment['id'],
                    "entity_type" => "payment",
                ],
            ]
        ];

        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertNull($response['JR4i3Gv63iiTkJ']['data']);
        $this->assertEquals('BAD_REQUEST_INVALID_ID', $response['JR4i3Gv63iiTkJ']['error']['code']);

        $this->assertNull($response['TestRefund0001']['data']);
        $this->assertEquals('BAD_REQUEST_INVALID_ID', $response['JR4i3Gv63iiTkJ']['error']['code']);

        $this->assertEquals('pay_' . $payment['id'], $response[$payment['id']]['data']['id']);
        $this->assertEquals('payment', $response[$payment['id']]['data']['entity']);
        $this->assertEquals('captured', $response[$payment['id']]['data']['status']);
        $this->assertNull($response[$payment['id']]['error']);

        // create refund transaction
        $testRefundId     = 'TestRefund0001';
        $testSettlementId = 'TestSettlement';

        $txnInput = [
            'id'               => $testRefundId,
            'payment_id'       => $payment['id'],
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/refunds/transaction_create';
        $testData['request']['content'] = $txnInput;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $txnId = $response['data']['transaction_id'];

        // create refund entity
        $refund = new RefundEntity();

        $refund->forceFill([
            'id'                => 'TestRefund0001',
            'payment_id'        => $payment['id'],
            'merchant_id'       => '10000000000000',
            'transaction_id'    => $txnId,
            'notes'             => [],
            'amount'            => 50000,
            'base_amount'       => 50000,
            'currency'          => 'INR',
            'status'            => 'processed',
            'gateway'           => $payment['gateway'],
        ]);

        $refund->saveOrFail();

        // create settlement entity
        $this->createSettlementEntry([
            'merchant_id'               => $payment['merchant_id'],
            'channel'                   => 'axis2',
            'balance_type'              => 'primary',
            'amount'                    => 1000,
            'fees'                      => 12,
            'tax'                       => 13,
            'settlement_id'             => $testSettlementId,
            'status'                    => 'processed',
            'type'                      => 'normal',
            'details'                   => [
                'payment' => [
                    'type' => 'credit',
                    'amount' => 50000,
                    'count'  => 1,
                ],
                'refund' => [
                    'type'  => 'debit',
                    'amount' => -50000,
                    'count'  => 1,
                ]
            ]
        ]);

        $this->fixtures->transaction->edit($txnId, ['settlement_id' => $testSettlementId]);
        $this->fixtures->settlement->edit($testSettlementId, ['transaction_id' => $txnId]);

        $sid = 'SettlementRaid';

        $input = [
            "public_entities" => [
                [
                    "entity_id" => $txnId,
                    "entity_type" => "transaction",
                    "expand" => ["settlement"],
                ],
                [
                    "entity_id" => $testRefundId,
                    "entity_type" => "refund",
                    "expand" => ["transaction.settlement"]
                ],
                [
                    "entity_id" => $payment['id'],
                    "entity_type" => "payment",
                ],
            ],
            "custom_public_entities" => [
                [
                    "entity_id" => $sid,
                    "entity_type" => "optimizer_settlement",
                    "transaction_id" => $txnId,
                ],
            ]
        ];

        $this->ba->scroogeAuth();

        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertNull($response[$payment['id']]['error']);

        $this->assertEquals('txn_' . $txnId, $response[$txnId]['data']['id']);
        $this->assertEquals('transaction', $response[$txnId]['data']['entity']);
        $this->assertEquals('refund', $response[$txnId]['data']['type']);
        $this->assertEquals('rfnd_' . $testRefundId, $response[$txnId]['data']['entity_id']);
        $this->assertEquals('setl_' . $testSettlementId, $response[$txnId]['data']['settlement']['id']);
        $this->assertEquals('processed', $response[$txnId]['data']['settlement']['status']);
        $this->assertNull($response[$txnId]['error']);

        $this->assertEquals('rfnd_' . $testRefundId, $response[$testRefundId]['data']['id']);
        $this->assertEquals('pay_' . $payment['id'], $response[$testRefundId]['data']['payment_id']);
        $this->assertEquals('txn_' . $txnId, $response[$testRefundId]['data']['transaction']['id']);
        $this->assertEquals('setl_' . $testSettlementId, $response[$testRefundId]['data']['transaction']['settlement']['id']);
        $this->assertNull($response[$testRefundId]['error']);

        $this->assertEquals('setl_settlement0001', $response[$sid]['data']['id']);
        $this->assertEquals('Razorpay', $response[$sid]['data']['settled_by']);
        $this->assertEquals('Razorpay', $response[$sid]['data']['optimizer_provider']);
        $this->assertNull($response[$sid]['error']);
    }
}

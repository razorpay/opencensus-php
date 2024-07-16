<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Constants\Entity;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;
use RZP\Tests\Traits\MocksRazorx;

class PaymentWalletTransferTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;
    use MocksSplitz;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentWalletTransferTestData.php';

        parent::setUp();

        $this->fixtures->create('payment:authorized');

        $this->payment = $this->getLastEntity('payment', false);

        $this->ba->privateAuth();
    }

    public function testCaptureAndTransferToInvalidCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->startTest();
    }

    public function testCreateWalletWithNonIndianContact()
    {
        $this->mockSplitzExperiment([
            "response" => [
                "variant" => [
                    "name" => 'sync',
                ]
            ]
        ]);

        $customer = $this->fixtures->create('customer', ['contact' => '+9293003939']);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();
    }

    public function testCustomerTransferB2bNotEnabled()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->startTest();
    }

    public function testCaptureAndTransferToUnknownCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $amount = $this->payment['amount'];

        $this->startTest();
    }

    public function testTransferToWalletFundsOnHold()
    {
        //
        // this been added as there is some issues in test framework
        // which resets the the mode to test
        // where as here we are operating on live mode.
        //
        $this->app['rzp.mode'] = 'live';

        $this->fixtures->merchant->holdFunds();

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->payment = $this->fixtures->on('live')->create('payment:captured')->toArrayPublic();

        $this->startTest(null, null, 'live');
    }

    public function testTransferToExistingCustomerWithNoExistingWallet()
    {
        $customer = $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($amount, $customerBalance['balance']);
    }

    public function testTransferAndVerifyCustomerBalance()
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $this->checkLastTransferEntity($customerPublicId, 'customer', $amount);
    }

    public function testTransferAndVerifyPricing()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->mockSplitzExperiment([
            "response" => [
                "variant" => [
                    "name" => 'sync',
                ]
            ]
        ]);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, 50000);

        $transfer = $this->startTest()['items'][0];

        $expectedTransfer = [
            'amount'      => 50000,
            'fees'        => 1180,
            'tax'         => 180,
        ];

        $this->assertArraySelectiveEquals($expectedTransfer, $transfer);

        $txn = $this->getTransferTxn($transfer['id']);

        // 2% fee plan defined - standard pricing
        $expectedTxn = [
            'amount'      => 50000,
            'fee'         => 1180,
            'tax'         => 180,
            'debit'       => 51180,
            'credit'      => 0
        ];

        $this->assertArraySelectiveEquals($expectedTxn, $txn);
    }

    public function testTransferCustomerUsageFirstTxn()
    {
        $customerValues = [
            'balance'       => 100,
            'monthly_usage' => 100,
        ];

        $customerBalance = $this->fixtures->create('customer:customer_balance', $customerValues);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->assertNull($this->getLastEntity('customer_transaction', true));

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $expected = [
            'balance'       => $amount + 100,
            'monthly_usage' => $amount,
        ];

        $this->assertArraySelectiveEquals($expected, $customerBalance);
    }

    public function testTransferAsync()
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->mockSplitzExperiment([
            "response" => [
                "variant" => [
                    "name" => 'async',
                ]
            ]
        ]);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, 50000);

        $transfer = $this->startTest()['items'][0];

        $expectedTransfer = [
            'amount'      => 50000,
            'status'      => 'created',
        ];

        $this->assertArraySelectiveEquals($expectedTransfer, $transfer);
    }

    protected function getTransferTxn(string $entityId)
    {
        $entity = Entity::getEntityClass('transfer');

        $entity::verifyIdAndSilentlyStripSign($entityId);

        $txn = $this->getEntities('transaction', ['entity_id' => $entityId], true);

        $this->assertEquals(1, count($txn['items']));

        return $txn['items'][0];
    }

    public function testTransferAndVerifyCustomerBalanceOnReverseShadow()
    {
        $this->mockSplitzTreatment([
            "experiment_id" => "OXHTK1FMuGzsTG",
            "id" => "10000000000000",
        ], [
            "response" => [
                "variant" => [
                    "name" => 'disabled',
                ]
            ]
        ]);

        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $this->checkLastTransferEntity($customerPublicId, 'customer', $amount);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction);
    }

    public function testCustomerTransferReverseShadowV2()
    {
        $this->mockSplitzTreatment([
            "experiment_id" => "OXHTK1FMuGzsTG",
            "id" => "10000000000000",
        ], [
            "response" => [
                "variant" => [
                    "name" => 'enabled',
                ]
            ]
        ]);

        $this->app['config']->set('application.ledger.enabled',true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times(1)
            ->andReturn([
                "body" => [
                    "accounts"  => [
                        [
                            "id"                => "sampleAccountID",
                            "name"              => "test name",
                            "status"            => "ACTIVATED",
                            "balance"           => "10000.000000",
                            "min_balance"       => "0.000000",
                            "merchant_id"       => "sampleMerchant",
                            "created_at"        => "1634027277",
                            "updated_at"        => "1634027277",
                            "entities"          => [
                                "account_type"      => ["payable"],
                                "fund_account_type" => ["merchant_balance"]
                            ]
                        ],
                        [
                            "id"                => "sampleAccountID",
                            "name"              => "test name",
                            "status"            => "ACTIVATED",
                            "balance"           => "0.000000",
                            "min_balance"       => "0.000000",
                            "merchant_id"       => "sampleMerchant",
                            "created_at"        => "1634027277",
                            "updated_at"        => "1634027277",
                            "entities"          => [
                                "account_type"      => ["payable"],
                                "fund_account_type" => ["merchant_fee_credits"]
                            ]

                        ],
                        [
                            "id"                => "sampleAccountID",
                            "name"              => "test name",
                            "status"            => "ACTIVATED",
                            "balance"           => "0.000000",
                            "min_balance"       => "0.000000",
                            "merchant_id"       => "sampleMerchant",
                            "created_at"        => "1634027277",
                            "updated_at"        => "1634027277",
                            "entities"          => [
                                "account_type"      => ["payable"],
                                "fund_account_type" => ["reward"]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->app->instance('ledger', $mockLedger);

        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals($amount, $transfer['amount']);

        $this->assertNull($transfer['transaction_id']);

        $outboxEntry = $this->getLastEntity('ledger_outbox', true);

        $this->assertEquals($transfer['id'], 'trf_' . $outboxEntry['entity_id']);

        $this->assertEquals('customer_transfer', $outboxEntry['entity_type']);

        $actualLedgerOutboxEntry = json_decode(base64_decode($outboxEntry['payload_serialized']), true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "customer_wallet_loading",
            "money_params" => [
                "base_amount" => "1000000",
                "merchant_balance_amount" => "1000000",
                "tax" => "0",
                "amount" => "1000000",
                "customer_wallet_amount" => "1000000",
                "transfer_commission"=> "0",
            ],
            "additional_params" => NULL,
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);

        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }
}

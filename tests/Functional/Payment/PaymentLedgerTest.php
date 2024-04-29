<?php

namespace Functional\Payment;

use Mockery;
use Carbon\Carbon;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Payment\Method;
use RZP\Services\KafkaMessageProcessor;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Traits\TestsWebhookEvents;

class PaymentLedgerTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InvoiceTestTrait;
    use TerminalTrait;
    use PaymentLinkTestTrait;
    use PaymentTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;
    use MocksRazorx;
    use MocksSplitz;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';
    const TEST_PLAN_ID  = '1000000000plan';

    const TEST_MID      = '10000000000000';
    const TEST_NCU_ID   = '10000000000ncu';
    const TEST_NCU_ID_2 = '10000000001ncu';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentLedgerTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->enableEmandate();
    }

    public function testNormalCapturePaymentWithCommissionMerchantBalanceDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "49000",
                "tax" => "0",
                "commission" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" =>  "true",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithFeeGreaterThanFeeCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "100.000000",
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
                ]
            );

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 40}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment['amount'] = '10142';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "10142",
                "gmv_amount" => "10142",
                "merchant_balance_amount" => "9788",
                "tax" => "54",
                "commission" => "300",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" =>  "true",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals(null, $actualLedgerOutboxEntry['money_params']['fee_credits']);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithCommissionFeeCreditsDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                "tax" => "0",
                "commission" => "1000",
                "fee_credits" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "fee_credits",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentAmountCreditsDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "50000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount"               => "50000",
                "gmv_amount"                => "50000",
                "merchant_balance_amount"   => "50000",
                "razorpay_rewards"          => "50000",
                "amount_credits"            => "50000"
            ],
            "additional_params" => [
                "credit_accounting" =>  "amount_credits_redemption",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testPaymentCaptureAmountCreditsLessThanAmountDeductFromFeeCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "50000.000000",
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
                                "balance"           => "500.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                "tax" => "0",
                "commission" => "1000",
                "fee_credits" => "1000"
            ],
            "additional_params" => [
                "credit_accounting" =>  "fee_credits"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testPaymentCaptureAmountCreditsLessThanAmountDeductFromFeeCreditsWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "50000.000000",
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
                                "balance"           => "500.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                "tax" => "0",
                "commission" => "1000",
                "fee_credits" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "fee_credits",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testPaymentCaptureAmountCreditsLessThanAmountDeductFromBalance()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times(1)
            ->andReturn([
                    "body" => [
                        "accounts"  => [
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => "50000.000000",
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
                                "balance"           => "500.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "49000",
                "tax" => "0",
                "commission" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithCommissionPostpaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                "tax" => "0",
                "commission" => "1000",
                "merchant_receivable_amount" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithAmountCreditsHigherPriorityOverFeeCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "90000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                'razorpay_rewards' => '50000',
                'amount_credits' => '50000'
            ],
            "additional_params" => [
                "credit_accounting" =>  "amount_credits_redemption",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithCommissionPostpaidHigherPriorityOverFeeAndAmountCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000",
                "tax" => "0",
                "commission" => "1000",
                "merchant_receivable_amount" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
                "fee_breakup" =>  "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithCustomerFeeBearer()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                ]
            );

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $order->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($order->getAmount() + $fee, $payment->getAmount());

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => strval($payment->getBaseAmount()),
                "gmv_amount" => strval($payment->getAmount()),
                "merchant_balance_amount" => strval($payment->getAmount() - $fee),
                "tax" => "0",
                "commission" => strval($fee),
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" =>  "true",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment->getPublicId(), $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithDynamicFeeBearerWithPostpaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                ]
            );

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order['amount'] + 120;
        $payment[Payment\Entity::FEE]             = 0;

        $payment['notes']['merchant_order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '10000',
                "tax" => "0",
                "commission" => '300',
                "merchant_receivable_amount" => '180',
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
                "fee_breakup" =>  "true",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithCustomerFeeBearerWithPostpaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                ]
            );

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $order->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($order->getAmount() + $fee, $payment->getAmount());

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => strval($payment->getBaseAmount()),
                "gmv_amount" => strval($payment->getAmount()),
                "merchant_balance_amount" => strval($payment->getAmount()),
                "tax" => "0",
                "commission" => strval($fee),
                "merchant_receivable_amount" => strval($fee),
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
                "fee_breakup" =>  "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment->getPublicId(), $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureFeeMerchantBalanceDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "merchant_balance_amount" => "1476",
                "tax" => "226",
                "commission" => "1250",
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureFeeMerchantBalanceDeductionWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'subscriptions', 'recurring_auto', 'raas', 'es_automatic']);
        $pricingPlanId = 'Nwq0KnbB6zptfM';
        $this->fixtures->pricing->createPricingPlanForFeeSplit($pricingPlanId);
        $this->fixtures->merchant->editPricingPlanId($pricingPlanId);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "merchant_balance_amount" => "5900",
                "tax" => "900",
                "commission" => "500",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '2000',
                "optimizer_commission" => '2500',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithDynamicFeeBearerWithPrepaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order['amount'] + 120;
        $payment[Payment\Entity::FEE]             = 0;

        $payment['notes']['merchant_order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '9820',
                "tax" => "0",
                "commission" => '300',
            ],
            "additional_params" => null,
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithDynamicFeeBearerWithPrepaidWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order['amount'] + 120;
        $payment[Payment\Entity::FEE]             = 0;

        $payment['notes']['merchant_order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '9820',
                "tax" => "0",
                "commission" => '300',
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" => 'true',
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithDynamicFeeBearerWithPrepaidFeeCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                ]
            );

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order['amount'] + 120;
        $payment[Payment\Entity::FEE]             = 0;

        $payment['notes']['merchant_order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '10000',
                "tax" => "0",
                "commission" => '300',
                "fee_credits" => '180',
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "credit_accounting" => "fee_credits",
                "fee_breakup" =>  "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithDynamicFeeBearerWithPrepaidAmountCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "20000.000000",
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
                ]
            );

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order['amount'] + 120;
        $payment[Payment\Entity::FEE]             = 0;

        $payment['notes']['merchant_order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '10000',
                "tax" => "0",
                "commission" => '120',
                'razorpay_rewards' => '10120',
                'amount_credits' => '10120'
            ],
            "additional_params" => [
                "credit_accounting" => "dfb_amount_credits"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        d($actualLedgerOutboxEntry);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalPaymentCaptureWithPrepaidMerchantDFBAndPaymentCFBWithFeeCredits()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'dynamic']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                ]
            );


        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $order->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($order->getAmount() + $fee, $payment->getAmount());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_",'',$payment['id'])]);
        $this->assertNotNull($payment);
        $payment->setFeeBearer('customer');
        $payment->saveOrFail();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '' . $payment->getAmount(),
                "gmv_amount" => '' . $payment->getAmount(),
                "merchant_balance_amount" => '' . ($payment->getAmount() - $fee),
                "tax" => "0",
                "commission" => '' . $fee,
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                'partner_commission' => '0',
                'partner_tax' => '0'
            ],
            "additional_params" => [
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals('pay_'.$payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureFeeCreditsDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $txn = $this->getDbLastEntity('transaction');

        $this->assertEquals($payment['id'], 'pay_'.$txn['entity_id']);
        $this->assertEquals(null, $txn['balance_id']);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "fee_credits" => "1476",
                "tax" => "226",
                "commission" => "1250",
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "credit_accounting" => "fee_credits"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG",
            "api_transaction_id" => $txn['id']
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($txn['id'], $actualLedgerOutboxEntry['api_transaction_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureFeeCreditsDeductionWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $txn = $this->getDbLastEntity('transaction');

        $this->assertEquals($payment['id'], 'pay_'.$txn['entity_id']);
        $this->assertEquals(null, $txn['balance_id']);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "fee_credits" => "1476",
                "tax" => "226",
                "commission" => "1250",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "credit_accounting" => "fee_credits",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG",
            "api_transaction_id" => $txn['id']
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($txn['id'], $actualLedgerOutboxEntry['api_transaction_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureAmountCreditsDeduction()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                'razorpay_rewards' => '50000',
                'amount_credits' => '50000'
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "credit_accounting"            =>  "amount_credits_redemption"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureWithVASFeatureEnabledMerchant()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "merchant_vas_amount" => "1476",
                "tax" => "226",
                "commission" => "1250"
            ],
            "additional_params" => [
                "direct_settlement_accounting"  =>  "direct_settlement",
                "accounting"                    => "vas_merchant_flow"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureWithVASFeatureEnabledMerchantWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "merchant_vas_amount" => "1476",
                "tax" => "226",
                "commission" => "1250",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "direct_settlement_accounting"  =>  "direct_settlement",
                "accounting"                    => "vas_merchant_flow",
                "fee_breakup"                   => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureWithVASFeatureEnabledWithPostpaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "merchant_vas_amount" => "1476",
                "tax" => "226",
                "commission" => "1250",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "direct_settlement_accounting"  =>  "direct_settlement",
                "accounting"                    => "vas_merchant_flow",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);

    }

    public function testDSPaymentCaptureWithPostpaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "merchant_receivable_amount" => "1476",
                "tax" => "226",
                "commission" => "1250",
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "credit_accounting" => "postpaid"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testDSPaymentCaptureWithPostpaidWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "10000.000000",
                                "min_balance"       => "10000.000000",
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
                                "balance"           => "60000.000000",
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
                ]
            );

        $payment = $this->createDirectSettlementPayment();

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "0",
                "merchant_receivable_amount" => "1476",
                "tax" => "226",
                "commission" => "1250",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
                "credit_accounting" => "postpaid",
                "fee_breakup" => "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    protected function createDirectSettlementPayment()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);

        return $payment;
    }

    public function testNormalCapturePaymentWithAsyncBalanceUpdateEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->merchant->addFeatures(['async_balance_update']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "49000",
                "tax" => "0",
                "commission" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" =>  "true"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testNormalCapturePaymentWithAsyncTxnFillDetailsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->merchant->addFeatures(['async_txn_fill_details']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "49000",
                "tax" => "0",
                "commission" => "1000",
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" =>  "true",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }


    // PG Ledger Acknowledgement Worker Tests

    private function createPaymentInReverseShadowDfbPostPaid(){
        $this->app['config']->set('applications.ledger.enabled', true);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '2000';

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;


        $paymentArray[Payment\Entity::AMOUNT]          = 10000 + 120;
        $paymentArray[Payment\Entity::FEE]             = 0;
        $paymentArray['notes']['merchant_order_id'] = $order->getPublicId();

        $paymentArray['order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($paymentArray);

        return $payment;
    }

    private function createPaymentInReverseShadow(){
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['amount'] = '2000';

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $payment = $this->doAuthAndCapturePayment($paymentArray);

        return $payment;
    }

    private function createPaymentInReverseShadowForFeeBreakup(){
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "1000.000000",
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
                ]
            );

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 2000]);

        $paymentArray = $this->getEmandatePaymentArray(IFSC::HDFC);
        $paymentArray['amount'] = 2000;
        $paymentArray['order_id'] = $order->getPublicId();
        $paymentArray['bank_account'] = [
            'account_number' => 'XXXXXXXXXXX5862',
            'ifsc'           => 'HDFC0001233',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $paymentDetails = $this->doAuthPayment($paymentArray);

        return $paymentDetails['razorpay_payment_id'];
    }

    private function createAuthorisedPaymentInReverseShadow(){
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '2000';

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $payment = $this->doAuthAndGetPayment($paymentArray);

        return $payment;
    }

    public function testMerchantCapturePaymentWithFeeCreditLoadingUsecase()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getPaymentArrayForFeeCreditLoading();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);




        $input = array('count' => 2);
        $ledgerOutboxEntities = $this->getEntities('ledger_outbox', $input, true);

        $this->assertGreaterThan(1, count($ledgerOutboxEntities['items']));

        $gatewayCapturedOutboxEntry = [];
        if ($ledgerOutboxEntities['count'])
            $gatewayCapturedOutboxEntry = $ledgerOutboxEntities['items'][1];


        $gatewayCapturedPayload = base64_decode($gatewayCapturedOutboxEntry['payload_serialized']);

        $actualGatewayCapturedLedgerOutboxEntry = json_decode($gatewayCapturedPayload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_gateway_captured",
            "money_params" => [
                "base_amount" => "50000",
                "amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "fee_credit_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualGatewayCapturedLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualGatewayCapturedLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualGatewayCapturedLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualGatewayCapturedLedgerOutboxEntry['money_params']);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "fee_credit_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testMerchantCapturePaymentWithRefundCreditLoadingUsecase()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['notes'] = [
            "type"          => "refund_credit",
            "merchant_id"   => "JVoa37lqQ0hMMv"
        ];

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);


        $input = array('count' => 2);
        $ledgerOutboxEntities = $this->getEntities('ledger_outbox', $input, true);

        $this->assertGreaterThan(1, count($ledgerOutboxEntities['items']));

        $gatewayCapturedOutboxEntry = [];
        if ($ledgerOutboxEntities['count'])
            $gatewayCapturedOutboxEntry = $ledgerOutboxEntities['items'][1];


        $gatewayCapturedPayload = base64_decode($gatewayCapturedOutboxEntry['payload_serialized']);

        $actualGatewayCapturedLedgerOutboxEntry = json_decode($gatewayCapturedPayload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_gateway_captured",
            "money_params" => [
                "base_amount" => "50000",
                "amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "refund_credit_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualGatewayCapturedLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualGatewayCapturedLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualGatewayCapturedLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualGatewayCapturedLedgerOutboxEntry['money_params']);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "refund_credit_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testMerchantCapturePaymentWithReserveBalanceLoadingUsecase()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['notes'] = [
            "type"          => "reserve_balance",
            "merchant_id"   => "JVoa37lqQ0hMMv"
        ];

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);


        $input = array('count' => 2);
        $ledgerOutboxEntities = $this->getEntities('ledger_outbox', $input, true);

        $this->assertGreaterThan(1, count($ledgerOutboxEntities['items']));

        $gatewayCapturedOutboxEntry = [];
        if ($ledgerOutboxEntities['count'])
            $gatewayCapturedOutboxEntry = $ledgerOutboxEntities['items'][1];


        $gatewayCapturedPayload = base64_decode($gatewayCapturedOutboxEntry['payload_serialized']);

        $actualGatewayCapturedLedgerOutboxEntry = json_decode($gatewayCapturedPayload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_gateway_captured",
            "money_params" => [
                "base_amount" => "50000",
                "amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "reserve_balance_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualGatewayCapturedLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualGatewayCapturedLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualGatewayCapturedLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualGatewayCapturedLedgerOutboxEntry['money_params']);

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => "50000",
                "gmv_amount" => "50000",
                "merchant_balance_amount" => "50000"
            ],
            "additional_params" => [
                "gmv_accounting"    => "reserve_balance_gmv"
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function getPaymentArrayForFeeCreditLoading()
    {
        //
        // default payment object
        //
        $payment = [
            'amount'            => '50000',
            'currency'          => 'INR',
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => [
                "type"          => "fee_credit",
                "merchant_id"   => "JVoa37lqQ0hMMv"
            ],
            'description'       => 'random description',
            'bank'              => 'UCBA',
        ];

        $payment['card'] = array(
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }

    private function getKafkaEventPayload($journal, $request = null, $msg = "")
    {
        $kafkaPayload = [
            "request"=> $request,
            "response"=> $journal,
            "error_response"=> [
                "msg"=> $msg
            ]
        ];

        $serializedPayload = base64_encode(json_encode($kafkaPayload));

        return  [
            "before"=> null,
            "after"=> [
                "id"=> "LLJMDzemcsroDp",
                "payload_serialized"=> $serializedPayload,
                "created_at"=> 1677466532,
                "updated_at"=> 1677466532
            ],
            "source"=> [
                "version"=> "2.1.1.Final",
                "connector"=> "postgresql",
                "name"=> "internal_db_stage_ledger_payments_test_outbox",
                "ts_ms"=> 1677466532793,
                "snapshot"=> "false",
                "db"=> "stage_ledger_pg_test",
                "sequence"=> "[\"60869735408\",\"60869737336\"]",
                "schema"=> "public",
                "table"=> "outbox_jobs_api_default",
                "txId"=> 242246764,
                "lsn"=> 60869737336,
                "xmin"=> null
            ],
            "op"=> "c",
            "ts_ms"=> 1677466533213,
            "transaction"=> null,
            "_record_source"=> "debezium_postgres"
        ];
    }

    // payment gateway capture

    private function getPaymentGatewayCapturedJournalResponsePayload($transactorId)
    {
        return [
            "id"=> "LLy5PLL9cCZhnr",
            "created_at"=> 1677609963,
            "updated_at"=> 1677609963,
            "amount"=> "2000",
            "base_amount"=> "2000",
            "currency"=> "INR",
            "tenant"=> "PG",
            "transactor_id"=> $transactorId,
            "transactor_event"=> "payment_gateway_captured",
            "transaction_date"=> 1677609961,
            "ledger_entry"=> [
                [
                    "id"=> "LLy5PLSZjuPgtq",
                    "created_at"=> 1677609963,
                    "updated_at"=> 1677609963,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLy5PLL9cCZhnr",
                    "account_id"=> "Jjpg2D3rgPjGWs",
                    "amount"=> "2000",
                    "base_amount"=> "2000",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "26700.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_gmv"
                        ]
                    ]
                ],
                [
                    "id"=> "LLy5PLSaxfb3Or",
                    "created_at"=> 1677609963,
                    "updated_at"=> 1677609963,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLy5PLL9cCZhnr",
                    "account_id"=> "Iu0hzNwbmQ6D1n",
                    "amount"=> "2000",
                    "base_amount"=> "2000",
                    "type"=> "debit",
                    "currency"=> "INR",
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "gateway"
                        ],
                        "gateway"=> [
                            "sharp"
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testKafkaSuccessForPaymentGatewayCaptureEventForAuthorisedPayment()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createAuthorisedPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $journal = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $this->assertEmpty($txn['fee']);
        $this->assertEmpty($txn['tax']);
        $this->assertEmpty($txn['credit']);
        $this->assertNull($txn['balance_id']);
        $this->assertNull($txn['balance_updated']);

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'authorized');
        $this->assertEquals($payment['fee'], 0);
        $this->assertEquals($payment['tax'], 0);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);

        $this->assertNotNull($ledgerOutboxEntity);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $this->assertEquals($actualLedgerOutboxEntry['api_txn_id'], $txn->getId(), 'transaction id does not match with api_txn_id');

        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');

        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testKafkaSuccessForPaymentGatewayCaptureEventCreditLoading()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $journal = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $payment = $this->getDbLastEntity('payment');

        $this->assertNotNull($payment);

        $this->assertEquals($txn['balance_updated'],false);
        $this->assertEquals($txn['fee'],0);
        $this->assertEquals($txn['tax'],0);
        $this->assertEquals($txn['debit'],0);
        $this->assertEquals($txn['credit'],0);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);

        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');

        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    // PaymentMerchantCaptured

    private function getPaymentMerchantCapturedJournalResponsePayload($transactorId, $journalId = "LLJMPzXXyjC93B")
    {
        return [
            "id"=> $journalId,
            "created_at"=> 1677466532,
            "updated_at"=> 1677466532,
            "amount"=> "2000",
            "base_amount"=> "2000",
            "currency"=> "INR",
            "tenant"=> "PG",
            "transactor_id"=> $transactorId,
            "transactor_event"=> "payment_merchant_captured",
            "transaction_date"=> 1677466530,
            "ledger_entry"=> [
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "40",
                    "base_amount"=> "40",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "rzp_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXYypsmcx",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUAEYlvYbEG",
                    "amount"=> "0",
                    "base_amount"=> "0",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "1284.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "rzp_gst"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZQZynxG",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jjpg2D3rgPjGWs",
                    "amount"=> "2000",
                    "base_amount"=> "2000",
                    "type"=> "debit",
                    "currency"=> "INR",
                    "balance"=> "23700.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_gmv"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZr1H4OB",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUD9PmJeNPk",
                    "amount"=> "1960",
                    "base_amount"=> "1960",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "468259.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_balance"
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getPaymentMerchantCapturedJournalResponsePayloadWithFeeSplit($transactorId, $journalId = "LLJMPzXXyjC93B")
    {
        return [
            "id"=> $journalId,
            "created_at"=> 1677466532,
            "updated_at"=> 1677466532,
            "amount"=> "2000",
            "base_amount"=> "2000",
            "currency"=> "INR",
            "tenant"=> "PG",
            "transactor_id"=> $transactorId,
            "transactor_event"=> "payment_merchant_captured",
            "transaction_date"=> 1677466530,
            "ledger_entry"=> [
                [
                    "id"=> "LLJMDzXYypsmcx",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUAEYlvYbEG",
                    "amount"=> "46",
                    "base_amount"=> "46",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "1284.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "rzp_gst"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZQZynxG",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jjpg2D3rgPjGWs",
                    "amount"=> "2000",
                    "base_amount"=> "2000",
                    "type"=> "debit",
                    "currency"=> "INR",
                    "balance"=> "23700.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_gmv"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZr1H4OB",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUD9PmJeNPk",
                    "amount"=> "1694",
                    "base_amount"=> "1694",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "468259.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_balance"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "20",
                    "base_amount"=> "20",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "rzp_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "0",
                    "base_amount"=> "0",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "upi_inapp_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "60",
                    "base_amount"=> "60",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "recurring_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "0",
                    "base_amount"=> "0",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "magic_checkout_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "80",
                    "base_amount"=> "80",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "esautomatic_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "100",
                    "base_amount"=> "100",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "optimizer_commission"
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventDfbPostPaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $splitzInput = [
            'experiment_id' => 'NwlHNFPY1mPCBm',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'control',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $payment = $this->createPaymentInReverseShadowDfbPostPaid();


        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '10000',
                "merchant_receivable_amount" => '180',
                "tax" => "0",
                "commission" => '300'
            ],
            "additional_params" => [
                "credit_accounting" => 'postpaid'
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualOutboxEntry);
        $this->assertEquals($paymentId, $actualOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualOutboxEntry['money_params']);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $journal['ledger_entry'][0]['amount'] = 420;
        $journal['ledger_entry'][1]['amount'] = 0;
        $journal['ledger_entry'][2]['amount'] = 10120;
        $journal['ledger_entry'][3]['amount'] = 10000;

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee'] + $txn['reference7']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax'] + $txn['reference8']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['reference7']-$txn['reference8']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventDfbPostPaidWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $payment = $this->createPaymentInReverseShadowDfbPostPaid();

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => '10120',
                "gmv_amount" => '10120',
                "merchant_balance_amount" => '10000',
                "merchant_receivable_amount" => '180',
                "tax" => "0",
                "commission" => '300',
                "upi_inapp_commission" => '0',
                "recurring_commission" => '0',
                "magic_checkout_commission" => '0',
                "esautomatic_commission" => '0',
                "optimizer_commission" => '0',
                "partner_commission" => '0',
                "partner_tax" => '0'
            ],
            "additional_params" => [
                "fee_breakup" => 'true',
                "credit_accounting" => 'postpaid',
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualOutboxEntry);
        $this->assertEquals($paymentId, $actualOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualOutboxEntry['money_params']);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $journal['ledger_entry'][0]['amount'] = 420;
        $journal['ledger_entry'][1]['amount'] = 0;
        $journal['ledger_entry'][2]['amount'] = 10120;
        $journal['ledger_entry'][3]['amount'] = 10000;

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee'] + $txn['reference7']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax'] + $txn['reference8']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['reference7']-$txn['reference8']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEvent()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'captured');
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);
        $this->assertEquals($payment['amount']-$payment['fee'], $txn['credit']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    // optimizer, esautomatic, recurring
    public function testKafkaSuccessForPaymentMerchantCaptureEventWithFeeSplit()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'subscriptions', 'recurring_auto', 'raas', 'es_automatic']);
        $pricingPlanId = 'Nwq0KnbB6zptfL';
        $this->fixtures->pricing->createPricingPlanForFeeSplit($pricingPlanId);
        $this->fixtures->merchant->editPricingPlanId($pricingPlanId);

        $paymentId = $this->createPaymentInReverseShadowForFeeBreakup();

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayloadWithFeeSplit($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'captured');
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);
        $this->assertEquals($payment['amount']-$payment['fee'], $txn['credit']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $totalFee = intval($journal['ledger_entry'][3]['amount']) + intval($journal['ledger_entry'][4]['amount']) + intval($journal['ledger_entry'][5]['amount']) + intval($journal['ledger_entry'][6]['amount']) + intval($journal['ledger_entry'][7]['amount']) + intval($journal['ledger_entry'][8]['amount']);
        $this->assertEquals($totalFee, $txn['fee']-$txn['tax']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']-$txn['fee']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithAsyncBalanceUpdateEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'async_balance_update' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithAsyncTxnFillDetailsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'async_txn_fill_details' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertTrue($txn->isBalanceUpdated());

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithBothAsyncEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'async_txn_fill_details','async_balance_update' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithShadowEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'pg_ledger_journal_writes' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithShadowAndBothAsyncEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'pg_ledger_journal_writes', 'async_txn_fill_details','async_balance_update' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    private function  getJournalRequestPayload($transactorId): array
    {
        return [
            "tenant"=> "PG",
            "mode"=> "",
            "idempotency_key"=> "2614f0fe-b798-11ed-a1aa-c24b7ef77506",
            "merchant_id"=> "JVoa37lqQ0hMMv",
            "currency"=> "INR",
            "amount"=> "2000",
            "base_amount"=> "2000",
            "commission"=> "",
            "tax"=> "",
            "transactor_id"=> $transactorId,
            "transactor_event"=> "payment_merchant_captured",
            "transaction_date"=> 1677609961,
            "api_transaction_id"=> "",
            "notes"=> null,
            "additional_params"=> null,
            "identifiers"=> [
                "gateway"=> "sharp"
            ],
            "ledger_integration_mode"=> "reverse-shadow",
            "money_params"=> []
        ];
    }

    public function testKafkaNonRetryableValidationFailures()
    {
        $data = $this->testData[__FUNCTION__];
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        foreach ($data as $errorMessage)
        {
            $payment = $this->createPaymentInReverseShadow();

            $paymentId = $payment['id'];

            $request = $this->getJournalRequestPayload($paymentId);

            $kafkaEventPayload = $this->getKafkaEventPayload(null, $request, $errorMessage);

            (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

            $txn = $this->getDbLastEntity('transaction');

            $this->assertNotNull($txn, 'authorised txn should be present');

            $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

            $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
            $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        }
    }

     public  function testKafkaRecordAlreadyExistsFailure(){

         $data = $this->testData[__FUNCTION__];

         $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

         $payment = $this->createPaymentInReverseShadow();

         $paymentId = $payment['id'];

         $entry = $this->getDbLastEntity('ledger_outbox');
         $this->assertNotNull( $entry);
         $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

         $payload = base64_decode($entry['payload_serialized']);
         $actualOutboxEntry = json_decode($payload, true);
         $apiTxnId = $actualOutboxEntry['api_transaction_id'];
         $this->assertNotNull( $apiTxnId);

         $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

         $journalId = $journal['id'];

         $kafkaEventPayload = $this->getKafkaEventPayload($journal);

         (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

         $txn = $this->getDbLastEntity('transaction');

         $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);

         $this->assertEquals($journalId, $txn['id']);

         $this->app['config']->set('applications.ledger.enabled', true);

         $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

         $this->app->instance('ledger', $mockLedger);

         $mockLedger->shouldReceive('fetchByTransactor')
             ->times(1)
             ->andReturn([
                     "body" => $journal
                 ]
             );

         $request = $this->getJournalRequestPayload($paymentId);

         $kafkaEventPayload = $this->getKafkaEventPayload(null, $request, $data[0]);

         (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

         $txnEntities = $this->getDbEntities('transaction', ['entity_id' => str_replace('pay_','',$paymentId)]);

         $this->assertEquals(1, count($txnEntities), 'more than 1 txn created for same payment');

         $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

         $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');

         $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

     }

    public function testKafkaRetryableFailures()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        foreach ($data as $errorMessage)
        {

            $payment = $this->createPaymentInReverseShadow();

            $paymentId = $payment['id'];

            $request = $this->getJournalRequestPayload($paymentId);

            $kafkaEventPayload = $this->getKafkaEventPayload(null, $request, $errorMessage);

            (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

            $txn = $this->getDbLastEntity('transaction');

            $this->assertNotNull($txn, 'authorised txn should be present');

            $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

            $this->assertEquals($ledgerOutboxEntity['is_deleted'], 0, 'outbox entry  soft deleted');
            $this->assertNull($ledgerOutboxEntity['deleted_at'], 'outbox entry  soft deleted');

        }
    }

    private function createPaymentForCron($mockLedger){

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
                                "balance"           => "1000.000000",
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
                                "balance"           => "1000.000000",
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
                ]
            );

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '2000';

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $payment = $this->doAuthAndCapturePayment($paymentArray);

        return $payment;
    }

    public function testCronRetrySuccessForPaymentMerchantCaptureEvent()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentForCron($mockLedger);

        $paymentId = $payment['id'];

        $this->assertNull($payment['transaction_id']);

        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox');

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[0]['id'], ['created_at' => $createdAtTimestamp]);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[1]['id'], ['created_at' => $createdAtTimestamp]);

        $this->assertEquals(2, count($ledgerOutboxEntities));

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $gatewayCaptureJournalResponse = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);
        $merchantCaptureJournalResponse = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);
        $payload = base64_decode($ledgerOutboxEntities[0]['payload_serialized']);
        $actualGatewayCaptureLedgerOutboxEntry = json_decode($payload, true);
        $merchantCaptureJournalResponse['id'] = $actualGatewayCaptureLedgerOutboxEntry['api_txn_id'];

        $mockLedger->shouldReceive('createJournal')
            ->times(2)
            ->andReturnValues([
                [
                    'code' => 200,
                    'body' => $gatewayCaptureJournalResponse,
                ],
                [
                    'code' => 200,
                    'body' => $merchantCaptureJournalResponse
                ]
            ]);

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbLastEntity('transaction');

        $gatewayCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);
        $merchantCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        // gateway capture assertions
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['retry_count'], 1);
        $this->assertEquals('payment', $gatewayCaptureLedgerOutboxEntity['entity_type']);
        $this->assertNotNull($gatewayCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        // merchant capture assertions
        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($merchantCaptureJournalResponse['id'], $txn['id']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);

        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['retry_count']);
        $this->assertEquals('payment', $merchantCaptureLedgerOutboxEntity['entity_type']);
        $this->assertNotNull($merchantCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testCronRetrySuccessForNonReverseShadowMerchant()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentForCron($mockLedger);

        $paymentId = $payment['id'];

        $this->assertNull($payment['transaction_id']);

        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox');

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[0]['id'], ['created_at' => $createdAtTimestamp]);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[1]['id'], ['created_at' => $createdAtTimestamp]);

        $this->assertEquals(2, count($ledgerOutboxEntities));

        $this->ba->cronAuth();

        $this->fixtures->merchant->removeFeatures(['pg_ledger_reverse_shadow']);

        $this->startTest();

        $gatewayCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);
        $merchantCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        // gateway capture assertions
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['retry_count'], 1);
        $this->assertNotNull($gatewayCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['retry_count']);
        $this->assertNotNull($merchantCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testNormalPaymentCaptureWithHDFCNonDSSurcharge()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $org = $this->fixtures->org->createHdfcOrg();

        $this->mockRazorxTreatmentV2('hdfc_vas_surcharge_2', 'on');

        $merchant = $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Org\Entity::HDFC_ORG_ID
            ]
        );

        $terminal =   $this->fixtures->create('terminal', [ 'merchant_id' => '10000000000000', 'gateway' => 'hdfc']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $attributes = [
            'name'        => 'hdfc_vas_cards_surcharge',
            'entity_id'   => $merchant->getOrgId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                                "balance"           => "1000.000000",
                                "min_balance"       => "1000.000000",
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
                ]
            );

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $payment = $this->getDefaultPaymentArray();

        $amount = $payment['amount'];

        $payment = $this->getFeesForPayment($payment)['input'];

        $response = $this->doAuthPayment($payment);

        $this->capturePayment($response['razorpay_payment_id'], $amount);

        $payment = $this->getDbLastEntity('payment');


        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "payment_merchant_captured",
            "money_params" => [
                "base_amount" => sprintf("%s", $payment->getBaseAmount()),
                "gmv_amount" => sprintf("%s", $payment->getAmount()),
                "merchant_balance_amount" => sprintf("%s", $payment->getAmount()),
                "tax" => "0",
                "commission" => "0",
            ],
            "additional_params" => [
                "accounting" =>  "hdfc_non_ds_surcharge_flow",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment->getPublicId(), $actualLedgerOutboxEntry['transactor_id']);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
    }

    public function testKafkaSuccessForPaymentMerchantFirstAndGatewayCaptureEventNext()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'captured');
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);
        $this->assertEquals($payment['amount']-$payment['fee'], $txn['credit']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        //gateway capture comes after merchant capture
        $journal = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $payment = $this->getDbLastEntity('payment');

        $this->assertNotNull($payment);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);

    }

    public function testKafkaSuccessForPaymentGatewayFirstAndMerchantCaptureEventNext()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        //gateway capture comes before merchant capture
        $journal = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $payment = $this->getDbLastEntity('payment');

        $this->assertNotNull($payment);

        // Note: as payment is captured before ack received and fee, tax is set in payment after outbox push, fee tax will not be 0
         $this->assertEquals(0, $txn['fee']);
         $this->assertEquals(0,$txn['tax']);
        $this->assertEquals(0,$txn['credit']);
        $this->assertNull($txn['balance_id']);
        $this->assertFalse($txn->isBalanceUpdated());

        // merchant capture ack
        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'captured');
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);
        $this->assertEquals($payment['amount']-$payment['fee'], $txn['credit']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    public function testInternalTxnCronInReverseShadowBlocked()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();
        $txn = $this->getDbEntity('transaction', ['entity_id' => str_replace("pay_",'',$payment['id'])]);
        $this->assertNotNull($txn);
        $this->assertNull($txn['balance_updated']);

        $request = [
            'url' => '/internal/transactions/cron',
            'method' => 'POST',
            'content' =>  ['payments_arr' => $payment['id'],]
        ];

        $this->app['config']->set('applications.ledger.enabled', true);

        $this->ba->cronAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);
        $this->assertCount(0,$response["success"]);
        $this->assertCount(1,$response["failures"]);
    }

    public function testInternalTxnCronInShadowSuccess()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $request = [
            'url' => '/internal/transactions/cron',
            'method' => 'POST',
            'content' =>  ['payments_arr' => $payment['id'],]

        ];
        $this->fixtures->merchant->removeFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);
        $this->assertCount(0,$response["failures"]);
        $this->assertCount(1,$response["success"]);
        $txn = $this->getDbLastEntity('transaction');
        $this->assertNotNull($txn);
    }

    public function testCronRetrySuccessForPaymentMerchantCaptureEventWithEntityTypeNull()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentForCron($mockLedger);

        $paymentId = $payment['id'];

        $this->assertNull($payment['transaction_id']);

        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox');

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[0]['id'], ['created_at' => $createdAtTimestamp,'entity_type' => null]);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntities[1]['id'], ['created_at' => $createdAtTimestamp,'entity_type' => null]);

        $this->assertEquals(2, count($ledgerOutboxEntities));

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $gatewayCaptureJournalResponse = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);
        $merchantCaptureJournalResponse = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);
        $payload = base64_decode($ledgerOutboxEntities[0]['payload_serialized']);
        $actualGatewayCaptureLedgerOutboxEntry = json_decode($payload, true);
        $merchantCaptureJournalResponse['id'] = $actualGatewayCaptureLedgerOutboxEntry['api_txn_id'];

        $mockLedger->shouldReceive('createJournal')
            ->times(2)
            ->andReturnValues([
                [
                    'code' => 200,
                    'body' => $gatewayCaptureJournalResponse,
                ],
                [
                    'code' => 200,
                    'body' => $merchantCaptureJournalResponse
                ]
            ]);

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbLastEntity('transaction');

        $gatewayCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);
        $merchantCaptureLedgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        // gateway capture assertions
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertEquals($gatewayCaptureLedgerOutboxEntity['retry_count'], 1);
        $this->assertNotNull($gatewayCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        // merchant capture assertions
        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($merchantCaptureJournalResponse['id'], $txn['id']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($merchantCaptureJournalResponse['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);

        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertEquals(1, $merchantCaptureLedgerOutboxEntity['retry_count']);
        $this->assertNotNull($merchantCaptureLedgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testKafkaSuccessForPaymentMerchantCaptureEventEarlyDispatchToSettlement()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'new_settlement_service']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        $creditTxnPayload = [
            "id"=> $journalId,
            "merchant_id"=> "10000000000000",
            "source_id"=> str_replace("pay_","",$paymentId),
            "source_type"=> "payment",
            "balance_type"=> "PRIMARY",
            "currency"=> "INR",
            "credit"=> 1960,
            "debit"=> 0,
            "fee"=> 40,
            "tax"=> 0,
            "settled_by"=> "Razorpay",
            "on_hold"=> null,
            "on_hold_reason"=> "",
            "meta"=> [
                "method"=> "card",
                "international"=> false
            ]
        ];

        $this->mockSns($creditTxnPayload);

        $this->mockRazorxTreatmentV2(RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_PAYMENTS, 'on');

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        //s($txn);
        $this->assertNotNull($txn);

        $this->assertNotNull($txn['fee']);
        $this->assertNotNull($txn['tax']);
        $this->assertNotNull($txn['credit']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertTrue($txn->isBalanceUpdated());

        $payment = $this->getDbEntity('payment', ['id' => str_replace("pay_", "", $paymentId)]);

        $this->assertNotNull($payment);

        $this->assertEquals($payment['status'], 'captured');
        $this->assertEquals($payment['fee'], $txn['fee']);
        $this->assertEquals($payment['tax'], $txn['tax']);
        $this->assertEquals($payment['amount']-$payment['fee'], $txn['credit']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);

        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['fee']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][2]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount']-$txn['fee']-$txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

    }

    protected function mockSns($creditTxnPayload)
    {
        $sns = Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->times(1)
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function ($input) use ($creditTxnPayload)
            {
                $json_decoded_input = json_decode($input, true);

                if ($json_decoded_input['id'] === $creditTxnPayload['id'])
                {
                    $this->assertEquals($creditTxnPayload['merchant_id'], $json_decoded_input['merchant_id']);
                    $this->assertEquals($creditTxnPayload['source_id'], $json_decoded_input['source_id']);
                    $this->assertEquals($creditTxnPayload['source_type'], $json_decoded_input['source_type']);
                    $this->assertEquals($creditTxnPayload['balance_type'], $json_decoded_input['balance_type']);
                    $this->assertEquals($creditTxnPayload['currency'], $json_decoded_input['currency']);
                    $this->assertEquals($creditTxnPayload['credit'], $json_decoded_input['credit']);
                    $this->assertEquals($creditTxnPayload['debit'], $json_decoded_input['debit']);
                    $this->assertEquals($creditTxnPayload['fee'], $json_decoded_input['fee']);
                    $this->assertEquals($creditTxnPayload['tax'], $json_decoded_input['tax']);
                    $this->assertEquals($creditTxnPayload['settled_by'], $json_decoded_input['settled_by']);
                    $this->assertEquals($creditTxnPayload['on_hold'], $json_decoded_input['on_hold']);
                    $this->assertEquals($creditTxnPayload['on_hold_reason'], $json_decoded_input['on_hold_reason']);
                    $this->assertEquals($creditTxnPayload['meta']['method'], $json_decoded_input['meta']['method']);
                    $this->assertEquals($creditTxnPayload['meta']['international'], $json_decoded_input['meta']['international']);
                    $this->assertEquals($creditTxnPayload['meta']['origin_method'], $json_decoded_input['meta']['origin_method']);
                }

                return $input;
            });

        $this->app->instance('sns', $sns);
    }

}

<?php

namespace Functional\Payment;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant\FeeBearer;
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
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "fee_credits",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
    }

    public function testNormalCapturePaymentWithCommissionAmountCreditsDeduction()
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "amount_credits",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "amount_credits",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
                "base_amount" => $payment->getBaseAmount(),
                "gmv_amount" => $payment->getAmount(),
                "merchant_balance_amount" => $payment->getAmount() - $fee,
                "tax" => "0",
                "commission" => $fee,
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment->getPublicId(), $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "additional_params" => [
                "credit_accounting" =>  "postpaid",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment->getPublicId(), $actualLedgerOutboxEntry['transactor_id']);
    }

    public function testDSPaymentCaptureFeeMerchantBalanceDeduction()
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

    }

    public function testDSPaymentCaptureFeeCreditsDeduction()
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
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);

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
                "merchant_balance_amount" => "0",
                "tax" => "0",
                "commission" => "0",
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);

    }

    public function testDSPaymentCaptureWithVASFeatureEnabled()
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
                "base_amount" => "0",
                "merchant_balance_amount" => "0",
                "tax" => "0",
                "commission" => "0",
            ],
            "additional_params" => [
                "direct_settlement_accounting" =>  "direct_settlement",
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($payment['id'], $actualLedgerOutboxEntry['transactor_id']);

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
                "base_amount" => "0",
                "merchant_receivable_amount" => "0",
                "tax" => "0",
                "commission" => "0",
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

    }

    public function testDSPaymentCaptureWithPostpaid()
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
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
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
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($paymentFromResponse['id'], $actualLedgerOutboxEntry['transactor_id']);
    }


    /* PG Ledger Acknowledgement Worker Tests */

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

    private function createCreditLoadingPaymentInReverseShadow(){
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

        $paymentArray = $this->getPaymentArrayForFeeCreditLoading();

        $paymentArray['amount'] = '2000';

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $payment = $this->doAuthAndCapturePayment($paymentArray);

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

    public function testKafkaSuccessForPaymentGatewayCaptureEvent()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $journal = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);

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

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_gateway_captured']);

        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');

        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    // PaymentMerchantCaptured

    private function getPaymentMerchantCapturedJournalResponsePayload($transactorId)
    {
        return [
            "id"=> 'LLJMDzRZGnZhGA',
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

    public function testKafkaSuccessForPaymentMerchantCaptureEventDfbPostPaid()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $payment = $this->createPaymentInReverseShadowDfbPostPaid();

        $paymentId = $payment['id'];

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

        $journalId = $journal['id'];

        $journal['ledger_entry'][0]['amount'] = 300;
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

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

    public function testKafkaSuccessForPaymentMerchantCaptureEventWithAsyncBalanceUpdateEnabled()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'async_balance_update' ]);

        $payment = $this->createPaymentInReverseShadow();

        $paymentId = $payment['id'];

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

            $this->assertNull($txn, 'txn should not be created');

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

         $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);

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

            $this->assertNull($txn, 'txn should not be created');

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

        $gatewayCaptureJournalResponse = $this->getPaymentGatewayCapturedJournalResponsePayload($paymentId);
        $merchantCaptureJournalResponse = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId);
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
}

<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Models\Ledger\ReverseShadow\Transfers\Core;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class PaymentWalletTransferLedgerTest extends TestCase
{
    use DbEntityFetchTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

    }

    public function initialiseLedger($merchantBalance = 0, $feeCredits = 0, $amountCredits = 0, $fetchAccountRequests = 1)
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times($fetchAccountRequests)
            ->andReturn([
                    "body" => [
                        "accounts"  => [
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => sprintf('%s.000000', $merchantBalance),
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
                                "balance"           => sprintf('%s.000000', $feeCredits),
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
                                "balance"           => sprintf('%s.000000', $amountCredits),
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

        return $mockLedger;
    }
    public function testCreateTransactionMessage() {
        $this->initialiseLedger(50000, 0, 0, 1);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "customer_wallet_loading",
            "money_params" => [
                "base_amount" => "10000",
                "merchant_balance_amount" => "10272",
                "tax" => "36",
                "amount" => "10000",
                "customer_wallet_amount" => "10000",
                "transfer_commission"=> "236",
            ],
            "additional_params" => NULL,
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $customer = $this->fixtures->create('customer');

        $this->fixtures->create('transfer', [
            'amount'      => 10000,
            'source_id'   => '10000000000000',
            'source_type' => 'payment',
            'to_id'       => $customer['id'],
            'to_type'     => 'customer'
        ]);

        $transfer = $this->getDbLastEntity('transfer');

        $actual = (new Core())->createTransactionMessageForCustomerWalletLoadingV2($transfer);

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actual);

        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actual['money_params']);

        $this->assertNull($actual['additional_params']);
    }

    public function testCreateTransactionMessageForAmountCredits() {
        $this->initialiseLedger(50000, 0, 10000, 1);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "customer_wallet_loading",
            "money_params" => [
                "amount"                  => "10000",
                "base_amount"             => "10000",
                "amount_credits"          => "10000",
                "merchant_balance_amount" => "10000",
                "customer_wallet_amount"  => "10000",
            ],
            "additional_params" => [
                'credit_accounting'       => 'amount_credits_redemption'
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $customer = $this->fixtures->create('customer');

        $this->fixtures->create('transfer', [
            'amount'      => 10000,
            'source_id'   => '10000000000000',
            'source_type' => 'payment',
            'to_id'       => $customer['id'],
            'to_type'     => 'customer'
        ]);

        $transfer = $this->getDbLastEntity('transfer');

        $actual = (new Core())->createTransactionMessageForCustomerWalletLoadingV2($transfer);

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actual);

        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actual['money_params']);

        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actual['additional_params']);
    }

    public function testCreateTransactionMessageForFeeCredits() {
        $this->initialiseLedger(50000, 10000, 0, 1);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "customer_wallet_loading",
            "money_params" => [
                "base_amount" => "10000",
                "merchant_balance_amount" => "10000",
                "tax" => "36",
                "amount" => "10000",
                "customer_wallet_amount" => "10000",
                "transfer_commission"=> "236",
                "fee_credits" => "272"
            ],
            "additional_params" => [
                'credit_accounting'       => 'fee_credits'
            ],
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $customer = $this->fixtures->create('customer');

        $this->fixtures->create('transfer', [
            'amount'      => 10000,
            'source_id'   => '10000000000000',
            'source_type' => 'payment',
            'to_id'       => $customer['id'],
            'to_type'     => 'customer'
        ]);

        $transfer = $this->getDbLastEntity('transfer');

        $actual = (new Core())->createTransactionMessageForCustomerWalletLoadingV2($transfer);

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actual);

        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actual['money_params']);

        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actual['additional_params']);
    }
}

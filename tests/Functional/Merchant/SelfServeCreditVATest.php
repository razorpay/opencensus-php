<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use RZP\Mail\Merchant\CreditsAdditionSuccess;
use RZP\Mail\Merchant\ReserveBalanceAdditionSuccess;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Traits\TestsWebhookEvents;

class SelfServeCreditVATest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected $user = NULL;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SelfServeCreditsTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->initializeMerchantAndBankAccount();
    }

    //Tests for fund Addition via Bank Transfer

    public function initializeMerchantAndBankAccount()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000001',
            'business_type' => 1
        ]);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create('bank_account', ['merchant_id' => '10000000000001','ifsc_code'  => 'UTIB0CCH274', "entity_id" => '10000000000001']);

        $this->user = $this->fixtures->user->createUserForMerchant('10000000000001');

    }

    public function testVACreation($creditType='refund_credit')
    {
        $user = $this->getDbLastEntity('user');

        $this->testData[__FUNCTION__]['request']['content']['type'] = $creditType;

        $this->ba->proxyAuth('rzp_test_10000000000001', $this->user->getId());

        $response = $this->startTest();

        return $response;
    }

    public function testValidateVACreation()
    {
        $response = $this->testVACreation();

        $payerBA = $this->getDbLastEntity('bank_account');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($response['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['account_number'], $payerBA->getAccountNumber());

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['ifsc'], $payerBA->getIfscCode());

    }

    public function testValidateVACreationForCreditWhenAlreadyPresent()
    {
        $va_creation_response = $this->testVACreation();

        $va_fetch_response = $this->testVACreation();

        $this->assertEquals($va_fetch_response['id'] , $va_creation_response['id']);

        $this->assertEquals($va_fetch_response['notes']['merchant_id'] , $va_creation_response['notes']['merchant_id']);

        $this->assertEquals($va_fetch_response['notes']['type'] , $va_creation_response['notes']['type']);

        $this->assertEquals($va_fetch_response['receivers'][0]['id'] , $va_creation_response['receivers'][0]['id']);

        $this->assertEquals($va_fetch_response['allowed_payers'][0]['bank_account']['account_number'] , $va_creation_response['allowed_payers'][0]['bank_account']['account_number']);

        $merchant = $this->getDbEntityById('merchant', '10000000000001');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($va_creation_response['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);
    }

    public function testVAIdsCreationOfDifferentTypes()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000000');

        $vaCreationRefund = $this->testVACreation();

        $vaCreationFee = $this->testVACreation('fee_credit');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($vaCreationRefund['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($vaCreationFee['id'], $merchantDetails->getFundAdditionVAIds()['fee_credit']);

        $this->assertEquals('10000000000001' , $vaCreationRefund['notes']['merchant_id']);

        $this->assertEquals('refund_credit' , $vaCreationRefund['notes']['type']);

        $this->assertEquals('10000000000001' , $vaCreationFee['notes']['merchant_id']);

        $this->assertEquals('fee_credit' , $vaCreationFee['notes']['type']);
    }

    public function testFundAdditionWebhookWithFundAdditionInvalidType()
    {

        $this->ba->directAuth();

        $this->fundAdditionToVirtualAccount();

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->startTest();
    }

    public function fundAdditionToVirtualAccount($type = 'refund_credit')
    {

        $response = $this->testVACreation($type);

        $bankAccount = $response['receivers'][0];

        $allowedPayer = $response['allowed_payers'][0];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_ifsc']    = $bankAccount['ifsc'];

        $this->testData[__FUNCTION__]['request']['content']['payer_account'] = $allowedPayer['bank_account']['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payer_ifsc']    = $allowedPayer['bank_account']['ifsc'];

        $this->startTest();
    }

    public function testFundAdditionViaWebhook()
    {
        Mail::fake();

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('refund_credit', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $response = $this->startTest($this->testData['addFundsViaWebhook']);

        $credit = $this->getDbLastEntity('credits');

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $credit->getCampaign());

        $this->assertEquals('10000000000001', $credit->getMerchantId());

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $credit->getValue());

        $balance = $this->getDbEntityById('balance', '100def000def00');

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $balance->getRefundCredits());

        Mail::assertQueued(CreditsAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Refund Credit",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testFundAdditionViaWebhookWithInvalidPaymentId()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['merchant_id'] = '10000000000001';

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['type'] = 'refund_credit';

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionViaWebhookWithInvalidVAId()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $payment = $this->getDbLastEntity('payment');

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['merchant_id'] = '10000000000001';

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['type'] = 'refund_credit';

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['id'] = $virtualAccount->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['fee'] = 0;

        $this->startTest();
    }

    public function testValidateVACreationForReserveBalance()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $response = $this->testVACreation('reserve_balance');

        $payerBA = $this->getDbLastEntity('bank_account');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($response['id'], $merchantDetails->getFundAdditionVAIds()['reserve_balance']);

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['account_number'], $payerBA->getAccountNumber());

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['ifsc'], $payerBA->getIfscCode());
    }

    public function testFundAdditionViaWebhookForReserveBalance()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('reserve_balance', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount('reserve_balance');

        $this->startTest($this->testData['addFundsViaWebhook']);

        $reserveBalance = $this->getDbLastEntity('balance');

        $payment = $this->getDbLastEntity('payment');

        $adjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals('10000000000001', $adjustment->getMerchantId());

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $reserveBalance->getBalance());

        Mail::assertQueued(ReserveBalanceAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Reserve Balance",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testValidateVACreationForCreditWhenAlreadyPresentForReserveBalance()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $va_creation_response = $this->testVACreation('reserve_balance');

        $va_fetch_response = $this->testVACreation('reserve_balance');

        $this->assertEquals($va_fetch_response['id'] , $va_creation_response['id']);

        $this->assertEquals($va_fetch_response['notes']['merchant_id'] , $va_creation_response['notes']['merchant_id']);

        $this->assertEquals($va_fetch_response['notes']['type'] , $va_creation_response['notes']['type']);

        $this->assertEquals($va_fetch_response['receivers'][0]['id'] , $va_creation_response['receivers'][0]['id']);

        $this->assertEquals($va_fetch_response['allowed_payers'][0]['bank_account']['account_number'] , $va_creation_response['allowed_payers'][0]['bank_account']['account_number']);

        $merchant = $this->getDbEntityById('merchant', '10000000000001');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($va_creation_response['id'], $merchantDetails->getFundAdditionVAIds()['reserve_balance']);
    }

    public function virtualAccountUpdation()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000000');

        $vaCreationRefund = $this->testVACreation();

        $vaCreationFee = $this->testVACreation('fee_credit');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($vaCreationRefund['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($vaCreationFee['id'], $merchantDetails->getFundAdditionVAIds()['fee_credit']);
    }

    public function testVACreationIfBankAccountDoesNotExist()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000002']);

        $this->fixtures->edit('merchant_detail','10000000000001', [
            'merchant_id' => '10000000000002',
            'business_type' => 1
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000002');

        $this->testData[__FUNCTION__]['request']['content']['type'] = 'refund_credit';

        $this->ba->proxyAuth('rzp_test_10000000000002', $user->getId());

        $response = $this->startTest();
    }
    public function testFundAdditionViaWebhookWithNotesNotPresent()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $payment = $this->getDbLastEntity('payment');

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['id'] = $virtualAccount->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['fee'] = 0;

        $this->startTest();
    }

    public function testFundAdditionViaWebhookForReserveBalanceCentralLedger()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '10000000000001');

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('reserve_balance', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );

        $this->fundAdditionToVirtualAccount('reserve_balance');

        $this->startTest($this->testData['addFundsViaWebhook']);

        $adjustment = $this->getDbLastEntity('adjustment');

        $expectedLedgerOutboxEntry = [
            "transactor_event"=> "merchant_reserve_balance_loading",
            "currency"=> "INR",
            "journals"=> [
                [
                    "merchant_id"=> "10000000000000",
                    "currency"=> "INR",
                    "money_params"=> [
                        "amount"=> $adjustment->getAmount(),
                        "base_amount"=> $adjustment->getAmount(),
                        "merchant_balance_amount"=> $adjustment->getAmount(),
                        "credit_control_amount"=> $adjustment->getAmount()
                    ],
                    "additional_params"=> [
                        "entry_type"=> "debit"
                    ]
                ],
                [
                    "merchant_id"=> "10000000000001",
                    "currency"=> "INR",
                    "money_params"=> [
                        "amount"=> $adjustment->getAmount(),
                        "base_amount"=> $adjustment->getAmount(),
                        "reserve_balance_amount"=> $adjustment->getAmount(),
                        "credit_control_amount"=> $adjustment->getAmount()
                    ],
                    "additional_params"=> [
                        "entry_type"=> "credit"
                    ]
                ]
            ],
            "ledger_integration_mode"=> "reverse-shadow",
            "tenant"=> "PG"
        ];

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $this->assertEquals('10000000000001', $adjustment->getMerchantId());

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);

        Mail::assertNotQueued(ReserveBalanceAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Reserve Balance",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testKafkaSuccessForReserveBalanceLoadingEvent()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '10000000000001');

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('reserve_balance', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );

        $this->fundAdditionToVirtualAccount('reserve_balance');

        $this->startTest($this->testData['addFundsViaWebhook']);

        $adjustment = $this->getDbLastEntity('adjustment');

        $journal = $this->getReserveBalanceLoadingJournalResponse($adjustment->getPublicId());

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $payloadName = $adjustment->getPublicId().'-merchant_reserve_balance_loading';

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $payloadName]);

        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry soft deleted');

        $apiTransaction = $this->getDbEntity('transaction', ['entity_id' => $adjustment->getId()]);

        $this->assertEquals($adjustment->getId(),$apiTransaction->getEntityId());

        $this->assertEquals('adjustment',$apiTransaction->getType());

        $reserveBalance = $this->getDbLastEntity('balance');

        $updatedAdjustment = $this->getDbEntityById('adjustment', $adjustment->getId());

        $this->assertEquals("processed",$updatedAdjustment->getStatus() , 'adjustment status should be processed');

        $this->assertEquals('10000000000001', $adjustment->getMerchantId());

        $this->assertEquals($adjustment->getAmount(), $reserveBalance->getBalance());

        Mail::assertQueued(ReserveBalanceAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Reserve Balance",  $mail->viewData['account_type']);
            return true;
        });
    }

    private function getReserveBalanceLoadingJournalResponse($transactorId)
    {
        return [
            "journals" => [
                [
                    "id" => "MKKW8I8D8ITig2",
                    "created_at" => 1690789363,
                    "updated_at" => 1690789363,
                    "merchant_id" => "10000000000001",
                    "amount" => 1000000,
                    "base_amount" => 1000000,
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $transactorId,
                    "transactor_event" => "merchant_reserve_balance_loading",
                    "transaction_date" => 1690787246,
                    "ledger_entry" => [
                        [
                            "id" => "MKKW8IEa9unbWt",
                            "created_at" => 1690789363,
                            "updated_at" => 1690789363,
                            "merchant_id" => "10000000000001",
                            "journal_id" => "MKKW8I8D8ITig2",
                            "account_id" => "KNIZVBBFrYHYbq",
                            "amount" => 1000000,
                            "base_amount" => 1000000,
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "",
                            "balance_updated" => false,
                            "account_entities" => [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["reserve_balance_control"]
                            ]
                        ],
                        [
                            "id" => "MKKW8IEbW5soLt",
                            "created_at" => 1690789363,
                            "updated_at" => 1690789363,
                            "merchant_id" => "10000000000001",
                            "journal_id" => "MKKW8I8D8ITig2",
                            "account_id" => "KkBoZrmdIrqy5o",
                            "amount" => 1000000,
                            "base_amount" => 1000000,
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => 2000000.000000,
                            "balance_updated" => true,
                            "account_entities" => [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["merchant_reserve_balance"]
                            ]
                        ]
                    ]
                ],
                [
                    "id" => "MKKW8IvhnsRe5g",
                    "created_at" => 1690789363,
                    "updated_at" => 1690789363,
                    "merchant_id" => "10000000000000",
                    "amount" => 1000000,
                    "base_amount" => 1000000,
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $transactorId,
                    "transactor_event" => "merchant_reserve_balance_loading",
                    "transaction_date" => 1690787246,
                    "ledger_entry" => [
                        [
                            "id" => "MKKW8J1YYTru3s",
                            "created_at" => 1690789363,
                            "updated_at" => 1690789363,
                            "merchant_id" => "EWmgrWCkBAJYML",
                            "journal_id" => "MKKW8IvhnsRe5g",
                            "account_id" => "KNIZVBBFrYHYbq",
                            "amount" => 1000000,
                            "base_amount" => 1000000,
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "",
                            "balance_updated" => false,
                            "account_entities" => [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["reserve_balance_control"]
                            ]
                        ],
                        [
                            "id" => "MKKW8J1ZkqtckO",
                            "created_at" => 1690789363,
                            "updated_at" => 1690789363,
                            "merchant_id" => "10000000000000",
                            "journal_id" => "MKKW8IvhnsRe5g",
                            "account_id" => "KSLxYJrZUawFHi",
                            "amount" => 1000000,
                            "base_amount" => 1000000,
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => 3907266490.000000,
                            "balance_updated" => true,
                            "account_entities" => [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["merchant_balance"]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getKafkaEventPayload($journal, $request = null, $msg = "")
    {
        $kafkaPayload = [
            "request" => $request,
            "response" => $journal,
            "error_response" => [
                "msg" => $msg
            ]
        ];

        $serializedPayload = base64_encode(json_encode($kafkaPayload));

        return [
            "before" => null,
            "after" => [
                "id" => "LLJMDzemcsroDp",
                "payload_serialized" => $serializedPayload,
                "created_at" => 1677466532,
                "updated_at" => 1677466532
            ],
            "source" => [
                "version" => "2.1.1.Final",
                "connector" => "postgresql",
                "name" => "internal_db_stage_ledger_payments_test_outbox",
                "ts_ms" => 1677466532793,
                "snapshot" => "false",
                "db" => "stage_ledger_pg_test",
                "sequence" => "[\"60869735408\",\"60869737336\"]",
                "schema" => "public",
                "table" => "outbox_jobs_api_default",
                "txId" => 242246764,
                "lsn" => 60869737336,
                "xmin" => null
            ],
            "op" => "c",
            "ts_ms" => 1677466533213,
            "transaction" => null,
            "_record_source" => "debezium_postgres"
        ];
    }
}

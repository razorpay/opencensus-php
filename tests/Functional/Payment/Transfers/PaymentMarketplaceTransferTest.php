<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use Carbon\Carbon;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\User\Role;
use RZP\Services\RazorXClient;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

class PaymentMarketplaceTransferTest extends TestCase
{
    use MocksSplitz;
    use MocksRazorx;
    use PaymentTrait;
    use PartnerTrait;
    use TransferTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceTransferTestData.php';

        parent::setUp();

        $this->initializeTestSetup();
    }

    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->ba->privateAuth();
    }

    public function testTransferToInvalidOrUnlinkedId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->startTest();
    }

    public function testTransferWithFeatureNotEnabled()
    {
        $this->startTest();
    }

    public function testMultipleTransfersOnSameAccountId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $this->startTest();

        $transferEntities = $this->getEntities('transfer', [],true);

        $this->assertEquals($this->payment['id'], $transferEntities['items'][0]['source']);
        $this->assertEquals($this->payment['id'], $transferEntities['items'][1]['source']);
    }

    public function testTransfersPaymentAsyc()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->willReturn('on');

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $this->startTest();

        $transferEntities = $this->getEntities('transfer', [],true);

        $this->assertEquals($this->payment['id'], $transferEntities['items'][0]['source']);
        $this->assertEquals($this->payment['id'], $transferEntities['items'][1]['source']);
    }

    public function testTransferPaymentInSync()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::TRANSFER_SYNC_PROCESSING_VIA_API_SEMAPHORE_CONFIG  => [
                'limit'          => 3,
                'retry_interval' => 0.1,
                'retries'        => 5
            ]
        ]);

        $transfers[0] = [
            'account_code'  => 'code-007',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $this->mockRazorxTreatmentV2(RazorxTreatment::ENABLE_TRANSFER_SYNC_PROCESSING_VIA_API, 'on');

        $response = $this->transferPayment($this->payment['id'], $transfers);
        $transfer = $response['items'][0];

        $this->assertEquals('transfer', $transfer['entity']);
        $this->assertEquals($this->payment['id'], $transfer['source']);
        $this->assertEquals('processed', $transfer['status']);
        $this->assertNotNull($transfer['processed_at']);
        $this->assertEquals('acc_10000000000001', $transfer['recipient']);
        $this->assertEquals('code-007', $transfer['account_code']);
        $this->assertEquals($this->payment['amount'], $transfer['amount']);
    }

    public function testTransferPaymentRazorxDisabledForSyncProcessing()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $transfers[0] = [
            'account_code'  => 'code-007',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $this->mockRazorxTreatmentV2(RazorxTreatment::ENABLE_TRANSFER_SYNC_PROCESSING_VIA_API, 'control');

        $response = $this->transferPayment($this->payment['id'], $transfers);
        $transfer = $response['items'][0];

        $this->assertEquals('transfer', $transfer['entity']);
        $this->assertEquals($this->payment['id'], $transfer['source']);
        $this->assertEquals('pending', $transfer['status']);
        $this->assertNull($transfer['processed_at']);
        $this->assertEquals('acc_10000000000001', $transfer['recipient']);
        $this->assertEquals('code-007', $transfer['account_code']);
        $this->assertEquals($this->payment['amount'], $transfer['amount']);
    }

    public function testTransferPaymentAmountGreaterThanCaptured()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => $this->payment['amount'] + 1000,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });
    }

    public function testTransferToCustomerAndAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'customer'=> 'cust_100000customer',
            'amount'  => 400,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });
    }

    public function testPartialAmountTransfer()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 4000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 4000,
                    'amount_reversed' => 0
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $transferFee = $this->getLastTransactionFee('10000000000001');

        $this->assertEquals(4000 - $transferFee, $this->getAccountBalance('10000000000001'));

        $this->assertArraySelectiveEquals($expected, $content);
    }

    public function testFullTransfer()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);
        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));

        $oldMarketBalance = $this->getAccountBalance('10000000000000');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 43000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 7000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 2,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 43000,
                    'amount_reversed' => 0
                ],
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000002',
                    'amount'          => 7000,
                    'amount_reversed' => 0
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertArraySelectiveEquals($expected, $content);

        $transferFee = $this->getLastTransactionFee('10000000000001');
        $this->assertEquals($transfers[0]['amount'] - $transferFee, $this->getAccountBalance('10000000000001'));

        $transferFee = $this->getLastTransactionFee('10000000000002');
        $this->assertEquals($transfers[1]['amount'] - $transferFee, $this->getAccountBalance('10000000000002'));

        $newMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertEquals(50000, $oldMarketBalance - $newMarketBalance);
    }

    public function testTransferForMerchantCustomerFeeBearer()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->pricing->editAllPricingPlanRules(self::STANDARD_PRICING_PLAN_ID,['fee_bearer' => FeeBearer::CUSTOMER]);

        $transfers[0] = [
            'account'  => 'acc_10000000000001',
            'amount'   => $this->payment['amount'],
            'currency' => 'INR',
        ];

        $content  = $this->transferPayment($this->payment['id'], $transfers);

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'    => $this->payment['id'],
                    'recipient' => 'acc_10000000000001',
                ],
            ]
        ];
        $this->assertArraySelectiveEquals($expected, $content);
    }

    public function testPaymentTransferFetch()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $transfers['items'][0];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/transfers';

        $testData['response']['content']['items'][0] += [
                    "id"                        => $transfer['id'],
                    "source"                    => $this->payment['id']
            ];

        $this->runRequestResponseFlow($testData);
    }

    public function testPaymentPlatformTransferFetch()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $id = $this->payment['id'];

        Payment\Entity::verifyIdAndSilentlyStripSign($id);

        $this->fixtures->create('transfer', [
            'id'            => 'LhV9fg1fXagWCN',
            'status'        => 'processed',
            'merchant_id'   => '10000000000000',
            'source_id'     => $id,
            'source_type'   => 'payment',
            'to_id'         => '10000000000001',
            'amount'        => 1000,
        ]);

        $this->fixtures->create('transfer', [
            'id'            => 'LhV9fg1fXagWCD',
            'status'        => 'processed',
            'merchant_id'   => '10000000000000',
            'source_id'     => $id,
            'source_type'   => 'payment',
            'to_id'         => '10000000000003',
            'amount'        => 1000,
        ]);

        $this->fixtures->create('merchant', [
            'id'    => '10000000000002',
            'email' => 'testmail@mail.info',
            'name'  => 'partner_test',
        ]);

        $this->fixtures->create('merchant', [
            'id'        => '10000000000003',
            'email'     => 'testmail@mail.info',
            'name'      => 'partner_test',
            'parent_id' => '10000000000002',
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/transfers';

        $this->runRequestResponseFlow($testData);
    }

    public function testTransferForSubMerchantCustomerFeeBearer()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->enableConvenienceFeeModel('10000000000001');

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $transfers[0] = [
            'account'  => 'acc_10000000000001',
            'amount'   => $this->payment['amount'],
            'currency' => 'INR',
        ];

        $content  = $this->transferPayment($this->payment['id'], $transfers);

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'    => $this->payment['id'],
                    'recipient' => 'acc_10000000000001',
                ],
            ]
        ];
        $this->assertArraySelectiveEquals($expected, $content);
    }

    public function testTransferPaymentUsingAccountCode()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $transfers[0] = [
            'account_code'  => 'code-007',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $response = $this->transferPayment($this->payment['id'], $transfers);
        $transfer = $response['items'][0];

        $this->assertEquals('transfer', $transfer['entity']);
        $this->assertEquals($this->payment['id'], $transfer['source']);
        $this->assertEquals('acc_10000000000001', $transfer['recipient']);
        $this->assertEquals('code-007', $transfer['account_code']);
        $this->assertEquals($this->payment['amount'], $transfer['amount']);
    }

    public function testTransferPaymentUsingAccountCodeWhenFeatureDisabled()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $transfers[0] = [
            'account_code'  => 'code-007',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $this->makeRequestAndCatchException(
            function() use ($transfers)
            {
                $this->transferPayment($this->payment['id'], $transfers);
            },
            BadRequestException::class,
            'account_code is not allowed for this merchant.'
        );
    }

    public function testTransferPaymentUsingAccountAndAccountCode()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $transfers[0] = [
            'account'       => 'acc_10000000000001',
            'account_code'  => 'code-007',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $this->makeRequestAndCatchException(
            function() use ($transfers)
            {
                $this->transferPayment($this->payment['id'], $transfers);
            },
            BadRequestValidationFailureException::class,
            'Exactly one of account, account_code & customer to be passed.'
        );
    }

    public function testTransferPaymentUsingInvalidAccountCode()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $transfers[0] = [
            'account_code'  => 'bro_code',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
        ];

        $this->makeRequestAndCatchException(
            function() use ($transfers)
            {
                $this->transferPayment($this->payment['id'], $transfers);
            },
            BadRequestException::class,
            'bro_code is an invalid account_code.'
        );
    }

    public function testTransferFailedWebhook()
    {
        $this->markTestSkipped('Failing due to PR-37809, will be fixed');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->editBalance(100);

        $transfers[0] = [
            'account'  => 'acc_10000000000001',
            'amount'   => $this->payment['amount'],
            'currency' => 'INR',
        ];

        $this->expectWebhookEventWithContents('transfer.failed', $this->testData[__FUNCTION__]);

        $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
    }

    public function testNoTransferFailedWebhookWithoutFeatureFlag()
    {
        $this->markTestSkipped('The feature flag is now permanently removed.');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->editBalance(100);

        $transfers[0] = [
            'account'  => 'acc_10000000000001',
            'amount'   => $this->payment['amount'],
            'currency' => 'INR',
        ];

        $this->dontExpectWebhookEvent('transfer.failed');

        $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
    }

    public function testCreateTransferFromBatch()
    {
        $this->fixtures->merchant->addFeatures('marketplace');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/transfers/batch';

        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals('processed', $transfer['status']);
    }

    public function testCreateTransferFromBatchWithOnHold()
    {
        $this->fixtures->merchant->addFeatures('marketplace');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/transfers/batch';

        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals('processed', $transfer['status']);
        $this->assertTrue($transfer['on_hold']);
    }

    public function testSettlementStatusForPaymentTransfer()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account'  => 'acc_10000000000001',
            'amount'   => $this->payment['amount'],
            'currency' => 'INR',
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('pending', $transfer['settlement_status']);
    }

    public function testSettlementStatusForPaymentTransferWithOnHold()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account'       => 'acc_10000000000001',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
            'on_hold'       => true,
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('on_hold', $transfer['settlement_status']);
    }

    public function testSettlementStatusForPaymentTransferWithOnHoldUntil()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account'       => 'acc_10000000000001',
            'amount'        => $this->payment['amount'],
            'currency'      => 'INR',
            'on_hold'       => true,
            'on_hold_until' => Carbon::tomorrow()->getTimestamp(),
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('on_hold', $transfer['settlement_status']);
    }

    public function testErrorCodeForTransferWithInsufficientBalance()
    {
        $this->markTestSkipped('Failing due to PR-37809, will be fixed');

        $this->mockRazorxTreatment('on');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->editBalance(100);

        $transfers[0] = [
            'account'   => 'acc_10000000000001',
            'amount'    => $this->payment['amount'],
            'currency'  => 'INR',
        ];

        $response = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertArrayHasKey('error', $response['items'][0]);
        $this->assertArrayHasKey('code', $response['items'][0]['error']);
        $this->assertArrayHasKey('description', $response['items'][0]['error']);
        $this->assertArrayHasKey('reason', $response['items'][0]['error']);
        $this->assertArrayHasKey('field', $response['items'][0]['error']);
        $this->assertArrayHasKey('step', $response['items'][0]['error']);
        $this->assertArrayHasKey('id', $response['items'][0]['error']);
        $this->assertArrayHasKey('source', $response['items'][0]['error']);
        $this->assertArrayHasKey('metadata', $response['items'][0]['error']);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE', $transfer['error_code']);
    }

    public function testErrorFieldInGetTransfer()
    {
        $this->markTestSkipped('Failing due to PR-37809, will be fixed');

        $this->mockRazorxTreatment('on');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->editBalance(100);

        $transfers[0] = [
            'account'   => 'acc_10000000000001',
            'amount'    => $this->payment['amount'],
            'currency'  => 'INR',
        ];

        $response = $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $this->getDbLastEntity('transfer');

        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE', $transfer['error_code']);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $testData['request']['url'] . '/' . $response['items'][0]['id'];

        $this->runRequestResponseFlow($testData);
    }

    protected function mockRazorxTreatment(string $variant = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->willReturn($variant);
    }

    public function testFetchLinkedAccountTransferByPaymentId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $transfers['items'][0];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/la-transfers/payment/' . $this->payment['id'];

        $testData['response']['content']['items'][0] += [
            "id"                                     => $transfer['id'],
            "source"                                 => $this->payment['id']
        ];

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->fixtures->merchant->addFeatures(['display_parent_payment_id'], '10000000000001');

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchLinkedAccountTransferByPaymentIdAndTransferId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transfer = $transfers['items'][0];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/la-transfers/payment/' . $this->payment['id'];

        $testData['request']['content']['id'] = $transfer['id'];

        $testData['response']['content']['items'][0] += [
            "id"                                     => $transfer['id'],
            "source"                                 => $this->payment['id']
        ];

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->fixtures->merchant->addFeatures(['display_parent_payment_id'], '10000000000001');

        $this->runRequestResponseFlow($testData);
    }

    public function testCronProcessPendingPaymentTransfers()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $paymentId = $this->payment['id'];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $dummyTransferData = [
            'id'                 => "AnyRandomID123",
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp()
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('pending', $transfer['status']);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $paymentIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('processed', $transfer['status']);

        $this->assertEquals($paymentId, $paymentIds[0]);

        $this->assertNotNULL($transfer['processed_at']);
    }

    public function testCronProcessPendingPaymentTransfersSync()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $paymentId = $this->payment['id'];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $dummyTransferData1 = [
            'id'                 => "AnyRandomID123",
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp()
        ];

        $dummyTransferData2 = [
            'id'                 => "AnyRandomID456",
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 100000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-7)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-8)->getTimestamp()
        ];

        $this->fixtures->transfer->create($dummyTransferData1);
        $this->fixtures->transfer->create($dummyTransferData2);

        $transfer1 = $this->getLastEntity('transfer', true);
        $transfer2 = $this->getLastEntity('transfer', true);

        $this->assertEquals('pending', $transfer1['status']);
        $this->assertEquals('pending', $transfer2['status']);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $paymentIds = $this->runRequestResponseFlow($data);

        $transfer1 = $this->getLastEntity('transfer', true);
        $transfer2 = $this->getLastEntity('transfer', true);

        $this->assertEquals('processed', $transfer1['status']);
        $this->assertEquals('processed', $transfer2['status']);

        $this->assertEquals($paymentId, $paymentIds[0]);

        $this->assertNotNULL($transfer1['processed_at']);
        $this->assertNotNULL($transfer2['processed_at']);
    }

    public function testCreatePaymentTransferWithPartnerAuthForMarketplace()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->setupMarketPlace($subMerchantId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $this->mockAllSplitzTreatment();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $response = $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbEntityById('transfer', $response['items'][0]['id']);

        $this->assertEquals($subMerchantId, $transfer->getMerchantId());
    }

    public function testCreatePaymentTransferWithOAuthForMarketplace()
    {
        $this->setPurePlatformContext(Mode::TEST);

        $this->fixtures->edit('merchant', '10000000000001', ['parent_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID]);

        $this->setupMarketPlace(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $this->mockAllSplitzTreatment();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $response = $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbEntityById('transfer', $response['items'][0]['id']);

        $this->assertEquals(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, $transfer->getMerchantId());

        $merchantApplication = $this->getDbLastEntity('merchant_application');

        $this->verifyEntityOrigin($transfer['id'], 'marketplace_app',  $merchantApplication['application_id']);
    }

    public function testCreatePaymentTransferWithOAuthForMarketplaceWithAppLevelFeature()
    {
        $this->setPurePlatformContext(Mode::TEST);

        $this->fixtures->edit('merchant', '10000000000001', ['parent_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID]);

        $this->setupMarketPlace(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->removeFeatures(['route_partnerships'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $merchantApplication = $this->getDbLastEntity('merchant_application');

        $this->fixtures->create('feature',
            [
                'name' => 'route_partnerships',
                'entity_id' => $merchantApplication['application_id'],
                'entity_type' => 'application',
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $this->mockAllSplitzTreatment();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $response = $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbEntityById('transfer', $response['items'][0]['id']);

        $this->assertEquals(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, $transfer->getMerchantId());

        $this->verifyEntityOrigin($transfer['id'], 'marketplace_app',  $merchantApplication['application_id']);
    }

    public function testCreatePaymentTransferEntityOriginWithPartnerAuthForMarketplace()
    {
        list($subMerchantId, $client) = $this->setUpPartnerAuthAndGetSubMerchantIdWithClient();

        $this->setupMarketPlace($subMerchantId);

        $testData = $this->testData[__FUNCTION__];

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $response = $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbEntityById('transfer', $response['items'][0]['id']);

        $this->assertEquals($subMerchantId, $transfer->getMerchantId());

        // Assert that the entity origin for the transfer is set to marketplace_app
        $this->verifyEntityOrigin($transfer['id'], 'marketplace_app',  $client->getApplicationId());
    }

    private function verifyEntityOrigin($entityId, $originType, $originId)
    {
        $this->fixtures->stripSign($entityId);

        $entityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $entityId]);

        $this->assertEquals($originType, $entityOrigin['origin_type']);

        $this->assertEquals($originId, $entityOrigin['origin_id']);
    }

    /**
     * @param mixed $subMerchantId
     * @return void
     */
    private function setupMarketPlace(string $subMerchantId, string $partnerId = '10000000000000'): void
    {
        $this->fixtures->merchant->addFeatures(['marketplace'], $partnerId);

        $this->fixtures->merchant->addFeatures(['route_partnerships'], $partnerId);

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $this->fixtures->edit('balance', '10000000000000', ['merchant_id' => $subMerchantId,]);

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $this->mockAllSplitzTreatment();
    }

    public function testCreatePaymentTransferWithPartnerAuthForInvalidPartnerMerchantMapping()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->setupMarketPlace($subMerchantId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $this->mockAllSplitzTreatment();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['merchant_id' => $merchant['id']]);

        $this->runRequestResponseFlow($testData);
    }

    public function testCreatePaymentTransferWithPartnerAuthForInvalidPartnerType()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->setupMarketPlace($subMerchantId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $this->mockAllSplitzTreatment();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartner()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction->id, ['on_hold' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertFalse($transaction->getOnHold());

        $this->assertFalse($transaction->isSettled());
    }

    public function testReleaseSubmerchantPaymentByPartnerUsingOAuth()
    {
        $this->setPurePlatformContext(Mode::TEST);

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction->id, ['on_hold' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertFalse($transaction->getOnHold());

        $this->assertFalse($transaction->isSettled());
    }

    public function testReleaseSubmerchantPaymentByPartnerUsingOAuthWithAppLevelFeature()
    {
        $this->setPurePlatformContext(Mode::TEST);

        $merchantApplication = $this->getDbLastEntity('merchant_application');

        $this->fixtures->create('feature',
            [
                'name' => 'subm_manual_settlement',
                'entity_id' => $merchantApplication['application_id'],
                'entity_type' => 'application',
            ]
        );

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction->id, ['on_hold' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertFalse($transaction->getOnHold());

        $this->assertFalse($transaction->isSettled());
    }

    public function testReleaseSubmerchantPaymentByInvalidPartnerTypeUsingOAuth()
    {
        $this->setPurePlatformContext(Mode::TEST);

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->fixtures->edit('merchant', Constants::DEFAULT_PLATFORM_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction->id, ['on_hold' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertTrue($transaction->getOnHold());

        $this->assertFalse($transaction->isSettled());
    }

    public function testReleaseSubmerchantPaymentByPartnerWithFeatureDisabled()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartnerWithInvalidPaymentMerchant()
    {
        $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->create('merchant', ['id' => '10000000000005']);

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => '10000000000005',]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_10000000000005';

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartnerWithUnmappedMerchant()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->create('merchant', ['id' => '10000000000005']);

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['merchant_id' => '10000000000005']);

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartnerWhichIsNotCaptured()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $this->fixtures->edit('payment', $this->payment['id'], ['status' => 'authorized']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartnerWithTrxnNotOnHold()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByPartnerWithTrxnAlreadySettled()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->edit('payment', $this->payment['id'], ['merchant_id' => $subMerchantId,]);

        $this->fixtures->edit('payment', $this->payment['id'], ['on_hold' => true]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction->id, ['on_hold' => true]);

        $this->fixtures->edit('transaction', $transaction->id, ['settled' => true]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseSubmerchantPaymentByInvalidPartnerType()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['subm_manual_settlement'], '10000000000000');

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $subMerchantId;

        $testData['request']['url'] = '/payments/' . $this->payment['id'] . '/settle';

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testTransferToSuspendedLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->edit('merchant', '10000000000001', ['suspended_at' => 1642901927]);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });
    }
}

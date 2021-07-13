<?php

namespace Functional\Payout;

use Mockery;
use Requests_Response;

use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payout\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Services\PayoutService\Retry as PayoutServiceRetry;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Services\PayoutService\Status as PayoutServiceStatus;
use RZP\Services\PayoutService\Cancel as PayoutServiceCancel;
use RZP\Services\PayoutService\Details as PayoutServiceDetails;

class PayoutServiceTest extends TestCase
{
    use PayoutTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutServiceTestData.php';

        parent::setUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->setUpMerchantForBusinessBankingLive(false, 10000000);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function mockPayoutServiceCreate($fail = false, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create', [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceCreateMock->shouldReceive('sendRequest')
                                ->withArgs(
                                    function($arg) use ($request) {
                                        try
                                        {
                                            // Using this method only here as we want to check if the keys in the
                                            // request are coming properly or not.
                                            $this->assertArrayKeySelectiveEquals($request, $arg);

                                            return true;
                                        }
                                        catch (\Throwable $e)
                                        {
                                            return false;
                                        }
                                    }
                                )
                                ->andReturn(
                                // We are returning this response only as we don't have a use case of supporting
                                // response based on $request, if needed, that can also be added here using
                                // andReturnUsing method instead of andReturn
                                    $this->createResponseForPayoutServiceMock($fail)
                                );

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);
    }

    public function mockPayoutServiceStatus($status, $fail = false)
    {
        $payoutServiceStatusMock = $this->getMockBuilder(PayoutServiceStatus::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $this->app->payout_service_status->method('sendRequest')
            ->willReturn($this->createResponseForPayoutServiceMock($fail, $status));
    }

    public function mockPayoutServiceRetry($fail = false)
    {
        $payoutRetryStatusMock = $this->getMockBuilder(PayoutServiceRetry::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendRequest'])
                                        ->getMock();

        $this->app->instance(PayoutServiceRetry::PAYOUT_SERVICE_RETRY, $payoutRetryStatusMock);

        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error"   =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    'total_count'       => 1,
                    'success_count'     => 1,
                    'failure_count'     => 0,
                    'failed_payout_ids' => [],
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        $this->app->payout_service_retry->method('sendRequest')
                                         ->willReturn($response);
    }

    public function mockPayoutServiceDetails($fail = false)
    {
        $payoutServiceDetailsMock = $this->getMockBuilder(PayoutServiceDetails::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $this->app->payout_service_detail->method('sendRequest')
            ->willReturn($this->createResponseForPayoutServiceMock($fail));
    }

    public function mockPayoutServiceCancel($fail = false)
    {
        $payoutServiceCancelMock = $this->getMockBuilder(PayoutServiceCancel::class)
                                         ->setConstructorArgs([$this->app])
                                         ->setMethods(['sendRequest'])
                                         ->getMock();

        $this->app->instance(PayoutServiceCancel::PAYOUT_SERVICE_CANCEL, $payoutServiceCancelMock);

        $this->app->payout_service_cancel->method('sendRequest')
                                         ->willReturn($this->createResponseForPayoutServiceMock($fail,
                                                                                                Status::CANCELLED));
    }

    public function createResponseForPayoutServiceMock($fail, $status = 'created')
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error"   =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"      => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    "id"                =>   "pout_Gg7sgBZgvYjlSB",
                    "entity"            =>   "payout",
                    "fund_account_id"   =>   "fa_100000000000fa",
                    "amount"            =>   100,
                    "currency"          =>   "INR",
                    "merchant_id"       =>   "10000000000000",
                    "notes"             =>   "",
                    "fees"              =>   0,
                    "tax"               =>   0,
                    "status"            =>   $status,
                    "purpose"           =>   "refund",
                    "utr"               =>   "",
                    "reference_id"      =>   null,
                    "narration"         =>   "test Merchant Fund Transfer",
                    "batch_id"          =>   "",
                    "initiated_at"      =>   1614325830,
                    "failure_reason"    =>   null,
                    "created_at"        =>   1614325826,
                    "fee_type"          =>   null
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    // Check payout Create Entry func on processor base
    public function testCreatePayoutEntry($mode = 'IMPS')
    {
        $this->ba->appAuthLive();

        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();
        $this->testData[__FUNCTION__]['request']['content']['mode'] = $mode;

        $this->startTest();

        $payout = $this->getLastEntity('payout', true,'live');

        $this->assertEquals($payout['id'], 'pout_Gg7sgBZgvYjlSB');
    }

    // Check payout Create transaction func on processor base
    public function testCreatePayoutServiceTransaction($mode = 'IMPS')
    {
        $this->testCreatePayoutEntry($mode);

        $this->ba->appAuthLive();

        $response = $this->startTest();

        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);
    }

    // Check payout Create fta func on processor base
    public function testCreatePayoutServiceFtaCreation($mode = 'IMPS')
    {
        $this->testCreatePayoutServiceTransaction($mode);

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testCreatePayout(): array
    {
        $this->mockPayoutServiceCreate();

        $this->testCreatePayoutServiceFtaCreation();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutServiceFailure()
    {
        $this->mockPayoutServiceCreate(true);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateReversalEntry()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => $payout->getId(),
            'utr'        => '123456678',
            'amount'     => $payout['amount'],
            'currency'   => $payout['currency'],
            'channel'    => $payout['channel'],
        ];

        $this->ba->appAuthLive();

        $response = $this->startTest();

        $this->assertNotEmpty($response['transaction_id']);
    }

    public function testCreateReversalEntryDuplicateCreationNotAllowed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = $this->testData['testCreateReversalEntry'];

        $testData['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => $payout->getId(),
            'utr'        => '123456678',
            'amount'     => $payout['amount'],
            'currency'   => $payout['currency'],
            'channel'    => $payout['channel'],
        ];

        $this->ba->appAuthLive();

        $response1 = $this->startTest($testData);

        $this->assertNotEmpty($response1['transaction_id']);

        $this->ba->appAuthLive();

        $response2 = $this->startTest($testData);

        $this->assertEquals($response1['transaction_id'], $response2['transaction_id']);
    }

    public function testUpdateFTAAndPayoutProcessed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('processed');

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ], 'live')->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('processed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutToFailed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('failed');

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ], 'live')->first();

        // Assert that fta status didn't update
        $this->assertEquals('failed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('reversed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutDetailsFailure()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails(true);

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ], 'live')->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('created', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutStatusFailure()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('processed', true);

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ], 'live')->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('created', $payout->getStatus());
    }

    public function testCreatePayoutForCard(): array
    {
        $this->markTestSkipped('For card in payout');

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockPayoutServiceCreate();

        $this->testCreatePayoutServiceFtaCreation('NEFT');

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('card_100000000lcard', 'card_' . $payoutAttempt['card_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    // Since PAYOUTS_ON_HOLD feature is enabled for the merchant, the payout won't go via payouts service and would
    // directly go to processing state.
    public function testCreatePayoutForOnHoldPayout()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should not have gone via payouts service
        $this->assertEquals(false, $payout->getIsPayoutService());

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout->getPublicId(), $payoutAttempt['source']);
        $this->assertEquals($payout->getMerchantId(), $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getDbLastEntity('transaction',  'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
    }

    // Since NEW_BANKING_ERROR feature is enabled for the merchant, the payout won't go via payouts service and would
    // directly go to processing state.
    public function testCreatePayoutForNewBankingErrorPayout()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->testData[__FUNCTION__] = $this->testData['testCreatePayoutForOnHoldPayout'];

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should not have gone via payouts service
        $this->assertEquals(false, $payout->getIsPayoutService());

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout->getPublicId(), $payoutAttempt['source']);
        $this->assertEquals($payout->getMerchantId(), $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getDbLastEntity('transaction',  'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
    }

    // Since payout has queue_if_low_balance flag set to true, it won't go via payouts service
    public function testCreateQueuedPayoutViaPayoutService()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 100
            ]
        );

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should not have gone via payouts service
        $this->assertEquals(false, $payout->getIsPayoutService());

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Payout should be in queued state
        $this->assertEquals('queued', $payout->getStatus());
    }

    // Assert that the array keys match selectively, we don't compare for values only the keys
    public function assertArrayKeySelectiveEquals(array $expected, array $actual)
    {
        foreach ($expected as $key => $value)
        {
            if (is_array($value))
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertArrayKeySelectiveEquals($expected[$key], $actual[$key]);
            }
            else
            {
                $this->assertArrayHasKey($key, $actual);
            }
        }
    }

    public function testServiceCancelQueuedPayoutProxyAuth()
    {
        $this->mockPayoutServiceCancel();

        $this->testCreatePayout();

        $queuedPayout = $this->getDbLastEntity('payout', 'live');

        $queuedPayout->SetStatus(Status::QUEUED);

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01', 'live')->toArrayPublic();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user'] = $cancellationUser;

        $this->ba->proxyAuthLive();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout', 'live');

        // Assert that payout got cancelled
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testServiceCancelQueuedPayoutPrivateAuth()
    {
        $this->mockPayoutServiceCancel();

        $this->testCreatePayout();

        $queuedPayout = $this->getDbLastEntity('payout', 'live');

        $queuedPayout->SetStatus(Status::QUEUED);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout', 'live');

        // Assert that payout got cancelled
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);
        $this->assertEquals($testData['request']['content']['remarks'], $cancelledPayout['remarks']);
    }

    public function testRetryPayoutService()
    {
        $this->mockPayoutServiceRetry();

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [
            $payout->getId()
        ];

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testRetryPayoutServiceFail()
    {
        $this->mockPayoutServiceRetry(true);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [
            $payout->getId()
        ];

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    // Since queued payout has is_payout_service value set to 1, it won't be processed via api
    public function testProcessQueuedPayoutCreatedPayoutService()
    {
        $this->testCreateQueuedPayoutViaPayoutService();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'is_payout_service' => 1
            ]
        );

        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 100000
            ]
        );

        $this->dispatchQueuedPayouts('live');

        $payout->reload();

        $this->assertEquals('queued', $payout->getStatus());
    }
}

<?php

namespace Functional\Payout;

use DB;
use Mockery;
use Requests_Response;

use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payout\Status;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Services\PayoutService\Get as PayoutServiceGet;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Services\PayoutService\Retry as PayoutServiceRetry;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Services\PayoutService\Status as PayoutServiceStatus;
use RZP\Services\PayoutService\Cancel as PayoutServiceCancel;
use RZP\Services\PayoutService\Details as PayoutServiceDetails;
use RZP\Services\PayoutService\QueuedInitiate as PayoutServiceQueuedInitiate;

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

    public function mockPayoutServiceCreate($fail = false, $request = [], $status = 'created')
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create',
                                                 [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceCreateMock->shouldReceive('sendRequest')
                                ->withArgs(
                                    function($arg) use ($request, $status) {
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
                                    $this->createResponseForPayoutServiceMock($fail, $status)
                                );

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);
    }

    public function mockPayoutServiceGet($fail = false, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceGetMock = Mockery::mock('RZP\Services\PayoutService\Get',
            [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceGetMock->shouldReceive('sendRequest')
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
                $this->getResponseForPayoutByIdServiceMock($fail)
            );

        $this->app->instance(PayoutServiceGet::PAYOUT_SERVICE_GET, $payoutServiceGetMock);
    }

    public function mockPayoutServiceGetAnalytics($fail = false, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceGetMock = Mockery::mock('RZP\Services\PayoutService\Get',
            [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceGetMock->shouldReceive('sendRequest')
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
                $this->getResponseForPayoutAnalyticsServiceMock($fail)
            );

        $this->app->instance(PayoutServiceGet::PAYOUT_SERVICE_GET, $payoutServiceGetMock);
    }

    public function mockPayoutServiceQueuedInitiate($fail = false, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request content that
        // is going to be sent to payout service.
        $payoutServiceQueuedInitiateMock = Mockery::mock('RZP\Services\PayoutService\QueuedInitiate',
                                                         [$this->app])->makePartial();

        $defaultRequest['content']['balance_ids'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceQueuedInitiateMock->shouldReceive('sendRequest')
                                        ->withArgs(
                                            function($arg) use ($request) {
                                                try
                                                {
                                                    // json decoding the content so that we can assert the keys of content.
                                                    $arg['content'] = json_decode($arg['content'], true);

                                                    // Using this method only here as we want to check if the keys in the
                                                    // request are coming properly or not.
                                                    $this->assertArrayKeySelectiveEquals($request, $arg);

                                                    if (empty($request['content']['balance_ids']) === false)
                                                    {
                                                        return ($request['content']['balance_ids'] ===
                                                                $arg['content']['balance_ids']);
                                                    }

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

        $this->app->instance(PayoutServiceQueuedInitiate::PAYOUT_SERVICE_QUEUED_INITIATE,
                             $payoutServiceQueuedInitiateMock);
    }

    public function mockPayoutServiceAdminFetch($request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceAdminFetchMock = Mockery::mock('overload:RZP\Services\PayoutService\AdminFetch')->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceAdminFetchMock->shouldReceive('fetch')
                                    ->withAnyArgs()
                                    ->andReturnUsing(
                                        function($entity, $id, $input) {
                                            return $this->adminGetResponseForService($entity, $id);
                                        }
                                    );
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

    public function getResponseForPayoutByIdServiceMock($fail, $status = 'processing')
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

    public function testCreateLedgerForOnHoldPayoutCreatedViaPayoutService()
    {
        $this->testCreateOnHoldPayoutViaPayoutService();

        $this->ba->appAuthLive($this->config['applications.payouts_service.secret']);

        $response = $this->startTest();

        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);
    }

    // fetch payment created from payouts service, currently used in axis cc
    public function testPaymentsFetchFromPayoutsService()
    {
        $this->testCreatePayoutServicePaymentCreation();

        $payment = $this->getLastEntity('payment', true, 'live');

        $paymentId = $payment['id'];

        $this->ba->appAuthLive();

        $request = [
            'method'  => 'GET',
            'url'     =>  '/payments_internal/'.$paymentId,
            'content' => [

            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['status'], 'authorized');
        $this->assertEquals($response['entity'],'payment');
        $this->assertNotNull($response);
        $this->assertEquals($response['id'],$paymentId);
    }

    // Check payout Create fta func on processor base
    public function testCreatePayoutServiceFtaCreation($mode = 'IMPS')
    {
        $this->testCreatePayoutServiceTransaction($mode);

        $this->ba->appAuthLive();

        $this->startTest();
    }


   //check create payment from payouts service for axis cc
    public function testCreatePayoutServicePaymentCreation()
    {
        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
                'moto' => '1'
            ]
        ]);

        $this->ba->appAuthLive();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true, 'live');

        $this->assertEquals($payment['status'], 'authorized');
        $this->assertNull($payment['order_id']);
        $this->assertEquals($payment['merchant_id'], '10000000000000');
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

    public function testCreatePayoutInternalContactWithoutFeatureFlag()
    {

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');


        $this->assertEquals($payout['merchant_id'], '10000000000000');
        $this->assertEquals($payout['fees'], 590);
        $this->assertEquals($payout['is_payout_service'], 0);


    }

    public function testCreatePayoutInternalContact()
    {
        $this->mockPayoutServiceCreate();

        $this->testCreatePayoutServiceFtaCreation();

        $this->fixtures->merchant->addFeatures([Feature\Constants::WORKFLOW_VIA_PAYOUTS_MS]);
        $this->fixtures->merchant->addFeatures([Feature\Constants::INTERNAL_CONTACT_VIA_PS]);

        $this->setupWorkflowForLiveMode();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');


        $this->assertEquals(Status::CREATED, $payout->getStatus());
        $this->assertEquals($payout['merchant_id'], '10000000000000');
        $this->assertEquals($payout['fees'], 590);
        $this->assertEquals($payout['is_payout_service'], 1);


    }

    public function testCreatePayoutServiceFailure()
    {
        $this->mockPayoutServiceCreate(true);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testGetPayoutById()
    {
        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', true, 'live');

        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $org = $this->getLastEntity('org', true);

        $this->fixtures->org->edit($org['id'], ['custom_code' => 'axis_cc']);

        $org = $this->getLastEntity('org', true);

        $orgId = trim($org['id'],"org_");

        $this->fixtures->merchant->edit('10000000000000',['org_id' => $orgId]);

        $this->mockPayoutServiceGet();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testGetPayoutAnalytics()
    {
        //TODO : Testcase has to be fixed
        $this->markTestSkipped("Skipping Testcase, Need to be fixed");

        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $org = $this->getLastEntity('org', true);

        $this->fixtures->org->edit($org['id'], ['custom_code' => 'axis_cc']);

        $org = $this->getLastEntity('org', true);

        $orgId = trim($org['id'],"org_");

        $this->fixtures->merchant->edit('10000000000000',['org_id' => $orgId]);

        $this->mockPayoutServiceGetAnalytics();

        $this->ba->proxyAuth();

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

    // Since idempotency feature is not available for payouts service,
    // the payout shouldn't go via payouts service
    public function testCreatePayoutWithIdempotencyKey()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should not have gone via payouts service
        $this->assertEquals(false, $payout->getIsPayoutService());

        $idempotencyEntity = $this->getLastEntity('idempotency_key', true,'live');

        $this->assertEquals($idempotencyEntity['idempotency_key'], 'idem_key_test');
        $this->assertEquals($idempotencyEntity['merchant_id'], '10000000000000');
        $this->assertEquals($idempotencyEntity['source_id'], $payout['id']);
        $this->assertEquals($idempotencyEntity['source_type'], 'payout');
    }

    // Since PAYOUTS_ON_HOLD feature is enabled for the merchant and experiment is enabled
    // to go via payouts service, payout should go via payouts service
    public function testCreateOnHoldPayoutViaPayoutService()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control',
            'on',
            'on',
            'control',
            'off',
            'on'
        );

        $this->mockPayoutServiceCreate(false, [], 'on_hold');

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('IMPS');

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'on_hold',
            ]
        );

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should have gone via payouts service
        $this->assertEquals(true, $payout->getIsPayoutService());

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        $this->assertEquals('on_hold', $payout->getStatus());
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
    public function testCreateQueuedPayoutViaPayoutService(string $balanceId = '')
    {
        if (empty($balanceId) === true)
        {
            $balanceId = $this->bankingBalance->getId();
        }

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 100
            ]
        );

        $balance = $this->getDbEntityById('balance', $balanceId, "live");

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['account_number'] = $balance->getAccountNumber();

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

    // Payout request has queue_if_low_balance flag set to true, workflow and on hold enabled for merchant, it won't go via payouts service
    public function testCreateQueuedPayoutViaAPIWhenWorkflowAndOnHoldEnabledForMerchant(string $balanceId = '')
    {
        if (empty($balanceId) === true)
        {
            $balanceId = $this->bankingBalance->getId();
        }

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 100
            ]
        );

        $balance = $this->getDbEntityById('balance', $balanceId, "live");

        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::WORKFLOW_VIA_PAYOUTS_MS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control',
            'on',
            'on',
            'control',
            'off',
            'on'
        );

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['account_number'] = $balance->getAccountNumber();

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

    // Since payout has queue_if_low_balance flag set to true, it won't go via payouts service
    public function testCreateQueuedPayoutViaPayoutServiceWhenQueuedPayoutViaServiceEnabledForMerchant(
        string $balanceId = '')
    {
        if (empty($balanceId) === true)
        {
            $balanceId = $this->bankingBalance->getId();
        }

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 100
            ]
        );

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control',
            'on',
            'on',
            'on'//Just use this as on, rest everything as default.
        );

        $balance = $this->getDbEntityById('balance', $balanceId, "live");

        $this->testData[__FUNCTION__] = $this->testData['testCreateQueuedPayoutViaPayoutService'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['account_number'] = $balance->getAccountNumber();

        $this->mockPayoutServiceCreate();

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('NEFT');

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'queued',
                'amount' => '500',
            ]
        );

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should have gone via payouts service
        $this->assertEquals(true, $payout->getIsPayoutService());

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

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), ['status' => Status::QUEUED]);

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

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), ['status' => Status::QUEUED]);

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

    // Since queued payout has is_payout_service value set to 1, it won't be processed via api. But we can assert that
    // it's balance id is being sent to payout service for dispatching.
    public function testProcessQueuedPayoutCreatedPayoutService()
    {
        $balanceId = $this->bankingBalance->getId();

        $request['content']['balance_ids'] = $balanceId;

        $this->mockPayoutServiceQueuedInitiate($request);

        $this->testCreateQueuedPayoutViaPayoutService();

        $payout1 = $this->getDbLastEntity('payout', 'live');

        // Doing this because as of now queued payouts can't be created via payout service
        $this->fixtures->edit(
            'payout',
            $payout1->getId(),
            [
                'is_payout_service' => 1
            ]
        );

        $this->testCreateQueuedPayoutViaPayoutService();

        $payout2 = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 1000000
            ]
        );

        $secondBankingBalance = $this->createSecondBankingBalance();

        $balanceId2 = $secondBankingBalance['id'];

        $this->fixtures->on('live')->create(
            'counter',
            [
                'balance_id' => $balanceId2,
                'account_type' => $secondBankingBalance->getAccountType(),
            ]
        );

        $this->testCreateQueuedPayoutViaPayoutService($balanceId2);

        $payout3 = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId2,
            [
                'balance' => 1000000
            ]
        );

        $response = $this->dispatchQueuedPayouts('live');

        $expectedResponse = [
            'balance_id_list' => [
                $balanceId,
                $balanceId2
            ]
        ];

        $this->assertEquals($expectedResponse, $response);

        $payout1->reload();

        $this->assertEquals('queued', $payout1->getStatus());

        $payout2->reload();

        // This payout should go to processing state as it is not created via payout service.
        $this->assertEquals('created', $payout2->getStatus());

        $payout3->reload();

        // This payout should go to processing state as it is not created via payout service.
        $this->assertEquals('created', $payout3->getStatus());
    }

    // Since queued payout has is_payout_service value set to 1, it won't be processed via api. Here we check that even
    // if dispatch to service fails, the dispatch functionality of queued payouts isn't affected at all. The balance
    // ids will be dispatched to payout service on later try of the cron.
    public function testProcessQueuedPayoutCreatedPayoutServiceWhenDispatchToServiceFails()
    {
        $balanceId = $this->bankingBalance->getId();

        // Since we are sending incorrect balance id to the mock, it'll fail there and it'll create failure response
        // from service and hence it'll behave as if the request to service failed.
        $request['content']['balance_ids'] = "random_balance_id";

        $this->mockPayoutServiceQueuedInitiate($request);

        $this->testCreateQueuedPayoutViaPayoutService();

        $payout1 = $this->getDbLastEntity('payout', 'live');

        // Doing this because as of now queued payouts can't be created via payout service
        $this->fixtures->edit(
            'payout',
            $payout1->getId(),
            [
                'is_payout_service' => 1
            ]
        );

        $this->testCreateQueuedPayoutViaPayoutService();

        $payout2 = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId,
            [
                'balance' => 1000000
            ]
        );

        $secondBankingBalance = $this->createSecondBankingBalance();

        $balanceId2 = $secondBankingBalance['id'];

        $this->fixtures->on('live')->create(
            'counter',
            [
                'balance_id' => $balanceId2,
                'account_type' => $secondBankingBalance->getAccountType(),
            ]
        );

        $this->testCreateQueuedPayoutViaPayoutService($balanceId2);

        $payout3 = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit(
            'balance',
            $balanceId2,
            [
                'balance' => 1000000
            ]
        );

        $response = $this->dispatchQueuedPayouts('live');

        $expectedResponse = [
            'balance_id_list' => [
                $balanceId,
                $balanceId2
            ]
        ];

        $this->assertEquals($expectedResponse, $response);

        $payout1->reload();

        $this->assertEquals('queued', $payout1->getStatus());

        $payout2->reload();

        // This payout should go to processing state as it is not created via payout service.
        $this->assertEquals('created', $payout2->getStatus());

        $payout3->reload();

        // This payout should go to processing state as it is not created via payout service.
        $this->assertEquals('created', $payout3->getStatus());
    }

    public function createSecondBankingBalance()
    {
        // Create second Balance
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'shared',
            'channel' => 'icici',
        ];

        $secondBankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        // Create Second Bank Account

        $virtualAccount = $this->fixtures->create('virtual_account');
        $secondBankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($secondBankAccount);
        $virtualAccount->balance()->associate($secondBankingBalance);
        $virtualAccount->save();

        $secondBankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());

        $secondBankingBalance->save();

        $mode = $this->getConnection()->getName();

        $balance = $this->getDbEntity('balance', [
            'merchant_id'  => '10000000000000',
            'account_type' => 'shared'
        ], $mode);

        return $balance;
    }

    public function testCreateLedgerForQueuedPayoutCreatedViaService()
    {
        $this->testCreatePayoutEntry();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->fixtures->on('live')->edit(
            'balance',
            $balance->getId(),
            [
                'balance' => 0
            ]
        );

        $this->ba->appAuthLive();

        $countOfTransactionsBefore = count($this->getDbEntities('transaction', [], 'live'));

        $response = $this->startTest();

        $countOfTransactionsAfter = count($this->getDbEntities('transaction', [], 'live'));

        $this->assertEquals($countOfTransactionsBefore, $countOfTransactionsAfter);

        $this->assertEquals(null, $response['transaction_id']);
    }

    public function testServiceCancelFailure()
    {
        $this->mockPayoutServiceCancel(true);

        $this->testCreatePayout();

        $queuedPayout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), ['status' => Status::QUEUED]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $queuedPayout->reload();

        $this->assertEquals(Status::QUEUED, $queuedPayout['status']);
    }

    public function testCreateLedgerForStatusCodeValueFowLowBalance()
    {
        $this->testCreatePayoutEntry();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->fixtures->on('live')->edit(
            'balance',
            $balance->getId(),
            [
                'balance' => 0
            ]
        );

        $this->ba->appAuthLive();

        $countOfTransactionsBefore = count($this->getDbEntities('transaction', [], 'live'));

        $response = $this->startTest();

        $countOfTransactionsAfter = count($this->getDbEntities('transaction', [], 'live'));

        $this->assertEquals($countOfTransactionsBefore, $countOfTransactionsAfter);

        $this->assertEquals(null, $response['transaction_id']);
    }

    public function testCreateWorkflowPayoutEntry()
    {
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->disableWorkflowMocks();

        $this->startTest();

        $workFlowActionAfter = $this->getDbLastEntity('workflow_action', 'live');

        $this->assertNotNull($workFlowActionAfter);

        $this->assertEquals($workFlowActionAfter['entity_id'],$this->testData[__FUNCTION__]['request']['content']['id']);

        $this->assertEquals('open', $workFlowActionAfter['state']);

        $this->assertEquals(0, $workFlowActionAfter['approved']);

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertNull($payout);
    }

    public function testCreateWorkflowPayoutEntryForNonWorkflowPayout()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertNull($payout);
    }

    // making a create workflow payout call twice and getting error
    // the 2nd time as there is already a wf action.
    public function testCreateWorkflowPayoutEntryDuplicateRequest()
    {
        $this->testCreateWorkflowPayoutEntry();
        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->startTest();
    }

    public function getResponseForPayoutAnalyticsServiceMock($fail, $status = 'processing')
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
                   'data' =>
                   [
                       'payouts_count' =>
                       [
                           'result' => [
                               [
                                   'value' => 3
                               ]
                               ],
                               'last_updated_at' => 1637643003
                       ],
                       'payouts_daywise' => [
                           'result' => [
                               [
                                  'value' => 0,
                                  'timestamp' =>  1635051003,
                               ],
                               [
                                   'value' => 100,
                                   'timestamp' => 1635137403,
                               ]
                           ],
                           'last_updated_at' => 1637643003
                       ],
                       'payouts' =>
                           [
                               'result' => [
                                   [
                                       'value' => 300
                                   ]
                               ],
                               'last_updated_at' => 1637643003
                           ],
                   ]
                ]);

            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    public function testAdminFetchPayoutsViaService()
    {
        $this->mockPayoutServiceAdminFetch();

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);

        $this->assertEquals('pout_Gg7sgBZgvYjlSB', $response['id']);
    }

    public function testAdminFetchReversalsViaService()
    {
        $this->mockPayoutServiceAdminFetch();

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);

        $this->assertEquals('rev_Gg7sgBZgvYjlSB', $response['id']);
    }

    public function testAdminFetchPayoutLogsViaService()
    {
        $this->mockPayoutServiceAdminFetch();

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);

        $this->assertEquals('poutlog_Gg7sgBZgvYjlSB', $response['id']);
    }

    public function testAdminFetchPayoutSourcesViaService()
    {
        $this->mockPayoutServiceAdminFetch();

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);

        $this->assertEquals('poutsrc_Gg7sgBZgvYjlSB', $response['id']);
    }

    public function adminGetResponseForService($entity, $id)
    {
        if (empty($entity) === true)
        {
            return [];
        }

        $entity = str_replace('_', '', $entity);

        $function = 'getAdminFetchFor' . ucfirst($entity) . 'ViaService';

        return $this->$function($id);
    }

    protected function getAdminFetchForPayoutsViaService($id)
    {
        if (empty($id) === false)
        {
            return $this->getAdminFetchForPayoutsByIdViaService();
        }

        return [];
    }

    protected function getAdminFetchForReversalsViaService($id)
    {
        if (empty($id) === false)
        {
            return $this->getAdminFetchForReversalsByIdViaService();
        }

        return [];
    }

    protected function getAdminFetchForPayoutLogsViaService($id)
    {
        if (empty($id) === false)
        {
            return $this->getAdminFetchForPayoutLogsByIdViaService();
        }

        return [];
    }

    protected function getAdminFetchForPayoutSourcesViaService($id)
    {
        if (empty($id) === false)
        {
            return $this->getAdminFetchForPayoutSourcesByIdViaService();
        }

        return [];
    }

    protected function getAdminFetchForPayoutsByIdViaService($status = 'created')
    {
        $response =
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
            ];

        return $response;
    }

    protected function getAdminFetchForReversalsByIdViaService()
    {
        $response =
            [
                "id"             => "rev_Gg7sgBZgvYjlSB",
                "entity"         => "reversal",
                "amount"         => 100,
                "currency"       => "INR",
                "payout_id"      => "Gg7sgBZgvYjlSC",
                "merchant_id"    => "10000000000000",
                "balance_id"     => "10000000000000",
                "notes"          => "",
                "fees"           => 0,
                "tax"            => 0,
                "utr"            => "",
                "transaction_id" => "Fg7sgBZgvYjlSB",
                "created_at"     => 1614325826,
                "channel"        => "yesbank",
            ];

        return $response;
    }

    protected function getAdminFetchForPayoutLogsByIdViaService()
    {
        $response =
            [
                "id"           => "poutlog_Gg7sgBZgvYjlSB",
                "entity"       => "payout_log",
                "payout_id"    => "Gg7sgBZgvYjlSC",
                "triggered_by" => "",
                "mode"         => 0,
                "to"           => "processed",
                "from"         => "initiated",
                "event"        => "txn_Fg7sgBZgvYjlSB",
                "created_at"   => 1614325826,
            ];

        return $response;
    }

    public function getAdminFetchForPayoutSourcesByIdViaService()
    {
        $response =
            [
                "id"          => "poutsrc_Gg7sgBZgvYjlSB",
                "entity"      => "payout_source",
                "payout_id"   => "Gg7sgBZgvYjlSC",
                "source_id"   => "vp_Gg7sgBZgvYjlSJ",
                "source_type" => "vendor_payment",
                "priority"    => 1,
                "created_at"  => 1614325826,
            ];

        return $response;
    }

    protected function mockRazorxDefault()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  return 'on';
                              }));
    }

    // test api is not sending status update to payout service when razorx experiment is on
    // below test is for processed status
    public function testStatusUpdateToPayoutServiceForProcessedStatusWhenCallerIsFtsWebhook()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $payoutServiceDetailsMock = Mockery::mock(PayoutServiceDetails::class, [$this->app])->makePartial()
                                           ->shouldReceive('updatePayoutDetailsViaFTS')
                                           ->withAnyArgs()->times(0)->getMock();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $payoutServiceStatusMock = Mockery::mock(PayoutServiceStatus::class, [$this->app])->makePartial()
                                        ->shouldReceive('updatePayoutStatusViaFTS')
                                        ->withAnyArgs()->times(0)->getMock();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $this->ba->appAuthLive();

        $this->mockRazorxDefault();

        $this->startTest();

        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                             ], 'live')->first();

        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('processed', $payout->getStatus());
    }

    // test api is  sending status update to payout service when razorx experiment is on but status updates were pushed
    // from admin dashboard . below test is for processed status
    public function testStatusUpdateToPayoutServiceForProcessedStatusWhenCalledManuallyViaAdminDashboard()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payoutServiceDetailsMock = Mockery::mock(PayoutServiceDetails::class, [$this->app])->makePartial()
                                           ->shouldReceive('updatePayoutDetailsViaFTS')
                                           ->withAnyArgs()->times(0)->getMock();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $payoutServiceStatusMock = Mockery::mock(PayoutServiceStatus::class, [$this->app])->makePartial()
                                          ->shouldReceive('updatePayoutStatusViaFTS')
                                          ->withAnyArgs()->times(1)->getMock();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $this->ba->appAuthLive();

        $this->mockRazorxDefault();

        $request = [
            'url'       => '/payouts/' . $payout['id'] . '/manual/status',
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'processed',
            ]
        ];

        $this->ba->adminAuth('live');

        $response = $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('processed', $payout->getStatus());
    }

    // even when disable_status_update_to_payout_service razorx experiment is on , status updates via admin dashboard
    // should go to payout service
    public function testStatusUpdateToPayoutServiceForFailedStatusWhenCalledManuallyViaAdminDashboard()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payoutServiceDetailsMock = Mockery::mock(PayoutServiceDetails::class, [$this->app])->makePartial()
                                           ->shouldReceive('updatePayoutDetailsViaFTS')
                                           ->withAnyArgs()->times(0)->getMock();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $payoutServiceStatusMock = Mockery::mock(PayoutServiceStatus::class, [$this->app])->makePartial()
                                          ->shouldReceive('updatePayoutStatusViaFTS')
                                          ->withAnyArgs()->times(1)->getMock();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $this->ba->appAuthLive();

        $this->mockRazorxDefault();

        $this->fixtures->on('live')->edit('payout', $payout['id'], ['status' => 'initiated', 'transaction_id' => null]);

        $request = [
            'url'       => '/payouts/' . $payout['id'] . '/manual/status',
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'failed',
            ]
        ];

        $this->ba->adminAuth('live');

        $response = $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('failed', $payout->getStatus());

        $this->assertNotNull($payout->getFailedAt());
    }

    public function testCreatePayoutEntryViaPayoutsLinkWithWFEnabled() {

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::WORKFLOW_VIA_PAYOUTS_MS]);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertNotNull($payout);
        $this->assertEquals($payout['workflow_feature'], 1);
        $this->assertEquals($payout['is_payout_service'], false);
    }
}

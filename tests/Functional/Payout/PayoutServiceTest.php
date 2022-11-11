<?php

namespace Functional\Payout;

use DB;
use Config;
use Mockery;
use Carbon\Carbon;
use Requests_Response;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Core;
use RZP\Models\Pricing\Fee;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Services\RazorXClient;
use RZP\Models\Payout\Validator;
use RZP\Models\Feature\Constants;
use RZP\Jobs\BatchPayoutsProcess;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\DataMigration;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\WorkflowFeature;
use RZP\Jobs\PayoutServiceDataMigration;
use RZP\Services\PayoutService\BulkPayout;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Balance\Type as Type;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Counter\Entity as CounterEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Jobs\FreePayoutMigrationForPayoutsService;
use RZP\Models\Settings\Entity as SettingsEntity;
use RZP\Models\Merchant\Balance\Entity as Balance;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Services\PayoutService\DataConsistencyChecker;
use RZP\Services\PayoutService\Get as PayoutServiceGet;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Merchant\Balance\AccountType as AccountType;
use RZP\Services\PayoutService\Retry as PayoutServiceRetry;
use RZP\Services\PayoutService\Fetch as PayoutServiceFetch;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Services\PayoutService\Status as PayoutServiceStatus;
use RZP\Services\PayoutService\Cancel as PayoutServiceCancel;
use RZP\Services\PayoutService\Details as PayoutServiceDetails;
use RZP\Services\PayoutService\PayoutsCreateFailureProcessingCron;
use RZP\Services\PayoutService\PayoutsUpdateFailureProcessingCron;
use RZP\Services\PayoutService\FreePayout as PayoutServiceFreePayout;
use RZP\Services\PayoutService\QueuedInitiate as PayoutServiceQueuedInitiate;
use RZP\Services\PayoutService\MerchantConfig as PayoutServiceMerchantConfig;
use RZP\Services\PayoutService\DashboardScheduleTimeSlots as PayoutServiceDashboardScheduleTimeSlots;

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

    public function mockPayoutServiceCreate($fail = false,
                                            $metadata = [],
                                            $request = [],
                                            $status = 'processing',
                                            $insufficient_balance = false,
                                            $newBankingError = false)
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

                                            if (isset($request['headers']
                                                    [RequestHeader::X_PAYOUT_IDEMPOTENCY]) === true)
                                            {
                                                $idempotencyKey =
                                                    $request['headers'][RequestHeader::X_PAYOUT_IDEMPOTENCY];

                                                if (empty($idempotencyKey) === false)
                                                {
                                                    return ($arg['headers'][RequestHeader::X_PAYOUT_IDEMPOTENCY] ===
                                                            $idempotencyKey);
                                                }
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
                                    $this->createResponseForPayoutServiceMock($fail, $status, $insufficient_balance, $newBankingError, $metadata)
                                );

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);
    }

    public function mockPayoutServiceFetch($fail = false, $request = [], $errorDescription = 'Service Failure')
    {
        $payoutServiceFetchMock = Mockery::mock('RZP\Services\PayoutService\Fetch',
                                                [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceFetchMock->shouldReceive('sendRequest')
                               ->withArgs(
                                   function($arg) use ($request) {
                                       try
                                       {
                                           // Using this method only here as we want to check if the keys in the
                                           // request are coming properly or not.
                                           $this->assertArrayKeySelectiveEquals($request, $arg);

                                           if (empty($request['url']) === false)
                                           {
                                               return (substr($arg['url'], -1 * strlen($request['url'])) ===
                                                       $request['url']);
                                           }

                                           return false;
                                       }
                                       catch (\Throwable $e)
                                       {
                                           return false;
                                       }
                                   }
                               )
                               ->andReturnUsing(
                                   function() use ($request, $fail, $errorDescription) {
                                       return $this->getResponseForPayoutFetchServiceMock($fail,
                                                                                          $request,
                                                                                          'processing',
                                                                                          $errorDescription);
                                   }
                               );

        $this->app->instance(PayoutServiceFetch::PAYOUT_SERVICE__FETCH, $payoutServiceFetchMock);
    }

    public function mockPayoutServiceFetchShouldNotBeInvoked()
    {
        $payoutServiceFetchMock = Mockery::mock('RZP\Services\PayoutService\Fetch',
                                                [$this->app])->makePartial();

        $payoutServiceFetchMock->shouldNotReceive('fetch');

        $payoutServiceFetchMock->shouldNotReceive('fetchMultiple');

        $this->app->instance(PayoutServiceFetch::PAYOUT_SERVICE__FETCH, $payoutServiceFetchMock);
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

    public function createResponseForPayoutServiceMock($fail, $status = 'processing', $insufficient_balance = false, $newBankingError = false, $metadata = [])
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
        elseif ($insufficient_balance === true)
        {
            $response->body = json_encode(
                [
                    "error"   =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
                            "field"       => "",
                            "source"      => "",
                            "step"        => "",
                            "reason"      => "",
                            "metadata"    => [],
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        }
        elseif ($newBankingError === true)
        {
            $content = [
                "id"                =>   "pout_Gg7sgBZgvYjlSB",
                "entity"            =>   "payout",
                "fund_account_id"   =>   "fa_100000000000fa",
                "amount"            =>   100,
                "currency"          =>   "INR",
                "merchant_id"       =>   "10000000000000",
                "notes"             =>   "",
                "fees"              =>   590,
                "tax"               =>   90,
                "status"            =>   $status,
                "purpose"           =>   "refund",
                "utr"               =>   "",
                "reference_id"      =>   null,
                "narration"         =>   "test Merchant Fund Transfer",
                "batch_id"          =>   "",
                "initiated_at"      =>   1614325830,
                "failure_reason"    =>   null,
                "created_at"        =>   1614325826,
                "fee_type"          =>   null,
                "mode"              =>   'IMPS',
                "error"   =>
                    [
                        "code"        => '',
                        "description" => '',
                        "field"       => '',
                        "source"      => '',
                        "step"        => '',
                        "reason"      => '',
                        "metadata"    => [],
                    ]
            ];

            foreach ($metadata as $key => $value)
            {
                $content[$key] = $value;
            }

            $response->body = json_encode($content);
            $response->status_code = 200;
            $response->success = true;
        }
        else
        {
            $content = [
                "id"                =>   "pout_Gg7sgBZgvYjlSB",
                "entity"            =>   "payout",
                "fund_account_id"   =>   "fa_100000000000fa",
                "amount"            =>   100,
                "currency"          =>   "INR",
                "merchant_id"       =>   "10000000000000",
                "notes"             =>   "",
                "fees"              =>   590,
                "tax"               =>   90,
                "status"            =>   $status,
                "purpose"           =>   "refund",
                "utr"               =>   "",
                "reference_id"      =>   null,
                "narration"         =>   "test Merchant Fund Transfer",
                "batch_id"          =>   "",
                "initiated_at"      =>   1614325830,
                "failure_reason"    =>   null,
                "created_at"        =>   1614325826,
                "fee_type"          =>   null,
                "mode"              =>   'IMPS',
            ];

            foreach ($metadata as $key => $value)
            {
                $content[$key] = $value;
            }

            $response->body = json_encode($content);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    public function getResponseForPayoutFetchServiceMock($fail,
                                                         $request,
                                                         $status = 'processing',
                                                         $errorDescription = 'Service Failure')
    {
        $url = $request['url'];

        if (empty($url) === false)
        {
            $getPayoutByIdUrl = '/payouts/pout_';

            if (substr($url, 0, strlen($getPayoutByIdUrl)) === $getPayoutByIdUrl)
            {
                return $this->getResponseForPayoutByIdServiceMock($fail, $status, $errorDescription);
            }
            else
            {
                return $this->getResponseForPayoutFetchMultipleServiceMock($fail, $status, $errorDescription);
            }
        }

        return new Requests_Response();
    }

    public function getResponseForPayoutByIdServiceMock($fail,
                                                        $status = 'processing',
                                                        $errorDescription = 'Service Failure')
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => $errorDescription,
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

    public function getResponseForPayoutFetchMultipleServiceMock($fail,
                                                                 $status = 'processing',
                                                                 $errorDescription = 'Service Failure')
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => $errorDescription,
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        }
        else
        {
            $response->body        = json_encode(
                [
                    "entity"   => "collection",
                    "count"    => 1,
                    "has_more" => true,
                    "items"    => [
                        [
                            "id"              => "pout_Gg7sgBZgvYjlSB",
                            "entity"          => "payout",
                            "fund_account_id" => "fa_100000000000fa",
                            "amount"          => 100,
                            "currency"        => "INR",
                            "merchant_id"     => "10000000000000",
                            "notes"           => "",
                            "fees"            => 0,
                            "tax"             => 0,
                            "status"          => $status,
                            "purpose"         => "refund",
                            "utr"             => "",
                            "reference_id"    => null,
                            "narration"       => "test Merchant Fund Transfer",
                            "batch_id"        => "",
                            "initiated_at"    => 1614325830,
                            "failure_reason"  => null,
                            "created_at"      => 1614325826,
                            "fee_type"        => null
                        ]
                    ]
                ]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function mockPayoutServiceCreateBulkPayout($fail = false, $request = [])
    {
        $createBulkPayoutMock = Mockery::mock('RZP\Services\PayoutService\BulkPayout',
                                           [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $defaultRequest['headers'][RequestHeader::X_Batch_Id] = "";

        $defaultRequest['headers'][RequestHeader::X_ENTITY_ID] = "";

        $request = array_merge($defaultRequest, $request);

        $createBulkPayoutMock->shouldReceive('sendRequest')
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
                              $this->createBulkPayoutResponseForPayoutServiceMock($fail)
                          );

        $this->app->instance(BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS, $createBulkPayoutMock);
    }

    public function createBulkPayoutResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'entity'          => 'payout',
                            'fund_account'    => [
                                'entity'       => 'fund_account',
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'HDFC0003780',
                                    'bank_name'      => 'HDFC Bank',
                                    'name'           => 'Vivek Karna',
                                    'account_number' => '50100244702362',
                                ],
                                'active'       => true,
                            ],
                            'amount'          => 100,
                            'currency'        => 'INR',
                            'fees'            => 590,
                            'tax'             => 90,
                            'status'          => 'processing',
                            'purpose'         => 'refund',
                            'utr'             => null,
                            'user_id'         => 'MerchantUser01',
                            'mode'            => 'IMPS',
                            'reference_id'    => null,
                            'narration'       => '123',
                            'idempotency_key' => 'batch_abc123'
                        ],
                    ]
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    public function mockPayoutServiceFreePayoutSet($fail = false, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $freePayoutSetMock = Mockery::mock('RZP\Services\PayoutService\FreePayout',
            [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $freePayoutSetMock->shouldReceive('sendRequest')
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
                $this->freePayoutSetResponseForPayoutServiceMock($fail)
            );

        $this->app->instance(PayoutServiceFreePayout::PAYOUT_SERVICE_FREE_PAYOUT, $freePayoutSetMock);
    }

    public function freePayoutSetResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    'free_payouts_count'           => 12,
                    'free_payouts_supported_modes' => ['IMPS']
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    public function mockPayoutServiceGetFreePayout($fail = false, $request = [])
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
                                 $this->freePayoutGetResponseForPayoutServiceMock($fail)
                             );

        $this->app->instance(PayoutServiceGet::PAYOUT_SERVICE_GET, $payoutServiceGetMock);
    }

    public function freePayoutGetResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {
            $response->body        = json_encode(
                [
                    'free_payouts_count'           => FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1,
                    'free_payouts_consumed'        => FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1,
                    'free_payouts_supported_modes' => FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES,
                ]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function mockPayoutServiceFreePayoutMigration($fail = false, $request = [])
    {
        $freePayoutMigrateMock = Mockery::mock('RZP\Services\PayoutService\FreePayout',
                                               [$this->app])->makePartial();

        $freePayoutMigrateMock->shouldReceive('sendRequest')
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
                                  $this->freePayoutMigrationResponseForPayoutServiceMock($fail)
                              );

        $this->app->instance(PayoutServiceFreePayout::PAYOUT_SERVICE_FREE_PAYOUT, $freePayoutMigrateMock);
    }

    public function freePayoutMigrationResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    'merchant_id'        => 'rzp12345678909',
                    'balance_id'         => 'bal12345678909',
                    'counter_migrated'   => true,
                    'settings_migrated'  => true
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    // Check payout Create Entry func on processor base
    public function testCreatePayoutEntry($mode = 'IMPS', $migratePayoutToPS = true)
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

        if ($migratePayoutToPS === true)
        {
            (new PayoutServiceDataMigration('live', [
                DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
                DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
                Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
            ]))->handle();

            $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

            $this->assertEquals($payout[Entity::ID], 'pout_' .$migratedPayout->id);

            $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);
        }

        return $payout;
    }

    // Check payout Create transaction func on processor base
    public function testCreatePayoutServiceTransaction($mode = 'IMPS', $migratePayoutToPS = true)
    {
        $payout = $this->testCreatePayoutEntry($mode, false);

        // Migration of the API Payout can be done by calling below code. It has a dedupe logic which won't allow migrating a payout more than once.
        // After migrating the payout we will change the id in API so that the code will not be able to find the payout
        // in API Db which will be the ideal scenario for PS payouts.
        if ($migratePayoutToPS === true)
        {
            (new PayoutServiceDataMigration('live', [
                DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
                DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
                Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
            ]))->handle();

            $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

            $this->assertEquals($payout[Entity::ID], 'pout_' .$migratedPayout->id);

            $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);
        }

        $this->ba->appAuthLive();

        $response = $this->startTest();

        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);
    }

    public function testDeductCreditsViaPayoutService()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'merchant_id'   => '10000000000000',
            'value'         => 500 ,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->startTest();

        $creditsAfterTestRun = $this->getDbLastEntity('credits', 'live');

        $creditsTransactionAfterTestRun = $this->getDbLastEntity('credit_transaction', 'live');

        $this->assertEquals(500, $creditsAfterTestRun["value"]);

        $this->assertEquals(100, $creditsAfterTestRun["used"]);

        $this->assertEquals('payout', $creditsTransactionAfterTestRun["entity_type"]);

        $this->assertEquals('Gg7sgBZgvYTTTT', $creditsTransactionAfterTestRun["entity_id"]);

        $this->assertEquals(100, $creditsTransactionAfterTestRun["credits_used"]);
    }

    public function testDeductCreditsViaPayoutServiceBadRequest()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testDeductCreditsViaPayoutServiceBadRequestInvalidStatus()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testDeductCreditsViaPayoutServiceAndCreditsNotAvailable()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'merchant_id'   => '10000000000000',
            'value'         => 50,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->startTest();

        $creditsAfterTestRun = $this->getDbLastEntity('credits', 'live');

        $this->assertEquals(50, $creditsAfterTestRun["value"]);

        $this->assertEquals(0, $creditsAfterTestRun["used"]);
    }

    public function testDeductCreditsViaPayoutServiceAndDoubleCreditRequestReceived()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12345',
            'merchant_id'   => '10000000000000',
            'value'         => 500,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id' => 'credTRANv12345',
            'entity_id'     => 'Gg7sgBZgvYTTTT',
            'entity_type'   => 'payout',
            'credits_used'  => 100,
            'credits_id'    => 'Gg7sgBZgv12345',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->startTest();

        $creditsAfterTestRun = $this->getDbLastEntity('credits', 'live');

        $creditsTransactionAfterTestRun = $this->getDbEntities('credit_transaction', ['credits_id' => 'Gg7sgBZgv12345', 'entity_type' => 'payout'], 'live');

        $this->assertEquals(500, $creditsAfterTestRun["value"]);

        $this->assertEquals(0, $creditsAfterTestRun["used"]);

        $this->assertEquals(1, $creditsTransactionAfterTestRun->count());
    }

    public function testDeductCreditsViaPayoutServiceAndInternalServerErrorCase()
    {
        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testReverseCreditsViaPayoutService()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12345',
            'merchant_id'   => '10000000000000',
            'value'         => 100,
            'used'          => 100,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12346',
            'merchant_id'   => '10000000000000',
            'value'         => 100,
            'used'          => 50,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12345',
            'entity_id'     => 'Gg7sgBZgvYTTTT',
            'entity_type'   => 'payout',
            'credits_used'  => 100,
            'credits_id'    => 'Gg7sgBZgv12345',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12346',
            'entity_id'     => 'Gg7sgBZgvYTTTT',
            'entity_type'   => 'payout',
            'credits_used'  => 50,
            'credits_id'    => 'Gg7sgBZgv12346',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->startTest();

        $creditsAfterTestRun1 = $this->getDbEntityById('credits','Gg7sgBZgv12345', 'live');

        $creditsAfterTestRun2 = $this->getDbEntityById('credits','Gg7sgBZgv12346', 'live');

        $creditsTransactionAfterTestRun1 = $this->getDbLastEntityOrderByCreatedAt('credit_transaction', ['credits_id' => 'Gg7sgBZgv12345', 'entity_type' => 'payout'], 'live');

        $creditsTransactionAfterTestRun2 = $this->getDbLastEntityOrderByCreatedAt('credit_transaction', ['credits_id' => 'Gg7sgBZgv12346', 'entity_type' => 'payout'], 'live');

        $this->assertEquals(0, $creditsAfterTestRun1["used"]);

        $this->assertEquals(0, $creditsAfterTestRun2["used"]);

        $this->assertEquals(-100, $creditsTransactionAfterTestRun1["credits_used"]);

        $this->assertEquals(-50, $creditsTransactionAfterTestRun2["credits_used"]);
    }

    public function testReverseCreditsViaPayoutServiceAndDoubleReversalRequestReceived()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12345',
            'merchant_id'   => '10000000000000',
            'value'         => 200,
            'used'          => 100,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12345',
            'entity_id'     => 'Revxyk0gB5Fx11',
            'entity_type'   => 'payout',
            'credits_used'  => -100,
            'credits_id'    => 'Gg7sgBZgv12345',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->startTest();

        $creditsAfterTestRun1 = $this->getDbEntityById('credits','Gg7sgBZgv12345', 'live');

        $creditsTransactionAfterTestRun1 = $this->getDbEntities('credit_transaction', ['credits_id' => 'Gg7sgBZgv12345', 'entity_type' => 'payout'], 'live');

        $this->assertEquals(200, $creditsAfterTestRun1["value"]);

        $this->assertEquals(100, $creditsAfterTestRun1["used"]);

        $this->assertEquals(1, $creditsTransactionAfterTestRun1->count());
    }

    public function testReverseCreditsViaPayoutServiceAndInternalServerErrorCase()
    {
        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testReverseCreditsViaPayoutServiceBadRequest()
    {
        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testReverseCreditsViaPayoutServiceAndSourceReversal()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12345',
            'merchant_id'   => '10000000000000',
            'value'         => 100,
            'used'          => 100,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12346',
            'merchant_id'   => '10000000000000',
            'value'         => 100,
            'used'          => 50,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12345',
            'entity_id'     => 'Gg7sgBZgvYTTTT',
            'entity_type'   => 'payout',
            'credits_used'  => 100,
            'credits_id'    => 'Gg7sgBZgv12345',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12346',
            'entity_id'     => 'Gg7sgBZgvYTTTT',
            'entity_type'   => 'payout',
            'credits_used'  => 50,
            'credits_id'    => 'Gg7sgBZgv12346',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->startTest();

        $creditsAfterTestRun1 = $this->getDbEntityById('credits','Gg7sgBZgv12345', 'live');

        $creditsAfterTestRun2 = $this->getDbEntityById('credits','Gg7sgBZgv12346', 'live');

        $creditsTransactionAfterTestRun1 = $this->getDbLastEntityOrderByCreatedAt('credit_transaction', ['credits_id' => 'Gg7sgBZgv12345', 'entity_type' => 'reversal'], 'live');

        $creditsTransactionAfterTestRun2 = $this->getDbLastEntityOrderByCreatedAt('credit_transaction', ['credits_id' => 'Gg7sgBZgv12346', 'entity_type' => 'reversal'], 'live');

        $this->assertEquals(0, $creditsAfterTestRun1["used"]);

        $this->assertEquals(0, $creditsAfterTestRun2["used"]);

        $this->assertEquals(-100, $creditsTransactionAfterTestRun1["credits_used"]);

        $this->assertEquals(-50, $creditsTransactionAfterTestRun2["credits_used"]);
    }

    public function testReverseCreditsViaPayoutServiceAndDoubleReversalRequestReceivedWithSourceReversal()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->fixtures->on('live')->create('credits', [
            'id'            => 'Gg7sgBZgv12345',
            'merchant_id'   => '10000000000000',
            'value'         => 200,
            'used'          => 100,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

        $this->fixtures->on('live')->create('credit_transaction', [
            'id'            => 'credTRANv12345',
            'entity_id'     => 'Revxyk0gB5Fx11',
            'entity_type'   => 'reversal',
            'credits_used'  => -100,
            'credits_id'    => 'Gg7sgBZgv12345',
            'created_at'    => Carbon::now()->subHours(6)->getTimestamp(),
            'updated_at'    => Carbon::now()->subHours(6)->getTimestamp()
        ]);

        $this->startTest();

        $creditsAfterTestRun1 = $this->getDbEntityById('credits','Gg7sgBZgv12345', 'live');

        $creditsTransactionAfterTestRun1 = $this->getDbEntities('credit_transaction', ['credits_id' => 'Gg7sgBZgv12345', 'entity_type' => 'reversal'], 'live');

        $this->assertEquals(200, $creditsAfterTestRun1["value"]);

        $this->assertEquals(100, $creditsAfterTestRun1["used"]);

        $this->assertEquals(1, $creditsTransactionAfterTestRun1->count());
    }

    public function testFetchPricingInfoForPayoutService($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testFetchPricingInfoForPayoutServiceBadRequest($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testFetchPricingInfoForPayoutServiceInternalServerError($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testProcessBulkPayoutDelayedInitiationForPayoutsService($mode = 'IMPS')
    {
        $this->testCreatePayoutEntry();

        $payout = $this->getDbLastEntity('payout','live');

        $this->assertTrue($payout->getIsPayoutService());

        $payoutID = $payout->getId();

        $this->fixtures->on('live')->edit(
            'payout',
            $payoutID,
            [
                Entity::STATUS => Status::BATCH_SUBMITTED,
            ]
        );

        $this->ba->cronAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout','live');

        $this->assertEquals($payoutID, $payout->getId());

        // Payout Should not have been processed and should remain in BATCH_SUBMITTED state only
        $this->assertEquals(Status::BATCH_SUBMITTED, $payout->getStatus());
    }

    public function testProcessBatchSubmittedPayoutForPayoutsService()
    {
        $this->testCreatePayoutEntry();

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                Entity::STATUS => Status::BATCH_SUBMITTED,
            ]
        );

        (new BatchPayoutsProcess('live', $payout[Entity::MERCHANT_ID]))->handle();

        $payout = $this->getDbEntityById('payout', $payout->getId(), 'live');

        // Payout Should not have been processed and should remain in BATCH_SUBMITTED state only
        $this->assertEquals(Status::BATCH_SUBMITTED, $payout->getStatus());
    }

    public function testCreatePayoutServiceTransactionWithFeeRewards($mode = 'IMPS')
    {
        $this->testCreatePayoutEntry($mode);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $testData = $this->testData['testCreatePayoutServiceTransaction'];

        $this->ba->appAuthLive();

        $response = $this->startTest($testData);

        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);

        $payout = $this->getLastEntity('payout', true, 'live');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        //$this->assertEquals(500, $payout['fees']);
        //$this->assertEquals('reward_fee', $payout['fee_type']);
    }

    public function testCreateLedgerForOnHoldPayoutCreatedViaPayoutService()
    {
        $psPayout = $this->testCreateOnHoldPayoutViaPayoutService();

        $this->ba->appAuthLive($this->config['applications.payouts_service.secret']);

        $response = $this->startTest();

        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);

        $payout = $this->getDbEntities('payout', ['id' => $psPayout['id']], 'live');

        $this->assertCount(0, $payout);
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
        $payout = $this->testCreatePayoutEntry($mode, false);

        //$request = [
        //    'method'  => 'POST',
        //    'url'     => '/payouts_service/create_ledger',
        //    'content' => [
        //        "id" => "Gg7sgBZgvYjlSB",
        //    ],
        //];
        //
        //$this->ba->appAuthLive();
        //
        //$response = $this->makeRequestAndGetContent($request);
        //s($response);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => 'randomtxnnnnnn',
            'status'         => 'created',
            'tax'            => 90,
            'fees'           => 590,
            'notes' => ['abc' => 'def']
        ]);

        $this->fixtures->create('transaction', [
            'id'          => 'randomtxnnnnnn',
            'entity_id'   => substr($payout['id'], 5),
            'type'        => 'payout',
            'merchant_id' => $payout['merchant_id'],
            'amount'      => $payout['amount'],
            'debit'       => $payout['amount'],
            'balance_id'  => $payout['balance_id'],
            'posted_at'   => $payout['created_at'],
        ]);

        $payout = $this->getLastEntity('payout', true, 'live');

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], 'pout_' . $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->assertEquals($payout[Entity::ID], 'pout_' . $fta->getSourceId());

        return $payout;
    }

    public function testPayoutServiceFtaCreationWithoutPayoutInAPI()
    {
        $payout = $this->testCreatePayoutEntry('IMPS', false);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => 'randomtxnnnnnn',
            'status'         => 'created',
        ]);

        $this->fixtures->create('transaction', [
            'id'          => 'randomtxnnnnnn',
            'entity_id'   => substr($payout['id'], 5),
            'type'        => 'payout',
            'merchant_id' => $payout['merchant_id'],
            'amount'      => $payout['amount'],
            'debit'       => $payout['amount'],
            'balance_id'  => $payout['balance_id'],
            'posted_at'   => $payout['created_at']
        ]);

        $payout = $this->getDbLastEntity('payout', 'live');

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout->getCreatedAt(),
            DataMigration\Processor::TO   => $payout->getCreatedAt(),
            Entity::BALANCE_ID            => $payout->getBalanceId()
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout->getId(), $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $mock = Mockery::mock(\RZP\Services\FTS\FundTransfer::class, [$this->app])->makePartial();
        $mock = $mock->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('createAndSendRequest')->once()->andReturn(
            [
                'body' => [
                    'status'           => 'initiated',
                    'fund_transfer_id' => 123,
                    'fund_account_id'  => 'D6Z9Jfir2egAUT'
                ],
                'code' => 201,
            ]
        );

        $this->app->instance('fts_fund_transfer', $mock);

        (new FtsFundTransfer('live', $fta->getId(), null))->handle();

        $payouts = $this->getDbEntities('payout', ['id' => 'Gg7sgBZgvYjlSB']);

        $this->assertCount(0, $payouts);

        $fta->reload();

        $this->assertEquals('Gg7sgBZgvYjlSB', $fta->source->getId());
        $this->assertEquals('initiated', $fta->getStatus());
    }

    public function testCreatePayoutServiceFtaCreationWithFeeRewards($mode = 'IMPS')
    {
        $payout = $this->testCreatePayoutEntry($mode, false);

        //$this->testCreatePayoutServiceTransactionWithFeeRewards($mode);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => 'randomtxnnnnnn',
            'status'         => 'created',
            'tax'            => 0,
            'fees'           => 500,
            'fee_type' => 'reward_fee'
        ]);

        $this->fixtures->create('transaction', [
            'id'          => 'randomtxnnnnnn',
            'entity_id'   => substr($payout['id'], 5),
            'type'        => 'payout',
            'merchant_id' => $payout['merchant_id'],
            'amount'      => $payout['amount'],
            'debit'       => $payout['amount'],
            'balance_id'  => $payout['balance_id'],
            'posted_at'   => $payout['created_at']
        ]);

        $this->fixtures->create('credits', [
            'id'          => 'randomcreditss',
            'merchant_id' => '10000000000000',
            'value'       => 1500,
            'campaign'    => 'test rewards',
            'type'        => 'reward_fee',
            'product'     => 'banking',
            'used' => 500
        ]);

        $this->fixtures->create('credit_transaction', [
            'id'             => 'randomcredittx',
            'entity_id'      => substr($payout['id'], 5),
            'entity_type'    => 'payout',
            'credits_used'   => 500,
            'credits_id'     => 'randomcreditss',
            'transaction_id' => 'randomtxnnnnnn',
        ]);

        $payout = $this->getDbLastEntity('payout', 'live');

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout->getCreatedAt(),
            DataMigration\Processor::TO   => $payout->getCreatedAt(),
            Entity::BALANCE_ID            => $payout->getBalanceId()
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout->getId(), $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $testData = $this->testData['testCreatePayoutServiceFtaCreation'];

        $this->ba->appAuthLive();

        $this->startTest($testData);
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

        $payout = $this->testCreatePayoutServiceFtaCreation();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        //$payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        //$this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        //$this->assertNotNull($txn['posted_at']);

        //$feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');
        //
        //$expectedBreakup = [
        //    'name'            => "payout",
        //    'transaction_id'  => $txnId,
        //    'pricing_rule_id' => "Bbg7cl6t6I3XA5",
        //    'percentage'      => null,
        //    'amount'          => 500,
        //];
        //
        //$this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutViaDashboard()
    {
        $this->mockPayoutServiceCreate();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuthLive();
        $response = $this->startTest();

        $this->assertEquals("test Merchant Fund Transfer", $response[Entity::NARRATION]);
        $this->assertEquals(1614325830, $response[Entity::INITIATED_AT]);
        $this->assertEquals("10000000000000", $response[Entity::MERCHANT_ID]);
    }

    public function testCreatePayoutWithNewBankingError(): array
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true);

        $payout = $this->testCreatePayoutServiceFtaCreation();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        //$payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        //$this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        //$feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');
        //
        //$expectedBreakup = [
        //    'name'            => "payout",
        //    'transaction_id'  => $txnId,
        //    'pricing_rule_id' => "Bbg7cl6t6I3XA5",
        //    'percentage'      => null,
        //    'amount'          => 500,
        //];
        //
        //$this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutInsufficientBalance()
    {
        $this->mockPayoutServiceCreate(false, [], [], 'created', true);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

    }

    public function testCreateInternalPayoutViaMicroService(): array
    {
        $this->mockPayoutServiceCreate();

        $payout = $this->testCreatePayoutServiceFtaCreation();

        $this->mockRazorxDefault();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        //$payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        //$this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        //$feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');
        //
        //$expectedBreakup = [
        //    'name'            => "payout",
        //    'transaction_id'  => $txnId,
        //    'pricing_rule_id' => "Bbg7cl6t6I3XA5",
        //    'percentage'      => null,
        //    'amount'          => 500,
        //];
        //
        //$this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutWithFeeRewards(): array
    {
        $metadata = [
            'entity'          => 'payout',
            'amount'          => 100,
            'purpose'         => 'refund',
            'status'          => 'processing',
            'mode'            => 'IMPS',
            'tax'             => 0,
            'fees'            => 500,
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        $this->testCreatePayoutServiceFtaCreationWithFeeRewards();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals('pout_Gg7sgBZgvYjlSB', $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], $payoutAttempt['channel']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertEquals($payout['balance_id'], $txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

        //$expectedBreakup = [
        //    'name' => "payout",
        //    'transaction_id' => $txnId,
        //    'pricing_rule_id' => "Bbg7cl6t6I3XA5",
        //    'percentage' => null,
        //    'amount' => 500,
        //];
        //
        //$this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][0]);

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
        $metadata = [
            'tax'             => 0,
            'fees'            => 0,
            'origin'          => 'api',
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        $this->fixtures->merchant->addFeatures([Feature\Constants::INTERNAL_CONTACT_VIA_PS]);

        $this->testCreatePayoutEntry('IMPS');

        $this->setupWorkflowForLiveMode();
        $this->fixtures->merchant->removeFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Status::CREATE_REQUEST_SUBMITTED, $payout->getStatus());
        $this->assertEquals($payout['merchant_id'], '10000000000000');
        $this->assertEquals($payout['is_payout_service'], 1);
    }

    public function testCreatePayoutInternalContactWithoutWorkflowsFlag()
    {
        $metadata = [
            'tax'             => 0,
            'fees'            => 0,
            'origin'          => 'api',
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        $this->fixtures->merchant->addFeatures([Feature\Constants::INTERNAL_CONTACT_VIA_PS]);

        $this->testCreatePayoutEntry('IMPS');

        $this->setupWorkflowForLiveMode();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'workflow_feature' => 4,
            ]
        );

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Status::CREATE_REQUEST_SUBMITTED, $payout->getStatus());
        $this->assertEquals($payout['merchant_id'], '10000000000000');
        $this->assertEquals($payout['is_payout_service'], 1);
        $this->assertEquals(WorkflowFeature::getWorkflowFeatureFromInt($payout['workflow_feature']),
            WorkflowFeature::SKIP_FOR_INTERNAL_PAYOUT);
    }

    public function testCreatePayoutInternalContactWithWorkflows()
    {
        $metadata = [
            'tax'             => 0,
            'fees'            => 0,
            'origin'          => 'api',
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        $this->fixtures->merchant->addFeatures([Feature\Constants::WORKFLOW_VIA_PAYOUTS_MS]);
        $this->fixtures->merchant->addFeatures([Feature\Constants::INTERNAL_CONTACT_VIA_PS]);

        $this->testCreatePayoutEntry('IMPS');

        $this->setupWorkflowForLiveMode();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'workflow_feature' => 1,
            ]
        );

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Status::CREATE_REQUEST_SUBMITTED, $payout->getStatus());
        $this->assertEquals($payout['merchant_id'], '10000000000000');
        $this->assertEquals($payout['is_payout_service'], 1);
        $this->assertEquals(WorkflowFeature::getWorkflowFeatureFromInt($payout['workflow_feature']),
            Constants::PAYOUT_WORKFLOWS);

    }

    public function testCreatePayoutServiceFailure()
    {
        $this->mockPayoutServiceCreate(true);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateReversalEntry()
    {
        $psPayout = $this->testCreatePayout();

        //$payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => substr($psPayout['id'], 5),
            'utr'        => '123456678',
            'amount'     => $psPayout['amount'],
            'currency'   => $psPayout['currency'],
            'channel'    => $psPayout['channel'],
        ];

        $this->ba->appAuthLive();

        $response = $this->startTest();

        $this->assertNotEmpty($response['transaction_id']);
    }

    public function testCreateReversalEntryWithFeeRewards()
    {
        $this->markTestSkipped("the route that this testcase tests will not be used going forward. However, this test case passes in local setup.");

        $this->testCreatePayoutWithFeeRewards();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = $this->testData['testCreateReversalEntry'];

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => 'Gg7sgBZgvYjlSB',
            'utr'        => '123456678',
            'amount'     => $payout['amount'],
            'currency'   => $payout['currency'],
            'channel'    => $payout['channel'],
        ];

        $this->ba->appAuthLive();

        $response = $this->startTest($testData);

        $this->assertNotEmpty($response['transaction_id']);

        $creditEntity = $this->getLastEntity('credits', true, 'live');
        $this->assertEquals(0, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true, 'live');
        $this->assertEquals('reversal', $creditTxnEntity['entity_type']);
        $this->assertEquals(-$payout['fees'], $creditTxnEntity['credits_used']);

        $reversal = $this->getLastEntity('reversal', true, 'live');
        $this->assertEquals($payout['amount'], $reversal['amount']);
    }

    public function testCreateReversalEntryDuplicateCreationNotAllowed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = $this->testData['testCreateReversalEntry'];

        $testData['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => 'Gg7sgBZgvYjlSB',
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
        $psPayout = $this->testCreatePayout();

        /** @var Entity $payout */
        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = substr($psPayout['id'], 5);

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('processed');

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => substr($psPayout['id'], 5),
                'source_type' => 'payout',
            ], 'live')->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        //$payout->reload();
        //
        //$this->assertEquals('processed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutToFailed()
    {
        $payout = $this->testCreatePayout();

        $payout = (new Core)->getAPIModelPayoutFromPayoutService(substr($payout['id'], 5));

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

        //$payout->reload();

        //$this->assertEquals('reversed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutDetailsFailure()
    {
        $psPayout = $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payout->setId(substr($psPayout['id'], 5));

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
        $psPayout = $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payout->setId(substr($psPayout['id'], 5));

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

    public function testCreatePayoutWithIdempotencyKey()
    {
        $request['headers'][RequestHeader::X_PAYOUT_IDEMPOTENCY] =
            $this->testData[__FUNCTION__]['request']['server']['HTTP_' . \RZP\Http\RequestHeader::X_PAYOUT_IDEMPOTENCY];

        $this->mockPayoutServiceCreate(false, [], $request, 'pending');

        $payoutData = [
            'id'                   => 'Gg7sgBZgvYjlSB',
            'merchant_id'          => "10000000000000",
            'fund_account_id'      => "100000000000fa",
            'method'               => "fund_transfer",
            'reference_id'         => null,
            'balance_id'           => "KHTaUGgTXc0dhH",
            'user_id'              => "random_user123",
            'batch_id'             => null,
            'idempotency_key'      => "random_key",
            'purpose'              => "refund",
            'narration'            => "Batman",
            'purpose_type'         => "refund",
            'amount'               => 100,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 590,
            'tax'                  => 90,
            'status'               => "pending",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'scheduled_at'         => null,
            'queued_at'            => null,
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'cancellation_user_id' => null,
            'registered_name'      => "SUSANTA BHUYAN",
            'queued_reason'        => "beneficiary_bank_down",
            'on_hold_at'           => 1663092113,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $idempotencyEntity = $this->getLastEntity('idempotency_key', true, 'live');

        $this->assertEquals($idempotencyEntity['idempotency_key'], 'idem_key_test');
        $this->assertEquals($idempotencyEntity['merchant_id'], '10000000000000');
        $this->assertEmpty($idempotencyEntity['source_id']);
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

        $metadata = [
            'status' => 'queued',
            'tax'    => 0,
            'fees'   => 0,
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('IMPS', false);

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'on_hold',
            ]
        );

        $payout = $this->getLastEntity('payout', true, 'live');

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], 'pout_' .$migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        //$payout = $this->getDbLastEntity('payout', 'live');
        //
        //// Payout should have gone via payouts service
        //$this->assertEquals(true, $payout->getIsPayoutService());
        //
        //// On private auth, payout.user_id should be null
        //$this->assertNull($payout['user_id']);
        //
        //$this->assertEquals('on_hold', $payout->getStatus());

        return $payout;
    }

    public function testCreateScheduledPayoutViaPayoutService()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::SCHEDULE_PAYOUT_VIA_PS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayoutViaPayoutService'];

        $testData['request']['url']              = '/payouts';
        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $metadata = [
            'status' => 'scheduled',
            'tax'    => 0,
            'fees'   => 0,
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('IMPS', false);

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'scheduled',
            ]
        );

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Payout should have gone via payouts service
        $this->assertEquals(true, $payout->getIsPayoutService());

        $this->assertEquals($currentTime->getTimestamp(), $payout['scheduled_on']);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        $this->assertEquals('scheduled', $payout->getStatus());
    }

    public function testCreateScheduledPayoutWhenScheduledPayoutFeatureIsNotEnabled()
    {
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayoutWhenScheduledPayoutFeatureIsNotEnabled'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';

        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->ba->proxyAuthLive();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(false, $payout->getIsPayoutService());

        $this->assertEquals('scheduled', $payout->getStatus());

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

        $metadata = [
            'amount'          => 500,
            'purpose'         => 'refund',
            'status'          => 'queued',
            'mode'            => 'NEFT',
            'tax'             => 0,
            'fees'            => 0,
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('NEFT', false);

        $payout = $this->getDbLastEntity('payout','live');

        $this->fixtures->on('live')->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'queued',
                'amount' => '500',
            ]
        );

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

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

        $psPayout = $this->testCreatePayoutEntry('IMPS', false);

        $queuedPayout = $payout = $this->getDbLastEntity('payout', 'live');

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01', 'live')->toArrayPublic();

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), [
            'status'               => Status::QUEUED,
            'tax'                  => 90,
            'fees'                 => 590,
            'cancellation_user_id' => 'MerchantUser01',
        ]);

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user'] = $cancellationUser;

        $this->ba->proxyAuthLive();

        $response = $this->startTest();

        // Commenting out cancellation related assertions as payout in API won't be updated in this route.

        //$cancelledPayout = $this->getDbLastEntity('payout', 'live');

        // Assert that payout got cancelled
        //$this->assertEquals(Status::QUEUED, $cancelledPayout['status']);
        //$this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);

        // Assert that payout has the correct cancellation user id as well.
        //$this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testServiceCancelQueuedPayoutPrivateAuth()
    {
        $this->mockPayoutServiceCancel();

        $psPayout = $this->testCreatePayoutEntry('IMPS', false);

        $queuedPayout = $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), [
            'status'               => Status::QUEUED,
            'tax'                  => 90,
            'fees'                 => 590,
            'cancellation_user_id' => 'MerchantUser01',
        ]);

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        // Commenting out cancellation related assertions as payout in API won't be updated in this route.

        //$cancelledPayout = $this->getDbLastEntity('payout', 'live');
        //
        //// Assert that payout got cancelled
        //$this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        //$this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);
        //$this->assertEquals($testData['request']['content']['remarks'], $cancelledPayout['remarks']);
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

        $payout = $this->testCreatePayoutEntry('IMPS', false);

        $queuedPayout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->edit('payout', $queuedPayout->getId(), ['status' => Status::QUEUED]);

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], 'pout_' .$migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

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
        $payout = $this->testCreatePayout();

        //$fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = substr($payout[Entity::ID], 5);

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

        $payout = (new Core)->getAPIModelPayoutFromPayoutService(substr($payout['id'],5))->toArray();

        $this->assertEquals('created', $payout[Entity::STATUS]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout[Entity::ID],
                                                 'source_type' => 'payout',
                                             ], 'live')->first();

        $this->assertEquals('processed', $ftaForPayout->getStatus());
    }

    // test api is  sending status update to payout service when razorx experiment is on but status updates were pushed
    // from admin dashboard . below test is for processed status
    public function testStatusUpdateToPayoutServiceForProcessedStatusWhenCalledManuallyViaAdminDashboard()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payout->setId('Gg7sgBZgvYjlSB');

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
            'url'       => '/payouts/' . 'Gg7sgBZgvYjlSB' . '/manual/status',
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'processed',
            ]
        ];

        $this->ba->adminAuth('live');

        $response = $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('created', $payout->getStatus());

        /** @var \RZP\Models\FundTransfer\Attempt\Entity $fta */
        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->assertEquals('processed', $fta->getStatus());
    }

    // even when disable_status_update_to_payout_service razorx experiment is on , status updates via admin dashboard
    // should go to payout service
    public function testStatusUpdateToPayoutServiceForFailedStatusWhenCalledManuallyViaAdminDashboard()
    {
        $psPayout = $this->testCreatePayoutEntry('IMPS', false);

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

        (new PayoutServiceDataMigration('live', [
            DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
            DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
            Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
        ]))->handle();

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = 'Gg7sgBZgvYjlSB'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSB', ['id' => 'Gg7sgBZgvYjlSC']);

        $this->fixtures->on('live')->create('fund_transfer_attempt', [
            'id'              => "KFrTfUdt2WmGMm",
            'merchant_id'     => "10000000000000",
            'purpose'         => "refund",
            'bank_account_id' => "1000000lcustba",
            'source_id'       => 'Gg7sgBZgvYjlSB',
            'source_type'     => 'payout',
            'channel'         => "yesbank",
            'version'         => "V3",
            'mode'            => "IMPS",
            'is_fts'          => 1,
            'status'          => "initiated",
            'narration'       => "test Merchant Fund Transfer",
            'failure_reason'  => null,
            'initiate_at'     => 1662739563,
            'created_at'      => 1662739563,
            'updated_at'      => 1662739563,
        ]);

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status'         => 'failed',
                'failure_reason' => 'manual update'
            ]
        ];

        $this->ba->adminAuth('live');

        $response = $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('initiated', $payout->getStatus());

        /** @var \RZP\Models\FundTransfer\Attempt\Entity $fta */
        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->assertEquals('failed', $fta->getStatus());
        $this->assertEquals('manual update', $fta->getFailureReason());
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

    public function testFreePayoutMigrationAdminAction()
    {
        $this->mockPayoutServiceFreePayoutMigration();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_COUNT,
            SettingsEntity::VALUE       => '250',
        ]);

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_SUPPORTED_MODES,
            SettingsEntity::VALUE       => 'IMPS,NEFT',
        ]);

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::LEDGER_REVERSE_SHADOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'   => '10000000000000',
                'entity_type' => EntityConstants::MERCHANT,
                'name'        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
            ],
            'live')->toArray();

        $this->fixtures->on('live')->edit(
            'feature',
            $feature['id'],
            [
                'name' => 'random_feature',
            ]
        );

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayBeforeTest);

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();

        $liveFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArray);
        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArray);
        $this->assertContains(Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArray);
        $this->assertContains(Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArray);

        $this->assertNotContains(Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArray);
    }

    public function testFreePayoutMigrationAdminActionWithIdempotencyPsToApiFeatureEnabled()
    {
        $this->mockPayoutServiceFreePayoutMigration();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_COUNT,
            SettingsEntity::VALUE       => '250',
        ]);

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_SUPPORTED_MODES,
            SettingsEntity::VALUE       => 'IMPS,NEFT',
        ]);

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::LEDGER_REVERSE_SHADOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $feature = $this->getDbEntity('feature',
                                      [
                                          'entity_id'   => '10000000000000',
                                          'entity_type' => EntityConstants::MERCHANT,
                                          'name'        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
                                      ],
                                      'live')->toArray();

        $this->fixtures->on('live')->edit(
            'feature',
            $feature['id'],
            [
                'name' => 'random_feature',
            ]
        );

        $this->fixtures->on('live')->merchant->addFeatures([Constants::IDEMPOTENCY_PS_TO_API]);

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
                                                          [
                                                              'entity_id' => '10000000000000',
                                                              'entity_type' => 'merchant'
                                                          ],
                                                          'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayBeforeTest);
        $this->assertContains(Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayBeforeTest);

        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $tagInputData = [
            'tags' => [Constants::IDEMPOTENCY_PS_TO_API],
        ];

        $merchantCore = new MerchantCore();

        $merchantCore ->setModeAndDefaultConnection('live');

        $merchantCore->addTags($merchant->getId(), $tagInputData, false);

        // This works without needing to reload from db somehow.
        $tagsBefore = $merchant->tagNames();

        // Doing this because it returns tag name with first char as capital always (not sure why).
        foreach ($tagsBefore as $key => $tag)
        {
            $tagsBefore[$key] = strtolower($tag);
        }

        $this->assertTrue(in_array(Constants::IDEMPOTENCY_PS_TO_API, $tagsBefore, true));

        $this->testData[__FUNCTION__] = $this->testData['testFreePayoutMigrationAdminAction'];

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();

        $liveFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ],
                                                'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArray);
        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArray);
        $this->assertContains(Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArray);
        $this->assertContains(Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArray);

        $this->assertNotContains(Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArray);

        // Doing this because you need to again fetch the entity from db to get the updated tags, ->reload() also
        // doesn't work (not known why).
        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $tagsAfter = $merchant->tagNames();

        foreach ($tagsAfter as $key => $tag)
        {
            $tagsAfter[$key] = strtolower($tag);
        }

        $this->assertFalse(in_array(Constants::IDEMPOTENCY_API_TO_PS, $tagsAfter, true));
    }

    public function testFreePayoutMigrationAdminActionWithLedgerReverseShadowNotAssigned()
    {
        $this->mockPayoutServiceFreePayoutMigration();

        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_COUNT,
            SettingsEntity::VALUE       => '250',
        ]);

        $this->fixtures->on('live')->create('settings', [
            SettingsEntity::ENTITY_ID   => $balance->getId(),
            SettingsEntity::ENTITY_TYPE => EntityConstants::BALANCE,
            SettingsEntity::MODULE      => FreePayout::FREE_PAYOUT,
            SettingsEntity::KEY         => FreePayout::FREE_PAYOUTS_SUPPORTED_MODES,
            SettingsEntity::VALUE       => 'IMPS,NEFT',
        ]);

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'   => '10000000000000',
                'entity_type' => EntityConstants::MERCHANT,
                'name'        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
            ],
            'live')->toArray();

        $this->fixtures->on('live')->edit(
            'feature',
            $feature['id'],
            [
                'name' => 'random_feature',
            ]
        );

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();

        $liveFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArray);
        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArray);
    }

    public function testFreePayoutMigrationAdminActionWithPayoutServiceEnabledFeature()
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFreePayoutMigrationAdminActionDisableAction()
    {
        $this->mockPayoutServiceFreePayoutMigration();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFreePayoutMigrationAdminActionValidationFailure()
    {
        $this->mockPayoutServiceFreePayoutMigration();

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFreePayoutMigrationAdminActionWithPayoutsServiceFailure()
    {
        $this->mockPayoutServiceFreePayoutMigration(true);

        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['ids'][0][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFreePayoutRollback()
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::LEDGER_REVERSE_SHADOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayBeforeTest);

        $this->ba->appAuthLive();

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArrayAfterTest);
        $this->assertContains(Feature\Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayAfterTest);
        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayAfterTest);
    }

    public function testFreePayoutRollbackWithIdempotencyApiToPsFeatureEnabled()
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__] = $this->testData['testFreePayoutRollback'];

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::LEDGER_REVERSE_SHADOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::IDEMPOTENCY_API_TO_PS]);

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
                                                          [
                                                              'entity_id' => '10000000000000',
                                                              'entity_type' => 'merchant'
                                                          ],
                                                          'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertContains(Feature\Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayBeforeTest);

        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $tagInputData = [
            'tags' => [Constants::IDEMPOTENCY_API_TO_PS],
        ];

        (new MerchantCore())->addTags($merchant->getId(), $tagInputData, false);

        $merchantCore = new MerchantCore();

        $merchantCore ->setModeAndDefaultConnection('live');

        $merchantCore->addTags($merchant->getId(), $tagInputData, false);

        // This works without needing to reload from db somehow.
        $tagsBefore = $merchant->tagNames();

        // Doing this because it returns tag name with first char as capital always (not sure why).
        foreach ($tagsBefore as $key => $tag)
        {
            $tagsBefore[$key] = strtolower($tag);
        }

        $this->assertTrue(in_array(Constants::IDEMPOTENCY_API_TO_PS, $tagsBefore, true));

        $this->ba->appAuthLive();

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
                                                         [
                                                             'entity_id' => '10000000000000',
                                                             'entity_type' => 'merchant'
                                                         ],
                                                         'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArrayAfterTest);
        $this->assertContains(Feature\Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayAfterTest);
        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayAfterTest);

        // Doing this because you need to again fetch the entity from db to get the updated tags, ->reload() also
        // doesn't work (not known why).
        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $tagsAfter = $merchant->tagNames();

        foreach ($tagsAfter as $key => $tag)
        {
            $tagsAfter[$key] = strtolower($tag);
        }

        $this->assertFalse(in_array(Constants::IDEMPOTENCY_API_TO_PS, $tagsAfter, true));
    }

    public function testFreePayoutRollbackValidationFailure()
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testFreePayoutRollbackWithCounterAttributes()
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number'   => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::LEDGER_REVERSE_SHADOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);

        $this->ba->appAuthLive();

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::LEDGER_REVERSE_SHADOW, $liveFeaturesArrayAfterTest);
        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
    }

    public function testFreePayoutRollbackWithoutLedgerReverseShadowFeatureAssigned()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);

        $this->ba->appAuthLive();

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
    }

    public function testFreePayoutRollbackWithoutPayoutServiceEnabledFeatureAssigned()
    {
        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content'][Entity::BALANCE_ID] = $balance->getId();

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'   => '10000000000000',
                'entity_type' => EntityConstants::MERCHANT,
                'name'        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
            ],
            'live')->toArray();

        $this->fixtures->on('live')->edit(
            'feature',
            $feature['id'],
                [
                'name' => 'random_feature',
                ]
        );

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);

        $this->ba->appAuthLive();

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
    }

    public function testUpdateFreePayoutsCountAndMode()
    {
        $this->mockPayoutServiceFreePayoutSet();

        $balance = $this->fixtures->create('balance',
            [
                Balance::ACCOUNT_TYPE => AccountType::SHARED,
                Balance::TYPE         => Type::BANKING,
            ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['url'] =
            '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->startTest();
    }

    public function testUpdateFreePayoutsServiceFailure()
    {
        $this->mockPayoutServiceFreePayoutSet(true);

        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['url'] =
            '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->startTest();
    }

    public function testAdminGetFreePayoutsCountFromPS()
    {
        $this->mockPayoutServiceGetFreePayout();

        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/admin/payouts/' . $balance[Balance::ID] . '/free_payout';

        $testData['response']['content']['free_payouts_count'] =
            FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1;

        $testData['response']['content']['free_payouts_consumed'] =
            FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1;

        $testData['response']['content']['free_payouts_supported_modes'] =
            FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;

        $this->startTest($testData);
    }

    public function testXDashboardGetFreePayoutsCountFromPS()
    {
        $this->mockPayoutServiceGetFreePayout();

        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts/' . $balance[Balance::ID] . '/free_payout';

        $testData['response']['content']['free_payouts_count'] =
            FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1;

        $testData['response']['content']['free_payouts_consumed'] =
            FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1;

        $testData['response']['content']['free_payouts_supported_modes'] =
            FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;

        $this->startTest($testData);
    }

    public function testBulkPayout()
    {
        $this->mockPayoutServiceCreateBulkPayout();

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'  => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id' => '10000000000000',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayoutServiceFailure()
    {
        $this->mockPayoutServiceCreateBulkPayout(true);

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'  => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id' => '10000000000000',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testDccPayoutsDetailsFetch()
    {
        $this->testCreateReversalEntry();

        $this->fixtures->edit('payout', 'Gg7sgBZgvYjlSC', ['id' => 'Gg7sgBZgvYjlSB']);

        $payout = $this->getDbLastEntity('payout', 'live')->toArray();

        $this->fixtures->on('live')->create(
            'payouts_status_details',
            [
                'id'           => 'ps100000000000',
                'payout_id'    => $payout['id'],
                'status'       => Status::INITIATED,
                'reason'       => 'Manually Initiated',
                'description'  => '',
                'mode'         => 'system',
                'triggered_by' => ''
            ]);

        $this->fixtures->on('live')->create(
            'payouts_status_details',
            [
                'id'           => 'ps100000000001',
                'payout_id'    => $payout['id'],
                'status'       => Status::REVERSED,
                'reason'       => 'Manually Reversed',
                'description'  => '',
                'mode'         => 'system',
                'triggered_by' => ''
            ]);

        $payout['reversal']       = $this->getDbLastEntity('reversal', 'live')->toArray();
        $payout['status_details'] = $this->getDbEntities(
            'payouts_status_details',
            ['payout_id' => $payout['id']],
            'live')->toArray();

        $this->ba->appAuthLive();

        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids' => [$payout['id']]
        ];

        $this->testData[__FUNCTION__]['response']['content'] = [
            "payout_details" => [$payout]
        ];

        $response = $this->startTest();

        // Payout Details assertions
        $this->assertEquals(count($this->testData[__FUNCTION__]['response']['content']['payout_details']),
                            count($response['payout_details']));

        $expectedPayoutDetails = $this->testData[__FUNCTION__]['response']['content']['payout_details'];
        $actualPayoutDetails   = $response['payout_details'];

        $this->assertEquals($expectedPayoutDetails[0]['id'], $actualPayoutDetails[0]['id']);
        $this->assertEquals($expectedPayoutDetails[0]['merchant_id'], $actualPayoutDetails[0]['merchant_id']);
        $this->assertEquals($expectedPayoutDetails[0]['fund_account_id'], $actualPayoutDetails[0]['fund_account_id']);
        $this->assertEquals($expectedPayoutDetails[0]['balance_id'], $actualPayoutDetails[0]['balance_id']);
        $this->assertEquals($expectedPayoutDetails[0]['amount'], $actualPayoutDetails[0]['amount']);
        $this->assertEquals($expectedPayoutDetails[0]['currency'], $actualPayoutDetails[0]['currency']);
        $this->assertEquals($expectedPayoutDetails[0]['fees'], $actualPayoutDetails[0]['fees']);
        $this->assertEquals($expectedPayoutDetails[0]['tax'], $actualPayoutDetails[0]['tax']);
        $this->assertEquals($expectedPayoutDetails[0]['status'], $actualPayoutDetails[0]['status']);
        $this->assertEquals($expectedPayoutDetails[0]['transaction_id'], $actualPayoutDetails[0]['transaction_id']);
        $this->assertEquals($expectedPayoutDetails[0]['pricing_rule_id'], $actualPayoutDetails[0]['pricing_rule_id']);
        $this->assertEquals($expectedPayoutDetails[0]['fts_transfer_id'], $actualPayoutDetails[0]['fts_transfer_id']);
        $this->assertEquals($expectedPayoutDetails[0]['queued_reason'], $actualPayoutDetails[0]['queued_reason']);

        // Payout Status Detail assertions
        $expectedPayoutStatusDetails = $expectedPayoutDetails[0]['status_details'];
        $actualPayoutStatusDetails   = $actualPayoutDetails[0]['status_details'];

        $this->assertEquals(count($expectedPayoutStatusDetails), count($actualPayoutStatusDetails));

        foreach ($expectedPayoutStatusDetails as $expectedPayoutStatusDetail)
        {
            foreach ($actualPayoutStatusDetails as $actualPayoutStatusDetail)
            {
                if ($expectedPayoutStatusDetail['id'] == $actualPayoutStatusDetail['id'])
                {
                    $this->assertEquals($expectedPayoutStatusDetail['id'], $actualPayoutStatusDetail['id']);
                    $this->assertEquals($expectedPayoutStatusDetail['payout_id'],
                                        $actualPayoutStatusDetail['payout_id']);
                    $this->assertEquals($expectedPayoutStatusDetail['status'], $actualPayoutStatusDetail['status']);
                    $this->assertEquals($expectedPayoutStatusDetail['reason'], $actualPayoutStatusDetail['reason']);
                    $this->assertEquals($expectedPayoutStatusDetail['description'],
                                        $actualPayoutStatusDetail['description']);
                }
            }
        }

        // Payout Reversal assertions
        $expectedReversalDetails = $expectedPayoutDetails[0]['reversal'];
        $actualReversalDetails   = $actualPayoutDetails[0]['reversal'];

        $this->assertEquals(count($expectedReversalDetails), count($actualReversalDetails));

        $this->assertEquals($expectedReversalDetails['id'], $actualReversalDetails['id']);
        $this->assertEquals($expectedReversalDetails['merchant_id'], $actualReversalDetails['merchant_id']);
        $this->assertEquals($expectedReversalDetails['entity_id'], $actualReversalDetails['entity_id']);
        $this->assertEquals($expectedReversalDetails['entity_type'], $actualReversalDetails['entity_type']);
        $this->assertEquals($expectedReversalDetails['balance_id'], $actualReversalDetails['balance_id']);
        $this->assertEquals($expectedReversalDetails['amount'], $actualReversalDetails['amount']);
        $this->assertEquals($expectedReversalDetails['tax'], $actualReversalDetails['tax']);
        $this->assertEquals($expectedReversalDetails['fee'], $actualReversalDetails['fee']);
        $this->assertEquals($expectedReversalDetails['currency'], $actualReversalDetails['currency']);
        $this->assertEquals($expectedReversalDetails['channel'], $actualReversalDetails['channel']);
        $this->assertEquals($expectedReversalDetails['utr'], $actualReversalDetails['utr']);
        $this->assertEquals($expectedReversalDetails['transaction_id'], $actualReversalDetails['transaction_id']);
    }

    public function testDccPayoutsDetailsFetchPayoutCountValidationFailure()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live')->toArray();

        $this->ba->appAuthLive();

        $this->testData[__FUNCTION__]['request']['content']['payout_ids'] = [];

        for ($i = 0; $i < Validator::MAX_COUNT_DATA_CONSISTENCY_CHECKER_PAYOUT_IDS + 1; $i++)
        {
            array_push($this->testData[__FUNCTION__]['request']['content']['payout_ids'], $payout['id']);
        }

        $this->startTest();
    }

    public function testDccPayoutsDetailsFetchPayoutIdLengthValidationFailure()
    {
        $this->ba->appAuthLive();

        $this->testData[__FUNCTION__]['request']['content']['payout_ids'] = ["12345678901234567"];

        $this->startTest();
    }

    public function mockPayoutServiceDataConsistencyCheckerCronCreate($fail = false)
    {
        $payoutServiceDataConsistencyCheckerMock = Mockery::mock(
            'RZP\Services\PayoutService\DataConsistencyChecker', [$this->app])->makePartial();

        $payoutServiceDataConsistencyCheckerMock->shouldReceive('sendRequest')
                                                ->andReturn(
                                                    $this->initiateDataConsistencyCheckerResponseForPayoutServiceMock($fail)
                                                );

        $this->app->instance(DataConsistencyChecker::PAYOUT_SERVICE_DATA_CONSISTENCY_CHECKER,
                             $payoutServiceDataConsistencyCheckerMock);
    }

    public function initiateDataConsistencyCheckerResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode([]);
            $response->status_code = 500;
            $response->success     = true;
        }
        else
        {
            $response->body        = json_encode([]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function payoutsServiceCreateFailureProcessingCronResponseMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode([]);
            $response->status_code = 500;
            $response->success     = true;
        }
        else
        {
            $response->body        = json_encode([]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function payoutsServiceUpdateFailureProcessingCronResponseMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode([]);
            $response->status_code = 500;
            $response->success     = true;
        }
        else
        {
            $response->body        = json_encode([]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function testInitiatePayoutsConsistencyCheck()
    {
        $this->ba->cronAuth();

        $this->mockPayoutServiceDataConsistencyCheckerCronCreate();

        $this->startTest();
    }

    public function testInitiatePayoutsConsistencyCheckError()
    {
        $this->ba->cronAuth();

        $this->mockPayoutServiceDataConsistencyCheckerCronCreate(true);

        $this->startTest();
    }

    public function testInitiateBatchSubmittedCron()
    {
        $this->ba->cronAuth();

        $this->mockPayoutServiceInitiateBatchSubmittedCronCreate();

        $this->startTest();
    }

    public function testInitiateBatchSubmittedCronFailure()
    {
        $this->ba->cronAuth();

        $this->mockPayoutServiceInitiateBatchSubmittedCronCreate(true);

        $this->startTest();
    }

    public function mockPayoutServiceInitiateBatchSubmittedCronCreate($fail = false)
    {
        $payoutServiceInitiateBatchSubmittedCronMock = Mockery::mock(
            'RZP\Services\PayoutService\BulkPayout', [$this->app])->makePartial();

        $payoutServiceInitiateBatchSubmittedCronMock->shouldReceive('sendRequest')
                                                    ->andReturn(
                                                        $this->initiateBatchSubmittedResponseForPayoutServiceMock($fail)
                                                    );

        $this->app->instance(BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS,
                             $payoutServiceInitiateBatchSubmittedCronMock);
    }

    public function initiateBatchSubmittedResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode([]);
            $response->status_code = 500;
            $response->success     = true;
        }
        else
        {
            $response->body        = json_encode([]);
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function testPayoutServiceMerchantFeatureAddition()
    {
        $this->fixtures->on('live')->merchant->removeFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate();

        $this->startTest();
    }

    public function testPayoutServiceMerchantFeatureAdditionServiceRequestFailure()
    {
        $this->fixtures->on('live')->merchant->removeFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate(true);

        $this->startTest();
    }

    public function testPayoutsServiceCreateFailureProcessingCron()
    {
        $this->ba->cronAuth();

        $payoutServiceCreateFailureProcessingCronClient = Mockery::mock(
            'RZP\Services\PayoutService\PayoutsCreateFailureProcessingCron', [$this->app])->makePartial();

        $payoutServiceCreateFailureProcessingCronClient->shouldReceive('sendRequest')
            ->andReturn(
                $this->payoutsServiceCreateFailureProcessingCronResponseMock(false)
            );

        $this->app->instance(PayoutsCreateFailureProcessingCron::PAYOUTS_CREATE_FAILURE_PROCESSING_CRON,
            $payoutServiceCreateFailureProcessingCronClient);

        $this->startTest();
    }

    public function testPayoutServiceMerchantFeatureDeletion()
    {
        $this->fixtures->merchant->addFeatures(['free_payout_ledger_via_ps']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate();

        $this->startTest();
    }

    public function testPayoutsServiceCreateFailureProcessingCronAndCountMissing()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testPayoutServiceMerchantFeatureDeletionServiceRequestFailure()
    {
        $this->fixtures->merchant->addFeatures(['free_payout_ledger_via_ps']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate(true);

        $this->startTest();
    }

    public function testPayoutsServiceCreateFailureProcessingCronAndDaysMissing()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function mockPayoutServiceMerchantConfigUpdate($fail = false, $request = [])
    {
        $merchantConfigUpdateMock = Mockery::mock('RZP\Services\PayoutService\MerchantConfig',
            [$this->app])->makePartial();

        $merchantConfigUpdateMock->shouldReceive('sendRequest')
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
            // andReturnUsing method instead of and Return
                $this->updateMerchantFeatureCacheResponseForPayoutServiceMock($fail)
            );

        $this->app->instance(PayoutServiceMerchantConfig::PAYOUT_SERVICE_MERCHANT_CONFIG, $merchantConfigUpdateMock);
    }

    public function updateMerchantFeatureCacheResponseForPayoutServiceMock($fail)
    {
        $response = new Requests_Response();

        if ($fail === true) {
            $response->body = json_encode(
                [
                    "error" =>
                        [
                            "code" => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field" => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        } else {
            $response->body = null;
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    public function testPayoutsServiceUpdateFailureProcessingCron()
    {
        $this->ba->cronAuth();

        $payoutServiceUpdateFailureProcessingCronClient = Mockery::mock(
            'RZP\Services\PayoutService\PayoutsUpdateFailureProcessingCron', [$this->app])->makePartial();

        $payoutServiceUpdateFailureProcessingCronClient->shouldReceive('sendRequest')
            ->andReturn(
                $this->payoutsServiceUpdateFailureProcessingCronResponseMock(false)
            );

        $this->app->instance(PayoutsUpdateFailureProcessingCron::PAYOUTS_UPDATE_FAILURE_PROCESSING_CRON,
            $payoutServiceUpdateFailureProcessingCronClient);

        $this->startTest();
    }

    public function mockPayoutServiceDashboardScheduleTimeSlots()
    {
        $payoutServiceDashboardTimeSlotsMock = $this->getMockBuilder(PayoutServiceDashboardScheduleTimeSlots::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceDashboardScheduleTimeSlots::PAYOUT_SERVICE_DASHBOARD_TIME_SLOTS, $payoutServiceDashboardTimeSlotsMock );

        $response = new Requests_Response();

        $response->body = json_encode(
            [
                '9',
                '13',
                '17',
                '21',
            ]);

        $this->app->payout_service_dashboard_time_slots->method('sendRequest')
            ->willReturn($response);
    }

    public function testGetScheduleTimeSlotsForDashboard()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::SCHEDULE_PAYOUT_VIA_PS]);

        $this->mockPayoutServiceDashboardScheduleTimeSlots();

        $testData = $this->testData['testGetScheduleTimeSlotsForDashboard'];
        $this->testData[__FUNCTION__] = $testData;
        $this->testData[__FUNCTION__]['request']['headers']['X-Passport-JWT-V1'] = "";

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDecrementFreePayoutsConsumedForPayoutsService()
    {
        $balance = $this->fixtures->create('balance', [
            'merchant_id'    => '10000000000000',
            'account_type'   => 'shared',
            'type'           => 'banking',
            'channel'        => 'icici',
            'balance'        => 10000000,
        ]);

        $balanceId = $balance->getId();

        $this->fixtures->create(
            'counter',
            [
                'balance_id'   => $balanceId,
                'account_type' => 'shared',
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['balance_id'] = $balanceId;

        $this->testData[__FUNCTION__] = $testData;

        $counter = $this->getDbEntity('counter', [
            CounterEntity::BALANCE_ID   => $balanceId,
            CounterEntity::ACCOUNT_TYPE => AccountType::SHARED
        ])->toArray();

        $this->fixtures->edit('counter', $counter['id'], ['free_payouts_consumed' => 300]);

        $this->ba->payoutInternalAppAuth();

        $this->startTest();

        // Assert that we have now consumed another free payout and counter has Decremented to 299.
        $updatedCounter = $this->getDbEntity('counter', [
            CounterEntity::BALANCE_ID   => $balanceId,
            CounterEntity::ACCOUNT_TYPE => AccountType::SHARED
        ])->toArray();

        $this->assertEquals(299, $updatedCounter['free_payouts_consumed']);
    }

    public function testDecrementFreePayoutsConsumedForPayoutsServiceValidationFailure()
    {
        $this->ba->payoutInternalAppAuth();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = "bal12345678901234567";

        $this->startTest();
    }

    public function testCreateContactWithPayoutsServiceInternalAuth()
    {
        $this->ba->appAuthLive($this->config['applications.payouts_service.secret']);

        $this->startTest();

        $contactDb = $this->getDbLastEntity('contact', 'live');

        $this->assertEquals($contactDb['type'],'vendor');

        $this->assertEquals($contactDb['batch_id'],'KNLfqctfnSg4yY');

        $this->assertEquals($contactDb['idempotency_key'],'batch_KJjiE5OFofdtBE');
    }

    public function testPayoutsServiceInternalFundAccountCreation()
    {
        $this->ba->appAuthLive($this->config['applications.payouts_service.secret']);

        $contact = $this->fixtures->on('live')->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => 'vendor'
                                           ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['contact_id'] = $contact->getPublicId();

        $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account', 'live');

        $contactDb = $this->getDbLastEntity('contact', 'live');

        $this->assertEquals($contactDb['type'],'vendor');

        $this->assertEquals($contactDb['id'], $contact['id']);

        $this->assertEquals($fundAccount->contact['id'], $contact['id']);
    }

    public function testFetchPayoutById()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutByIdWithExpandParam()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = explode('?', $this->testData[__FUNCTION__]['request']['url'])[0];

        $input = [
            'expand' => ['user', 'fund_account.contact'],
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutByIdWithErrorFromService()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $this->mockPayoutServiceFetch(true, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutByIdWithUnsupportedParamsForService()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $request['url'] = '/payouts/' . $payout->getPublicId() . '?' . explode('?', $request['url'])[1];

        $this->testData[__FUNCTION__]['request']['url'] = $request['url'];

        $this->testData[__FUNCTION__]['response']['content']['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutByIdOnTestModeForService()
    {
        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchPayoutByIdWithNonProxyOrPrivateAuth()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts_internal/' . $payout->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $request['url'];

        $this->testData[__FUNCTION__]['response']['content']['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testFetchPayoutByIdWithIdNotFoundErrorFromService()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $request['url'] = '/payouts/' . $payout->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $request['url'];

        $this->testData[__FUNCTION__]['response']['content']['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetch(true, $request, PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutMultiple()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts';

        $input = [
            'mode'           => 'imps',
            'account_number' => $this->bankingBalance->getAccountNumber(),
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithExpandParam()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts';

        $input = [
            'mode'           => 'imps',
            'expand'         => ['user', 'fund_account.contact'],
            'account_number' => $this->bankingBalance->getAccountNumber(),
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithErrorFromService()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts';

        $input = [
            'mode'           => 'imps',
            'account_number' => $this->bankingBalance->getAccountNumber(),
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(true, $request);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithUnsupportedParamsForService()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $request['url'] .= '&contact_id=' . $payout->fundAccount->contact->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $request['url'];

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithNonSharedBalanceType()
    {
        $this->testCreatePayout();

        $this->fixtures->on('live')->edit(
            'balance',
            $this->bankingBalance->getId(),
            [
                'account_type' => 'direct',
            ]
        );

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithNoAccountNumberAndOnlyDirectBankingAccount()
    {
        $this->testCreatePayout();

        $this->fixtures->on('live')->edit(
            'balance',
            $this->bankingBalance->getId(),
            [
                'account_type' => 'direct',
            ]
        );

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithNoAccountNumberAndOnlySharedBankingAccount()
    {
        $request['url'] = '/payouts';

        $input = [
            'product' => 'banking',
            'count'   => 2,
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithNoAccountNumberAndMoreThanOneBankingBalance()
    {
        $this->testCreatePayout();

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type' => 'direct',
                'merchant_id' => $this->bankingBalance->getMerchantId(),
                'type' => 'banking',
                'account_number' =>  $this->bankingBalance->getAccountNumber() + 2,
            ]
        );

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithNonProxyOrPrivateAuth()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $payout->getPublicId();

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testFetchPayoutMultipleOnTestModeForService()
    {
        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchPayoutMultipleWithPayoutModeParam()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts';

        $input = [
            'expand'         => ['user', 'fund_account.contact'],
            'account_number' => $this->bankingBalance->getAccountNumber(),
            'mode'           => 'imps',
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testWorkflowStateCallbackFromPayoutService()
    {
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       => 'Exag5ZpN5MWuBW',
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $this->ba->payoutInternalAppAuth('live');

        $this->startTest();
    }

    public function testWorkflowStateUpdateCallbackFromPayoutService()
    {
        $this->testWorkflowStateCallbackFromPayoutService();

        $this->ba->payoutInternalAppAuth('live');

        $this->startTest();
    }

    public function testEnablePayoutServiceFeature()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'   => '10000000000000',
                'entity_type' => EntityConstants::MERCHANT,
                'name'        => Feature\Constants::PAYOUT_SERVICE_ENABLED,
            ],
            'live')->toArray();

        $this->fixtures->on('live')->edit(
            'feature',
            $feature['id'],
            [
                'name' => 'random_feature',
            ]
        );

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);

        $this->startTest();

        $liveFeaturesArrayAfterTest = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertNotContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayAfterTest);
    }
}

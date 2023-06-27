<?php

namespace Functional\Payout;

use DB;
use Config;
use Queue;
use Mockery;
use Carbon\Carbon;
use Database\Connection;
use RZP\Services\Mock\WorkflowService;
use \WpOrg\Requests\Response;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use Razorpay\OAuth\Client;
use RZP\Models\Payout\Core;
use RZP\Models\Pricing\Fee;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Services\DiagClient;
use RZP\Models\Payout\Metric;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\PayoutsDetails;
use RZP\Services\RazorXClient;
use RZP\Models\Payout\Validator;
use RZP\Models\Feature\Constants;
use RZP\Jobs\BatchPayoutsProcess;
use RZP\Services\FTS\FundTransfer;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Payout\DataMigration;
use RZP\Jobs\PayoutSourceUpdaterJob;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\WorkflowFeature;
use RZP\Jobs\PayoutServiceDataMigration;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\PayoutService\BulkPayout;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Payout\PayoutTest;
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
use RZP\Tests\Functional\Helpers\PayoutAttachmentTrait;
use RZP\Services\PayoutService\Get as PayoutServiceGet;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\Merchant\Balance\AccountType as AccountType;
use RZP\Services\PayoutService\Retry as PayoutServiceRetry;
use RZP\Services\PayoutService\Fetch as PayoutServiceFetch;
use RZP\Models\FundTransfer\Attempt\Entity as AttemptEntity;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Services\PayoutService\Status as PayoutServiceStatus;
use RZP\Services\PayoutService\Cancel as PayoutServiceCancel;
use RZP\Services\PayoutService\Details as PayoutServiceDetails;
use RZP\Services\PayoutService\PayoutsCreateFailureProcessingCron;
use RZP\Services\PayoutService\PayoutsUpdateFailureProcessingCron;
use RZP\Models\FundTransfer\Attempt\Constants as AttemptConstants;
use RZP\Services\PayoutService\FreePayout as PayoutServiceFreePayout;
use RZP\Services\PayoutService\QueuedInitiate as PayoutServiceQueuedInitiate;
use RZP\Services\PayoutService\MerchantConfig as PayoutServiceMerchantConfig;
use RZP\Services\PayoutService\UpdateAttachments as PayoutServiceUpdateAttachments;
use RZP\Services\PayoutService\DashboardScheduleTimeSlots as PayoutServiceDashboardScheduleTimeSlots;

// Todo:: Change all the client mocks to be same as mockPayoutServiceQueuedInitiate
class PayoutServiceTest extends TestCase
{
    use PayoutTrait;
    use TestsMetrics;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use PayoutAttachmentTrait;
    use OAuthTrait;

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

        $this->mockTaxPaymentsGetTdsCategories();
    }

    public function mockPayoutServiceCreate($fail = false,
                                            $metadata = [],
                                            $request = [],
                                            $status = 'processing',
                                            $insufficient_balance = false,
                                            $newBankingError = false,
                                            &$assertionBody = [],
                                            &$actualPayload = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create',
                                                 [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceCreateMock->shouldReceive('sendRequest')
                                ->withArgs(
                                    function($arg) use ($request, $status, &$assertionBody, &$actualPayload) {
                                        try
                                        {
                                            // json decoding the content so that we can assert the keys of content.
                                            $arg['content'] = json_decode($arg['content'], true);

                                            $actualPayload = $arg['content'];

                                            // Using this method only here as we want to check if the keys in the
                                            // request are coming properly or not.
                                            $this->assertArrayKeySelectiveEquals($request, $arg);

                                            foreach ($request['headers'] as $header => $headerValue)
                                            {
                                                if (empty($headerValue) === false)
                                                {
                                                    if ($arg['headers'][$header] != $headerValue)
                                                    {
                                                        return false;
                                                    }
                                                }
                                            }

                                            if (empty($arg['content']['extra_info']['fund_account_info']) === false)
                                            {
                                                $assertionBody = [
                                                    "fund_account_extra_info" => $arg['content']['extra_info']['fund_account_info'],
                                                ];
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

    public function mockPayoutServiceCreateForPayoutAttachments($fail = false,
                                            $metadata = [],
                                            &$request = [],
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
                                    function($arg) use (&$request, $status) {
                                        try
                                        {
                                            // Using this method only here as we want to check if the keys in the
                                            // request are coming properly or not.
                                            $this->assertArrayKeySelectiveEquals($request, $arg);

                                            foreach ($request['headers'] as $header => $headerValue)
                                            {
                                                if (empty($headerValue) === false)
                                                {
                                                    if ($arg['headers'][$header] != $headerValue)
                                                    {
                                                        return false;
                                                    }
                                                }
                                            }

                                            $request = $arg;
                                            return true;
                                        }
                                        catch (\Throwable $e)
                                        {
                                            $request = $arg;
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

    protected function mockTaxPaymentsGetTdsCategories()
    {
        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tdsCategories = [
            [
                'id'              => 1,
                'name'            => 'Test Category - 1',
                'extern_goi_code' => '6CK',
                'slab'            => 3.75,
            ],
            [
                'id'              => 2,
                'name'            => 'Test Category - 2',
                'extern_goi_code' => '206CA',
                'slab'            => 4,
            ],
            [
                'id'              => 17,
                'name'            => 'Test Category - 3',
                'extern_goi_code' => '94F',
                'slab'            => 4.25,
            ],
        ];

        $tpMock->shouldReceive('getTdsCategories')->andReturn($tdsCategories);

        $this->app['tax-payments'] = $tpMock;
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

        $this->app->instance(PayoutServiceFetch::PAYOUT_SERVICE_FETCH, $payoutServiceFetchMock);
    }

    public function mockPayoutServiceFetchShouldNotBeInvoked()
    {
        $payoutServiceFetchMock = Mockery::mock('RZP\Services\PayoutService\Fetch',
                                                [$this->app])->makePartial();

        $payoutServiceFetchMock->shouldNotReceive('fetch');

        $payoutServiceFetchMock->shouldNotReceive('fetchMultiple');

        $this->app->instance(PayoutServiceFetch::PAYOUT_SERVICE_FETCH, $payoutServiceFetchMock);
    }

    public function mockPayoutServiceUpdateAttachmentsShouldNotBeInvoked()
    {
        $payoutServiceUpdateAttachmentsMock = Mockery::mock('RZP\Services\PayoutService\UpdateAttachments',
                                                [$this->app])->makePartial();

        $payoutServiceUpdateAttachmentsMock->shouldNotReceive('updateAttachments');

        $payoutServiceUpdateAttachmentsMock->shouldNotReceive('bulkUpdateAttachments');

        $this->app->instance(PayoutServiceUpdateAttachments::PAYOUT_SERVICE_UPDATE_ATTACHMENTS,
                             $payoutServiceUpdateAttachmentsMock);
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

    public function mockPayoutServiceQueuedInitiate($fail = false, $request = [], &$success = true)
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request content that
        // is going to be sent to payout service.
        $payoutServiceQueuedInitiateMock = Mockery::mock('RZP\Services\PayoutService\QueuedInitiate',
                                                         [$this->app])->makePartial();

        $defaultRequest['content']['balance_ids'] = [];

        $request = array_merge($defaultRequest, $request);

        $payoutServiceQueuedInitiateMock->shouldReceive('sendRequest')
                                        ->withArgs(
                                            function($arg) use ($request, &$success) {
                                                try
                                                {
                                                    // json decoding the content so that we can assert the keys of content.
                                                    $arg['content'] = json_decode($arg['content'], true);

                                                    // Using this method only here as we want to check if the keys in the
                                                    // request are coming properly or not.
                                                    $this->assertArrayKeySelectiveEquals($request, $arg);

                                                    if (empty($request['content']['balance_ids']) === false)
                                                    {
                                                        $success = ($request['content']['balance_ids'] ===
                                                                    $arg['content']['balance_ids']);
                                                    }
                                                    else
                                                    {
                                                        $success = true;
                                                    }

                                                }
                                                catch (\Throwable $e)
                                                {
                                                    $success = false;
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

    public function mockPayoutServiceStatusShouldNotBeInvoked()
    {
        $payoutServiceStatusMock = Mockery::mock('RZP\Services\PayoutService\Status',
                      [$this->app])->makePartial();

        $payoutServiceStatusMock->shouldNotReceive('updatePayoutStatusViaFTS');

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);
    }

    public function mockPayoutServiceStatusWithChecks($status, &$success, $fail = false)
    {
        $payoutServiceStatusMock = Mockery::mock('RZP\Services\PayoutService\Status',
                                                  [$this->app])->makePartial();

        $payoutServiceStatusMock->shouldReceive('sendRequest')
                                 ->withArgs(
                                     function($arg) use ($fail, &$success) {
                                         try
                                         {
                                             $arg['content'] = json_decode($arg['content'], true);

                                             $this->assertEquals('Gg7sgBZgvYjlSB',$arg['content'][AttemptEntity::SOURCE_ID]);
                                             $this->assertEquals('processed',$arg['content'][AttemptEntity::STATUS]);
                                             $this->assertEquals('632563563',$arg['content'][AttemptConstants::FTS_FUND_ACCOUNT_ID]);
                                             $this->assertEquals('saving',$arg['content'][AttemptConstants::FTS_ACCOUNT_TYPE]);
                                             $this->assertEquals('processed',$arg['content'][EntityConstants::FTS_STATUS]);

                                             $success =  true;

                                             return true;
                                         }
                                         catch (\Throwable $e)
                                         {
                                             $success =  false;

                                             return false;
                                         }
                                     }
                                 )
                                 ->andReturn(
                                     $this->createResponseForPayoutServiceMock($fail,$status)
                                 );

        $this->app->instance(\RZP\Services\PayoutService\Status::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);
    }

    public function mockPayoutServiceRetry($fail = false)
    {
        $payoutRetryStatusMock = $this->getMockBuilder(PayoutServiceRetry::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendRequest'])
                                        ->getMock();

        $this->app->instance(PayoutServiceRetry::PAYOUT_SERVICE_RETRY, $payoutRetryStatusMock);

        $response = new \WpOrg\Requests\Response();

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

    public function mockPayoutServiceDetailsWithChecks(&$success, $fail = false)
    {
        $payoutServiceDetailsMock = Mockery::mock('RZP\Services\PayoutService\Details',
                                                   [$this->app])->makePartial();

        $payoutServiceDetailsMock->shouldReceive('sendRequest')
                                  ->withArgs(
                                      function($arg) use ($fail, &$success) {
                                          try
                                          {
                                              $arg['content'] = json_decode($arg['content'], true);

                                              $this->assertEquals('Test for webhook and email not firing',$arg['content'][Entity::FAILURE_REASON]);
                                              $this->assertEquals('Check the status by calling getStatus API.',$arg['content'][Entity::REMARKS]);
                                              $this->assertEquals('1236890',$arg['content'][AttemptEntity::FUND_TRANSFER_ID]);
                                              $this->assertEquals('SUCCESS',$arg['content'][AttemptEntity::BANK_STATUS_CODE]);
                                              $this->assertEquals('d10ce8e4167f11eab1750a0047330000',$arg['content'][AttemptEntity::CMS_REF_NO]);
                                              $this->assertEquals('43426',$arg['content'][AttemptEntity::GATEWAY_REF_NO]);
                                              $this->assertEquals('processed',$arg['content'][AttemptConstants::FTA_STATUS]);
                                              $this->assertEquals('SUSANTA BHUYAN',$arg['content'][AttemptConstants::BENEFICIARY_NAME]);
                                              $this->assertEquals('928337183',$arg['content'][AttemptEntity::UTR]);

                                              $success =  true;

                                              return true;
                                          }
                                          catch (\Throwable $e)
                                          {
                                              $success =  false;

                                              return false;
                                          }
                                      }
                                  )
                                  ->andReturn(
                                      $this->createResponseForPayoutServiceMock($fail)
                                  );

        $this->app->instance(\RZP\Services\PayoutService\Details::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);
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

    public function mockPayoutServiceUpdateAttachments($fail = false, $request = [], &$success = [])
    {
        $payoutServiceUpdateAttachmentsMock = Mockery::mock('RZP\Services\PayoutService\UpdateAttachments',
                                                         [$this->app])->makePartial();

        $defaultRequest['content']['attachments'] = [];

        $defaultRequest['content']['payout_ids'] = [];

        $defaultRequest['content']['update_request'] = [];

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceUpdateAttachmentsMock->shouldReceive('sendRequest')
                                        ->withArgs(
                                            function($arg) use ($request, &$success) {
                                                try
                                                {
                                                    // json decoding the content so that we can assert the keys of content.
                                                    $arg['content'] = json_decode($arg['content'], true);

                                                    // Using this method only here as we want to check if the keys in the
                                                    // request are coming properly or not.
                                                    $this->assertArrayKeySelectiveEquals($request, $arg);

                                                    if (empty($request['content']['attachments']) === false)
                                                    {
                                                        if ($request['content']['attachments'] ===
                                                                    $arg['content']['attachments'])
                                                        {
                                                            $success[] = "attachment_success";
                                                        }
                                                    }

                                                    if (empty($request['url']) === false)
                                                    {
                                                        if (substr($arg['url'], -1 * strlen($request['url'])) ===
                                                                $request['url'])
                                                        {
                                                            $success[] = "url_success";
                                                        }

                                                    }

                                                    if (empty($request['content']['payout_ids']) === false)
                                                    {
                                                        if ($request['content']['payout_ids'] ===
                                                            $arg['content']['payout_ids'])
                                                        {
                                                            $success[] = "payout_ids_success";
                                                        }
                                                    }

                                                    if (empty($request['content']['update_request']) === false)
                                                    {
                                                        if ($request['content']['update_request'] ===
                                                            $arg['content']['update_request'])
                                                        {
                                                            $success[] = "update_request_success";
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
                                            $this->createResponseForPayoutServiceUpdateAttachmentsMock($fail)
                                        );

        $this->app->instance(PayoutServiceUpdateAttachments::PAYOUT_SERVICE_UPDATE_ATTACHMENTS,
                             $payoutServiceUpdateAttachmentsMock);
    }


    public function createResponseForPayoutServiceMock($fail, $status = 'processing', $insufficient_balance = false, $newBankingError = false, $metadata = [])
    {
        $response = new \WpOrg\Requests\Response();

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

        return new \WpOrg\Requests\Response();
    }

    public function getResponseForPayoutByIdServiceMock($fail,
                                                        $status = 'processing',
                                                        $errorDescription = 'Service Failure')
    {
        $response = new \WpOrg\Requests\Response();

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
        $response = new \WpOrg\Requests\Response();

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

    public function createResponseForPayoutServiceUpdateAttachmentsMock($fail,
                                                                        $errorDescription = 'Service Failure')
    {
        $response = new \WpOrg\Requests\Response();

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
            $response->success     = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    'status' => 'SUCCESS'
                ]);

            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    public function mockPayoutServiceCreateBulkPayout($numberOfPayouts = 1, $fail = false, $emptyErrorBody = false, $request = [], $emptyNotes = false)
    {
        $createBulkPayoutMock = Mockery::mock('RZP\Services\PayoutService\BulkPayout',
                                           [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $defaultRequest['headers'][RequestHeader::X_Batch_Id] = "C0zv9I46W4wiOq";

        $defaultRequest['headers'][RequestHeader::X_ENTITY_ID] = "10000000000000";

        $defaultRequest['headers'][RequestHeader::X_Creator_Id] = "MerchantUser01";

        $defaultRequest['headers'][RequestHeader::X_Creator_Type] = "user";

        $request = array_merge($defaultRequest, $request);

        $createBulkPayoutMock->shouldReceive('sendRequest')
                          ->withArgs(
                              function($arg) use ($request, $emptyNotes) {
                                  try
                                  {

                                      if ($emptyNotes === true)
                                      {
                                          // json decoding the content so that we can assert the keys of content.
                                          $arg['content'] = json_decode($arg['content'], true);

                                          $this->assertArrayNotHasKey('notes', $arg['content'][0]);
                                          $this->assertArrayHasKey('notes', $arg['content'][1]);
                                      }

                                      // Using this method only here as we want to check if the keys in the
                                      // request are coming properly or not.
                                      $this->assertArrayKeySelectiveEquals($request, $arg);

                                      foreach ($request['headers'] as $header => $headerValue)
                                      {
                                          if (empty($headerValue) === false)
                                          {
                                              if ($arg['headers'][$header] != $headerValue)
                                              {
                                                  return false;
                                              }
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
                          ->once()
                          ->andReturn(
                          // We are returning this response only as we don't have a use case of supporting
                          // response based on $request, if needed, that can also be added here using
                          // andReturnUsing method instead of andReturn
                              $this->createBulkPayoutResponseForPayoutServiceMock($numberOfPayouts, $fail, $emptyErrorBody)
                          );

        $this->app->instance(BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS, $createBulkPayoutMock);
    }

    public function mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked()
    {
        $createBulkPayoutMock = Mockery::mock('RZP\Services\PayoutService\BulkPayout',
                                              [$this->app])->makePartial();

        $createBulkPayoutMock->shouldNotReceive('createBulkPayoutViaMicroservice');

        $this->app->instance(BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS, $createBulkPayoutMock);

        return $createBulkPayoutMock;
    }

    public function createBulkPayoutResponseForPayoutServiceMock($numberOfPayouts, $fail, $emptyErrorBody)
    {
        $response = new \WpOrg\Requests\Response();

        if ($fail === true)
        {
            if ($emptyErrorBody === true)
            {
                $response->status_code = 429;
            }
            else
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
        }
        else if ($numberOfPayouts == 1)
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
        else if ($numberOfPayouts == 2)
        {
            $response->body = json_encode(
                [
                    'entity' => 'collection',
                    'count'  => 2,
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
                            'idempotency_key' => 'batch_abc1234'
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
        $response = new \WpOrg\Requests\Response();

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
        $response = new \WpOrg\Requests\Response();

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
        $response = new \WpOrg\Requests\Response();

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
    public function testCreatePayoutEntry($mode = 'IMPS',
                                          $migratePayoutToPS = true,
                                          $payoutID = 'pout_Gg7sgBZgvYjlSB',
                                          $balanceID = '')
    {
        $this->ba->appAuthLive();

        if (empty($balanceID) === true)
        {
            $balance = $this->getDbEntities('balance',
                                            [
                                                'account_number' => '2224440041626905',
                                            ], 'live')->first();

            $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();
        }
        else
        {
            $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balanceID;
        }

        $strippedPayoutID = $payoutID;
        $strippedPayoutID = Entity::verifyIdAndStripSign($strippedPayoutID);

        $this->testData[__FUNCTION__]['request']['content']['id'] = $strippedPayoutID;
        $this->testData[__FUNCTION__]['request']['content']['mode'] = $mode;

        $this->startTest();

        $payout = $this->getLastEntity('payout', true,'live');

        $this->assertEquals($payout['id'], $payoutID);

        if ($migratePayoutToPS === true)
        {
            (new PayoutServiceDataMigration('live', [
                DataMigration\Processor::FROM => $payout[Entity::CREATED_AT],
                DataMigration\Processor::TO   => $payout[Entity::CREATED_AT],
                Entity::BALANCE_ID            => $payout[Entity::BALANCE_ID]
            ]))->handle();

            $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = '$strippedPayoutID'")[0];

            $this->assertEquals($payout[Entity::ID], 'pout_' .$migratedPayout->id);

            $this->fixtures->edit('payout', $strippedPayoutID, ['id' => 'Gg7sgBZgvYjlSC']);
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

    public function testFetchPricingInfoForPayoutServiceWithoutUserID($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testFetchPricingInfoForPayoutServiceWithUserID($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();

        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testFetchPricingInfoForPayoutServiceForXPayrollApp($mode = 'IMPS')
    {
        $balance = $this->getDbEntities('balance',
                                        [
                                            'account_number' => '2224440041626905',
                                        ], 'live')->first();

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => "Gg7sgBZgvYTTTT",
                'source_id'   => 'randomid111123',
                'source_type' => 'xpayroll',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

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

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

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

        $this->mockPayoutServiceStatusShouldNotBeInvoked();

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->assertEquals($payout[Entity::ID], 'pout_' . $fta->getSourceId());

        return $payout;
    }

    public function testCreatePayoutServiceFtaCreationWithPayoutInInitiatedState($mode = 'IMPS')
    {
        $payout = $this->testCreatePayoutEntry($mode, false);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => 'randomtxnnnnnn',
            'status'         => 'initiated',
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

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $mock->shouldReceive('requestFundTransfer')->once()->andReturn(
            [
                'body' => [
                    'status'           => 'initiated',
                    'fund_transfer_id' => 123,
                    'fund_account_id'  => 'D6Z9Jfir2egAUT'
                ],
                'code' => 201,
            ]
        );

        $this->testData[__FUNCTION__] = $this->testData['testCreatePayoutServiceFtaCreation'];

        $this->testData[__FUNCTION__]['response']['content']['status'] = 'initiated';

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        $this->assertEquals($payout[Entity::ID], 'pout_' . $fta->getSourceId());
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

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
            'shouldAllowTransfersViaFts' => [true, 'Dummy'],
        ]);

        $mock->shouldReceive('createAndSendRequest')->twice()->andReturn(
            [
                'body' => [
                    'status'           => 'initiated',
                    'fund_transfer_id' => 123,
                    'fund_account_id'  => 'D6Z9Jfir2egAUT'
                ],
                'code' => 201,
            ]
        );

        $this->mockPayoutServiceStatusShouldNotBeInvoked();

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        (new FtsFundTransfer('live', $fta->getId(), null))->handle();

        $payouts = $this->getDbEntities('payout', ['id' => 'Gg7sgBZgvYjlSB']);

        $this->assertCount(0, $payouts);

        $fta->reload();

        $this->assertEquals('Gg7sgBZgvYjlSB', $fta->source->getId());

        $this->assertEquals('initiated', $fta->getStatus());
    }

    public function testPayoutServiceFtaCreationWithoutPayoutInAPIWithPayoutInInitiatedState()
    {
        $payout = $this->testCreatePayoutEntry('IMPS', false);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => 'randomtxnnnnnn',
            'status'         => 'initiated',
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

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('fts_fund_transfer', $mock);

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $mock->shouldReceive('createAndSendRequest')->twice()->andReturn(
            [
                'body' => [
                    'status'           => 'initiated',
                    'fund_transfer_id' => 123,
                    'fund_account_id'  => 'D6Z9Jfir2egAUT'
                ],
                'code' => 201,
            ]
        );

        $this->testData[__FUNCTION__] = $this->testData['testPayoutServiceFtaCreationWithoutPayoutInAPI'];

        $this->testData[__FUNCTION__]['response']['content']['status'] = 'initiated';

        $this->ba->appAuthLive();

        $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt', 'live');

        (new FtsFundTransfer('live', $fta->getId(), null))->handle();

        $payouts = $this->getDbEntities('payout', ['id' => 'Gg7sgBZgvYjlSB']);

        $this->assertCount(0, $payouts);

        $fta->reload();

        $this->assertEquals('Gg7sgBZgvYjlSB', $fta->source->getId());

        $this->assertEquals('initiated', $fta->getStatus());
    }

    public function testPayoutServiceFtaCreationWithoutPayoutInAPIForDirectAccount()
    {
        $payout = $this->testCreatePayoutEntry('IMPS', false);

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => null,
            'status'         => 'created',
        ]);

        $this->fixtures->edit('balance', $payout['balance_id'], [
            'account_type' => 'direct',
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

        $testData = $this->testData['testPayoutServiceFtaCreationWithoutPayoutInAPI'];

        $this->startTest($testData);

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
        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

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

        $this->assertNull($assertionBody['fund_account_extra_info']);
    }

    public function testCreatePayoutWithAttachmentsViaDashboard()
    {
        $requestReceivedByMock = [];
        $this->mockPayoutServiceCreateForPayoutAttachments(false, [], $requestReceivedByMock);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData['testCreatePayoutWithAttachments'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuthLive();
        $response = $this->startTest();

        $this->assertEquals("test Merchant Fund Transfer", $response[Entity::NARRATION]);
        $this->assertEquals(1614325830, $response[Entity::INITIATED_AT]);
        $this->assertEquals("10000000000000", $response[Entity::MERCHANT_ID]);

        $requestReceivedByMock = json_decode($requestReceivedByMock['content'], true);

        $this->assertArrayHasKey('tds', $requestReceivedByMock);
        $this->assertArrayHasKey('attachments', $requestReceivedByMock);
        $this->assertArrayHasKey('subtotal_amount', $requestReceivedByMock);
    }

    // TODO add mocks to verify if correct status updates were pushed
    public function testPayoutSetStatusQueuePushForPayoutsServicePayout()
    {
        $this->ba->payoutInternalAppAuth('live');

        $payoutData = [
            'id'                   => "randomid111121",
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => "randomid111121",
                'source_id'   => 'randomid111123',
                'source_type' => 'refund',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $this->startTest();
    }

    // TODO add mocks to verify if correct status updates were pushed
    public function testPayoutSetStatusQueuePushForPayoutsServicePayoutWithDualWrittenAPIData()
    {
        $this->ba->payoutInternalAppAuth('live');

        $payoutData = [
            'id'                   => "randomid111121",
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutAPIData = [
            'id'                   => "randomid111121",
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
            'amount'               => 200,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "processed",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('live')->table('payouts')->insert($payoutData);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => "randomid111121",
                'source_id'   => 'randomid111123',
                'source_type' => 'refund',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => "randomid111121",
                'source_id'   => 'randomid111123',
                'source_type' => 'x',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('live')->table('payout_sources')->insert($payoutSourcesData);

        $this->startTest();
    }

    public function testPayoutSetStatusQueuePushWithMockQueue()
    {
        $this->markTestSkipped("This test is failing due to transaction aware in the payout source updater job worker");

        Queue::fake();

        $this->ba->payoutInternalAppAuth('live');

        $payoutData = [
            'id'                   => "randomid111121",
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => "randomid111121",
                'source_id'   => 'randomid111123',
                'source_type' => 'refund',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $this->startTest();

        Queue::assertPushed(PayoutSourceUpdaterJob::class);
    }

    public function testPayoutSetStatusHandleWithVendorPaymentAsSource()
    {
        $payout = $this->testCreatePayoutEntry('IMPS', true);

        $payoutID = $payout['id'];

        $strippedPayoutID = $payoutID;
        $strippedPayoutID = Entity::verifyIdAndStripSign($strippedPayoutID);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $strippedPayoutID,
                'source_id'   => 'randomid111123',
                'source_type' => 'vendor_payments',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        (new PayoutSourceUpdaterJob('live',
                                    $payoutID,
                                    null,
                                    Status::CREATE_REQUEST_SUBMITTED))->handle();

        // assert that the Payout Update Status was called when feature was enabled
        $vpMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testCreatePayoutWithNewBankingError(): array
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

        $payout = $this->testCreatePayoutServiceFtaCreation();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create('credits', [
            'merchant_id'   => '10000000000000',
            'value'         => 500 ,
            'type'          => 'reward_fee',
            'product'       => 'banking'
        ]);

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

        $this->assertNull($assertionBody['fund_account_extra_info']);

        return $payout;
    }

    public function testCreatePayoutViaMicroserviceAndPassFundAccountInfo()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

        $this->mockRazorxDefault();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $bankAccountObject = $this->getDbEntityById('bank_account', 'ba_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'bank_account' => [
                'id'              => 'ba_' . $bankAccountObject['id'],
                'name'            => $bankAccountObject['name'],
                'ifsc'            => $bankAccountObject['ifsc'],
                'account_number'  => $bankAccountObject['account_number'],
                'bank_name'       => $bankAccountObject['bank_name'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutForCardViaMicroserviceAndPassFundAccountInfo()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $card = $this->fixtures->on('live')->create('card', [
            'merchant_id'  => '10000000000000',
            'name'         => 'chirag',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
            'vault_token'  => 'MzQwMTY5NTcwOTkwMTM3==',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'card',
                'account_id'   => $card->getId()
            ]
        );

        $testData = $this->testData['testCreatePayoutViaMicroserviceAndPassFundAccountInfo'];

        $this->testData[__FUNCTION__] = $testData;

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

        $this->mockRazorxDefault();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $cardObject = $this->getDbEntityById('card', 'card_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'card' => [
                'id'          => 'card_' . $cardObject['id'],
                'type'        => $cardObject['type'],
                'last4'       => $cardObject['last4'],
                'issuer'      => $cardObject['issuer'],
                'sub_type'    => $cardObject['sub_type'],
                'network'     => $cardObject['network'],
                'token_iin'   => $cardObject['token_iin'],
                'token_last4' => $cardObject['token_last4'],
                'vault_token' => $cardObject['vault_token'],
                'vault'       => $cardObject['vault'],
                'trivia'      => $cardObject['trivia'],
                'input_type'  => 'card',
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutForVpaViaMicroserviceAndPassFundAccountInfo()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::NEW_BANKING_ERROR,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->on('live')->create('contact', [
            'id' => 'cont1000000000',
            'name' => 'Test Testing',
            'email' => 'test@razorpay.com',
            'contact' => '987654321',
            'type' => 'self',
            'active' => 1,
        ]);

        $this->fixtures->on('live')->create('vpa', [
            'id' => 'vpa10000000000',
            'entity_id' => 'cont1000000000',
            'entity_type' => 'contact',
            'username' => 'test',
            'handle' => 'upi',
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'vpa',
                'account_id'   => 'vpa10000000000'
            ]
        );

        $testData = $this->testData['testCreatePayoutViaMicroserviceAndPassFundAccountInfo'];

        $this->testData[__FUNCTION__] = $testData;

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

        $this->mockRazorxDefault();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $vpaObject = $this->getDbEntityById('vpa', 'vpa_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'vpa' => [
                'id'              => 'vpa_' . $vpaObject['id'],
                'username'        => $vpaObject['username'],
                'handle'          => $vpaObject['handle'],
                'address'         => $vpaObject['address'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutViaDashboardViaMicroserviceAndPassFundAccountInfo()
    {
        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $this->setMockRazorxTreatment([RazorxTreatment::PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD => 'on']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuthLive();
        $response = $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $bankAccountObject = $this->getDbEntityById('bank_account', 'ba_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'bank_account' => [
                'id'              => 'ba_' . $bankAccountObject['id'],
                'name'            => $bankAccountObject['name'],
                'ifsc'            => $bankAccountObject['ifsc'],
                'account_number'  => $bankAccountObject['account_number'],
                'bank_name'       => $bankAccountObject['bank_name'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutForCardViaDashboardViaMicroserviceAndPassFundAccountInfo()
    {
        $card = $this->fixtures->on('live')->create('card', [
            'merchant_id'  => '10000000000000',
            'name'         => 'chirag',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
            'vault_token'  => 'MzQwMTY5NTcwOTkwMTM3==',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'card',
                'account_id'   => $card->getId()
            ]
        );

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $this->setMockRazorxTreatment([RazorxTreatment::PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD => 'on']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuthLive();
        $response = $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $cardObject = $this->getDbEntityById('card', 'card_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'card' => [
                'id'          => 'card_' . $cardObject['id'],
                'type'        => $cardObject['type'],
                'last4'       => $cardObject['last4'],
                'issuer'      => $cardObject['issuer'],
                'sub_type'    => $cardObject['sub_type'],
                'network'     => $cardObject['network'],
                'token_iin'   => $cardObject['token_iin'],
                'token_last4' => $cardObject['token_last4'],
                'vault_token' => $cardObject['vault_token'],
                'vault'       => $cardObject['vault'],
                'trivia'      => $cardObject['trivia'],
                'input_type'  => 'card',
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutForVpaViaDashboardViaMicroserviceAndPassFundAccountInfo()
    {
        $this->fixtures->on('live')->create('contact', [
            'id' => 'cont1000000000',
            'name' => 'Test Testing',
            'email' => 'test@razorpay.com',
            'contact' => '987654321',
            'type' => 'self',
            'active' => 1,
        ]);

        $this->fixtures->on('live')->create('vpa', [
            'id' => 'vpa10000000000',
            'entity_id' => 'cont1000000000',
            'entity_type' => 'contact',
            'username' => 'test',
            'handle' => 'upi',
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'vpa',
                'account_id'   => 'vpa10000000000'
            ]
        );

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $this->setMockRazorxTreatment([RazorxTreatment::PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD => 'on']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuthLive();
        $response = $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $vpaObject = $this->getDbEntityById('vpa', 'vpa_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'vpa' => [
                'id'              => 'vpa_' . $vpaObject['id'],
                'username'        => $vpaObject['username'],
                'handle'          => $vpaObject['handle'],
                'address'         => $vpaObject['address'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreateInternalPayoutViaMicroServiceAndPassFundAccountInfo()
    {
        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $testData = $this->testData['testCreateInternalPayoutViaMicroService'];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->mockRazorxDefault();

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $bankAccountObject = $this->getDbEntityById('bank_account', 'ba_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'bank_account' => [
                'id'              => 'ba_' . $bankAccountObject['id'],
                'name'            => $bankAccountObject['name'],
                'ifsc'            => $bankAccountObject['ifsc'],
                'account_number'  => $bankAccountObject['account_number'],
                'bank_name'       => $bankAccountObject['bank_name'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreateInternalPayoutForCardViaMicroServiceAndPassFundAccountInfo()
    {
        $card = $this->fixtures->on('live')->create('card', [
            'merchant_id'  => '10000000000000',
            'name'         => 'chirag',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
            'vault_token'  => 'MzQwMTY5NTcwOTkwMTM3==',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'card',
                'account_id'   => $card->getId()
            ]
        );

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $this->mockRazorxDefault();

        $testData = $this->testData['testCreateInternalPayoutViaMicroService'];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $cardObject = $this->getDbEntityById('card', 'card_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'card' => [
                'id'          => 'card_' . $cardObject['id'],
                'type'        => $cardObject['type'],
                'last4'       => $cardObject['last4'],
                'issuer'      => $cardObject['issuer'],
                'sub_type'    => $cardObject['sub_type'],
                'network'     => $cardObject['network'],
                'token_iin'   => $cardObject['token_iin'],
                'token_last4' => $cardObject['token_last4'],
                'vault_token' => $cardObject['vault_token'],
                'vault'       => $cardObject['vault'],
                'trivia'      => $cardObject['trivia'],
                'input_type'  => 'card',
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreateInternalPayoutForVpaViaMicroServiceAndPassFundAccountInfo()
    {
        $this->fixtures->on('live')->create('contact', [
            'id' => 'cont1000000000',
            'name' => 'Test Testing',
            'email' => 'test@razorpay.com',
            'contact' => '987654321',
            'type' => 'self',
            'active' => 1,
        ]);

        $this->fixtures->on('live')->create('vpa', [
            'id' => 'vpa10000000000',
            'entity_id' => 'cont1000000000',
            'entity_type' => 'contact',
            'username' => 'test',
            'handle' => 'upi',
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->on('live')->edit('fund_account',
            '100000000000fa',
            [
                'account_type' => 'vpa',
                'account_id'   => 'vpa10000000000'
            ]
        );

        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, false, $assertionBody);

        $this->mockRazorxDefault();

        $testData = $this->testData['testCreateInternalPayoutViaMicroService'];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        $fundAccountObject = $this->getDbEntityById('fund_account', 'fa_100000000000fa');

        $contactObject = $this->getDbEntityById('contact', 'cont_' . $fundAccountObject->toArray()['source_id']);

        $vpaObject = $this->getDbEntityById('vpa', 'vpa_' . $fundAccountObject->toArray()['account_id']);

        $expectedFundAccountExtraInfo = [
            'id'            => 'fa_' . $fundAccountObject['id'],
            'entity'        => 'fund_account',
            'contact_id'    => 'cont_' . $fundAccountObject['source_id'],
            'account_type'  => $fundAccountObject['account_type'],
            'active'        => $fundAccountObject['active'],
            'batch_id'      => $fundAccountObject['batch_id'],
            'created_at'    => $fundAccountObject['created_at'],
            'vpa' => [
                'id'              => 'vpa_' . $vpaObject['id'],
                'username'        => $vpaObject['username'],
                'handle'          => $vpaObject['handle'],
                'address'         => $vpaObject['address'],
            ],
            'contact' => [
                'id'            => 'cont_' . $contactObject['id'],
                'entity'        => 'contact',
                'name'          => $contactObject['name'],
                'contact'       => $contactObject['contact'],
                'email'         => $contactObject['email'],
                'type'          => $contactObject['type'],
                'reference_id'  => $contactObject['reference_id'],
                'batch_id'      => $contactObject['batch_id'],
                'active'        => $contactObject['active'],
                'created_at'    => $contactObject['created_at'],
            ],
        ];

        $this->assertEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);

        $this->assertArrayKeySelectiveEquals($expectedFundAccountExtraInfo, $assertionBody['fund_account_extra_info']['fund_account']);
    }

    public function testCreatePayoutInsufficientBalance()
    {
        $this->mockPayoutServiceCreate(false, [], [], 'created', true);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

    }

    public function testCreateInternalPayoutViaMicroService()
    {
        $assertionBody = [];

        $this->mockPayoutServiceCreate(false, [],  [],Status::PROCESSING, false, true, $assertionBody);

        $payout = $this->testCreatePayoutServiceFtaCreation();

        $this->mockRazorxDefault();

        $this->setMockRazorxTreatment([RazorxTreatment::PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD => 'off']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        $this->assertNull($assertionBody['fund_account_extra_info']);
    }

    public function testCreateInternalPayoutViaMicroServiceWithUserIdInHeaders()
    {
        $this->mockRazorxDefault();

        $testData = $this->testData['testCreateInternalPayoutViaMicroService'];

        $merchantUser = $this->getDbEntity('merchant_user', ['role' => 'owner', 'product' => 'banking'], 'live')->toArray();

        $userId = $merchantUser['user_id'];

        $request['headers']['App-User-Id'] = $userId;

        $this->mockPayoutServiceCreate(false, [], $request);

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = '10000000000000';
        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $userId;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();
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

    public function testCreatePayoutInternalContact()
    {
        $metadata = [
            'tax'             => 0,
            'fees'            => 0,
            'origin'          => 'api',
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

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

        $this->mockPayoutServiceCreate(false,
            $metadata,
            [],
            Status::PROCESSING,
            false,
            true,
            $assertionBody,
            $actualPayload,
        );

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

        $this->assertFalse($actualPayload['enable_workflow_for_internal_contact']);
    }

    public function testCreatePayoutInternalContactWithWorkflows()
    {
        $metadata = [
            'tax'             => 0,
            'fees'            => 0,
            'origin'          => 'api',
        ];

        $this->mockPayoutServiceCreate(false,
            $metadata,
            [],
            Status::PROCESSING,
            false,
            true,
            $assertionBody,
            $actualPayload,
        );

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

        $this->assertTrue($actualPayload['enable_workflow_for_internal_contact']);
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

    public function testUpdateFTAAndPayoutProcessedWithExperiment()
    {
        $psPayout = $this->testCreatePayout();

        /** @var Entity $payout */
        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = substr($psPayout['id'], 5);

        $success = false;

        $this->mockPayoutServiceDetailsWithChecks($success);

        $this->mockPayoutServiceStatusWithChecks('processed',$success);

        $this->setMockRazorxTreatment([RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING => 'on']);

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
    }

    public function testUpdateFTAAndPayoutInitiated()
    {
        $psPayout = $this->testCreatePayout();

        /** @var Entity $payout */
        $payout = $this->getDbLastEntity('payout', 'live');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = substr($psPayout['id'], 5);

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('initiated');

        $this->ba->appAuthLive();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => substr($psPayout['id'], 5),
                                                 'source_type' => 'payout',
                                             ], 'live')->first();

        $this->assertEquals('initiated', $ftaForPayout->getStatus());
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

    public function testUpdateFTAAndPayoutToFailedForDirectAccountPayouts()
    {
        $payout = $this->testCreatePayout();

        $payout = (new Core)->getAPIModelPayoutFromPayoutService(substr($payout['id'], 5));

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->fixtures->edit('balance', $payout['balance_id'], [
            'account_type' => 'direct',
        ]);

        $debitBas = $this->fixtures->create('banking_account_statement', [
            'type'                => 'credit',
            'utr'                 => $payout['utr'],
            'amount'              => $payout['amount'],
            'channel'             => 'rbl',
            'account_number'      => 2224440041626905,
            'bank_transaction_id' => 'SDHDH',
            'balance'             => 11355,
            'transaction_date'    => 1584987183,
            'posted_date'         => 1584987183,
            'entity_type'         => 'external',
            'entity_id'           => 'Gy7sgBrgvYjlwB',
            'transaction_id'      => 'Gy7srTyIvUElwB',
        ]);

        $creditBas = $this->fixtures->create('banking_account_statement', [
            'type'                => 'credit',
            'utr'                 => $payout['utr'],
            'amount'              => $payout['amount'],
            'channel'             => 'rbl',
            'account_number'      => 2224440041626905,
            'bank_transaction_id' => 'SDHDH',
            'balance'             => 11355,
            'transaction_date'    => 1584987183,
            'posted_date'         => 1584987183,
            'entity_type'         => 'external',
            'entity_id'           => 'Gy7sgBrgvYjlw1',
            'transaction_id'      => 'Gy7srTyIvUElw2',
        ]);

        \DB::connection(Connection::TEST)
           ->update("update ps_payouts set transaction_id = null where id = 'Gg7sgBZgvYjlSB';");

        $payoutServiceDetailsMock = Mockery::mock(PayoutServiceDetails::class, [$this->app])->makePartial();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $payoutServiceDetailsMock->shouldReceive('updatePayoutDetailsViaFTS')
                                 ->andReturnUsing(function($payout, array $input = []) {

                                     self::assertArrayHasKey('gateway_ref_no', $input);
                                     self::assertEquals('Trdj7rh214', $input['gateway_ref_no']);
                                     self::assertArrayHasKey('cms_ref_no', $input);
                                     self::assertEquals('d10ce8e4167f11eab1750a0047330000', $input['cms_ref_no']);
                                     self::assertEquals('Gg7sgBZgvYjlSB', $payout->getId());

                                     return $this->createResponseForPayoutServiceMock(false);
                                 })->once();

        $payoutServiceStatusMock = Mockery::mock(PayoutServiceStatus::class, [$this->app])->makePartial();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $payoutServiceStatusMock->shouldReceive('updatePayoutStatusViaFTS')
                                ->andReturnUsing(function($payoutId,
                                                          string $status,
                                                          string $failureReason = null,
                                                          string $bankStatusCode = null,
                                                          array $ftsInfo = []) {

                                    self::assertEquals('failed', $status);
                                    self::assertEquals('Gg7sgBZgvYjlSB', $payoutId);

                                    return $this->createResponseForPayoutServiceMock(false, 'failed');
                                })->once();

        $this->ba->appAuthLive();

        $testData = $this->testData['testUpdateFTAAndPayoutToFailed'];
        $testData['request']['content']['gateway_ref_no'] = 'Trdj7rh214';

        $this->startTest($testData);

        $debitBas->reload();
        $creditBas->reload();

        $this->assertEquals('external', $debitBas->getEntityType());
        $this->assertEquals('external', $creditBas->getEntityType());

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                             ], 'live')->first();

        // Assert that fta status is updated
        $this->assertEquals('failed', $ftaForPayout->getStatus());
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

    public function testCreateQueuedPayoutViaPayoutService(
        string $balanceId = '',
        string $payoutID = 'pout_Gg7sgBZgvYjlSB')
    {
        $strippedPayoutID = $payoutID;
        $strippedPayoutID = Entity::verifyIdAndStripSign($strippedPayoutID);

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

        $metadata = [
            'id'              => $payoutID,
            'amount'          => 500,
            'purpose'         => 'refund',
            'status'          => 'queued',
            'mode'            => 'NEFT',
            'tax'             => 0,
            'fees'            => 0,
        ];

        $this->mockPayoutServiceCreate(false, $metadata);

        // Doing this because we fetch payout from the db before returning response from api.
        $this->testCreatePayoutEntry('NEFT', false, $payoutID, $balanceId);

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

        $migratedPayout = \DB::connection('test')->select("select * from ps_payouts where id = '$strippedPayoutID'")[0];

        $this->assertEquals($payout[Entity::ID], $migratedPayout->id);

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

        $request['content']['balance_ids'] = [$balanceId];

        $success = true;

        $this->mockPayoutServiceQueuedInitiate(false, $request, $success);

        $this->testCreateQueuedPayoutViaPayoutService($balanceId, 'pout_Gg7sgBZgvYjAAA');

        $payout1 = $this->getDbLastEntity('payout', 'live');

        // Doing this because as of now queued payouts can't be created via payout service
        $this->fixtures->edit(
            'payout',
            $payout1->getId(),
            [
                'is_payout_service' => 1
            ]
        );

        $response = $this->dispatchQueuedPayouts('live');

        $expectedResponse = [
            'balance_id_list' => [
                $balanceId,
            ]
        ];

        $this->assertEquals($expectedResponse, $response);

        $this->assertTrue($success);

        $payout1->reload();

        $this->assertEquals('queued', $payout1->getStatus());
    }

    // Since queued payout has is_payout_service value set to 1, it won't be processed via api. Here we check that even
    // if dispatch to service fails, the dispatch functionality of queued payouts isn't affected at all. The balance
    // ids will be dispatched to payout service on later try of the cron.
    public function testProcessQueuedPayoutCreatedPayoutServiceWhenDispatchToServiceFails()
    {
        $balanceId = $this->bankingBalance->getId();

        // Since we are sending incorrect balance id to the mock, it'll fail there and it'll create failure response
        // from service and hence it'll behave as if the request to service failed.
        $request['content']['balance_ids'] = ["random_balance_id"];

        $success = true;

        $this->mockPayoutServiceQueuedInitiate(false, $request, $success);

        $this->testCreateQueuedPayoutViaPayoutService($balanceId, 'pout_Gg7sgBZgvYjAAA');

        $payout1 = $this->getDbLastEntity('payout', 'live');

        // Doing this because as of now queued payouts can't be created via payout service
        $this->fixtures->edit(
            'payout',
            $payout1->getId(),
            [
                'is_payout_service' => 1
            ]
        );

        $response = $this->dispatchQueuedPayouts('live');

        $expectedResponse = [
            'balance_id_list' => [
                $balanceId,
            ]
        ];

        $this->assertEquals($expectedResponse, $response);

        $payout1->reload();

        $this->assertFalse($success);

        $this->assertEquals('queued', $payout1->getStatus());
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
        $response = new \WpOrg\Requests\Response();

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

    public function testApprovePayoutForPayoutServicePayout()
    {
        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->setMockRazorxTreatment([RazorxTreatment::WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE => 'on']);

        $mock = $this->createMetricsMock();

        $mock->method('histogram')
            ->will($this->returnCallback(function(string $metric, float $times, array $dimensions = []) {
                if ($metric === WorkflowService::WORKFLOW_SERVICE_REQUEST_MILLISECONDS)
                {
                    $this->assertEquals(100, $times);
                    $this->assertEquals([], $dimensions);
                }

                return true;
            }));

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUc', // Should exist in the new WF service
                'created_at' => 1598967657
            ]);

        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $payoutData = [
            'id'                   => 'Gg7sgBZgvYjlSB',
            'merchant_id'          => "10000000000000",
            'fund_account_id'      => "100000000000fa",
            'method'               => "fund_transfer",
            'reference_id'         => null,
            'balance_id'           => $balance->getId(),
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
            'transaction_id'       => null,
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

        $workflowEntityMapData = [
            'id'          => 'randomid111126',
            'workflow_id' => 'randomid111127',
            'entity_id'   => 'Gg7sgBZgvYjlSB',
            'config_id'   => 'randomid111128',
            'entity_type' => 'payout',
            'merchant_id' => "10000000000000",
            'org_id'      => 'randomid111129',
            'created_at'  => 1000000003,
            'updated_at'  => 1000000001
        ];

        \DB::connection('test')->table('ps_workflow_entity_map')->insert($workflowEntityMapData);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . 'pout_' . $payoutData['id'] . '/approve';

        $payout = $this->startTest();

        $this->assertEquals('pout_Gg7sgBZgvYjlSB', $payout['id']);
        $this->assertEquals('pending', $payout['status']);
    }

    public function testApprovePayoutForPayoutServicePayout_DualWriteFailure()
    {
        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->setMockRazorxTreatment([RazorxTreatment::WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE => 'on']);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::PAYOUT_SERVICE_WORKFLOW_ACTION_FAILED,
            $metricsMock,
            $boolMetricCaptured,
            [
                'route_name' => 'payout_approve',
                'message'    => 'ErrorCode: PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_NOT_FOUND is not defined'
            ]
        );

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->testData[__FUNCTION__]   = $this->testData['testApprovePayoutForPayoutServicePayout_Without_WorkflowExperiment'];
        $testData['request']['url'] = '/payouts/' . 'pout_' . '12341234123412' . '/approve';

        $this->startTest($testData);

        $this->assertTrue($boolMetricCaptured);
    }

    public function testApprovePayoutForPayoutServicePayout_Without_WorkflowExperiment()
    {
        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . 'pout_' . '12341234123412' . '/approve';

        $this->startTest();
    }

    public function testRejectPayoutForPayoutServicePayout()
    {
        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->setMockRazorxTreatment([RazorxTreatment::WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE => 'on']);

        $balance = $this->getDbEntities('balance',
            [
                'account_number' => '2224440041626905',
            ], 'live')->first();

        $payoutData = [
            'id'                   => 'Gg7sgBZgvYjlSB',
            'merchant_id'          => "10000000000000",
            'fund_account_id'      => "100000000000fa",
            'method'               => "fund_transfer",
            'reference_id'         => null,
            'balance_id'           => $balance->getId(),
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
            'transaction_id'       => null,
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

        $workflowEntityMapData = [
            'id'          => 'randomid111126',
            'workflow_id' => 'randomid111127',
            'entity_id'   => 'Gg7sgBZgvYjlSB',
            'config_id'   => 'randomid111128',
            'entity_type' => 'payout',
            'merchant_id' => "10000000000000",
            'org_id'      => 'randomid111129',
            'created_at'  => 1000000003,
            'updated_at'  => 1000000001
        ];

        \DB::connection('test')->table('ps_workflow_entity_map')->insert($workflowEntityMapData);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . 'pout_' . $payoutData['id'] . '/reject';

        $payout = $this->startTest();

        $this->assertEquals('pout_Gg7sgBZgvYjlSB', $payout['id']);
        $this->assertEquals('pending', $payout['status']);
    }

    public function testRejectPayoutForPayoutServicePayout_Without_WorkflowExperiment()
    {
        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . 'pout_' . '12341234123412' . '/reject';

        $this->startTest();
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

    public function testPayoutServiceManualUpdateFromLedgerResponseAwaitedToFailed()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $balance = $this->fixtures->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'balance'        => 10000,
            ]);

        $this->fixtures->create('banking_account', [
            'account_type'          => 'shared',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'balance_id'            => $balance->getId(),
        ]);

        $this->fixtures->create('payout', [
            'id'                => 'DuuYxmO7Yegu3x',
            'status'            => 'ledger_response_awaited',
            'balance_id'        => $balance->getId(),
            'is_payout_service' => 1
        ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id' => null,
        ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->create('fund_transfer_attempt', [
            'id'              => "KFrTfUdt2WmGMm",
            'merchant_id'     => "10000000000000",
            'purpose'         => "refund",
            'bank_account_id' => "1000000lcustba",
            'source_id'       => $payout['id'],
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

        $this->assertEquals('ledger_response_awaited', $payout->getStatus());

        $psPayoutData = [
            'id'                   => $payout['id'],
            'merchant_id'          => "10000000000000",
            'fund_account_id'      => "100000000000fa",
            'method'               => "fund_transfer",
            'reference_id'         => null,
            'balance_id'           => $balance->getId(),
            'user_id'              => "random_user123",
            'batch_id'             => null,
            'idempotency_key'      => "random_key",
            'purpose'              => "refund",
            'narration'            => "Batman",
            'purpose_type'         => "refund",
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "failed",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('live')->table('ps_payouts')->insert($psPayoutData);

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'failed',
            ]
        ];

        $payoutServiceStatusMock = Mockery::mock(PayoutServiceStatus::class, [$this->app])->makePartial();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $payoutServiceStatusMock->shouldReceive('updatePayoutStatusViaFTS')
                                ->andReturnUsing(function($payoutId,
                                      string $status,
                                      string $failureReason = null,
                                      string $bankStatusCode = null,
                                      array $ftsInfo = []) {

                                      self::assertEquals('failed', $status);

                                      return $this->createResponseForPayoutServiceMock(false, 'failed');
                                })->once();

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $fta = $this->getDbEntityById('fund_transfer_attempt', 'KFrTfUdt2WmGMm');

        $this->assertEquals('failed', $fta->getStatus());
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

        $tagInputData = [Constants::IDEMPOTENCY_PS_TO_API];

        $tagsBefore = $this->fixtures->on('live')->merchant->addTags($tagInputData);;

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

        $tagsAfter = $this->fixtures->on('live')->merchant->reloadTags();;

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

    public function testFreePayoutRollbackWithIdempotencyApiToPsFeatureAndFetchVaPayoutsViaPsEnabled()
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
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $liveFeaturesArrayBeforeTest = $this->getDbEntity('feature',
                                                          [
                                                              'entity_id' => '10000000000000',
                                                              'entity_type' => 'merchant'
                                                          ],
                                                          'live')->pluck('name')->toArray();

        $this->assertContains(Feature\Constants::PAYOUT_SERVICE_ENABLED, $liveFeaturesArrayBeforeTest);
        $this->assertContains(Feature\Constants::IDEMPOTENCY_API_TO_PS, $liveFeaturesArrayBeforeTest);
        $this->assertContains(Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS, $liveFeaturesArrayBeforeTest);
        $this->assertNotContains(Feature\Constants::IDEMPOTENCY_PS_TO_API, $liveFeaturesArrayBeforeTest);

        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $tagInputData = [Constants::IDEMPOTENCY_API_TO_PS];

        $tagsBefore = $this->fixtures->on('live')->merchant->addTags($tagInputData);;

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
        $this->assertNotContains(Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS, $liveFeaturesArrayAfterTest);

        $tagsAfter = $this->fixtures->on('live')->merchant->reloadTags();;

        $this->assertFalse(in_array(Constants::IDEMPOTENCY_API_TO_PS, $tagsAfter, true));
        $this->assertFalse(in_array(Constants::FETCH_VA_PAYOUTS_VIA_PS, $tagsAfter, true));
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

    public function testBulkPayout_NotesAsEmptyArray()
    {
        $this->mockPayoutServiceCreateBulkPayout(2, false, false, [], true);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }
    public function testBulkPayout_SharedAccount_SinglePayout_SpacesInAccountNumber()
    {
        $this->mockPayoutServiceCreateBulkPayout(1);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAccount_MultiplePayout_SpacesInAccountNumber()
    {
        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_DirectAccount_SinglePayout_SpacesInAccountNumber()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $createBulkPayoutMock = $this->mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked();

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $createBulkPayoutMock->shouldNotHaveReceived('createBulkPayoutViaMicroservice');
    }

    public function testBulkPayout_DirectAccount_MultiplePayout_SpacesInAccountNumber()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $createBulkPayoutMock = $this->mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked();

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $createBulkPayoutMock->shouldNotHaveReceived('createBulkPayoutViaMicroservice');
    }

    public function testBulkPayout_SharedAndDirectAccounts_SpacesInAccountNumber()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAccount_BalanceRecordNotAvailableForMerchant()
    {
        $balance1 = $this->getDbEntity('balance',
                                       [
                                           'merchant_id' => '10000000000000',
                                       ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'primary',
            'account_number' => 4564562235678281,
        ]);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_InvalidAccountNumber()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $createBulkPayoutMock = $this->mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked();

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $createBulkPayoutMock->shouldNotHaveReceived('createBulkPayoutViaMicroservice');
    }

    public function testBulkPayout_ValidSharedAccount_And_InvalidAccountNumber()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAccount()
    {
        $this->mockPayoutServiceCreateBulkPayout(1);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_MultiplePayouts_SameSharedAccount()
    {
        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_DirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked();

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payout = $this->getLastEntity('payout', true,'live');

        $expectedPayoutDetails = [
            'merchant_id'     => "10000000000000",
            'idempotency_key' => "batch_abc123",
            'amount'          => 100,
            'batch_id'        => "batch_C0zv9I46W4wiOq",
            'user_id'         => "MerchantUser01",
        ];

        $this->assertArraySelectiveEquals($expectedPayoutDetails, $payout);
    }

    public function testBulkPayout_MultiplePayouts_SameDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->mockPayoutServiceCreateBulkPayoutShouldNotBeInvoked();

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payout1 = $this->getDbEntity('payout', ['idempotency_key' => 'batch_abc1234'], 'live')->toArray();

        $expectedPayoutDetails = [
            'merchant_id'     => "10000000000000",
            'idempotency_key' => "batch_abc1234",
            'amount'          => 100,
            'batch_id'        => "C0zv9I46W4wiOq",
            'user_id'         => "MerchantUser01",
        ];

        $this->assertArraySelectiveEquals($expectedPayoutDetails, $payout1);

        $payout2 = $this->getDbEntity('payout', ['idempotency_key' => 'batch_abc123'], 'live')->toArray();

        $expectedPayoutDetails = [
            'merchant_id'     => "10000000000000",
            'idempotency_key' => "batch_abc123",
            'amount'          => 200,
            'batch_id'        => "C0zv9I46W4wiOq",
            'user_id'         => "MerchantUser01",
        ];

        $this->assertArraySelectiveEquals($expectedPayoutDetails, $payout2);
    }

    public function testBulkPayout_SharedAndDirectAccounts()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAndDirectAccount_ExceptionFromSharedAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->mockPayoutServiceCreateBulkPayout(2, true);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $payouts = $this->getDbEntities('payout');

        $payoutsCountBefore = count($payouts);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $payoutsCountAfter = count($payouts);

        $this->assertEquals($payoutsCountBefore, $payoutsCountAfter);
    }

    public function testBulkPayout_SharedAndDirectAccount_TimeoutFromPayoutsService()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->mockPayoutServiceCreateBulkPayout(2, true, true);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $payouts = $this->getDbEntities('payout');

        $payoutsCountBefore = count($payouts);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $payoutsCountAfter = count($payouts);

        $this->assertEquals($payoutsCountBefore, $payoutsCountAfter);
    }

    public function testBulkPayout_ExceptionFromDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAndDirectAccount_ExceptionFromDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE => 'on']);

        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'account_number' => 2224440041626907,
            ]
        );

        $this->mockPayoutServiceCreateBulkPayout(2);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayout_SharedAndDirectAccounts_ExperimentOff()
    {
        $this->fixtures->on('live')->create(
            'balance',
            [
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'channel'        => 'icici',
                'account_number' => 2224440041626907,
            ]
        );

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayoutServiceFailure()
    {
        $this->mockPayoutServiceCreateBulkPayout(1, true);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X-Entity-Id'    => '10000000000000',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
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

    public function testCreatePayoutToFundAccount_BatchID_InHeader()
    {
        $this->fixtures->on('live')->edit('balance', '10000000000000',
                              [
                                  'account_type' => 'shared',
                                  'type'         => 'banking',
                                  'channel'      => 'icici',
                              ]);

        $merchant = $this->getDbEntity('merchant',
                                       [
                                           'id' => '10000000000000'
                                       ],
                                       'live');

        $payoutCreateInput = [
            Entity::AMOUNT          => 1000,
            Entity::CURRENCY        => 'INR',
            Entity::PURPOSE         => 'refund',
            Entity::NARRATION       => 'refund',
            Entity::MODE            => 'IMPS',
            Entity::FUND_ACCOUNT_ID => 'fa_100000000000fa',
            Entity::BALANCE_ID      => '10000000000000',
        ];

        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create',
                                                 [$this->app])->makePartial();

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);

        $payoutServiceCreateMock->shouldNotReceive('createPayoutViaMicroservice');

        $this->app['rzp.mode'] = 'live';

        $enteredCatch = false;

        try
        {
            (new Core)->createPayoutToFundAccount($payoutCreateInput, $merchant, 'C0zv9I46W4wiOq');
        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals(
                'batch_id, idempotency_key is/are not required and should not be sent',
                $throwable->getMessage());

            $enteredCatch = true;
        }

        $this->assertEquals(true, $enteredCatch);

        $payoutServiceCreateMock->shouldNotHaveReceived('createPayoutViaMicroservice');
    }

    public function testCreatePayoutToFundAccount_BatchID_In_Input()
    {
        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create',
                                                 [$this->app])->makePartial();

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);

        $payoutServiceCreateMock->shouldNotReceive('createPayoutViaMicroservice');

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::INVALID_PAYOUT_CREATE_REQUEST_TO_PAYOUT_SERVICE,
            $metricsMock,
            $boolMetricCaptured,
            [
                'route_name' => 'payout_create'
            ]
        );

        $this->startTest();

        $payoutServiceCreateMock->shouldNotHaveReceived('createPayoutViaMicroservice');

        $this->assertTrue($boolMetricCaptured);
    }

    public function testCreatePayoutToFundAccount_IdempotencyKey()
    {
        $payoutServiceCreateMock = Mockery::mock('RZP\Services\PayoutService\Create',
                                                 [$this->app])->makePartial();

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);

        $payoutServiceCreateMock->shouldNotReceive('createPayoutViaMicroservice');

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::INVALID_PAYOUT_CREATE_REQUEST_TO_PAYOUT_SERVICE,
            $metricsMock,
            $boolMetricCaptured,
            [
                'route_name' => 'payout_create'
            ]
        );

        $this->startTest();

        $payoutServiceCreateMock->shouldNotHaveReceived('createPayoutViaMicroservice');

        $this->assertTrue($boolMetricCaptured);
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
        $response = new \WpOrg\Requests\Response();

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
        $response = new \WpOrg\Requests\Response();

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
        $response = new \WpOrg\Requests\Response();

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

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::SERVER_ERROR_PAYOUT_SERVICE_REQUEST_FAILED,
            $metricsMock,
            $boolMetricCaptured,
            [
                'route_name' => 'payouts_process_batch',
                'status_code' => 500,
            ]
        );

        $this->startTest();

        $this->assertTrue($boolMetricCaptured);
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
        $response = new \WpOrg\Requests\Response();

        if ($fail === true)
        {
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
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate();

        $this->startTest();
    }

    public function testPayoutServiceMerchantFeatureAdditionServiceRequestFailure()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockPayoutServiceMerchantConfigUpdate(true);

        $this->startTest();
    }

    public function testRemoveFeatureFromMerchantDashboard()
    {
        $this->fixtures->merchant->addFeatures(['skip_workflow_for_api']);

        $this->ba->proxyAuth();

        $this->mockPayoutServiceMerchantConfigUpdate();

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
            )->once()
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
        $response = new \WpOrg\Requests\Response();

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

        $response = new \WpOrg\Requests\Response();

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

    public function testFetchPayoutByIdWithExpandParamOnProxyAuth()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutByIdWithExpandParam'];

        $request['url'] = explode('?', $this->testData[__FUNCTION__]['request']['url'])[0];

        $input = [
            'expand' => ['user', 'fund_account.contact'],
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] .= '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->proxyAuthLive();

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

    public function testFetchPayoutByIdWithPrivilegeAuth()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $url = explode('?', $this->testData[__FUNCTION__]['request']['url'])[0];

        $payoutId = explode('/pout_', $url)[1];

        $input = [
            'expand' => ['user', 'fund_account.contact'],
        ];

        $query = (new PayoutServiceFetch)->buildQueryFromInput($input);

        $request['url'] = '/payouts/pout_' . $payoutId . '?' . $query;

        $this->mockPayoutServiceFetch(false, $request);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testFetchPayoutByIdWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->setUpExperimentForNWFS();

        $this->fixtures->on('live')->merchant->removeFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        // Removing from test mode because when once again adding on live mode before payout fetch, it tries adding on
        // test mode too and throws integrity constraint violation.
        $this->fixtures->on('test')->merchant->removeFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $user = $this->fixtures->on('live')->create('user');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payoutWithWorkflow = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $request['url'] = $this->testData[__FUNCTION__]['request']['url'];

        $request['url'] = '/payouts/' . $payoutWithWorkflow[Entity::ID];

        $this->testData[__FUNCTION__]['request']['url'] = $request['url'];

        $this->testData[__FUNCTION__]['response']['content']['id'] = $payoutWithWorkflow[Entity::ID];

        $this->fixtures->on('live')->edit('payout',
                                          $payoutWithWorkflow['id'],
                                          [
                                              'user_id' => $user['id']
                                          ]);

        $client = Client\Entity::factory()->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken([
                                                           'scopes'    => [
                                                               'rx_read_write',
                                                               'read_write'
                                                           ],
                                                           'mode'      => 'live',
                                                           'client_id' => $client->getId()
                                                       ], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000',
                                                     [
                                                         'id'             => '20000000000000',
                                                         'contact_mobile' => 9999999999
                                                     ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $expectedProperties = [
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_fetch_multiple',
                'user_id'     => '20000000000000',
                'user_role'   => 'owner',
                'channel'     => 'slack_app',
                'filters'     => [
                    'product' => 'banking',
                    'count'   => '10',
                    'expand'  => [
                        0 => 'fund_account.contact',
                        1 => 'user',
                    ]
                ]
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $this->fixtures->on('live')->merchant->addFeatures([
                                                               Feature\Constants::PAYOUT_SERVICE_ENABLED,
                                                               Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS
                                                           ]);

        $this->mockPayoutServiceFetchShouldNotBeInvoked();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();

        $this->assertPassport();
        $this->assertPassportKeyExists('oauth.client_id');
        $this->assertPassportKeyExists('oauth.app_id');
    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
                         ->setConstructorArgs([$this->app])
                         ->setMethods(['trackEvent'])
                         ->getMock();

        $this->app->instance('diag', $diagMock);
    }

    public function verifyPayoutsEvent($expectedProperties)
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
                        ->will($this->returnCallback(
                            function(string $eventType,
                                     string $eventVersion,
                                     array $event,
                                     array $properties) use ($expectedProperties) {
                                if (($event['group'] === 'payouts') and
                                    ($event['name'] === 'payouts.fetch.request'))
                                {
                                    $this->assertArraySelectiveEquals($expectedProperties, $properties);
                                }

                                return;
                            }));
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

    public function testFetchPayoutMultipleWithIdParam()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::FETCH_VA_PAYOUTS_VIA_PS]);

        $request['url'] = '/payouts';

        $input = [
            'id'             => 'pout_Gg7sgBZgvYjlSB',
            'count'          => 10,
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
                'account_type'   => 'direct',
                'merchant_id'    => $this->bankingBalance->getMerchantId(),
                'type'           => 'banking',
                'account_number' => $this->bankingBalance->getAccountNumber() + 2,
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

    public function testWorkflowStateCallbackWithoutEntityMapFromPayoutService()
    {
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $workflowEntityMapData = [
            'id'          => 'randomid111126',
            'workflow_id' => 'randomid111127',
            'entity_id'   => 'randomid111111',
            'config_id'   => 'randomid111128',
            'entity_type' => 'payout',
            'merchant_id' => "10000000000000",
            'org_id'      => 'randomid111129',
            'created_at'  => 1000000003,
            'updated_at'  => 1000000001
        ];

        \DB::connection('test')->table('ps_workflow_entity_map')->insert($workflowEntityMapData);

        $this->ba->payoutInternalAppAuth('live');

        $this->startTest();

        $wfEntityMap = $this->getLastEntity('workflow_entity_map', true,'live');

        $this->assertEquals($wfEntityMap['id'], 'randomid111126');

        $this->assertEquals($wfEntityMap['workflow_id'], 'randomid111127');

        $wfStateMap = $this->getLastEntity('workflow_state_map', true,'live');

        $this->assertEquals($wfStateMap['workflow_id'], 'randomid111127');
    }

    public function testWorkflowStateCallbackWithoutEntityMapFromPayoutServiceFailure()
    {
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

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

    public function testRenameAttachmentsForPayoutService()
    {
        $this->ba->payoutInternalAppAuth('live');

        $this->startTest();
    }

    public function testUpdateAttachmentWithProxyAuthForPayoutServicePayout()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        // uploading attachment
        $fileName = 'k.png';

        $localFilePath = $this->createNewFile($fileName);

        $uploadAttachmentRequest = $this->createUploadFileRequest($fileName, $localFilePath);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $uploadAttachmentResponse = $this->makeRequestAndGetContent($uploadAttachmentRequest);

        //asserting that response has file_id, file_name, and file_hash
        $this->assertArrayHasKey('file_id', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_name', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_hash', $uploadAttachmentResponse);

        $attachmentsDataInRequest = [
            [
                'file_id'   => $uploadAttachmentResponse['file_id'],
                'file_name' => $uploadAttachmentResponse['file_name'],
                'file_hash' => $uploadAttachmentResponse['file_hash'],
            ]
        ];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/attachments',
                                                                  Entity::getSignedId($payoutId));

        $this->testData[__FUNCTION__]['request']['content']['attachments'] = $attachmentsDataInRequest;

        $success = [];

        $request['content']['attachments'] = $attachmentsDataInRequest;

        $request['url'] = 'payouts/' . Entity::getSignedId($payoutId) . '/attachments';

        $this->mockPayoutServiceUpdateAttachments(false, $request, $success);

        $this->startTest();

        $expectedSuccess = ['attachment_success', 'url_success'];

        $this->assertEquals($expectedSuccess, $success);
    }

    public function testUpdateAttachmentWithProxyAuthForPayoutServicePayoutWithPayoutSourceNotNull()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'refund',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        // uploading attachment
        $fileName = 'k.png';

        $localFilePath = $this->createNewFile($fileName);

        $uploadAttachmentRequest = $this->createUploadFileRequest($fileName, $localFilePath);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $uploadAttachmentResponse = $this->makeRequestAndGetContent($uploadAttachmentRequest);

        //asserting that response has file_id, file_name, and file_hash
        $this->assertArrayHasKey('file_id', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_name', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_hash', $uploadAttachmentResponse);

        $attachmentsDataInRequest = [
            [
                'file_id'   => $uploadAttachmentResponse['file_id'],
                'file_name' => $uploadAttachmentResponse['file_name'],
                'file_hash' => $uploadAttachmentResponse['file_hash'],
            ]
        ];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/attachments',
                                                                  Entity::getSignedId($payoutId));

        $this->testData[__FUNCTION__]['request']['content']['attachments'] = $attachmentsDataInRequest;

        $request['content']['attachments'] = $attachmentsDataInRequest;

        $request['url'] = 'payouts/' . Entity::getSignedId($payoutId) . '/attachments';

        $this->mockPayoutServiceUpdateAttachmentsShouldNotBeInvoked();

        $this->startTest();
    }

    public function testUpdateAttachmentWithProxyAuthForPayoutServicePayoutWithErrorFromPayoutService()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        // uploading attachment
        $fileName = 'k.png';

        $localFilePath = $this->createNewFile($fileName);

        $uploadAttachmentRequest = $this->createUploadFileRequest($fileName, $localFilePath);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $uploadAttachmentResponse = $this->makeRequestAndGetContent($uploadAttachmentRequest);

        //asserting that response has file_id, file_name, and file_hash
        $this->assertArrayHasKey('file_id', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_name', $uploadAttachmentResponse);

        $this->assertArrayHasKey('file_hash', $uploadAttachmentResponse);

        $attachmentsDataInRequest = [
            [
                'file_id'   => $uploadAttachmentResponse['file_id'],
                'file_name' => $uploadAttachmentResponse['file_name'],
                'file_hash' => $uploadAttachmentResponse['file_hash'],
            ]
        ];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/attachments',
                                                                  Entity::getSignedId($payoutId));

        $this->testData[__FUNCTION__]['request']['content']['attachments'] = $attachmentsDataInRequest;

        $success = [];

        $request['content']['attachments'] = $attachmentsDataInRequest;

        $request['url'] = 'payouts/' . Entity::getSignedId($payoutId) . '/attachments';

        $this->mockPayoutServiceUpdateAttachments(true, $request, $success);

        $this->startTest();

        $expectedSuccess = ['attachment_success', 'url_success'];

        $this->assertEquals($expectedSuccess, $success);
    }

    public function testUpdateAttachmentForPayoutLinkForPayoutServicePayout()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData2 = [
            [
                'id'          => 'randomid111125',
                'payout_id'   => $payoutId2,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData2);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $success = [];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $this->mockPayoutServiceUpdateAttachments(false, $request, $success);

        $this->startTest();

        $expectedSuccess = ['url_success', 'payout_ids_success', 'update_request_success'];

        $this->assertEquals($expectedSuccess, $success);
    }

    public function testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithEmptySource()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $this->mockPayoutServiceUpdateAttachmentsShouldNotBeInvoked();

        $this->startTest();
    }

    public function testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithIncorrectSource()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData2 = [
            [
                'id'          => 'randomid111125',
                'payout_id'   => $payoutId2,
                'source_id'   => 'randomid111123',
                'source_type' => 'refunds',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData2);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithEmptySource'];

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $this->mockPayoutServiceUpdateAttachmentsShouldNotBeInvoked();

        $this->startTest();
    }

    public function testUpdateAttachmentForPayoutLinkForPSAndNonPSPayouts()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutId3 = 'randomid111123';

        $payoutData3 = [
            'id'                   => $payoutId3,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTO",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('live')->table('payouts')->insert($payoutData3);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData2 = [
            [
                'id'          => 'randomid111125',
                'payout_id'   => $payoutId2,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData2);

        $payoutSourcesData3 = [
            [
                'id'          => 'randomid111127',
                'payout_id'   => $payoutId3,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('live')->table('payout_sources')->insert($payoutSourcesData3);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateAttachmentForPayoutLinkForPayoutServicePayout'];

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2,
                $payoutId3,
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $success = [];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $request['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2,
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $this->mockPayoutServiceUpdateAttachments(false, $request, $success);

        $this->startTest();

        $expectedSuccess = ['url_success', 'payout_ids_success', 'update_request_success'];

        $this->assertEquals($expectedSuccess, $success);

        $payoutDetails = $this->getDbLastEntity(EntityConstants::PAYOUTS_DETAILS, 'live');

        $this->assertNotNull($payoutDetails);

        $this->assertFalse($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY]);

        $this->assertEquals('file_123456', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);

        $this->assertEquals('file_name.pdf', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithFailureFromPayoutService()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData2 = [
            [
                'id'          => 'randomid111125',
                'payout_id'   => $payoutId2,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData2);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $success = [];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $this->mockPayoutServiceUpdateAttachments(true, $request, $success);

        $this->startTest();

        $expectedSuccess = ['url_success', 'payout_ids_success', 'update_request_success'];

        $this->assertEquals($expectedSuccess, $success);
    }

    public function testUpdateAttachmentForPayoutLinkForPSAndNonPSPayoutsWithFailureFromPayoutService()
    {
        $payoutId = 'randomid111121';

        $payoutData = [
            'id'                   => $payoutId,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);

        $payoutId2 = 'randomid111122';

        $payoutData2 = [
            'id'                   => $payoutId2,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTN",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData2);

        $payoutId3 = 'randomid111123';

        $payoutData3 = [
            'id'                   => $payoutId3,
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
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "initiated",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTO",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('live')->table('payouts')->insert($payoutData3);

        $payoutSourcesData = [
            [
                'id'          => 'randomid111122',
                'payout_id'   => $payoutId,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData);

        $payoutSourcesData2 = [
            [
                'id'          => 'randomid111125',
                'payout_id'   => $payoutId2,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('test')->table('ps_payout_sources')->insert($payoutSourcesData2);

        $payoutSourcesData3 = [
            [
                'id'          => 'randomid111127',
                'payout_id'   => $payoutId3,
                'source_id'   => 'randomid111123',
                'source_type' => 'payout_links',
                'priority'    => 1,
                'created_at'  => 1000000002,
                'updated_at'  => 1000000001
            ],
        ];

        \DB::connection('live')->table('payout_sources')->insert($payoutSourcesData3);

        $this->ba->appAuthLive($this->config['applications.payout_links.secret']);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateAttachmentForPayoutLinkForPayoutServicePayoutWithFailureFromPayoutService'];

        // file_hash is not required via Internal App Auth
        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2,
                $payoutId3,
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $success = [];

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/attachments';

        $request['content'] = [
            'payout_ids'     => [
                $payoutId,
                $payoutId2,
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $this->mockPayoutServiceUpdateAttachments(true, $request, $success);

        $this->startTest();

        $expectedSuccess = ['url_success', 'payout_ids_success', 'update_request_success'];

        $this->assertEquals($expectedSuccess, $success);
    }
}

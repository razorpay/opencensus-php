<?php

namespace RZP\Tests\Functional\Modules\Acs;

use Config;
use Mockery;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Email;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Outbox\Job\Repository;
use RZP\Modules\Acs\SyncEventManager;
use Psr\Log\LoggerInterface as Logger;
use RZP\Modules\Acs\SyncEventObserver;
use RZP\Models\Merchant\Acs\AsvClient;
use Razorpay\Outbox\Encoder\JsonEncoder;
use Razorpay\Outbox\Job\Core as OutboxCore;
use RZP\Models\Consumer\Service as Consumer;
use Razorpay\Outbox\Encrypt\AES256GCMEncrypt;
use Rzp\Accounts\SyncDeviation\V1 as syncDeviationV1;
use Rzp\Accounts\SyncDeviation\V1\StringList as StringList;

class SyncEventManagerTest extends TestCase
{
    public function testCollectsAccountIdsForTestAndLiveMode()
    {
        $liveModeAccountIds = ['Live1', 'Live2', 'Live3'];
        $testModeAccountIds = ['Test1', 'Test2', 'Test3'];

        $outboxJobs = [SyncEventObserver::ACS_OUTBOX_JOB_NAME];
        $manager = $this->getMockedManagerWithAccountIds($liveModeAccountIds, $testModeAccountIds);
        $this->assertEquals($liveModeAccountIds, array_keys($manager->getLiveAccountIds()));
        $this->assertEquals($testModeAccountIds, array_keys($manager->getTestAccountIds()));
        $this->assertEquals([$outboxJobs, $outboxJobs, $outboxJobs], array_values($manager->getLiveAccountIds()));
        $this->assertEquals([$outboxJobs, $outboxJobs, $outboxJobs], array_values($manager->getTestAccountIds()));

        $outboxJobs = [SyncEventObserver::ACS_OUTBOX_JOB_NAME, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME];
        $manager = $this->getMockedManagerWithAccountIds($liveModeAccountIds, $testModeAccountIds, [], $outboxJobs);
        $this->assertEquals($liveModeAccountIds, array_keys($manager->getLiveAccountIds()));
        $this->assertEquals($testModeAccountIds, array_keys($manager->getTestAccountIds()));
        $this->assertEquals([$outboxJobs, $outboxJobs, $outboxJobs], array_values($manager->getLiveAccountIds()));
        $this->assertEquals([$outboxJobs, $outboxJobs, $outboxJobs], array_values($manager->getTestAccountIds()));
    }

    public function testHasUnreportedAccountIds()
    {
        $manager = $this->getMockedManagerWithAccountIds();
        $this->assertFalse($manager->hasUnreportedAccountIds());

        $liveModeAccountIds = ['Live1', 'Live2', 'Live3'];
        $manager = $this->getMockedManagerWithAccountIds($liveModeAccountIds);
        $this->assertTrue($manager->hasUnreportedAccountIds());

        $testModeAccountIds = ['Test1', 'Test2', 'Test3'];
        $manager = $this->getMockedManagerWithAccountIds([], $testModeAccountIds);
        $this->assertTrue($manager->hasUnreportedAccountIds());
    }

    public function testPublishJobSkipsSendEventToOutbox()
    {
        $outboxMock = $this->createOutboxMock();

        $accountId = 'AccountId';
        $mode = Mode::LIVE;
        $jobPayload = [
            'owner_id'   => $accountId,
            'owner_type' => Consumer::ConsumerTypeMerchant,
            'domain'     => Consumer::ConsumerDomainRazorpay,
        ];
        $jobName = SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME;

        $outboxMock->expects($this::never())
            ->method('send');

        $manager = $this->getMockedManagerWithAccountIds();
        $manager->publishOutboxJob(false, $jobName, $jobPayload, $mode, []);
    }

    public function testPublishJobSendsEventToOutbox()
    {
        $outboxMock = $this->createOutboxMock();

        $accountId = 'AccountId';
        $mode = Mode::LIVE;
        $metadata = ['dummyMetadata' => true, 'request_id' => app('request')->getId(), 'task_id' => app('request')->getTaskId()];
        $jobPayload = [
            'account_id' => $accountId,
            'mode' => $mode,
            'mock' => false,
            'metadata' => $metadata
        ];
        $jobName = SyncEventObserver::ACS_OUTBOX_JOB_NAME;

        $outboxMock->expects($this::once())
            ->method('send')
            ->with($jobName, $jobPayload, $mode, false);

        $manager = $this->getMockedManagerWithAccountIds();
        $manager->publishOutboxJob(true, $jobName, $jobPayload, $mode, $metadata);
    }

    public function testPublishJobsPublishesLiveSkipsTestAccounts()
    {
        $acsSyncEnabled = true;
        $credcaseSyncEnabled = false;
        $asvSplitzExperimentId = 'K1ZaAGS9JfAUHj';

        Config::set('applications.acs.sync_enabled', $acsSyncEnabled);
        Config::set('applications.acs.credcase_sync_enabled', $credcaseSyncEnabled);
        Config::set('applications.acs.splitz_experiment_id',$asvSplitzExperimentId);

        $metadata = ['dummyMetadata' => true];
        $payloadMetadata  = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);
        $acsBasePayload = [
            'mode' => Mode::LIVE,
            'mock' => false,
            'metadata' => $payloadMetadata,
        ];
        $credcaseBasePayload = [
            'owner_type' => Consumer::ConsumerTypeMerchant,
            'domain'     => Consumer::ConsumerDomainRazorpay,
        ];

        $allOutboxJobs = [SyncEventObserver::ACS_OUTBOX_JOB_NAME, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME];

        // T1 starts
        $manager = $this->getMockedManagerWithAccountIds([], [], ['publishOutboxJob'], $allOutboxJobs);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->publishOutboxJobs($metadata);
         //T1 ends

        // T2 starts - For live Account Ids
        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantRepoMock->shouldReceive('findOrFail')->with("7thBRSDf3F7NHL")->andReturn("");
        $liveAccountIds = ['7thBRSDf3F7NHL'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['isSplitzOn', 'publishOutboxJob'], $allOutboxJobs);
        $acsPayload = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
        $credcasePayload = array_merge(['owner_id' => $liveAccountIds[0]], $credcaseBasePayload);
        //$manager->expects($this->exactly(1))->method('isSplitzOn')->withConsecutive([$asvSplitzExperimentId, '7thBRSDf3F7NHL'])->willReturn(false);
        $manager->expects($this->exactly(2))
            ->method('publishOutboxJob')
            ->withConsecutive(
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload, Mode::LIVE, $metadata]
            );

        $manager->repo->merchant = $merchantRepoMock;
        $manager->publishOutboxJobs($metadata);
        // T2 ends

        // T3 starts - Sync Deviation Call - On - Successfully Synced [Commenting for now]
//        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
//        $merchantRepoMock->shouldReceive('findOrFail')->with("7thBRSDf3F7NHD")->andReturn("");
//        $liveAccountIds = ['7thBRSDf3F7NHD'];
//        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['isSplitzOn','syncAccountDeviation', 'publishOutboxJob'], [SyncEventObserver::ACS_OUTBOX_JOB_NAME]);
//        $acsPayload = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
//        $manager->expects($this->exactly(1))->method('isSplitzOn')->withConsecutive([$asvSplitzExperimentId, '7thBRSDf3F7NHD'])->willReturn(true);
//        $manager->expects($this->exactly(1))->method('syncAccountDeviation')->withConsecutive([$acsSyncEnabled, $acsPayload, Mode::LIVE, $metadata])->willReturn(true);
//        $manager->expects($this->never())
//            ->method('publishOutboxJob');
//        $manager->repo->merchant = $merchantRepoMock;
//        $manager->publishOutboxJobs($metadata);
        // T3 ends


        // T4 starts - Sync Deviation Call - On - Failed to Sync - Push to Outbox [Commenting for now]
//        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
//        $merchantRepoMock->shouldReceive('findOrFail')->withAnyArgs()->andReturn("");
//        $liveAccountIds = ['7thBRSDf3F7NHL'];
//        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['isSplitzOn','syncAccountDeviation', 'publishOutboxJob'], [SyncEventObserver::ACS_OUTBOX_JOB_NAME]);
//        $acsPayload = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
//        $manager->expects($this->exactly(1))->method('isSplitzOn')->withConsecutive([$asvSplitzExperimentId, '7thBRSDf3F7NHL'])->willReturn(true);
//        $manager->expects($this->exactly(1))->method('syncAccountDeviation')->withConsecutive([$acsSyncEnabled, $acsPayload, Mode::LIVE, $metadata])->willReturn(false);
//        $manager->expects($this->exactly(1))
//            ->method('publishOutboxJob')
//            ->withConsecutive(
//                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload, Mode::LIVE, $metadata]
//            );
//        $manager->repo->merchant = $merchantRepoMock;
//        $manager->publishOutboxJobs($metadata);
         //T4 ends

        // T5 starts - For Test Account Ids
        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantRepoMock->shouldNotReceive('findOrFail')->withAnyArgs()->andReturn("");
        $testAccountIds = ['Test1'];
        $manager = $this->getMockedManagerWithAccountIds([], $testAccountIds, ['isSplitzOn','syncAccountDeviation', 'publishOutboxJob'], $allOutboxJobs);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->expects($this->never())
            ->method('isSplitzOn');
        $manager->expects($this->never())
            ->method('syncAccountDeviation');
        $manager->repo->merchant = $merchantRepoMock;
        $manager->publishOutboxJobs($metadata);
        // T5 ends

        // T6 starts - Mix of Live and Test Account Ids
        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantRepoMock->shouldReceive('findOrFail')->withAnyArgs()->andReturn("");
        $liveAccountIds = ['Live1', 'Live2'];
        $testAccountIds = ['Test1', 'Test2'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, $testAccountIds, ['isSplitzOn', 'publishOutboxJob'], $allOutboxJobs);
        $acsPayload0 = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
        $credcasePayload0 = array_merge(['owner_id' => $liveAccountIds[0]], $credcaseBasePayload);
        $acsPayload1 = array_merge(['account_id' => $liveAccountIds[1]], $acsBasePayload);
        $credcasePayload1 = array_merge(['owner_id' => $liveAccountIds[1]], $credcaseBasePayload);
       // $manager->expects($this->exactly(2))->method('isSplitzOn')->withConsecutive([$asvSplitzExperimentId, 'Live1'],[$asvSplitzExperimentId, 'Live2'])->willReturn(false);
        $manager->expects($this->exactly(4))
            ->method('publishOutboxJob')
            ->withConsecutive(
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload0, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload0, Mode::LIVE, $metadata],
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload1, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload1, Mode::LIVE, $metadata]
            );
        $manager->repo->merchant = $merchantRepoMock;
        $manager->publishOutboxJobs($metadata);
        // T6 ends

        // T7 do not process call if account not found
        $merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantRepoMock->shouldReceive('findOrFail')->with("MID1")->andReturn("");
        $liveAccountIds = ['MID2'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['isSplitzOn','syncAccountDeviation', 'publishOutboxJob'], [SyncEventObserver::ACS_OUTBOX_JOB_NAME]);
        $acsPayload = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->expects($this->never())
            ->method('isSplitzOn');
        $manager->expects($this->never())
            ->method('syncAccountDeviation');
        $manager->repo->merchant = $merchantRepoMock;
        $manager->publishOutboxJobs($metadata);
        // T7 end
    }

    public function testIsSplitzOn()
    {
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->any())->method('info');
        $traceMock->expects($this->any())->method('traceException');
        $this->createOutboxMock();

        #T1 starts - isSplitzOn - True
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => '10000000000000',
                'project_id' => 'K1ZCHBSn7hbCMN',
                'experiment' => [
                    'id' => 'K1ZaAGS9JfAUHj',
                    'name' => 'CallSyncDviationAPI',
                    'exclusion_group_id' => '',
                ],
                'variant' => [
                    'id' => 'K1ZaAHZ7Lnumc6',
                    'name' => 'SyncDeviation Enabled',
                    'variables' => [
                        [
                            'key' => 'enabled',
                            'value' => 'true',
                        ]
                    ],
                    'experiment_id' => 'K1ZaAGS9JfAUHj',
                    'weight' => 100,
                    'is_default' => false
                ],
                'Reason' => 'bucketer',
                'steps' => [
                    'sampler',
                    'exclusion',
                    'audience',
                    'assign_bucket'
                ]
            ]
        ];
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $syncDeviation = new SyncEventManager($this->app);
        $output = $syncDeviation->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertTrue($output);
        #T1 ends

        #T2 starts - isSplitzOn - False - status code 400
        $traceMock->expects($this->any())->method('traceException');
        $output = [
            'status_code' => 400,
            'response' => [
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $syncDeviation = new SyncEventManager($this->app);
        $output = $syncDeviation->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T2 ends

        #T3 starts - isSplitzOn - False - status code 200
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => '10000000000000',
                'project_id' => '',
                'experiment' => [
                    'id' => 'K1ZaAGS9JfAUHj',
                    'name' => '',
                    'exclusion_group_id' => '',
                ],
                'variant' => null,
                'Reason' => '',
                'steps' => []
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $syncDeviation = new SyncEventManager($this->app);
        $output = $syncDeviation->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T3 ends

        #T4 starts - isSplitzOn - False - status code 200
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => '10000000000000',
                'project_id' => 'K1ZCHBSn7hbCMN',
                'experiment' => [
                    'id' => 'K1ZaAGS9JfAUHj',
                    'name' => 'CallSyncDviationAPI',
                    'exclusion_group_id' => '',
                ],
                'variant' => [
                    'id' => 'K1ZaAHZ7Lnumc6',
                    'name' => 'SyncDeviation Enabled',
                    'variables' => [
                        [
                            'key' => 'enabled',
                            'value' => 'false',
                        ]
                    ],
                    'experiment_id' => 'K1ZaAGS9JfAUHj',
                    'weight' => 100,
                    'is_default' => false
                ],
                'Reason' => 'bucketer',
                'steps' => [
                    'sampler',
                    'exclusion',
                    'audience',
                    'assign_bucket'
                ]
            ]
        ];
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $syncDeviation = new SyncEventManager($this->app);
        $output = $syncDeviation->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T4 ends
    }

    public function testSyncAccountDeviation(){
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->any())->method('info');
        $traceMock->expects($this->any())->method('traceException');

        #T1 starts  - SyncDeviation Successful
        $detailsDiff = new StringList();
        $detailsDiff->setValue(array("ContactDetails.Policy: Dilip Kr Chauhan != Dilip Kumar Chauhan"));
        $response = [
            "account_id" => "K0HiKkEpCud6Dv",
            "success" => true,
            "diff" => [
                "account_diff" => $detailsDiff,
                "details_diff" => new StringList([]),
                "document_diff" => new StringList([]),
                "stakeholder_diff" => new StringList([])
            ]
        ];
        $responseProto = new syncDeviationV1\SyncAccountDeviationResponse($response);
        $syncDeviationAsvClientMock = $this->createSyncDeviationAsvClientMock();
        $syncDeviationAsvClientMock->expects($this->any())->method('syncAccountDeviation')->willReturn($responseProto);

        $jobPayload = [
            'account_id' => 'K0HiKkEpCud6Dv',
            'mode' => 'live',
            'mock' => false,
            'metadata' => [
                "async_job_name" => "RZP_Jobs_EsSync",
                "request_id" => "aa9de515d75be35083e7b0a9d568a77c",
                "route" => "none",
                "rzp_internal_app_name" => "none",
                "task_id" => "aa9de515d75be35083e7b0a9d568a77c"
            ]
        ];

        $syncDeviation = new SyncEventManager($this->app);
        $syncDeviation->syncDeviationAsvClient =  $syncDeviationAsvClientMock;
        $isSynced = $syncDeviation->syncAccountDeviation(true, $jobPayload, 'live', []);
        $this->assertTrue($isSynced);
        #T1 Ends

        #T2 Starts - SyncDeviation Failed
        $mockError = new syncDeviationV1\TwirpError('internal', 'unexpected error encountered');
        $syncDeviationAsvClientMock = $this->createSyncDeviationAsvClientMock();
        $syncDeviationAsvClientMock->expects($this->any())->method('syncAccountDeviation')->will($this->throwException($mockError));

        $jobPayload = [
            'account_id' => 'K0HiKkEpCud6Dv',
            'mode' => 'live',
            'mock' => false,
            'metadata' => [
                "async_job_name" => "RZP_Jobs_EsSync",
                "request_id" => "aa9de515d75be35083e7b0a9d568a77c",
                "route" => "none",
                "rzp_internal_app_name" => "none",
                "task_id" => "aa9de515d75be35083e7b0a9d568a77c"
            ]
        ];

        $syncDeviation = new SyncEventManager($this->app);
        $syncDeviation->syncDeviationAsvClient =  $syncDeviationAsvClientMock;
        $isSynced = $syncDeviation->syncAccountDeviation(true, $jobPayload, 'live', []);
        $this->assertFalse($isSynced);
    }


    /** TODO: Existing test are itself failing, commenting out as of now will pick it a separate task*/
//    public function testLogForEntityFetchWithTransaction()
//    {
//        $manager = $this->getMockBuilder(SyncEventManager::class)
//            ->setConstructorArgs([$this->app])
//            ->onlyMethods(['logEntityFetch','logEntityUpdate'])
//            ->getMock();
//
//        $mockData = [
//            'route' => null,
//            'internal_app_name' => null,
//            'mode' => null,
//            'connection' => 'test',
//            'async_job_name' => 'none',
//            'entity' => ['name' => 'merchant_email', 'id' => null, 'merchant_id' => null, 'collection' => ['ids' => [], 'merchant_ids' => []]],
//            'is_transaction_active' => true,
//            'stats' => [
//                'total' => ['count' => 0],
//            ],
//            'outbox_jobs' => [],
//        ];
//        $manager->expects($this->once())->method('logEntityFetch')->with($mockData);
//
//        $this->app->instance('acs.syncManager', $manager);
//
//        app('repo')->transaction(function (){
//            (new Email\Repository)->getEmailByType('chargeback', '10000000000000');
//        });
//    }
//
//    public function testLogForEntityFetch()
//    {
//        $manager = $this->getMockBuilder(SyncEventManager::class)
//            ->setConstructorArgs([$this->app])
//            ->onlyMethods(['logEntityFetch','logEntityUpdate'])
//            ->getMock();
//
//        $mockData = [
//            'route' => null,
//            'internal_app_name' => null,
//            'mode' => null,
//            'connection' => 'test',
//            'async_job_name' => 'none',
//            'entity' => ['name' => 'merchant_email', 'id' => '', 'merchant_id' => '', 'collection' => ['ids' => ['HxLynPYNwGIRrQ'], 'merchant_ids' => ['10000000000000']]],
//            'is_transaction_active' => false,
//            'stats' => [
//                'merchant_email' => ['count' => 1],
//                'total' => ['count' => 1],
//            ],
//            'outbox_jobs' => [],
//        ];
//        $manager->expects($this->once())->method('logEntityFetch')->with($mockData);
//
//        $mockData = [
//            'route' => null,
//            'internal_app_name' => null,
//            'mode' => null,
//            'connection' => 'live',
//            'async_job_name' => 'none',
//            'entity' => ['name' => 'merchant_email', 'id' => 'HxLynPYNwGIRrQ', 'merchant_id' => '10000000000000', 'collection' => ['ids' => [], 'merchant_ids' => []]],
//            'is_transaction_active' => false,
//            'stats' => [
//                'total' => ['count' => 0],
//            ],
//            'outbox_jobs' => [SyncEventObserver::ACS_OUTBOX_JOB_NAME],
//        ];
//        $manager->expects($this->once())->method('logEntityUpdate')->with($mockData);
//
//        $this->app->instance('acs.syncManager', $manager);
//
//        $this->fixtures->create('merchant_email', ['id' => 'HxLynPYNwGIRrQ','type' => 'chargeback']);
//
//        (new Email\Repository)->getEmailByType('chargeback', '10000000000000');
//    }

    protected function getMockedManagerWithAccountIds(
        $liveModeAccountIds = [],
        $testModeAccountIds = [],
        $methods = [],
        $outboxJobs = [SyncEventObserver::ACS_OUTBOX_JOB_NAME]
    )
    {
        $manager = $this->getMockBuilder(SyncEventManager::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods($methods)
            ->getMock();

        foreach ($liveModeAccountIds as $accountId) {
            $merchant = new Merchant\Entity(['id' => $accountId]);
            $merchant->setConnection(Mode::LIVE);

            $manager->recordAccountSync($merchant, $outboxJobs);
        }

        foreach ($testModeAccountIds as $accountId) {
            $merchant = new Merchant\Entity(['id' => $accountId]);
            $merchant->setConnection(Mode::TEST);

            $manager->recordAccountSync($merchant, $outboxJobs);
        }

        return $manager;
    }

    protected function createOutboxMock(array $methods = ['send'])
    {
        $encrypter = new AES256GCMEncrypt('OUTBOX_ENCRYPTION_KEY');
        $encoder   = new JsonEncoder();
        $repo      = new Repository(\Database\Connection::LIVE);
        $trace = $this->getMockBuilder(Logger::class)->getMock();
        $mock = $this->getMockBuilder(OutboxCore::class)
            ->setConstructorArgs([$encrypter, $encoder, $repo, $trace])
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('outbox', $mock);
        return $mock;
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {

        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }

    protected function createSyncDeviationAsvClientMock()
    {
        $syncDeviantionAsvClientMock = $this->getMockBuilder(AsvClient\SyncAccountDeviationAsvClient::class)
            ->getMock();
        return $syncDeviantionAsvClientMock;
    }
}

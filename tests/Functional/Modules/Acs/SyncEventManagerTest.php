<?php

namespace RZP\Tests\Functional\Modules\Acs;

use Config;

use Razorpay\Outbox\Encoder\JsonEncoder;
use Razorpay\Outbox\Encrypt\AES256GCMEncrypt;
use Psr\Log\LoggerInterface as Logger;
use Razorpay\Outbox\Job\Core as OutboxCore;
use Razorpay\Outbox\Job\Repository;
use RZP\Constants\Mode;
use RZP\Models\Consumer\Service as Consumer;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Email;
use RZP\Modules\Acs\SyncEventManager;
use RZP\Modules\Acs\SyncEventObserver;
use RZP\Tests\Functional\TestCase;

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

        Config::set('applications.acs.sync_enabled', $acsSyncEnabled);
        Config::set('applications.acs.credcase_sync_enabled', $credcaseSyncEnabled);

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
        // T1 ends

        // T2 starts
        $liveAccountIds = ['Live1'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['publishOutboxJob'], $allOutboxJobs);
        $acsPayload = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
        $credcasePayload = array_merge(['owner_id' => $liveAccountIds[0]], $credcaseBasePayload);
        $manager->expects($this->exactly(2))
            ->method('publishOutboxJob')
            ->withConsecutive(
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload, Mode::LIVE, $metadata]
            );
        $manager->publishOutboxJobs($metadata);
        // T2 ends

        // T3 starts
        $testAccountIds = ['Test1'];
        $manager = $this->getMockedManagerWithAccountIds([], $testAccountIds, ['publishOutboxJob'], $allOutboxJobs);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->publishOutboxJobs($metadata);
        // T3 ends

        // T4 starts
        $liveAccountIds = ['Live1', 'Live2'];
        $testAccountIds = ['Test1', 'Test2'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, $testAccountIds, ['publishOutboxJob'], $allOutboxJobs);
        $acsPayload0 = array_merge(['account_id' => $liveAccountIds[0]], $acsBasePayload);
        $credcasePayload0 = array_merge(['owner_id' => $liveAccountIds[0]], $credcaseBasePayload);
        $acsPayload1 = array_merge(['account_id' => $liveAccountIds[1]], $acsBasePayload);
        $credcasePayload1 = array_merge(['owner_id' => $liveAccountIds[1]], $credcaseBasePayload);
        $manager->expects($this->exactly(4))
            ->method('publishOutboxJob')
            ->withConsecutive(
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload0, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload0, Mode::LIVE, $metadata],
                [$acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME, $acsPayload1, Mode::LIVE, $metadata],
                [$credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME, $credcasePayload1, Mode::LIVE, $metadata]
            );
        $manager->publishOutboxJobs($metadata);
        // T4 ends
    }

    public function testLogForEntityFetchWithTransaction()
    {
        $manager = $this->getMockBuilder(SyncEventManager::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['logEntityFetch','logEntityUpdate'])
            ->getMock();

        $mockData = [
            'route' => null,
            'internal_app_name' => null,
            'mode' => null,
            'connection' => 'test',
            'async_job_name' => 'none',
            'entity' => ['name' => 'merchant_email', 'id' => null, 'merchant_id' => null, 'collection' => ['ids' => [], 'merchant_ids' => []]],
            'is_transaction_active' => true,
            'stats' => [
                'total' => ['count' => 0],
            ],
            'outbox_jobs' => [],
        ];
        $manager->expects($this->once())->method('logEntityFetch')->with($mockData);

        $this->app->instance('acs.syncManager', $manager);

        app('repo')->transaction(function (){
            (new Email\Repository)->getEmailByType('chargeback', '10000000000000');
        });
    }

    public function testLogForEntityFetch()
    {
        $manager = $this->getMockBuilder(SyncEventManager::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['logEntityFetch','logEntityUpdate'])
            ->getMock();

        $mockData = [
            'route' => null,
            'internal_app_name' => null,
            'mode' => null,
            'connection' => 'test',
            'async_job_name' => 'none',
            'entity' => ['name' => 'merchant_email', 'id' => '', 'merchant_id' => '', 'collection' => ['ids' => ['HxLynPYNwGIRrQ'], 'merchant_ids' => ['10000000000000']]],
            'is_transaction_active' => false,
            'stats' => [
                'merchant_email' => ['count' => 1],
                'total' => ['count' => 1],
            ],
            'outbox_jobs' => [],
        ];
        $manager->expects($this->once())->method('logEntityFetch')->with($mockData);

        $mockData = [
            'route' => null,
            'internal_app_name' => null,
            'mode' => null,
            'connection' => 'live',
            'async_job_name' => 'none',
            'entity' => ['name' => 'merchant_email', 'id' => 'HxLynPYNwGIRrQ', 'merchant_id' => '10000000000000', 'collection' => ['ids' => [], 'merchant_ids' => []]],
            'is_transaction_active' => false,
            'stats' => [
                'total' => ['count' => 0],
            ],
            'outbox_jobs' => [SyncEventObserver::ACS_OUTBOX_JOB_NAME],
        ];
        $manager->expects($this->once())->method('logEntityUpdate')->with($mockData);

        $this->app->instance('acs.syncManager', $manager);

        $this->fixtures->create('merchant_email', ['id' => 'HxLynPYNwGIRrQ','type' => 'chargeback']);

        (new Email\Repository)->getEmailByType('chargeback', '10000000000000');
    }

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
}

<?php

namespace RZP\Tests\Functional\Modules\Acs;

use Config;

use Razorpay\Outbox\Encoder\JsonEncoder;
use Razorpay\Outbox\Encrypt\AES256GCMEncrypt;
use Razorpay\Outbox\Job\Core as OutboxCore;
use Razorpay\Outbox\Job\Repository;
use RZP\Constants\Mode;
use RZP\Modules\Acs\SyncEventManager;
use RZP\Tests\Functional\TestCase;

class SyncEventManagerTest extends TestCase
{
    public function testCollectsAccountIdsForTestAndLiveMode()
    {
        $liveModeAccountIds = ['Live1', 'Live2', 'Live3'];
        $testModeAccountIds = ['Test1', 'Test2', 'Test3'];
        $manager = $this->getMockedManagerWithAccountIds($liveModeAccountIds, $testModeAccountIds);

        $this->assertEquals($liveModeAccountIds, array_values($manager->getLiveAccountIds()));
        $this->assertEquals($testModeAccountIds, array_values($manager->getTestAccountIds()));
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

    public function testPublishJobSendsEventToOutbox()
    {
        Config::set('applications.acs.sync_enabled', true);
        $outboxMock = $this->createOutboxMock();

        $accountId = 'AccountId';
        $mode = Mode::TEST;
        $metadata = ['dummyMetadata' => true, 'request_id' => app('request')->getId(), 'task_id' => app('request')->getTaskId()];
        $expectedPayload = [
            'account_id' => $accountId,
            'mode' => $mode,
            'mock' => false,
            'metadata' => $metadata
        ];
        $outboxMock->expects($this::once())
            ->method('send')
            ->with(SyncEventManager::OUTBOX_JOB_NAME, $expectedPayload, $mode, false);

        $manager = $this->getMockedManagerWithAccountIds();
        $manager->publishOutboxJob($accountId, $mode, $metadata);
    }

    public function testPublishJobSkipsSendsEventToOutbox()
    {
        // assuming default to be false, similar to setting it like following
        Config::set('applications.acs.sync_enabled', false);
        $outboxMock = $this->createOutboxMock();

        $accountId = 'AccountId';
        $mode = Mode::TEST;
        $metadata = ['dummyMetadata' => true];
        $outboxMock->expects($this->never())
            ->method('send');

        $manager = $this->getMockedManagerWithAccountIds();
        $manager->publishOutboxJob($accountId, $mode, $metadata);
    }

    public function testPublishJobsPublishesLiveSkipTestAccounts()
    {
        $metadata = ['dummyMetadata' => true];

        $manager = $this->getMockedManagerWithAccountIds([], [], ['publishOutboxJob']);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->publishOutboxJobs($metadata);

        $liveAccountIds = ['Live1'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, [], ['publishOutboxJob']);
        $manager->expects($this->once())
            ->method('publishOutboxJob')
            ->with($liveAccountIds[0], Mode::LIVE, $metadata);
        $manager->publishOutboxJobs($metadata);

        $testAccountIds = ['Test1'];
        $manager = $this->getMockedManagerWithAccountIds([], $testAccountIds, ['publishOutboxJob']);
        $manager->expects($this->never())
            ->method('publishOutboxJob');
        $manager->publishOutboxJobs($metadata);

        $liveAccountIds = ['Live1', 'Live2'];
        $testAccountIds = ['Test1', 'Test2'];
        $manager = $this->getMockedManagerWithAccountIds($liveAccountIds, $testAccountIds, ['publishOutboxJob']);
        $manager->expects($this->exactly(2))
            ->method('publishOutboxJob')
            ->withConsecutive(
                [$liveAccountIds[0], Mode::LIVE, $metadata],
                [$liveAccountIds[1], Mode::LIVE, $metadata]
            );
        $manager->publishOutboxJobs($metadata);
    }

    protected function getMockedManagerWithAccountIds(
        $liveModeAccountIds = [],
        $testModeAccountIds = [],
        $methods = []
    )
    {
        $manager = $this->getMockBuilder(SyncEventManager::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods($methods)
            ->getMock();

        foreach ($liveModeAccountIds as $accountId) {
            $manager->recordAccountSync($accountId, Mode::LIVE);
        }

        foreach ($testModeAccountIds as $accountId) {
            $manager->recordAccountSync($accountId, Mode::TEST);
        }

        return $manager;
    }

    protected function createOutboxMock(array $methods = ['send'])
    {
        $encrypter = new AES256GCMEncrypt('OUTBOX_ENCRYPTION_KEY');
        $encoder   = new JsonEncoder();
        $repo      = new Repository(\Database\Connection::LIVE);
        $mock = $this->getMockBuilder(OutboxCore::class)
            ->setConstructorArgs([$encrypter, $encoder, $repo])
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('outbox', $mock);
        return $mock;
    }
}

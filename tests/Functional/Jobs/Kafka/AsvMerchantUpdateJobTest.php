<?php

namespace Functional\Jobs\Kafka;

use Redis;

use RZP\Trace\Trace;
use RZP\Constants\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Repository;
use RZP\Jobs\Kafka\AsvMerchantUpdateJob;

class AsvMerchantUpdateJobTest extends TestCase
{
    public function testHandleMethodInvalidatesRedis(): void
    {
        config(['app.query_cache.mock' => false]);

        $merchant = $this->fixtures->create('merchant');

        $payload = [
            AsvMerchantUpdateJob::MERCHANT_ID => $merchant->getId(),
            AsvMerchantUpdateJob::ENTITY_NAME => Entity::MERCHANT,
        ];

        // populates redis cache
        (new Repository())->findOrFail($merchant->getId());

        $liveKeyValue = Redis::connection('query_cache_redis')->zrange('live:tag:merchant_'.$merchant->getId().':entries', 0, 10);
        $this->assertNotEmpty($liveKeyValue);

        $job = new AsvMerchantUpdateJob($payload);

        // Act
        $job->handle();

        $liveKeyValue = Redis::connection('query_cache_redis')->zrange('live:tag:merchant_'.$merchant->getId().':entries', 0, 10);
        $this->assertEmpty($liveKeyValue);

    }

    public function testCreateAuditForEntities(): void
    {
        config(['app.query_cache.mock' => false]);

        $testAuditId = "10000000000005";
        $testAdminId = "admin090012351";

        $merchant = $this->fixtures->create('merchant');

        $payload = [
            AsvMerchantUpdateJob::MERCHANT_ID => $merchant->getId(),
            AsvMerchantUpdateJob::ENTITY_NAME => Entity::MERCHANT,
            'entity' => ['audit_id' => $testAuditId],
            'metadata' => [
                'actor'=> [
                    'id' => $testAdminId,
                    'type' => 'admin'
                ],
                'auth_type' => 'private',
                'app_name' => 'bvs',
                'ip' => '127.0.0.1',
                'task_id' => '5a2d4ab160e2a2c35d4b001b01ea6427'
            ]
        ];

        $job = new AsvMerchantUpdateJob($payload);

        $job->handle();

        $auditInfoEntity = (new \RZP\Models\Base\Audit\Repository())->findOrFailPublic($testAuditId, ['*'], 'live');
        $this->assertNotNull($auditInfoEntity);

    }
}

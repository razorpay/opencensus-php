<?php

namespace Functional\Jobs\Kafka;

use Redis;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\Merchant\Core;
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

        $liveKeyValue = Redis::connection('query_cache_redis')->get('live:tag:merchant_'.$merchant->getId().':key');
        $this->assertNotNull($liveKeyValue);

        $job = new AsvMerchantUpdateJob($payload);

        // Act
        $job->handle();

        $liveKeyValue = Redis::connection('query_cache_redis')->get('live:tag:merchant_'.$merchant->getId().':key');
        $this->assertNull($liveKeyValue);

    }
}

<?php

namespace RZP\Tests\Unit\Models\Payout;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\EsRepository;

class EsRepositoryTest extends TestCase
{
    public function testBuildQueryForUserId(): void
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $userId = "someUserId";
        $esRepo->buildQueryForUserId($query, $userId);
        $this->assertSame(array_get($query, "bool.filter.bool.must.0.term")["user_id.keyword"]["value"], $userId);
    }
}

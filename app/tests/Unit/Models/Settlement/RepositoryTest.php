<?php
namespace Tests\Unit\Models\Settlement;

use Tests\Functional\Fixtures\Entity\Merchant as MerchantFixture;
use Tests\Functional\Fixtures\Entity\Settlement as Fixture;
use Tests\Functional\TestCase;
use Models\Settlement\Repository as SettlementRepository;

class RepositoryTest extends TestCase
{
    function setUp()
    {
        parent::setUp();
        $fixture = (new Fixture);
        $this->merchantId = '10000000000000';
        $this->startTime = time();

        $settlement = $fixture->createEntity([
            'id'    =>  '121212121',
            'status'    =>  'transferred',
            'merchant_id'   => $this->merchantId,
            'amount'        => 1000
        ]);

        $this->endTime = time();
    }

    function testSettlementFetch()
    {
        $settlements = (new SettlementRepository)->fetchBetweenTimestamp(
            $this->startTime, $this->endTime, $this->merchantId);
        $this->assertEquals(1, count($settlements));
    }
}

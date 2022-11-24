<?php

namespace Unit\PayoutSource;

use Mockery;
use RZP\Models\PayoutSource\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutSource\Entity;
use RZP\Models\Base\UniqueIdEntity;

class PayoutSourceTest extends TestCase
{
    public function testGetPayoutSource()
    {
        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        $psMock =  Mockery::mock('\RZP\Models\PayoutSource\Repository');

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $mockPayoutSource = new Entity();

        $mockPayoutSource->setId($uniqueId);

        $mockPayoutSource->setSourceType('vendor-payment');

        $mockPayoutSource->setSourceId('vdpm_'.UniqueIdEntity::generateUniqueId());

        $repoMock->shouldReceive('driver')->with('payout_source')->andReturn($psMock);

        $psMock->shouldReceive('getPayoutSourceByPayoutIdAndPriority')->andReturn($mockPayoutSource);

        $this->app->instance('repo', $repoMock);

        $payoutSourceCore = new Core();

        $payoutSource = $payoutSourceCore->getPayoutSource($uniqueId);

        $this->assertEquals($payoutSource->getSourceId(), $mockPayoutSource->getSourceId());

        $this->assertEquals($payoutSource->getSourceType(), $mockPayoutSource->getSourceType());

        $psMock->shouldReceive('getPayoutSourceByPayoutIdAndPriority')->andReturn(null);

        $payoutSource = $payoutSourceCore->getPayoutSource($uniqueId);

        $this->assertNotNull($payoutSource);
    }
}

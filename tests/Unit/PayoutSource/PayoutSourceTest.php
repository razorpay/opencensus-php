<?php

namespace Unit\PayoutSource;

use App;
use RZP\Models\PayoutSource\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutSource\Entity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\PayoutSource\Repository;

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

        $psRepoMock = $this->getMockBuilder(Repository::class)
                           ->setConstructorArgs([$this->app])
                           ->addMethods(['getPayoutSourceByPayoutIdAndPriority'])
                           ->getMock();

        $this->app->instance('repo', $psRepoMock);

        $payoutSourceCore = new Core();
        $mockPayoutSource = new Entity();
        $uniqueId = UniqueIdEntity::generateUniqueId();
        $mockPayoutSource->setId($uniqueId);
        $mockPayoutSource->setSourceType('vendor-payment');
        $mockPayoutSource->setSourceId('vdpm_'.UniqueIdEntity::generateUniqueId());
        $psRepoMock->method('getPayoutSourceByPayoutIdAndPriority')->willReturn($mockPayoutSource);

        $payoutSource = $payoutSourceCore->getPayoutSource($uniqueId);
        $this->assertEquals($payoutSource->getSourceId(), $mockPayoutSource->getSourceId());
        $this->assertEquals($payoutSource->getSourceType(), $mockPayoutSource->getSourceType());
        $psRepoMock->method('getPayoutSourceByPayoutIdAndPriority')->willReturn(null);
        $payoutSource = $payoutSourceCore->getPayoutSource($uniqueId);
        $this->assertNull($payoutSource);
    }
}
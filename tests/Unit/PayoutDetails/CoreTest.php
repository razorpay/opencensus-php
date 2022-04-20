<?php

namespace Unit\PayoutDetails;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\PayoutsDetails\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutSource\Entity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\PayoutSource\Repository;

class CoreTest extends TestCase
{
    public function testUpdateAttachments()
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

        // returning payout with source should not allow updates
        $mockPayoutSource = new Entity();

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $mockPayoutSource->setId($uniqueId);

        $mockPayoutSource->setSourceType('vendor-payment');

        $mockPayoutSource->setSourceId('vdpm_'.UniqueIdEntity::generateUniqueId());

        $psRepoMock->method('getPayoutSourceByPayoutIdAndPriority')->willReturn($mockPayoutSource);

        $payoutDetails = new Core();

        $response = $payoutDetails->updateAttachments('pout_'.$uniqueId, []);

        $this->assertEquals('FAIL', $response['status']);

        $this->assertEquals(TraceCode::PAYOUT_INVALID_UPDATE, $response['error']);

        // in case payout does not have source, updates should be allowed

        $psRepoMock->method('getPayoutSourceByPayoutIdAndPriority')->willReturn(null);

        $payoutRepoMock = $this->getMockBuilder(\RZP\Models\Payout\Repository::class)
                               ->setConstructorArgs([$this->app])
                               ->addMethods(['updatePayout'])
                               ->getMock();

        $payoutRepoMock->method('updatePayout')->willReturn(null);

        $response = $payoutDetails->updateAttachments('pout_'.$uniqueId,
                                                ['attachments' =>
                                                     ['file_id' => 'file_yeherkw', 'file_name' => 'abdsh.pdf']]);

        $this->assertEquals('SUCCESS', $response['status']);

        $this->assertNull($response['error']);

        // error while update should return failure
        $payoutRepoMock->method('updatePayout')->willThrowException(new \Exception('could not update'));

        $response = $payoutDetails->updateAttachments('pout_'.$uniqueId,
                                                ['attachments' =>
                                                     ['file_id' => 'file_yeherkw', 'file_name' => 'abdsh.pdf']]);

        $this->assertEquals('FAIL', $response['status']);

        $this->assertEquals('could not update', $response['error']);
    }
}
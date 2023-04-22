<?php

namespace Unit\PayoutDetails;

use Mockery;
use RZP\Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\PayoutsDetails\Entity;
use RZP\Models\Base\PublicCollection;

class CoreTest extends TestCase
{
    public function testUpdateAttachmentsWithNoPayoutDetails()
    {
        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        $pdRepoMock =  Mockery::mock('\RZP\Models\PayoutsDetails\Repository');

        $repoMock->shouldReceive('driver')->with('payouts_details')->andReturn($pdRepoMock);

        $pdRepoMock->shouldReceive('getPayoutDetailsByPayoutId')->andReturn(new PublicCollection());

        $pdRepoMock->shouldReceive('saveOrFail')->andReturn([]);

        $this->app->instance('repo', $repoMock);

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $mock = $this->getMockBuilder('\RZP\Models\PayoutsDetails\Core')
            ->onlyMethods(array('renameAttachments'))
            ->getMock();

        $mock->method('renameAttachments')->willReturn([]);

        $response = $mock->updateAttachments($uniqueId, []);

        $this->assertEquals('SUCCESS', $response['status']);
    }

    public function testUpdateAttachmentsWithPayoutDetails()
    {
        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        $pdRepoMock =  Mockery::mock('\RZP\Models\PayoutsDetails\Repository');

        $repoMock->shouldReceive('driver')->with('payouts_details')->andReturn($pdRepoMock);

        $payoutDetails = new PublicCollection();

        $payoutDetail = new Entity();

        $payoutDetail->setAttribute("payout_id", "pout_1234567890");

        $payoutDetails->push($payoutDetail);

        $pdRepoMock->shouldReceive('getPayoutDetailsByPayoutId')->andReturn($payoutDetails);

        $pdRepoMock->shouldReceive('updatePayoutDetails')->andReturn([]);

        $this->app->instance('repo', $repoMock);

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $mock = $this->getMockBuilder('\RZP\Models\PayoutsDetails\Core')
            ->onlyMethods(array('renameAttachments'))
            ->getMock();

        $mock->method('renameAttachments')->willReturn([]);

        $response = $mock->updateAttachments($uniqueId, []);

        $this->assertEquals('SUCCESS', $response['status']);
    }

    public function testUpdateAttachmentsFailed()
    {
        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        $pdRepoMock =  Mockery::mock('\RZP\Models\PayoutsDetails\Repository');

        $repoMock->shouldReceive('driver')->with('payouts_details')->andReturn($pdRepoMock);

        $payoutDetails = new PublicCollection();

        $payoutDetail = new Entity();

        $payoutDetail->setAttribute("payout_id", "pout_1234567890");

        $payoutDetails->push($payoutDetail);

        $pdRepoMock->shouldReceive('getPayoutDetailsByPayoutId')->andReturn($payoutDetails);

        $this->app->instance('repo', $repoMock);

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $mock = $this->getMockBuilder('\RZP\Models\PayoutsDetails\Core')
            ->onlyMethods(array('renameAttachments'))
            ->getMock();

        try
        {
            $mock->updateAttachments($uniqueId, []);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\ServerErrorException::class);
        }
    }
}

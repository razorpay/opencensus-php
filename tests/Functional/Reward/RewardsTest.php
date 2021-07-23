<?php


namespace Functional\Reward;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class RewardsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/RewardsTestData.php';

        parent::setUp();
    }

    public function testCreateReward()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateRewardWithUniqueCoupons()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testActivateReward()
    {
        $this->ba->proxyAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id]);

        $this->testData[__FUNCTION__]['request']['content']['reward_id'] = $reward->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['live_reward_id'] = $reward->id;

        $this->startTest();
    }

    public function testUpdateReward()
    {
        $this->ba->adminAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward',['reward_id' => $reward->id]);

        $this->testData[__FUNCTION__]['request']['content']['reward']['id'] = $reward->getPublicId();


        $this->testData[__FUNCTION__]['response']['content']['reward']['id'] = $reward->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['reward']['ends_at'] = Carbon::now()->addDays(2)->getTimestamp();

        $this->testData[__FUNCTION__]['response']['content']['reward']['starts_at'] = $reward->starts_at;

        $this->testData[__FUNCTION__]['response']['content']['reward']['ends_at'] = Carbon::now()->addDays(2)->getTimestamp();

        $this->startTest();
    }
    
    public function testUpdateRewardWithUniqueCoupons()
    {
        $this->ba->adminAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward',['reward_id' => $reward->id]);

        $this->testData[__FUNCTION__]['request']['content']['reward']['id'] = $reward->getPublicId();


        $this->testData[__FUNCTION__]['response']['content']['reward']['id'] = $reward->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['reward']['ends_at'] = Carbon::now()->addDays(2)->getTimestamp();

        $this->testData[__FUNCTION__]['response']['content']['reward']['starts_at'] = $reward->starts_at;

        $this->testData[__FUNCTION__]['response']['content']['reward']['ends_at'] = Carbon::now()->addDays(2)->getTimestamp();

        $this->startTest();
    }


    public function testUpdateRewardWithWrongStartTime()
    {
        $this->ba->adminAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward',['reward_id' => $reward->id]);

        $this->testData[__FUNCTION__]['request']['content']['reward']['id'] = $reward->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['reward']['starts_at'] = Carbon::yesterday()->getTimestamp();

        $this->startTest();
    }

    public function testDeactivateReward()
    {
        $this->ba->proxyAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
                                               'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $this->testData[__FUNCTION__]['request']['content']['reward_id'] = $reward->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['deactivated_reward_id'] = $reward->id;

        $this->startTest();
    }

    public function testRewardMovingToLiveOnActivate()
    {
        $this->ba->adminAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward2 = $this->fixtures->create('reward', ['name' => 'Test Reward 2']);

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward2->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward3 = $this->fixtures->create('reward', ['name' => 'Test Reward 3']);

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward3->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward4 = $this->fixtures->create('reward', ['name' => 'Test Reward 4']);

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward4->id, 'status' => 'available']);

        $this->ba->proxyAuth();

        $content = [
            'reward_id' => $reward4->getPublicId(),
            'activate'  => 1,
        ];

        $request = [
            'method' => 'PATCH',
            'url' => '/rewards',
            'content' => $content
        ];

        $response = $this->makeRequestAndGetContent($request, $callback);

        $this->assertEquals('live', $response['status']);
    }

    public function testDeleteReward()
    {
        $this->ba->adminAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $this->testData[__FUNCTION__]['request']['url'] = '/rewards/'. $reward->getPublicId();

        $this->startTest();
    }

    public function testActivateRewardWithWrongStatus()
    {
        $this->ba->proxyAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $this->testData[__FUNCTION__]['request']['content']['reward_id'] = $reward->getPublicId();

        $this->startTest();
    }

    public function testDeactivateRewardWithWrongStatus()
    {
        $this->ba->proxyAuth();

        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id]);

        $this->testData[__FUNCTION__]['request']['content']['reward_id'] = $reward->getPublicId();

        $this->startTest();
    }

    public function testExpireRewardCron()
    {
        //$this->markTestSkipped("Skipping the test until 25-01-2021: manual testing done");
        $callback = null;

        $this->ba->cronAuth();

        $expiredReward = $this->fixtures->create('reward',
            ['starts_at' => Carbon::now()->addDays(-2)->getTimestamp(), 'ends_at' => Carbon::yesterday()->getTimestamp()]);

        $expiredMerchantReward = $this->fixtures->create('merchant_reward', ['reward_id' => $expiredReward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward1 = $this->fixtures->create('reward', ['name' => 'Test Reward 1']);

        $merchantReward1 = $this->fixtures->create('merchant_reward', ['reward_id' => $reward1->id, 'status' => 'queue',
            'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward2 = $this->fixtures->create('reward', ['name' => 'Test Reward 2']);

        $merchantReward2 = $this->fixtures->create('merchant_reward', ['reward_id' => $reward2->id, 'status' => 'queue',
            'accepted_at' => Carbon::today()->getTimestamp()]);


        $reward3 = $this->fixtures->create('reward', ['name' => 'Test Reward 3']);

        $merchantReward3 = $this->fixtures->create('merchant_reward', ['reward_id' => $reward3->id, 'status' => 'queue',
            'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward4 = $this->fixtures->create('reward', ['name' => 'Test Reward 4']);

        $merchantReward4 = $this->fixtures->create('merchant_reward', ['reward_id' => $reward4->id, 'status' => 'queue',
            'accepted_at' => Carbon::today()->getTimestamp()]);

        $reward5 = $this->fixtures->create('reward', ['name' => 'Test Reward 5', 'starts_at'=> Carbon::tomorrow()->getTimestamp()]);

        $merchantReward5 = $this->fixtures->create('merchant_reward', ['reward_id' => $reward5->id, 'status' => 'queue',
            'accepted_at' => Carbon::today()->getTimestamp()]);

        $request = array(
            'url'     => '/rewards/expire',
            'method'  => 'POST');

        $response = $this->makeRequestAndGetContent($request, $callback);

        $this->assertEquals(1, $response['success']);

        $this->ba->proxyAuth();

        $fetchRequest = [
            'method' => 'GET',
            'url' => '/rewards',
            'content' => []
        ];

        $fetchResponse = $this->makeRequestAndGetContent($fetchRequest, $callback);

        $this->assertEquals('live', $fetchResponse[0]['status']);

        $this->assertEquals('live', $fetchResponse[1]['status']);

        $this->assertEquals('live', $fetchResponse[2]['status']);

        $this->assertEquals('live', $fetchResponse[3]['status']);

        $this->assertEquals('queue', $fetchResponse[4]['status']);


    }

    public function testGetNullAdvertiserLogo()
    {
        $this->ba->adminAuth();

        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['url'] = '/advertiser/logo/'.$merchant->id;

        $this->startTest();
    }
    public function testGetAdvertiserLogo()
    {
        $this->ba->adminAuth();

        $merchant = $this->fixtures->create('merchant',['logo_url' => "/advertiser_logo_url"]);

        $this->testData[__FUNCTION__]['request']['url'] = '/advertiser/logo/'.$merchant->id;

        $this->startTest();
    }
}

<?php

namespace RZP\Tests\Functional\Promotion;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Services\HubspotClient;
use RZP\Models\Promotion\Event;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;

class PromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use CreateLegalDocumentsTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PromotionsTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
    }

    public function testCreateOneTimePromotion()
    {
        $this->startTest();
    }

    public function testCreateOneTimePromotionWithPartnerId()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->startTest();

        $promo = $this->getLastEntity('promotion', true);

        $this->assertEquals('10000000000000', $promo['partner_id']);
    }

    public function testCreateOneTimePromotionWithInvalidPartnerId()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);

        $this->startTest();
    }

    public function testCreateOneTimePromotionWithNonPartner()
    {
        $this->startTest();
    }

    public function testCreateRecurringPromotion()
    {
        $this->startTest();

        $schedule = $this->getLastEntity('schedule', true);

        $this->assertArraySelectiveEquals(
            $this->testData['scheduleEntity'], $schedule);
    }

    public function testUpdateExistingOnetimePromotion()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['url'] = '/promotions/' . $promotion->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $promotion->getPublicId();

        $this->startTest();
    }

    public function testUpdateExistingRecurringPromotion()
    {
        $promotion = $this->fixtures->create('promotion:recurring');

        $this->testData[__FUNCTION__]['request']['url'] = '/promotions/' . $promotion->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $promotion->getPublicId();

        $this->startTest();

        $schedule =  $payment = $this->getLastEntity('schedule', true);

        $this->assertEquals($schedule['interval'], 3);
    }

    public function testUpdateUsedPromotion()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $content = [
            'merchant_id' => '10000000000000',
            'code' => 'RANDOM',
        ];

        $this->applyCouponOnMerchant($content);

        $this->testData[__FUNCTION__]['request']['url'] ='/promotions/' . $promotion->getPublicId();

        $this->startTest();

    }

    public function applyCouponOnMerchant(array $content)
    {
        $request = [
            'url'     => '/coupons/apply',
            'method'  => 'post',
            'content' => $content
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testPromotionWithUnsupportedCreditType()
    {
        $this->startTest();
    }

    public function testPromotionWithInvalidInterval()
    {
        $this->startTest();
    }

    public function testPromotionWithInvalidPeriod()
    {
        $this->startTest();
    }

    public function testPromotionWithMissingInterval()
    {
        $this->startTest();
    }

    public function testPromotionWithMissingPeriod()
    {
        $this->startTest();
    }

    public function testCreateBankingPromotion($input = null)
    {
        $this->ba->adminAuth('live');

        $response = $this->makeEventForPromotion();

        if ($input !== null)
        {
            $this->testData[__FUNCTION__]['request']['content'] += $input;
        }

        $this->testData[__FUNCTION__]['request']['content']['event_id']  = $response['id'];

        $this->startTest();

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $event = $this->getDbLastEntity('promotion_event', 'live');

        $this->assertEquals($event['id'], $response['id']);

        $this->assertEquals('activated', $promotion['status']);

        $this->assertEquals('reward_fee', $promotion['credit_type']);

        if ($input !== null)
        {
            $this->assertEquals($input['end_at'], $promotion['end_at']);
        }
    }

    public function testCreateBankingPromotionOverlap()
    {
        $this->testCreateBankingPromotion();

        $event = $this->getDbLastEntity('promotion_event', 'live');

        $this->testData[__FUNCTION__]['request']['content']['event_id']  = $event['id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data);
    }

    public function testDeactivateBankingPromotion()
    {
        $this->testCreateBankingPromotion();

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__]['request']['url'] = '/promotions/'. $promotion->getId().'/deactivate';

        $this->startTest();
    }

    public function testMerchantSignUpWithBankingPromotionWithEndAtNull()
    {
        $this->mockBvsService();

        $this->testCreateBankingPromotion();

        $event = $this->getDbLastEntity('promotion_event', 'live');

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail');

        $user = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->startTest();

        $credit = $this->getDbLastEntity('credits', 'live');
        $this->assertEquals(100, $credit['value']);

        $creditBalance = $this->getDbLastEntity('credit_balance', 'live');
        $this->assertEquals(100, $creditBalance['balance']);

        // test merchant dashboard API call to fetch credit balances of merchant
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $request = [
            'url' => '/merchants/credits/balance/banking',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method' => 'GET',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('reward_fee', $response[0]['type']);
    }

    public function testMerchantSignUpWithBankingPromotionWithEndAtInFuture()
    {
        $this->mockBvsService();

        $timestamp = Carbon::now()->addYear()->getTimestamp();;

        $input = [
          'end_at' => $timestamp,
        ];

        $this->testCreateBankingPromotion($input);

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->assertEquals($timestamp, $promotion['end_at']);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail');

        $user = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->startTest();

        $credit = $this->getDbLastEntity('credits', 'live');

        // test merchant dashboard API call to fetch credit balances of merchant
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $request = [
            'url' => '/merchants/credits/balance/banking',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method' => 'GET',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('reward_fee', $response[0]['type']);

        $this->assertEquals(100, $response[0]['balance']);
    }

    public function testMerchantSignUpWithBankingPromotionWithEndAtInPast()
    {
        $this->mockBvsService();

        $timestamp = Carbon::createFromDate(2020, 12, 01, Timezone::IST)->getTimestamp();

        $this->testCreateBankingPromotion();

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->fixtures->edit('promotion',$promotion['id'],['end_at' => $timestamp,]);

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->assertEquals($timestamp, $promotion['end_at']);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail');
        $user = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $user->getId());

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->startTest();

        $credit = $this->getDbLastEntity('credits', 'live');

        $this->assertNull($credit);

        // test merchant dashboard API call to fetch credit balances of merchant
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $user->getId());

        $request = [
            'url' => '/merchants/credits/balance/banking',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method' => 'GET',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEmpty($response);
    }

    protected function makeEventForPromotion()
    {
        $data = [
            Event\Entity::NAME           => 'sign up',
            Event\Entity::DESCRIPTION    => 'sign up related credits',

        ];

        $request = [
            'content' => $data,
            'url'     => '/promotions/events',
            'method'  => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockHubSpotClient($methodName)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods([$methodName])
            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        $hubSpotMock->expects($this->exactly(1))
            ->method($methodName);
    }

    public function testUpdatePromotionWithoutPermission()
    {
        $promotion = $this->fixtures->create('promotion');

        $this->testData[__FUNCTION__]['request']['url'] ='/promotions/' . $promotion->getPublicId();

        $role = $this->ba->getAdmin()->roles->first();

        $permission = $this->getDbEntities('permission', ['name' => 'payment_promotion_event_update']);

        $role->permissions()->detach($permission[0]['id']);

        $this->startTest();
    }

    public function testUpdatePromotionWithPermission()
    {
        $promotion = $this->fixtures->create('promotion');

        $this->testData[__FUNCTION__]['request']['url'] ='/promotions/' . $promotion->getPublicId();

        $this->startTest();
    }

}

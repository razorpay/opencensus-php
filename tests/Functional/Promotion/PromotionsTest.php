<?php

namespace RZP\Tests\Functional\Promotion;

use RZP\Models\Promotion\Event;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PromotionsTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
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

    public function testCreateBankingPromotion()
    {
        $this->ba->adminAuth('live');

        $response = $this->makeEventForPromotion();

        $this->testData[__FUNCTION__]['request']['content']['event_id']  = $response['id'];

        $this->startTest();

        $promotion = $this->getDbLastEntity('promotion', 'live');

        $this->assertEquals('activated', $promotion['status']);

        $this->assertEquals('reward_fee', $promotion['credit_type']);

        $this->assertNull($promotion['end_at']);
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

    protected function makeEventForPromotion()
    {
        $data = [
            Event\Entity::NAME           => 'sign up',
            Event\Entity::DESCRIPTION    => 'sign up related credits'
        ];

        $request = [
            'content' => $data,
            'url'     => '/promotions/events',
            'method'  => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}

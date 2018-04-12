<?php

namespace RZP\Tests\Functional\Promotion;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;

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
}


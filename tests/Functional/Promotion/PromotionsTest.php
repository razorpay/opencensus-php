<?php

namespace RZP\Tests\Functional\Promotion;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PromotionsTestData.php';

        parent::setUp();

        $this->ba->appAuth();
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

    public function testGetMultiplePromotions()
    {
        $offer = $this->fixtures->create('promotion:onetime');

        $this->startTest();
    }

    public function testFetchPromotionById()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['url'] = '/promotions/' . $promotion->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $promotion->getPublicId();

        $this->startTest();
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


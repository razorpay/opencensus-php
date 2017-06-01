<?php

namespace RZP\Tests\Functional\Coupon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CouponsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CouponsTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testCreateCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->startTest();
    }

    public function testGetCouponsByPromotionId()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->startTest();
    }

    public function testDeleteCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/coupons/' . $coupon->getPublicId();

        $this->startTest();
    }
}

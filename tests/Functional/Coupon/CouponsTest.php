<?php

namespace RZP\Tests\Functional\Coupon;

use Carbon\Carbon;
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

    public function createCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->startTest();
    }

    public function testMissingParams()
    {
        $this->createCoupon();

        $content = [
            'merchant_id' => '10000000000000',
        ];

        $requestData = $this->testData[__FUNCTION__ . 'MissingCode'];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });


        $content = [
            'code' => 'RANDOM-123',
        ];

        $requestData = $this->testData[__FUNCTION__ . 'MissingMerchant'];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    public function testCreateCoupon()
    {
        $this->createCoupon();
    }

    public function testCreateCouponWithInvalidTime()
    {
       $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $tomorrowTimestamp = Carbon::now()->addDays(3)->timestamp;

        $this->testData[__FUNCTION__]['request']['content']['start_at'] = $tomorrowTimestamp;

        $this->testData[__FUNCTION__]['request']['content']['end_at'] = Carbon::tomorrow()->timestamp;

        $this->testData[__FUNCTION__]['response'] = $this->testData[__FUNCTION__ . 'ExceptionData']['response'];

        $this->testData[__FUNCTION__]['exception'] = $this->testData[__FUNCTION__ . 'ExceptionData']['exception'];

        $this->startTest();
    }

    public function testMerchantSignUpWithCoupon()
    {
        $this->createCoupon();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $balanceRequest = [
            'url'    => '/balance',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt');

        $response = $this->makeRequestAndGetContent($balanceRequest);

        $this->assertEquals(0, $response['fee_credits']);
    }

    public function testMerchantSignUpWithCouponAndActivation()
    {
        $this->createCoupon();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'website' => 'abc.com',
            'category' => 1100,
            'billing_label' => 'labore',
            'transaction_report_email' => 'test@razorpay.com',
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttributes);

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, ['submitted' => true]);

        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, ['submitted' => true]);

        $balanceRequest = [
            'url'    => '/balance',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $response = $this->makeRequestAndGetContent($balanceRequest);

        $this->assertEquals(0, $response['fee_credits']);

        $activationRequest = [
            'url' => '/merchants/' . $merchantId .  '/activate',
            'method' => 'post',
        ];

        $this->ba->appAuth();

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $this->fixtures
             ->create(
                'merchant:bank_account',
                ['merchant_id' => $merchantId,
                 'entity_id'   => $merchantId,
                 'type'        => 'merchant']);

        $response = $this->makeRequestAndGetContent($activationRequest);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals('100', $credits['value']);
    }

    public function testMerchantSignUpWithInValidCoupon()
    {
        $this->createCoupon();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Coupon code not found', $response['apply_coupon']['message']);

        $balanceRequest = [
            'url'    => '/balance',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt');

        $response = $this->makeRequestAndGetContent($balanceRequest);

        $this->assertEquals(0, $response['fee_credits']);
    }


    public function testCouponWithUsage()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->testData[__FUNCTION__]['request']['content']['max_count'] = 1;

        $this->startTest();

        $content = [
            'merchant_id' => '10000000000000',
            'code' => 'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);
    }

    public function testCouponExceedingUsage()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->testData[__FUNCTION__]['request']['content']['max_count'] = 1;

        $this->startTest();

        $content = [
            'merchant_id' => '10000000000000',
            'code' => 'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);

        $content = [
            'merchant_id' => '100000Razorpay',
            'code' => 'RANDOM-123',
        ];

        $requestData = $this->testData[__FUNCTION__ . 'ExceptionData'];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    public function testCreateCouponAndApplyOnMerchant()
    {
        $this->createCoupon();

        $content = [
            'merchant_id' => '10000000000000',
            'code' =>  'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);
    }

    public function testMultiCouponApply()
    {
        $this->createCoupon();

        $content = [
            'merchant_id' => '10000000000000',
            'code' =>  'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);

        $requestData = $this->testData[__FUNCTION__ . 'ExceptionData'];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    public function testInvaliCouponApply()
    {
        $this->createCoupon();

        $content = [
            'merchant_id' => '10000000000000',
            'code' =>  'RAND123',
        ];

        $requestData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    public function checkValidResponse(array $response)
    {
        $this->assertEquals($response['message'], 'Coupon Applied Successfully');
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

    public function testDeleteCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/coupons/' . $coupon->getPublicId();

        $this->startTest();
    }

    public function testApplyOnetimeCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->fixtures->merchant->activate('10000000000000');

        $this->startTest();

        $credit = $this->getLastEntity('credits', true);

        $this->assertNull($credit['expired_at']);
    }

    public function testApplyRecurringCoupon()
    {
        $promotion = $this->fixtures->create('promotion:recurring');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->fixtures->merchant->activate('10000000000000');

        $this->startTest();

        //This is to test if the credit created has expired_at
        $credit = $this->getLastEntity('credits', true);

        $this->assertNotNull($credit['expired_at']);
    }

    public function testDeleteUsedCoupon()
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

        $this->testData[__FUNCTION__]['request']['url'] = '/coupons/' . $coupon->getPublicId();

        $this->startTest();
    }

    public function testApplyExpiredCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $yesterdayTimestamp = Carbon::yesterday()->timestamp;

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'end_at'    => $yesterdayTimestamp,
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $content = [
            'merchant_id' => '10000000000000',
            'code' => 'RANDOM',
        ];

        $requestData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    public function testApplyNotApplicableCoupon()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $tomorrowTimestamp = Carbon::tomorrow()->timestamp;

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'start_at'  => $tomorrowTimestamp,
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $content = [
            'merchant_id' => '10000000000000',
            'code' => 'RANDOM',
        ];

        $requestData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $response = $this->applyCouponOnMerchant($content);
            });
    }

    protected function merchantAssignPricingPlan($planId, $id = '10000000000000')
    {
        $request = array(
            'url' => '/merchants/'.$id.'/pricing',
            'method' => 'POST',
            'content' => ['pricing_plan_id' => $planId]);

        return $this->makeRequestAndGetContent($request);
    }
}

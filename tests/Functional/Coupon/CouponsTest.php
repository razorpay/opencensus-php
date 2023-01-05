<?php

namespace RZP\Tests\Functional\Coupon;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Exception;
use RZP\Constants\Product;
use RZP\Models\Coupon\Constants;
use RZP\Models\Schedule\Period;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class CouponsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use OAuthTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/CouponsTestData.php';

        parent::setUp();



        $this->ba->adminAuth();
    }

    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    public function createCoupon(array $attributes = [])
    {
        $promotion = $this->fixtures->create('promotion:onetime', $attributes);

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['entity_type'] = 'promotion';

        $this->startTest();

        return $promotion;
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

    public function testCreateMultipleCouponsPerPromotion()
    {
        $response = $this->createCoupon();

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $response->getPublicId();

        $this->startTest();
    }

    public function helperToCreatePromotionCoupon(int $startDays, int $endDays)
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $this->testData['testCreateCoupon']['request']['content']['entity_id'] = $promotion->getPublicId();

        $this->testData['testCreateCoupon']['request']['content']['entity_type'] = 'promotion';

        $response = $this->makeRequestAndGetContent($this->testData['testCreateCoupon']['request']);

        $start_at = Carbon::now()->addDays($startDays)->timestamp;

        $end_at = Carbon::now()->addDays($endDays)->timestamp;

        return [$response,$start_at,$end_at];
    }

    public function testUpdateCoupon()
    {
        list($response,$start_at,$end_at) = $this->helperToCreatePromotionCoupon(1,3);

        $this->testData[__FUNCTION__]['request']['content']['start_at'] = $start_at;

        $this->testData[__FUNCTION__]['request']['content']['end_at'] = $end_at;

        $this->testData[__FUNCTION__]['request']['url'] = '/coupons/'.$response['id'];

        $this->testData[__FUNCTION__]['request']['method'] = 'PATCH';

        $this->testData[__FUNCTION__]['response']['content']['start_at'] = $start_at;

        $this->testData[__FUNCTION__]['response']['content']['end_at'] = $end_at;

        $this->startTest();
    }

    public function testUpdateCouponWithInvalidTime()
    {
        list($response,$start_at,$end_at) = $this->helperToCreatePromotionCoupon(3,1);

        $this->testData[__FUNCTION__]['request']['content']['start_at'] = $start_at;

        $this->testData[__FUNCTION__]['request']['content']['end_at'] = $end_at;

        $this->testData[__FUNCTION__]['request']['url'] = '/coupons/'.$response['id'];

        $this->testData[__FUNCTION__]['request']['method'] = 'PATCH';

        $this->startTest();
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

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

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

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $balanceRequest = [
            'url'    => '/balance',
            'method' => 'GET',
        ];

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user->getId());

        $response = $this->makeRequestAndGetContent($balanceRequest);

        $this->assertEquals(0, $response['fee_credits']);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $this->fixtures
             ->create(
                'merchant:bank_account',
                ['merchant_id' => $merchantId,
                 'entity_id'   => $merchantId,
                 'type'        => 'merchant']);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

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

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

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
            'code'        => 'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);
    }

    public function testCreateCouponAndApplyOnMerchantandVerifyPricingPlan()
    {
        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $this->createCoupon($promotionAttributes);

        $content = [
            'merchant_id' => '10000000000000',
            'code'        => 'RANDOM-123',
        ];

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);

        $testMerchant = $this->getDbEntityById('merchant', '10000000000000', 'test');

        $this->assertSame('BAJq6FJDNJ4ZqD', $testMerchant->getPricingPlanId());
    }

    public function testCreateCouponAndApplyOnMerchantandVerifyPartner()
    {
        $promotionAttributes = [
            'partner_id' => '10000000000000',
        ];

        $this->fixtures->merchant->markPartner();

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev');

        $merchant = $this->fixtures->create('merchant');

        $this->createCoupon($promotionAttributes);

        $content = [
            'merchant_id' => $merchant['id'],
            'code'        => 'RANDOM-123',
        ];

        $this->createMerchantApplication('10000000000000', 'fully_managed', $client->application_id);

        $response = $this->applyCouponOnMerchant($content);

        $this->checkValidResponse($response);

        $accessMap = $this->getLastEntity('merchant_access_map', true);

        $this->assertNotNull($accessMap);

        $this->assertEquals('10000000000000', $accessMap['entity_owner_id']);

        $this->assertEquals($merchant['id'], $accessMap['merchant_id']);

        $this->assertEquals('application', $accessMap['entity_type']);
    }

    public function testCreateCouponAndApplyOnMerchantInvalidPartner()
    {
        $promotionAttributes = [
            'partner_id' => '10000000000000',
        ];

        $merchant = $this->fixtures->create('merchant');

        $this->createCoupon($promotionAttributes);

        $content = [
            'merchant_id' => $merchant['id'],
            'code'        => 'RANDOM-123',
        ];

        $requestData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $requestData,
            function() use ($content)
            {
                $this->applyCouponOnMerchant($content);
            });

        $accessMap = $this->getLastEntity('merchant_access_map', true);

        $this->assertNull($accessMap);
    }

    public function testValidateCoupon()
    {
        $scheduleAttributes = [
            'period' => Period::DAILY,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promoAttributes = [
            'schedule_id' => $schedule->getId(),
        ];

        $this->createCoupon($promoAttributes);

        $content = [
            'code' => 'RANDOM-123',
        ];

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId', 'agent');

        $response = $this->checkCouponOnMerchant($content);

        $this->assertEquals($response['credit_amount'], 100);

        $this->assertEquals($response['expire_days'],1);
    }

    public function testValidateBankingCouponInX()
    {
        $scheduleAttributes = [
            'period' => Period::DAILY,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promoAttributes = [
            'schedule_id' => $schedule->getId(),
            'product'     => Product::BANKING
        ];

        $this->createCoupon($promoAttributes);

        $content = [
            'code' => 'RANDOM-123',
        ];

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $response = $this->checkCouponOnMerchant($content, Product::BANKING);

        $this->assertEquals($response['credit_amount'], 100);

        $this->assertEquals($response['expire_days'],1);
    }

    public function testValidatePrimaryCouponInX()
    {
        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_COUPON_CODE);

        $scheduleAttributes = [
            'period' => Period::DAILY,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promoAttributes = [
            'schedule_id' => $schedule->getId(),
            'product'     => Product::PRIMARY
        ];

        $this->createCoupon($promoAttributes);

        $content = [
            'code' => 'RANDOM-123',
        ];

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->checkCouponOnMerchant($content, Product::BANKING);
    }

    public function testValidateCouponProxyAuthWithMerchantId()
    {
        $scheduleAttributes = [
            'period' => Period::DAILY,
        ];

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $promoAttributes = [
            'schedule_id'       => $schedule->getId(),
        ];

        $this->createCoupon($promoAttributes);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId', 'agent');

        $this->startTest();
    }

    public function testValidateCouponWithoutSchedule()
    {
        $this->createCoupon();

        $content = [
            'code'        => 'RANDOM-123',
        ];

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId', 'agent');

        $response = $this->checkCouponOnMerchant($content);

        $this->assertEquals($response['credit_amount'], 100);

        $this->assertEquals($response['expire_days'],null);
    }

    public function testMultiCouponApply()
    {
        $this->createCoupon();

        $content = [
            'merchant_id' => '10000000000000',
            'code'        => 'RANDOM-123',
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
            'code'        => 'RAND123',
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

    public function couponAlert(array $payload)
    {
       $this->ba->cronAuth();

        $request = [
            'url'     => '/coupons/alert',
            'method'  => 'post',
            'content' => $payload
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }


    public function applyMtuCouponOnMerchant()
    {
        $request = [
            'url'     => '/coupons/apply/mtu',
            'method'  => 'post',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function checkCouponOnMerchant(array $content, string $product = Product::PRIMARY)
    {
        $request = [
            'url'     => '/coupons/validate',
            'method'  => 'post',
            'content' => $content
        ];

        if ($product === Product::BANKING)
        {
            $request['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
        }

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

        $this->assertNotNull($credit['expired_at']);

        $created = Carbon::parse($credit['created_at']);
        $expiry = Carbon::parse($credit['expired_at']);

        $diffInDays = $expiry->diffInDays($created);

        $this->assertEquals(92, $diffInDays);
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

    public function testCouponExpiryAlert()
    {
        $promotion = $this->fixtures->create('promotion:onetime');

        $tomorrowTimestamp = Carbon::tomorrow()->timestamp;

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'end_at'    => $tomorrowTimestamp,
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $payload = [
            "emails" =>["himanshu.gangwar@razorpay.com","karkala.vasanthi@razorpay.com"],
            "days" => [1]
        ];

        $requestData = $this->testData[__FUNCTION__];
        $response = $this->couponAlert($payload);

        $this->assertEquals($requestData['response']['content'], $response);


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

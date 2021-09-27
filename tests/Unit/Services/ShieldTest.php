<?php

namespace RZP\Tests\Unit\Services;

use ReflectionClass;
use RZP\Services\Shield;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\RecurringType;
use RZP\Constants\Shield as ShieldConstants;

class ShieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.shield.mock', true);
    }

    public function testCreateRule()
    {
        $input = [
            'expression' => 'amount > 10000'
        ];

        $result = $this->app['shield']->createRule($input);

        $this->assertSame([
            'id'         => '12345678',
            'expression' => 'amount > 10000',
            'is_active'  => true,
            'created_at' => 1518608813,
            'updated_at' => 1518608813
        ], $result);
    }

    public function testPopulatePaymentDetailsForRecurring()
    {
        $class = new ReflectionClass(Shield::class);
        $populatePaymentDetailsMethod = $class->getMethod('populatePaymentDetails');
        $populatePaymentDetailsMethod->setAccessible(true);

        $token = $this->fixtures->create('customer:emandate_token', [
            'max_amount'     => 10000
        ]);

        $payment = $this->fixtures->create('payment:captured', [
            'token_id'       => $token->getId(),
            'recurring'      => true,
            'recurring_type' => RecurringType::AUTO,
        ]);

        $input = [];

        $populatePaymentDetailsMethod->invokeArgs(new Shield($this->app), [$payment, &$input]);

        $this->assertEquals(RecurringType::AUTO, $input[ShieldConstants::RECURRING_TYPE]);

        $this->assertEquals($token->getId(), $input[ShieldConstants::TOKEN_ID]);

        $this->assertEquals($token->getMaxAmount(), $input[ShieldConstants::TOKEN_MAX_AMOUNT]);
    }

    private function createCommonDataForBusinessDetail()
    {
        $merchant = $this->fixtures->create('merchant', [
            'risk_threshold' => 101
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
        ]);

        return $merchant;
    }

    private function setMethod($methodName)
    {
        $class = new ReflectionClass(Shield::class);

        $populateMerchantDetailsMethod = $class->getMethod($methodName);

        $populateMerchantDetailsMethod->setAccessible(true);

        return $populateMerchantDetailsMethod;
    }

    public function testMerchantBusinessDetailNotPresent()
    {
        $populateMerchantDetailsMethod = $this->setMethod('populateMerchantDetails');

        $merchant = $this->createCommonDataForBusinessDetail();

        $payloadDetails = [];

        $populateMerchantDetailsMethod->invokeArgs(new Shield($this->app), [$merchant, &$payloadDetails]);

        $this->assertFalse(array_key_exists(ShieldConstants::MERCHANT_WHITELISTED_APP_URLS, $payloadDetails));
    }

    public function testMerchantBusinessDetailPresentWithoutAppUrl()
    {
        $populateMerchantDetailsMethod = $this->setMethod('populateMerchantDetails');

        $merchant = $this->createCommonDataForBusinessDetail();

        $payloadDetails = [];

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId()
        ]);

        $populateMerchantDetailsMethod->invokeArgs(new Shield($this->app), [$merchant, &$payloadDetails]);

        $this->assertNull($payloadDetails[ShieldConstants::MERCHANT_WHITELISTED_APP_URLS]);
    }

    public function testMerchantBusinessDetailPresentWithAppUrl()
    {
        $populateMerchantDetailsMethod = $this->setMethod('populateMerchantDetails');

        $merchant = $this->createCommonDataForBusinessDetail();

        $payloadDetails = [];

        $appUrls = [
            'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
        ];

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => $appUrls
        ]);

        $populateMerchantDetailsMethod->invokeArgs(new Shield($this->app), [$merchant, &$payloadDetails]);

        $this->assertEquals($appUrls, $payloadDetails[ShieldConstants::MERCHANT_WHITELISTED_APP_URLS]);
    }
}

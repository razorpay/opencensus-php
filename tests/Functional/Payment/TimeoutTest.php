<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentVerifyTrait;

class TimeoutTest extends TestCase
{
    use PaymentTrait;
    use MocksRedisTrait;
    use DbEntityFetchTrait;
    use PaymentVerifyTrait;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TimeoutTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testTimeoutInvalidId()
    {
        $this->ba->pgRouterAuth();

        $this->startTest();
    }

    public function testTimeoutPaymentStatusNotCreated()
    {
        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'status'        => 'captured',
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $payment['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->pgRouterAuth();

        $this->startTest();
    }

    public function testTimeoutPaymentShouldNotTimeout()
    {
        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'status'        => 'created',
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $payment['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->pgRouterAuth();

        $this->startTest();
    }

    public function testTimeoutPaymentOldPayment()
    {
        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'status'        => 'created',
            'created_at'    => Carbon::now()->subMinutes(15)->getTimestamp()
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $payment['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->pgRouterAuth();

        $this->startTest();
    }
}

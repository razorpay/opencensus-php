<?php

namespace Tests\Functional\HdfcGateway;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase payments are used, also tests if payments are automatically
 * captured on successful payments. Hold Payments are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayAuthTest extends TestCase
{
    use PaymentTrait;

    protected $successDebitNumbers = array(
        '4012001037141112',
        '4005559876540',
        '4012001037167778',
        '4012001037490014',
        '4012001037141112');

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/cards.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testCardTimeout()
    {
        $this->startTest();
    }

    public function testCreditCardSuccess()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012001038443335';

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testCreditCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    public function testCreditCardAuthNotAvailable2()
    {
        $this->startTest();
    }

    public function testSignatureFailure1()
    {
        $this->startTest();
    }

    public function testSignatureFailure2()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess()
    {
        $payment = $this->getDefaultPaymentArray();

        foreach ($this->successDebitNumbers as $number)
        {
            $payment['card']['number'] = '4012001037141112';
            $this->doAuthAndGetPayment($payment);
        }
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures
             ->on('live')
             ->create('terminal', ['merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testJsonpPaymentReturnFields()
    {
        $fields = array(
            'request',
            'version',
            'payment_id',
            'gateway',
            'http_status_code');

        $dataFields = array(
            'TermUrl',
            'MD',
            'PaReq');

        $content = $this->startTest();

        $this->assertEquals($fields, array_keys($content));
        $this->assertEquals($dataFields, array_keys($content['request']['content']));
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }
}

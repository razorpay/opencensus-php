<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase payments are used, also tests if payments are automatically
 * captured on successful payments. Hold Payments are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayAuthTest extends TestCase
{
    use PaymentTrait;

    protected $successDebitNumbers = array(
        '4005559876540',
        '4012001037167778',
        '4012001037490014',
    );

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/cards.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->enableInternational();
    }

    public function testCardTimeout()
    {
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        // No Entry will be created for Failed Enroll Request
        $this->assertEquals($hdfc, null);
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
            $payment['card']['number'] = $number;
            $this->doAuthAndGetPayment($payment);
        }
    }

    public function testMaestroCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';
        $this->doAuthAndCapturePayment($payment);
        $paymentRes = $this->getLastPayment(true);
        $this->assertEquals($paymentRes['gateway'], 'hdfc');

        $payment['card']['number'] = '5049730032100035510';
        $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastPayment(true);
        $this->assertEquals($payment['gateway'], 'hdfc');
    }

    public function testTerminalRotator()
    {
        // fail the payment with a card that throws timeout and
        // succeed wih another terminal and assert so.
        $this->fixtures->create('terminal:shared_axis_terminal');

        $defaultPayment = $this->getDefaultPaymentArray();

        $defaultPayment['card']['number'] = '4012001036275556';

        $payment = array();

        $payment = array_merge($defaultPayment, $payment);

        $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals($payment['gateway'], 'axis_migs');
    }

    public function testCreditCardAuthNotApproved()
    {
        $this->startTest();
    }

    public function testDebitCardAuthNotApproved()
    {
        $this->startTest();
    }

    public function testCaptureTimeout()
    {
        $this->defaultAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->captureErrorReturnGatewayTimeout();

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals($payment['status'], 'captured');

        $hdfc = $this->getEntities('hdfc', [], true);

        $this->assertEquals($hdfc['items'][0]['status'], 'captured');

        $this->assertEquals($hdfc['items'][1]['status'], 'capture_failed');
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        // $this->fixtures->merchant->activate();

        $this->fixtures
             ->on('live')
             ->create('terminal', ['merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testJsonpPaymentReturnFields()
    {
        $fields = array(
            'type',
            'request',
            'version',
            'payment_id',
            'gateway',
            'amount',
            'image',
            'http_status_code');

        $dataFields = array(
            'TermUrl',
            'MD',
            'PaReq');

        $content = $this->startTest();

        $this->assertEquals($fields, array_keys($content));
        $this->assertEquals($dataFields, array_keys($content['request']['content']));
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }
}

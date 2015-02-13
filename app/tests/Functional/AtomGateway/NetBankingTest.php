<?php

namespace Tests\Functional\AtomGateway;

use Carbon\Carbon;
use Config;
use Mockery;
use Requests;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\TestCase;

class NetBankingTest extends TestCase
{
    use PaymentTrait;

    /**
     * Whether atom gateway is mocked or not
     * @var boolean
     */
    protected $mock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/netbanking.php';

        parent::setUp();

        $this->fixtures->create('terminal:atom_terminal');

        $this->payment = array(
            'method' => 'netbanking',
            'bank' => 'SBIN',
            'amount' => '5000',
            'email' => 'ab@g.com',
            'contact' => '9431495816',
            'currency' => 'INR');

        $this->gateway = 'atom';
    }

    public function testNetBankingPaymentAuthorize()
    {
        $this->ba->publicAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testNetBankingPaymentCapture()
    {
        $content = $this->doAtomPaymentAuthorize();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testNetBankingPaymentRefund()
    {
        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment:netbanking_captured');
        $id = $payment->getPublicId();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/refund';

        $refund = $this->runRequestResponseFlow($testData);
    }

    public function testNBPaymentFailureAtBank()
    {
        $this->ba->publicAuth();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('atom', $payment['gateway']);
    }

    public function testCardPayment()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $payment = &$this->payment;

        unset($this->payment['bank']);
        $cardData = [
            'number' => '4111111111111111',
            'cvv' => '500',
            'expiry_month' => '05',
            'expiry_year' => '20', 'name' => 'shk'];

        $payment['card'] = $cardData;
        $payment['method'] = 'card';

        $content = $this->doAtomPaymentAuthorize();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_atom', true);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures
            ->on('live')
            ->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        $this->currentTestData = $testData;

        return $this->runRequestResponseFlow($testData);
    }

    protected function doAtomPaymentAuthorize()
    {
        $request = array(
            'content' => $this->payment);

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
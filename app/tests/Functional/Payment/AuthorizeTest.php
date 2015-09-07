<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class AuthorizeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testInvalidEmailInPayment()
    {
        $this->startTest();
    }

    public function testJsonpPayment()
    {
        $content = $this->startTest();
        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testEmailMissing()
    {
        unset($this->payment['email']);
        $this->startTest();
    }

    public function testContactTooShort()
    {
        $this->startTest();
    }

    public function testContactTooLong()
    {
        $this->startTest();
    }

    public function testNonInrCurrency()
    {
        $this->startTest();
    }

    public function testCardMissing()
    {
        unset($this->payment['card']);

        $this->startTest();
    }

    public function testPaymentCardAsString()
    {
        $this->startTest();
    }

    public function testAmountBelowMin()
    {
        $this->startTest();
    }

    public function testAmountVeryHigh()
    {
        $this->startTest();
    }

    public function testAuthorizeTimestamp()
    {
        $lower = time()-1;
        $this->defaultAuthPayment();
        $upper = time()+1;

        $payment = $this->getLastEntity('payment', true);

        $authorizedAt = $payment['authorized_at'];

        $this->assertLessThanOrEqual($authorizedAt, $lower);

        $this->assertGreaterThanOrEqual($authorizedAt, $upper);
    }

    public function testAmountLessThan50ForNetbanking()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function testAmountNonNumeric()
    {
        $this->startTest();
    }

    public function testAmountMissing()
    {
        $this->startTest();
    }

    public function testDescriptionAsArray()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['description']['key'] = 'value';

        $this->startTest();
    }

    public function testDescriptionTooLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeText = implode(',', range(1,1000,1));

        $testData['request']['content']['description'] = $largeText;

        $this->startTest();
    }

    public function testNotesStringNotArray()
    {
        $this->startTest();
    }

    public function testExcessValuesInNotes()
    {
        $testData = & $this->testData[__FUNCTION__];

        foreach (range(1, 16, 1) as $i)
        {
            $testData['request']['content']['notes'][$i] = 'value';
        }

        $this->startTest();
    }

    public function testArrayInNotesValue()
    {
        $this->startTest();
    }

    public function testArrayInNotesKey()
    {
        $this->startTest();
    }

    public function testNotesKeyLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeKey = implode(',', range(1,100,1));
        $testData['request']['content']['notes'][$largeKey] = 'value';

        $this->startTest();
    }

    public function testNotesValueLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeValue = implode(',', range(1,100,1));
        $testData['request']['content']['notes']['key'] = $largeValue;

        $this->startTest();
    }

    public function testTimeoutOldPayment()
    {
        $payment = $this->fixtures->create('payment:status_created', ['created_at' => time() - 60*100]);

        $this->ba->appAuth();

        $request = array('url' => '/payments/timeout');
        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$payment['public_id'];

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testFailTimeoutOldPayments()
    {
        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 60*100, 'status' => 'authorized', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->ba->appAuth();

        $request = array('url' => '/payments/timeout');
        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 0);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$payment['public_id'];

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCancelPayment()
    {
        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 60*100, 'status' => 'created', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->cancelPayment($payment->getPublicId());

        $contentType = 'application/json';
        $this->assertContentTypeForResponse($contentType, $this->response);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->markTestIncomplete();

        $payment = $this->fixtures->create(
            'payment:failed');

        $this->authorizeFailedPayment($payment['public_id']);
    }

    public function testContentTypeHtmlOnPaymentCreateRoute()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        $contentType = 'text/html; charset=UTF-8';
        $this->assertContentTypeForResponse($contentType, $this->response);
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        return $this->runRequestResponseFlow($testData);
    }
}
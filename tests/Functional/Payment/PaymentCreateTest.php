<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;

class PaymentCreateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreatePaymentWithInvalidMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'invalid';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithoutCardNumber()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['card']['number']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithoutContact()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['contact']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecure()
    {
        $this->payment['card']['number'] = '555555555555558';

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecureError()
    {
        $this->payment['card']['number'] = '555555555555559';

        $this->changeEnvToNonTest();

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertEquals($content['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_VALIDATION_FAILURE');
    }

    public function testPaymentWithUnderscoreArray()
    {
        $this->payment['_'] = array('1' => 2, '3' => 4);

        $content = $this->doAuthPayment($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCallbackOnAuthorizedPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->doAuthPayment();
        $id = $payment['razorpay_payment_id'];
    }

    public function testWalletPostFormViaPaymentCreate()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getWalletFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testInternationalPayment()
    {
        $this->fixtures->merchant->enableInternational();
        $this->payment['card']['number'] = '4012010000000007';
        $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testIntlPaymentWhenNotAllowed()
    {
        $this->fixtures->merchant->disableInternational();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED');
    }

    public function testCreatePaymentInEs()
    {
        $esMock = $this->createEsMock(['bulkUpdate']);

        $expected = $this->testData[__FUNCTION__];

        // Ref to InvoiceTest.testCreateInvoiceAndAssertEsSync() test on why
        // this is being asserted differently.

        $expectedNotes = [
            'merchant_order_id' => 'random order id',
        ];

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected, $expectedNotes)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                            $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                            $this->assertNotEmpty($actual['body'][1]['id']);

                            return true;
                        }));

        $this->doAuthPaymentViaCheckoutRoute($this->payment);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForSuccess()
    {
        $payment = $this->doAuthPayment();

        $callbackUrl = $this->recorder->callbackUrl;

        $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($payment, $content);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForError()
    {
        // This will have raised an insufficient balance error.
        $this->makeRequestAndCatchException(function()
        {
            $this->payment['card']['number'] = '5010101010101015';
            $content = $this->doAuthPayment($this->payment);
            $this->doAuthPayment();
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $callbackUrl = $this->recorder->callbackUrl;

            $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);
        });
    }

    public function testPaymentS2SOnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->config['app.throw_exception_in_testing'] = false;

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['cvv'] = '1';

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $error = $response['error'];
        $this->assertEquals($error['field'], 'cvv');
        $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($error['description'], 'The cvv must be between 3 and 4 digits.');
    }

    public function testNotEnrolledCardPaymentS2SOnPrivateAuth()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testPaymentWithEmptyAcquirerData()
    {
        $paymentData = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($paymentData);

        $request = [
            'method'    => 'GET',
            'url'       => '/payments/' . $payment['razorpay_payment_id']
        ];

        $this->ba->privateAuth();

        // Get raw response
        $response = $this->sendRequest($request)->getContent();

        $this->assertRegexp('/' . preg_quote('"acquirer_data":{}') . '/', $response);
    }

    public function testPaymentWithAcquirerData()
    {
        $paymentData = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthPayment($paymentData);

        $payment = $this->getLastEntity('payment');

        $this->assertArrayHasKey('acquirer_data', $payment);

        $this->assertArrayHasKey('bank_transaction_id', $payment['acquirer_data']);
    }
}

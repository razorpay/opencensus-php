<?php

namespace Functional\Gateway\PayLater;

use RZP\Tests\Functional\Helpers;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PayLaterGatewayTest extends TestCase
{
    use PaymentTrait;
    use Helpers\DbEntityFetchTrait;

    protected $provider = 'epaylater';

    protected $method = 'paylater';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PayLaterGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'paylater';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_epaylater_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');
    }

//    skipping this testcase as epaylater is discontinued

//    public function testSubMerchantPreferences()
//    {
//        $this->createSubMerchant();
//
//        $preferences = $this->getPreferences();
//
//        $acquirer = $this->sharedTerminal->getGatewayAcquirer();
//
//        $this->assertArraySelectiveEquals([$acquirer => true], $preferences['methods']['paylater']);
//
//        $this->resetPublicAuthToTestAccount();
//    }

    public function testPaymentAndRefund()
    {
        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $payment['contact'] = '+91' . '8602579721';

        $this->setOtp('123456');

        // authorize
        $response = $this->doAuthPayment($payment);

        $this->assertNotNull($response['razorpay_payment_id']);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');

        // capture
        $this->capturePayment($response['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCaptureEntity');

        //refund
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['status']       = 'Success';
            }

            return $content;
        });

        $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertNotNull($cardlessEmiEntity['refund_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentRefundEntity');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('1234567', $refund['acquirer_data']['arn']);
    }

    public function testPaymentForSubMerchant()
    {
        $subMerchant = $this->createSubMerchant();

        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $payment['contact'] = '+91' . '8602579721';

        $this->setOtp('123456');

        // authorize
        $response = $this->doAuthPayment($payment);

        $this->resetPublicAuthToTestAccount();

        $this->assertNotNull($response['razorpay_payment_id']);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');

        $key = $this->getDbEntity('key', ['merchant_id' => $subMerchant]);

        // capture
        $this->capturePaymentWithKey($response['razorpay_payment_id'], $payment['amount'], 'rzp_test_' . $key->getKey());

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $this->assertEquals($subMerchant, $payment['merchant_id']);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertNotNull($cardlessEmiEntity['gateway_reference_id']);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCaptureEntity');
    }

    public function testAccountDoesNotExist()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'check_account')
            {
                $content = [
                    'errorCode'         => 'USER_DNE',
                    'errorDescription'  => 'Customer with given mobile number %s does not exists.',
                ];
            }

            return $content;
        });

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchTokenFailed()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'fetch_token')
            {
                $content = [
                    'errorCode'         => 'FETCH_TOKEN_FAILED',
                    'errorDescription'  => 'Customer with given mobile number %s does not exists.',
                ];
            }

            return $content;
        });

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFailedPayment()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['status'] = 'failed';
            }

            return $content;
        });

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFailedCapture()
    {
        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                $content['errorCode'] = 'CAPTURE_FAILED';
                $content['errorDescription'] = 'Payment capture failed';
                unset($content['entity']);
                unset($content['rzp_payment_id']);
                unset($content['provider_payment_id']);
                unset($content['status']);
                unset($content['currency']);
                unset($content['amount']);
            }
        });

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payments/' . '%s' . '/capture', $response['razorpay_payment_id']);

        $this->startTest();
    }

    public function testRefundFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'refund')
            {
                $content['status']     = 'failed';
                $content['error_code'] = 'REFUND_FAILED';
                $content['error_description'] = 'Refund failed';
            }
        });

        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $this->capturePayment($response['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

        $gatewayRefund = $this->getLastEntity('cardless_emi',true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals('REFUND_FAILED', $gatewayRefund['error_code']);
        $this->assertEquals('Refund failed', $gatewayRefund['error_description']);
    }

    public function testPaymentVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['errorCode'] = 'payment_verify_failed';
                $content['errorDescription'] = 'Payment verification failed';
                unset($content['status']);
            }
        });

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

                $authPayment = $this->doAuthPayment($payment);

                $this->verifyPayment($authPayment['razorpay_payment_id']);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 0);
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testInvalidOtt()
    {
        $this->runRequestResponseFlow($this->testData[__FUNCTION__],
            function()
            {
                $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

                $payment['email'] = 'invalidott@gmail.com';

                $this->setOtp('123456');

                $this->ba->publicAuth();

                $this->doAuthPayment($payment);
            });
    }

    protected function createSubMerchant()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $subMerchantId]);

        $this->fixtures->merchant->enablePayLater($subMerchantId);

        $this->fixtures->create('balance',
            [
                'merchant_id' => $subMerchantId,
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->ba->getAdmin()->merchants()->attach($subMerchantId);

        $this->assignSubMerchant($this->sharedTerminal->getId(), $subMerchantId);

        $this->setSubMerchantPublicAuth($subMerchantId);

        return $subMerchantId;
    }

    protected function assignSubMerchant(string $tid, string $mid)
    {
        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url'    => $url,
            'method' => 'PUT',
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);
    }

    protected function setSubMerchantPublicAuth($merchantId)
    {
        $key = $this->fixtures->create('key', ['merchant_id' => $merchantId]);

        $key = $key->getKey();

        $this->ba->publicAuth('rzp_test_' . $key);
    }

    protected function resetPublicAuthToTestAccount()
    {
        $this->ba->publicAuth('rzp_test_' . 'TheTestAuthKey');
    }

    protected function getPreferences()
    {
        $response = $this->makeRequestAndGetContent([
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ]);

        return $response;
    }

   // This is required as private auth has to be set with a non-default key for successful capture request
    protected function capturePaymentWithKey($id, $amount, $key, $currency = 'INR', $verifyAmount = 0, $status = 'captured')
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount));

        if ($currency !== 'INR')
        {
            $request['content']['currency'] = $currency;
        }

        $this->ba->privateAuth($key);
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        if ($verifyAmount !== 0)
        {
            $this->assertEquals($content['amount'], $verifyAmount);
        }
        else
        {
            $this->assertEquals($content['amount'], $amount);
        }

        $this->assertEquals($content['status'], $status);

        return $content;
    }
}

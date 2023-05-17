<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi;

use RZP\Gateway\CardlessEmi;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardlessEmiGatewayTest extends TestCase
{
    use PaymentTrait;
    use Helpers\DbEntityFetchTrait;

    protected $provider = 'earlysalary';

    protected $method = 'cardless_emi';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/CardlessEmiGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'cardless_emi';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');
    }

    public function testCheckAccount()
    {
        $data = $this->getCheckAccountArray($this->provider);

        $contact = '+919918899029';

        $this->checkAccount($data);

        $emiPlans = $this->app['cache']->get(sprintf('gateway:emi_plans_EARLYSALARY_%s',
            $contact . '_10000000000000'), 0);

        $loanUrl = $this->app['cache']->get(sprintf('gateway:loan_url_EARLYSALARY_%s',
            $contact . '_10000000000000'), 0);

        $this->assertTestResponse($emiPlans, 'testEmiPlans');

        $this->assertEquals('link_to_loan_agreement', $loanUrl);
    }

    public function testCheckAccountUserDne()
    {
        $response = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'check_account')
            {
                unset($content['account_exists']);
                unset($content['emi_plans']);
                unset($content['loan_agreement']);
                $content['error_code'] = 'USER_DNE';
            }
        });

        $this->runRequestResponseFlow($response,
            function()
            {
                $data = $this->getCheckAccountArray($this->provider);

                $this->checkAccount($data);
            });
    }

    public function testFetchTokenFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'fetch_token')
            {
                unset($content['token']);
                unset($content['expiry']);
                $content['error_code'] = 'FETCH_TOKEN_FAILED';
                $content['error_description'] = 'Fetch token failed';
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
                $payment['contact'] = '+91' . $payment['contact'];
                $this->doAuthPayment($payment);
            });
    }

    public function testSubMerchantPreferences()
    {
        $this->createSubMerchant();

        $preferences = $this->getPreferences();

        $acquirer = $this->sharedTerminal->getGatewayAcquirer();

        $this->assertArraySelectiveEquals([$acquirer => true], $preferences['methods']['cardless_emi']);

        $this->resetPublicAuthToTestAccount();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $this->assertNotNull($response['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');
    }

    public function testPaymentForSubMerchant()
    {
        $this->createSubMerchant();

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $this->assertNotNull($response['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');

        $this->resetPublicAuthToTestAccount();
    }

    public function testFailedPayment()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['status'] = 'failed';
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

                $payment['contact'] = '+91' . $payment['contact'];

                $this->doAuthPayment($payment);
            });
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['error_code'] = 'payment_verify_failed';
                $content['error_description'] = 'Payment verification failed';
                unset($content['status']);
            }
        });

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

                $payment['contact'] = '+91' . $payment['contact'];

                $authPayment = $this->doAuthPayment($payment);

                $this->verifyPayment($authPayment['razorpay_payment_id']);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 0);
    }

    public function testCapturePayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals('captured', $capturedPayment['status']);

        $cardlessEmi = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmi, 'testPaymentCaptureEntity');

        $paymentId = explode('_', $capturedPayment['id'])[1];

        $this->assertEquals($paymentId, $cardlessEmi[CardlessEmi\Entity::PAYMENT_ID]);
    }

    public function testCapturePaymentFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                $content['error_code'] = 'CAPTURE_FAILED';
                $content['error_description'] = 'Payment capture failed';
                unset($content['entity']);
                unset($content['rzp_payment_id']);
                unset($content['provider_payment_id']);
                unset($content['status']);
                unset($content['currency']);
                unset($content['amount']);
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
                $payment['contact'] = '+91' . $payment['contact'];

                $this->doAuthPayment($payment);

                $payment = $this->getLastEntity('payment', true);

                $this->capturePayment($payment['public_id'], $payment['amount']);
            });
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);

        $this->assertEquals('1234567', $refund['acquirer_data']['arn']);

        $this->assertEquals('rfnd_' . $gatewayRefund['refund_id'], $refund['id']);
    }

    public function testReversePayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->doAuthPayment($payment);

        $this->fixtures->merchant->addFeatures('void_refunds','10000000000000');

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);

        $this->assertEquals('rfnd_' . $gatewayRefund['refund_id'], $refund['id']);
    }

    public function testRefundFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'failed';
                $content['error_code'] = 'REFUND_FAILED';
                $content['error_description'] = 'Refund failed';
            }
        });

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals('REFUND_FAILED', $gatewayRefund['error_code']);
        $this->assertEquals('Refund failed', $gatewayRefund['error_description']);
    }

    public function testForceAuthorizePayment()
    {
        $data = $this->testData['testFailedPayment'];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['status'] = 'failed';
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
                $payment['contact'] = '+91' . $payment['contact'];

                $this->doAuthPayment($payment);
            });

        $payment = $this->getLastEntity('payment', true);

        $content = $this->forceAuthorizeFailedPayment($payment['id'], ['provider_payment_id' => 290]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $gatewayPayment = $this->getLastEntity('cardless_emi', true);

        $this->assertEquals($gatewayPayment['gateway_reference_id'], 290);
    }

    public function getCheckAccountArray($provider)
    {
        $array = [
            'provider'  => $provider,
            'amount'    => 100.00
        ];

        return $array;
    }

    public function checkAccount($data, $key = null)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/customers/status/9918899029',
            'content' => $data
        ];

        $this->ba->publicAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
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

    protected function createSubMerchant()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $subMerchantId]);

        $this->fixtures->merchant->enableCardlessEmiProviders(['earlysalary' => 1] , $subMerchantId);

        $this->fixtures->merchant->enableCardlessEmi($subMerchantId);

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

    protected function createSubMerchantForFlexmoney()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $subMerchantId]);

        $this->fixtures->merchant->enableCardlessEmiProviders(['hdfc' => 1 , 'kkbk' => 1, 'idfb' => 1,'icic' => 1 , 'barb' => 1, 'cshe' => 1, 'tvsc' => 1, 'krbe' => 1] , $subMerchantId);

        $this->fixtures->merchant->enableCardlessEmi($subMerchantId);

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
}

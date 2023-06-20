<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi;

use RZP\Gateway\CardlessEmi;

class FlexMoneyGatewayTest extends CardlessEmiGatewayTest
{
    protected $provider = 'flexmoney';

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:cardlessEmiFlexMoneyTerminal');

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');
    }

    public function testFetchTokenFailed()
    {
        // Fetch token is not required for FlexMoney, so overriding the base function.
        $this->assertEquals('1', '1');
    }

    public function testCustomCheckoutPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        unset($payment['emi_duration']);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentFlexMoney');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');
    }

    public function testPaymentSubProvider()
    {
        $this->provider = 'kkbk';

        $this->sharedTerminal = $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        unset($payment['emi_duration']);

        $payment['contact'] = '+91' . $payment['contact'];

        $response = $this->doAuthPayment($payment);

        $paymentAnalytics = $this->getLastEntity('payment_analytics', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'],'pay_'.$paymentAnalytics['payment_id']);

        $this->assertTestResponse($payment, 'testPaymentFlexMoneySubprovider');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentFlexMoney');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');
    }

    public function testPaymentForSubMerchant()
    {
        $this->createSubMerchant();

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentFlexMoneyForSubMerchant');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');

        $this->resetPublicAuthToTestAccount();
    }

    public function testCheckAccount()
    {
        $data = $this->getCheckAccountArray($this->provider);

        $contact = '+919918899029';

        $this->checkAccount($data);

        $emiPlans = $this->app['cache']->get(sprintf('gateway:emi_plans_%s',
            strtoupper($this->provider) . '_' . $contact . '_10000000000000'), 0);

        $redirectUrl = $this->app['cache']->get(sprintf('gateway:redirect_url_%s',
            strtoupper($this->provider) . '_' . $contact . '_10000000000000'), 0);

        $this->assertTestResponse($emiPlans, 'testEmiPlans');

        $this->assertEquals('dummy_redirect_url', $redirectUrl);
    }

    public function testFailedPayment()
    {
        $data = $this->testData['testFlexmoneyFailedPayment'];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['status'] = 'failed';
                $content['error_code'] = 'USER_DNES';
                $content['error_description'] = 'User does not exist';

                unset($content['checksum']);

                $checksum = $this->getMockServer()->generateCheckSum($content);

                $content['checksum'] = $checksum;
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray('flexmoney');

                $this->checkAccount($payment);

                $this->doAuthPayment($payment);
            });
    }

    public function testForceAuthorizePayment()
    {
        $data = $this->testData['testFlexmoneyFailedPayment'];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['status'] = 'failed';
                $content['error_code'] = 'USER_DNES';
                $content['error_description'] = 'User does not exist';

                unset($content['checksum']);

                $checksum = $this->getMockServer()->generateCheckSum($content);

                $content['checksum'] = $checksum;
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $payment = $this->getDefaultCardlessEmiPaymentArray('flexmoney');

                $this->checkAccount($payment);

                $this->doAuthPayment($payment);
            });

        $payment = $this->getLastEntity('payment', true);

        $content = $this->forceAuthorizeFailedPayment($payment['id'], ['provider_payment_id' => 290]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $gatewayPayment = $this->getLastEntity('cardless_emi', true);

        $this->assertEquals($gatewayPayment['gateway_reference_id'], 290);
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

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
                $payment = $this->getDefaultCardlessEmiPaymentArray('flexmoney');

                $payment['contact'] = '+91' . $payment['contact'];

                $this->checkAccount($payment);

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

        $this->checkAccount($payment);

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

                $this->checkAccount($payment);

                $this->doAuthPayment($payment);

                $payment = $this->getLastEntity('payment', true);

                $this->capturePayment($payment['public_id'], $payment['amount']);
            });
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        //$this->assertTestResponse($gatewayRefund);
        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);
    }

    public function testReversePayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $input = ['amount' => $payment['amount']];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'verify_refund')
            {
                $content['status']     = 'failed';
            }
        });

        $this->refundAuthorizedPayment($paymentId, $input);

        $gatewayEntity = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($paymentId, $refund['payment_id']);

        $this->assertEquals('refund', $gatewayEntity['action']);
    }

    public function testReverseFailed()
    {
        $data = $this->testData['testRefundFailed'];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if (in_array($action, ['refund','verify_refund'], true) === true)
            {
                $content['status']     = 'failed';
                $content['error_code'] = 'REFUND_FAILED';
                $content['error_description'] = 'Refund failed';
            }
        });

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundAuthorizedPayment($payment['id']);

        $gatewayEntity = $this->getLastEntity('cardless_emi',true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals('REFUND_FAILED', $gatewayEntity['error_code']);
        $this->assertEquals('Refund failed', $gatewayEntity['error_description']);
    }

    public function testRefundFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if (in_array($action, ['refund','verify_refund'], true) === true)
            {
                $content['status']     = 'failed';
                $content['error_code'] = 'REFUND_FAILED';
                $content['error_description'] = 'Refund failed';
            }
        });

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi',true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals('REFUND_FAILED', $gatewayRefund['error_code']);
        $this->assertEquals('Refund failed', $gatewayRefund['error_description']);
    }

    public function testSubMerchantPreferences()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

        $this->createSubMerchantForFlexmoney();

        $preferences = $this->getPreferences();

        $this->assertEquals($preferences['methods']['cardless_emi']['hdfc'],true);

        $this->assertEquals($preferences['methods']['cardless_emi']['kkbk'],true);

        $this->assertEquals($preferences['methods']['cardless_emi']['idfb'],true);

        $this->assertEquals($preferences['methods']['cardless_emi']['icic'],true);

//        $this->assertEquals($preferences['methods']['cardless_emi']['barb'],true); // barb is deprecated

        $this->assertEquals($preferences['methods']['cardless_emi']['krbe'],true);

        $this->assertEquals($preferences['methods']['cardless_emi']['cshe'],true);

        $this->assertEquals($preferences['methods']['cardless_emi']['tvsc'],true);

        $this->resetPublicAuthToTestAccount();
    }
}

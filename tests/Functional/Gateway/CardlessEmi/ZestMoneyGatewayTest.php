<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi;

class ZestMoneyGatewayTest extends CardlessEmiGatewayTest
{
    protected $provider = 'zestmoney';

    protected function setUp(): void
    {
        parent::setUp();

        //since zestmoney is moved to nbplus
        $this->markTestSkipped();

        $this->sharedTerminal = $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');
    }

    public function testCheckAccount()
    {

        $data = $this->getCheckAccountArray($this->provider);

        $contact = '+919918899029';

        $this->checkAccount($data);

        $emiPlans = $this->app['cache']->get(sprintf('gateway:emi_plans_%s',
            strtoupper($this->provider) . '_' . $contact . '_10000000000000'), 0);

        $loanUrl = $this->app['cache']->get(sprintf('gateway:loan_url_%s',
            strtoupper($this->provider) . '_' . $contact . '_10000000000000'), 0);

        $this->assertTestResponse($emiPlans, 'testEmiPlans');

        $this->assertEquals('link_to_loan_agreement', $loanUrl);
    }

    public function testPayment()
    {

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $this->checkAccount($payment);

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentZestMoney');

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

        $this->assertTestResponse($payment, 'testPaymentZestMoneyForSubMerchant');

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, 'testPaymentCardlessEmiEntity');

        $this->resetPublicAuthToTestAccount();
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
    }

    public function testRefundFailed()
    {

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'verify_refund')
            {
                $content['status'] = 'failed';
                $content['error_code'] = 'REFUND_FAILED';
                $content['error_description'] = 'Refund failed';
            }

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

    public function testVerifyRefundPayment()
    {

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
        $payment['contact'] = '+91' . $payment['contact'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);

        $this->assertEquals('1234567', $refund['acquirer_data']['arn']);

        $this->assertEquals($payment['status'], 'refunded');

        $this->assertEquals($refund['status'], 'processed');
    }
}

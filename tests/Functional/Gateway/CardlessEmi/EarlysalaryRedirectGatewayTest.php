<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi;

class EarlysalaryRedirectGatewayTest extends CardlessEmiGatewayTest
{
    protected $provider = 'earlysalary';

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');
        $this->fixtures->merchant->enableCardlessEmiProviders(['earlysalary' => 1]);
    }

    public function testPaymentRedirect()
    {
        $this->fixtures->merchant->addFeatures('redirect_to_earlysalary');

        $order = $this->fixtures->create('order', [
            'amount' => '50000',
            'receipt' => "xyz",
        ]);

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $payment['contact'] = '+91' . $payment['contact'];

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        $this->assertNotNull($response['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment,"testPaymentEarlysalary");

        $cardlessEmiEntity = $this->getLastEntity('cardless_emi', true);

        $this->assertTestResponse($cardlessEmiEntity, "testPaymentCardlessEmiEntity");
    }

    public function testPaymentRedirectFailed()
    {
        $this->fixtures->merchant->addFeatures('redirect_to_earlysalary');

        $data = $this->testData["testFailedPaymentEarlysalary"];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'check_account')
            {
                unset($content["redirect_url"]);
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $order = $this->fixtures->create('order', [
                    'amount' => '50000',
                    'receipt' => "xyz",
                ]);

                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

                $payment['contact'] = '+91' . $payment['contact'];

                $payment['order_id'] = $order->getPublicId();

                $this->doAuthPayment($payment);
            });
    }

    public function testPaymentChecksumError()
    {
        $this->fixtures->merchant->addFeatures('redirect_to_earlysalary');

        $data = $this->testData["testChecksumFailedEarlysalary"];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['checksum'] = "abcd";
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $order = $this->fixtures->create('order', [
                    'amount' => '50000',
                    'receipt' => "xyz",
                ]);

                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

                $payment['contact'] = '+91' . $payment['contact'];

                $payment['order_id'] = $order->getPublicId();

                $this->doAuthPayment($payment);
            });
    }

    public function testPaymentAmountError()
    {
        $this->fixtures->merchant->addFeatures('redirect_to_earlysalary');

        $data = $this->testData["testPaymentAmountErrorEarlysalary"];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'check_account')
            {
                $content['redirect_url'] = str_replace("amount=","amount=10",$content['redirect_url']);
            }
        });

        $this->runRequestResponseFlow($data,
            function()
            {
                $order = $this->fixtures->create('order', [
                    'amount' => '50000',
                    'receipt' => "xyz",
                ]);

                $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

                $payment['contact'] = '+91' . $payment['contact'];

                $payment['order_id'] = $order->getPublicId();

                $this->doAuthPayment($payment);
            });
    }

}

<?php

namespace RZP\Tests\Functional\Gateway\Amex;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Error;
use RZP\Error\PublicErrorCode;

class AmexGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AmexGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'amex';

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);

        $this->fixtures->create('merchant:bank_account', ['merchant_id' => '10000000000000']);

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['card']['number'] = '341111111111111';
        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $this->fixtures->create(
            'iin',
            [
                'iin' => 345678,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);
        $this->assertEquals($payment[Entity::TWO_FACTOR_AUTH], TwoFactorAuth::PASSED);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('amex', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAmexEntity'], $payment);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('amex', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->verifyPayment($payment['id']);
    }

    public function testPaymentVerify3DSFailed()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'verify')
            {
                $content['vpc_3DSenrolled'] = 'C';
                $content['vpc_3DSstatus'] = 'N';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->doAuthAndCapturePayment($this->payment);

            $this->verifyPayment($payment['id']);
        });
    }

    public function testAmexCardWhenNotEnabled()
    {
        $this->ba->publicLiveAuth();
        $this->fixtures->merchant->activate();
        $this->fixtures->merchant->enableCard();
        $this->fixtures->merchant->disableMethod('10000000000000', 'amex');
        $this->fixtures->merchant->disableCardNetworks('10000000000000', ['amex']);

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testAmexPricingCheckWhenEnablingAmex()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $methods = ['amex' => 1];

            $content = $this->setPaymentMethods($methods);
        });
    }

    public function testFailureWhen3DSFailsForDomesticMerchant()
    {
        $this->fixtures->merchant->disableInternational();

        $testData = $this->testData['testFailureWhen3DSNotEnrolled'];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->payment['card']['number'] = '345678000000007';
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFailureWhen3DSFailsForRiskyMerchant()
    {
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->enableRisky();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->payment['card']['number'] = '345678000000007';
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFailureWhen3DSNotEnrolled()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->payment['card']['number'] = '345678000000007';
            $this->doAuthPayment($this->payment);
        });
    }

}

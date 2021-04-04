<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class IciciPaylaterGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $provider = 'icic';

    protected $method = 'paylater';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/IciciPaylaterGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_icici_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->payment = $this->getDefaultPayLaterPaymentArray($this->provider);
    }

    public function testPayment()
    {
        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($response['razorpay_payment_id']);

        $entity = $this->getLastEntity('mozart', true);

        $this->assertequals($entity['amount'],$payment['amount']);

        $this->assertequals($payment['id'],$response['razorpay_payment_id']);
    }

    public function testCheckBalanceFailed()
    {
        $payment = $this->payment;

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'check_balance')
            {
                $content['data']['amount'] = '1200';
            }
        });

        $payment['contact'] = '7602579721';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(function (& $content, $action) {
            if ($action === 'verify') {
                $content['errorCode']        = 'payment_verify_failed';
                $content['errorDescription'] = 'Payment verification failed';
                unset($content['status']);
            }
            $payment = $this->payment;

            $payment['contact'] = '7602579721';

            $authPayment = $this->doAuthPayment($payment);

            $this->verifyPayment($authPayment['razorpay_payment_id']);

            $payment = $this->getLastEntity('payment', true);

            $this->assertSame($payment['verified'], 0);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testOtpRetryExceededPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
            'method'        => 'paylater',
            'gateway'       => 'paylater',
            'wallet'        => 'icic',
            'otp_attempts'  => 3,
            'terminal_id'   => $this->sharedTerminal->id,
        ]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }

}

<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GetsimplGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $provider = 'getsimpl';

    protected $method = 'paylater';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/GetsimplGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->sharedTerminal = $this->fixtures->create('terminal:getsimpl_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->payment = $this->getDefaultPayLaterPaymentArray($this->provider);
    }

    public function testCheckAccountOTPFlow()
    {
        //OTP FLOW
        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($response['razorpay_payment_id']);

        $getsimplEntity = $this->getLastEntity('mozart', true);

        $this->assertequals($getsimplEntity['amount'],$payment['amount']);

        $this->assertequals($payment['id'],$response['razorpay_payment_id']);
    }

    public function testCheckAccountRedirectionFlow()
    {
        //Redirection FLOW
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $payment = $this->getLastEntity('payment', true);

        $getsimplEntity = $this->getLastEntity('mozart', true);

        $this->assertequals($payment['status'], 'created');

        $this->assertequals($payment['id'], 'pay_'.$getsimplEntity['payment_id']);

        $this->assertequals($getsimplEntity['action'], 'check_account');

        $this->assertequals($getsimplEntity['data']['status'], 'eligibility_successful');

        $this->assertequals($getsimplEntity['data']['error_code'], 'linking_required');
    }

    public function testRedirectionPaymentFlow()
    {
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $this->makeRequestParent($request);

        $this->processStaticCallback();

        $payment = $this->getLastEntity('payment', true);

        $getsimplEntity = $this->getLastEntity('mozart', true);

        $this->assertequals($payment['status'], 'authorized');

        $this->assertequals($payment['id'], 'pay_'.$getsimplEntity['payment_id']);

        $this->assertequals($getsimplEntity['action'], 'authorize');

        $this->assertequals($getsimplEntity['data']['status'], 'payment_successful');

        $this->assertequals($getsimplEntity['data']['data']['transaction']['status'], 'CLAIMED');
    }

    public function testMultipleCallback()
    {
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $this->makeRequestParent($request);

        $this->processStaticCallback();

        try {

            $this->processStaticCallback();
            $this->fail('Expected exception ' . BadRequestException::class . ' was not thrown');

        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("The payment has already been processed", $e->getMessage());
        }

    }

    public function testOldRedirectionPaymentFlow()
    {
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $this->makeRequestParent($request);

        $this->processStaticCallback(null, 'token', 'Test_Token', 'old');

        $payment = $this->getLastEntity('payment', true);

        $getsimplEntity = $this->getLastEntity('mozart', true);

        $this->assertequals($payment['status'], 'authorized');

        $this->assertequals($payment['id'], 'pay_'.$getsimplEntity['payment_id']);

        $this->assertequals($getsimplEntity['action'], 'authorize');

        $this->assertequals($getsimplEntity['data']['status'], 'payment_successful');

        $this->assertequals($getsimplEntity['data']['data']['transaction']['status'], 'CLAIMED');
    }

    public function testRedirectionPaymentFlowNoToken()
    {
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $this->makeRequestParent($request);

        try
        {
            $this->processStaticCallback(null,'error',null);
        }

        catch( BadRequestException $e)
        {
            $payment = $this->getLastEntity('payment', true);
            $this->assertequals($payment['status'], 'failed');
            self::assertNotNull($e->getData()['payment_id']);
            self::assertNotNull($e->getData()['order_id']);
            self::assertNotNull($e->getError());
        }
    }

    public function testRedirectionPaymentFlowTokenNull()
    {
        $payment = $this->payment;

        $payment['contact'] = '8602579721';

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $this->makeRequestParent($request);

        try
        {
            $this->processStaticCallback(null,'token',"null");
        }

        catch( BadRequestException $e)
        {
            $payment = $this->getLastEntity('payment', true);
            $this->assertequals($payment['status'], 'failed');
            self::assertNotNull($e->getData()['payment_id']);
            self::assertNotNull($e->getData()['order_id']);
            self::assertNotNull($e->getError());
        }
    }

    public function testRefund()
    {
        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment()->toArray();

        $trans = $this->fixtures->create('transaction', ['entity_id' => $payment['id'], 'merchant_id' => $payment['merchant_id'], 'type' => 'payment']);

        $this->fixtures->edit('payment', $payment['id'], ['status' => 'captured','gateway_captured' => true, 'transaction_id' => $trans['id']]);

        $this->payment = $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testFailedPayment()
    {
        $payment = $this->payment;

        $payment['contact'] = '9602579721';

        $this->setOtp('123456');

        try {
              $this->doAuthPayment($payment);
        }
        catch( GatewayErrorException $e)
        {
            self::assertNotNull($e->getError());
        }
    }

    public function testRefundFailed()
    {
        $this->mockServerContentFunction(function (& $content, $action) {
            if ($action === 'refund') {
                $content['status']              = 'failed';
                $content['error_code']          = 'REFUND_FAILED';
                $content['error_description']   = 'Refund failed';
            }
        });

        $this->setOtp('123456');

        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment()->toArray();

        $trans = $this->fixtures->create('transaction', ['entity_id' => $payment['id'], 'merchant_id' => $payment['merchant_id'], 'type' => 'payment']);

        $this->fixtures->edit('payment', $payment['id'], ['status' => 'captured','gateway_captured' => true, 'transaction_id' => $trans['id']]);

        $this->mockServerContentFunction(function (& $content, $action, $response , $payment) {

            $this->payment = $this->refundPayment($response['razorpay_payment_id'], $payment['amount']);

            if ($action === 'refund') {
                $content['status']              = 'failed';
                $content['error_code']          = 'REFUND_FAILED';
                $content['error_description']   = 'Refund failed';
            }

            $gatewayRefund = $this->getLastEntity('mozart', true);

            $refund = $this->getLastEntity('refund', true);

            $this->assertEquals('failed', $refund['status']);

            $this->assertEquals('REFUND_FAILED', $gatewayRefund['error_code']);

            $this->assertEquals('Refund failed', $gatewayRefund['error_description']);
        });
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

    public function testInvalidOtt()
    {
        $payment = $this->payment;

        $payment['contact'] = '7602579721';

        $payment['email']   = 'invalidott@gmail.com';

        $this->setOtp('123456');

        $this->ba->publicAuth();

        try
        {
            $this->doAuthPayment($payment);
        }
        catch (BadRequestValidationFailureException $e)
        {
            self::assertNotNull($e->getError());
        }

    }

    public function processStaticCallback($key = null, $tkey = 'token', $tval = 'Test_Token', $route = 'new')
    {
        $getsimplEntity = $this->getLastEntity('mozart', true);

        $data = [
            'available_credit_in_paise' => 1999400,
            'merchant_payload'          => $getsimplEntity['payment_id'],
            'success'                   => true,
             $tkey                      => $tval
        ];

        $url = '/gateway/getsimpl/callback';

        if($route === 'old')
        {
            $url = '/callback/getsimpl';
        }

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => $data
        ];

        $this->ba->publicAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}

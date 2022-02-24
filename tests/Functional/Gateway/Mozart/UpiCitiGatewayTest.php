<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiCitiGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_citi';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_citi_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        $this->payment['description'] = 'success';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();
        $this->assertTrue($payment->isCreated());

        $content = $this->mockServer()->getCallbackRequest($payment->toArray());

        $response = $this->makeRequestAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        $payment->refresh();

        // The payment should now be authorized
        $this->assertTrue($payment->isAuthorized());
        $this->assertSame('upi_citi', $payment->getGateway());

        $this->capturePayment($payment->getPublicId(), $payment['amount']);

        return $payment;
    }

    public function testPaymentAmountMismatch($status = 'created')
    {
        $this->payment['description'] = 'callbackAmountMismatch';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();
        $this->assertTrue($payment->isCreated());

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $this->makeRequestAndCatchException(function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        });

        $payment->refresh();

        // The payment should now be authorized
        $this->assertTrue($payment->isFailed());
        $this->assertSame('SERVER_ERROR', $payment->getErrorCode());

        return $payment;
    }

    public function testFailedCallbackResponse($status = 'created')
    {
        $this->payment['description'] = 'toBeRejected';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();
        $this->assertTrue($payment->isCreated());

        $request = $this->mockServer()->getCallbackRequest($payment->toArray());

        $this->makeRequestAndCatchException(function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            },
            Exception\GatewayErrorException::class,
            'Payment rejected by customer' . PHP_EOL .
            'Gateway Error Code: ZA' . PHP_EOL .
            'Gateway Error Desc: TRANSACTION DECLINED BY CUSTOMER');

        $payment->refresh();

        $this->assertTrue($payment->isFailed());
        $this->assertSame('BAD_REQUEST_ERROR', $payment->getErrorCode());
        $this->assertSame('You may have declined the payment request on the UPI app. Please retry when you are ready.', $payment->getErrorDescription());
    }

    public function testVerifyPayment()
    {
        $this->payment['description'] = 'verifySuccess';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->makeRequestAndGetContent($this->mockServer()->getCallbackRequest($payment->toArray()));

        $this->verifyPayment($payment->getPublicId());

        $payment->refresh();

        $this->assertSame(1, $payment->verified);
    }

    public function testPaymentFailedVerify()
    {
        $this->payment['description'] = 'verifyNotFound';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->refresh();

        $this->assertSame(1, $payment->verified);
    }

    public function testPaymentLateAuthorized()
    {
        $this->payment['description'] = 'verifySuccess';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment->refresh();

        $this->assertTrue($payment->isAuthorized());
        $this->assertTrue($payment->isLateAuthorized());
    }

    public function testRefundPayment()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->makeRequestAndGetContent($this->mockServer()->getCallbackRequest($payment->toArray()));

        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $this->refundPayment($payment->getPublicId());

        $payment->refresh();

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testPaymentCallbackFromDisabledIp()
    {
        // Changed from 10.10.123.123 to 10.10.123.124
        config()->set('gateway.mozart.upi_citi.allowed_s2p_client_ips', '127.0.0.1, 10.10.123.124');

        $content = $this->mockServer()->getCallbackRequest([
            'gateway'       => 'upi_citi',
            'id'            => 'pay_itsnotrelevant',
            'amount'        => '100.00',
            'description'   => 'wrong_ip'
        ]);

        $this->makeRequestAndCatchException(function() use ($content)
            {
                $this->makeRequestAndGetContent($content);
            },
            Exception\GatewayErrorException::class,
            'Payment processing failed due to error at bank or wallet gateway' . PHP_EOL .
            'Gateway Error Code: ' . PHP_EOL .
            'Gateway Error Desc: S2S request received from wrong blocked IP');

    }
}

<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Airtel;

use RZP\Gateway\Base\Action;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Base\Entity;
use RZP\Gateway\Netbanking\Airtel\AuthFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingAirtelGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAirtelGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_airtel';

        $this->payment = $this->getDefaultNetbankingPaymentArray('AIRP');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_airtel_terminal');
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        // Assert that bank payment id exists and is an integer
        $this->assertArrayHasKey('bank_payment_id', $gatewayPayment);

        $this->assertTrue(filter_var($gatewayPayment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'callback')
            {
                $content['TRAN_AMT'] = '100';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund above payment in full
        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals($refund['amount'], $payment['amount']);
    }

    public function testPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund above payment in full
        $refund = $this->refundPayment($payment['id'], 10000);

        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedAuthPayment()
    {
        $this->mockPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testUndefinedHashFailedPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['STATUS'] = 'FAL';
            $content['CODE']   = '900';
            $content['HASH']   = 'undefined';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testEmptyHashFailedPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['STATUS'] = 'FAL';
            $content['CODE']   = '900';
            $content['HASH']   = '';
        });

        $data = $this->testData['testUndefinedHashFailedPayment'];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testUndefinedHashSuccessPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['HASH']   = 'undefined';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    // Testing verify on a failed payment who's ID is not present on gateway's database.
    // we assert that this results in a status_match as
    // both apiSuccess and gatewaySuccess are false
    public function testAuthCancelledVerify()
    {
        $payment = $this->fixtures->create('payment:netbanking_created', [
            'bank'        => 'AIRP',
            'terminal_id' => '100NbAirtlTmnl',
            'gateway'     => 'netbanking_airtel'
        ]);

        $gatewayPayment = $this->fixtures->create('netbanking',
            [
                'bank'            => 'AIRP',
                'status'          => 'created',
                'payment_id'      => $payment['id'],
                'caps_payment_id' => $payment['id']
            ]
        );

        $this->mockAuthCancelVerifyResponse();

        $verify = $this->verifyPayment('pay_' . $gatewayPayment['payment_id']);

        $this->assertEquals($verify['gateway']['apiSuccess'], false);
        $this->assertEquals($verify['gateway']['gatewaySuccess'], false);
    }

    // This tests the case when the callback is not received from the gateway
    // Upon verifying, we get success response from the gateway and a verify mismatch
    public function testNullBankPaymentIdVerify()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:netbanking_created', [
            'bank'        => 'AIRP',
            'terminal_id' => '100NbAirtlTmnl',
            'gateway'     => 'netbanking_airtel'
        ]);

        $gatewayPayment = $this->fixtures->create('netbanking',
            [
                'bank'            => 'AIRP',
                'status'          => 'created',
                'payment_id'      => $payment['id'],
                'caps_payment_id' => $payment['id']
            ]
        );

        $this->runRequestResponseFlow($data, function() use ($gatewayPayment)
        {
            $this->verifyPayment('pay_' . $gatewayPayment['payment_id']);
        });
    }

    public function testVerifyMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyStatusFailure();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    // Verify doesn't contain the bank_payment_id from auth
    public function testUnexpectedVerifyResponse()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockSetVerifyFakeTransactionId();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testNullVerifyResponse()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockNullVerifyResponse();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    // Authorization fails, but verify shows success
    // Results in a payment verification error
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testFailedRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockRefundFailure();

        $this->refundPayment($payment['id'], 100);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('created', $refund['status']);
    }

    public function testAuthResponseHashFailure()
    {
        $this->mockAuthHashFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testRefundAuthorizedPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $refund = $this->refundAuthorizedPayment($payment['razorpay_payment_id']);

        $this->assertSame($refund['payment_id'], $payment['razorpay_payment_id']);
    }

    public function testPaymentCallbackLongErrorMessage()
    {
	    $errorMessage = str_random(280);

		$this->mockServerContentFunction(function(&$content, $action = null) use($errorMessage)
			{
				if ($action === Action::CALLBACK)
				{
					$content[AuthFields::MSG] = $errorMessage;
				}
			});

	    $this->doAuthPayment($this->payment);

	    $gatewayEntity = $this->getLastEntity('netbanking', true);

	    $expectedErrorMessage = substr($errorMessage, 0, 255);

	    $this->assertEquals($expectedErrorMessage, $gatewayEntity[Entity::ERROR_MESSAGE]);
    }

    protected function mockSetVerifyFakeTransactionId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['txns'][0]['txnid'] = '12345';
        });
    }

    protected function mockPaymentFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['STATUS'] = 'FAL';
            $content['CODE']   = '902';
        });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['code'] = '1';
            $content['errorCode'] = '9002';
        });
    }

    protected function mockAuthCancelVerifyResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['code'] = '1';
            $content['errorCode'] = '910';
            $content['txns'] = [];
        });
    }

    protected function mockVerifyStatusFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $gatewayPayment = $this->getLastEntity('netbanking', true);

            $content['txns'][0]['txnid'] = $gatewayPayment['bank_payment_id'];
            $content['txns'][0]['status'] = 'FAL';
        });
    }

    protected function mockNullVerifyResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['txns'] = [];
        });
    }

    protected function mockAuthHashFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if($action === 'hash')
            {
                // Shuffling the hash so that hash verification fails
                $content['HASH'] = 'this_is_a_random_hash_string';
            }
        });
    }

    protected function mockRefundFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['code'] = '1';
            $content['errorCode'] = '9002';
        });
    }
}

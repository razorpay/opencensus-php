<?php

namespace RZP\Tests\Functional\Gateway\Hitachi;

use RZP\Models\Card;
use RZP\Gateway\Hitachi;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Blade\Mock\CardNumber;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HitachiGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/HitachiGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_hitachi_terminal');

        //
        // Hitachi is a lower priority card gateway than hdfc in
        // RZP\Models\Gateway\Priority\Defaults in the Method::Card section.
        // Therefore, we must disable hdfc terminal for the test cases
        // to run via the shared Hitachi terminal.
        //
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->mockTokenex();
    }

    public function testSuccessful13DigitPanForEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $payment);
    }

    public function testNotEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_NOT_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);
        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $payment);
    }

    public function testPaymentVerify()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $verify = $this->verifyPayment($payment['id']);

        $hitachi = $verify['gateway']['gatewayPayment'];

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertEquals(1, $verify['payment']['verified']);
        $this->assertEquals('status_match', $verify['gateway']['status']);

        //
        // Asserting that the request id from the response is being
        // saved as the  gateway transaction id in the hitachi entity
        //
        $this->assertEquals($verifyResponseContent[Hitachi\ResponseFields::REQUEST_ID],
                            $hitachi[Hitachi\Entity::REQUEST_ID]);

        $this->assertEquals('S', $hitachi[Hitachi\Entity::STATUS]);
    }

    public function testPaymentVerifyFailed()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertEquals('F', $hitachi[Hitachi\Entity::STATUS]);
    }

    public function testCapturePayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals('captured', $capturedPayment['status']);

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertTestResponse($hitachi, 'testPaymentCaptureEntity');

        $paymentId = explode('_' ,$capturedPayment['id'])[1];

        $this->assertEquals($paymentId, $hitachi[Hitachi\Entity::PAYMENT_ID]);
        $this->assertNotNull($hitachi[Hitachi\Entity::REQUEST_ID]);
        $this->assertNotNull($hitachi[Hitachi\Entity::RRN]);
    }

    public function testCaptureFailure()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $data = $this->testData['testCaptureFailureException'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->capturePayment($payment['public_id'], $payment['amount']);
            });

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertTestResponse($hitachi, 'testCaptureFailureEntity');
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    public function testPartialRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(10000, $refund['amount']);

        $this->paymentRefundReverseTestHelper($payment, 10000, 'partial');
    }

    public function testRefundFailure()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->refundReverseFailureTestHelper($payment);
    }

    public function testPaymentReverse()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundAuthorizedPayment($payment['id']);

        $this->paymentRefundReverseTestHelper($payment);
    }

    public function testReverseFailure()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $this->refundAuthorizedPayment($payment['id']);

        $this->refundReverseFailureTestHelper($payment);
    }

    public function testInvalidJson()
    {
        $this->mockAuthFormatError();

        $data = $this->testData['testInvalidJson'];

        $this->runRequestResponseFlow(
            $data,
            function ()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::VALID_ENROLL_NUMBER,
                        'expiry_month' => '02',
                        'expiry_year'  => '21',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }
}

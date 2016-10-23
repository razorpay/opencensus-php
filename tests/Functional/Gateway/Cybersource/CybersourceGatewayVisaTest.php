<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Cybersource;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CybersourceGatewayVisaTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayVisaTestData.php';

        parent::setUp();

        $this->sharedHdfcTerminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testEnrolledCardSuccessfulAuthentication()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000002';

        $response = $this->doAuthPayment($payment);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($response['razorpay_payment_id']);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertTestResponse($cybersource);
    }

    public function testAuthenticationSuccessfulInvalidPares()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000000071';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorize_failed', $cybersource['status']);
        $this->assertEquals(476, $cybersource['reason_code']);
        $this->assertNull($cybersource['eci']);
    }

    public function testEnrolledAttemptsProcessing()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000000063';

        $response = $this->doAuthPayment($payment);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($response['razorpay_payment_id']);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertNotNull($cybersource['xid']);
        $this->assertTestResponse($cybersource);
    }

    public function testEnrolledIncompleteAuthentication()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000036';

        $response = $this->doAuthPayment($payment);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($response['razorpay_payment_id']);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertEquals('authorized', $cybersource['status']);
        $this->assertEquals(100, $cybersource['reason_code']);
        $this->assertEquals('internet', $cybersource['commerce_indicator']);
        $this->assertEquals('07', $cybersource['eci']);
    }

    public function testUnsuccessfulAuthenticationUserFailed()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000028';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorize_failed', $cybersource['status']);
        $this->assertEquals(476, $cybersource['reason_code']);
        $this->assertEquals('N', $cybersource['pares_status']);
        $this->assertNotNull($cybersource['xid']);
        $this->assertNull($cybersource['eci']);
    }

    public function testEnrolledCardUnavailableAuthentication()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000000014';

        $this->doAuthPayment($payment);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorized', $cybersource['status']);
        $this->assertEquals('U', $cybersource['veresEnrolled']);
        $this->assertEquals('internet', $cybersource['commerce_indicator']);
        $this->assertNull($cybersource['xid']);
        $this->assertNull($cybersource['eci']);
    }

    public function testAuthenticationError()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000093';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorize_failed', $cybersource['status']);
        $this->assertEquals(476, $cybersource['reason_code']);
        $this->assertEquals('07', $cybersource['eci']);
    }

    public function testCardNotEnrolled()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000051';

        $response = $this->doAuthPayment($payment);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorized', $cybersource['status']);
        $this->assertEquals('N', $cybersource['veresEnrolled']);
        $this->assertEquals('vbv_attempted', $cybersource['commerce_indicator']);
        $this->assertNull($cybersource['xid']);
        $this->assertEquals('06', $cybersource['eci']);
    }

    public function testEnrollmentErrorCheckWithErrorResponse()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000085';

        $response = $this->doAuthPayment($payment);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorized', $cybersource['status']);
        $this->assertEquals('U', $cybersource['veresEnrolled']);
        $this->assertEquals('internet', $cybersource['commerce_indicator']);
        $this->assertNull($cybersource['xid']);
        $this->assertNull($cybersource['eci']);
    }

    public function testEnrollmentErrorCheckWithIncorrectConf()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000077';

        $response = $this->doAuthPayment($payment);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('authorized', $cybersource['status']);
        $this->assertEquals('U', $cybersource['veresEnrolled']);
        $this->assertEquals('internet', $cybersource['commerce_indicator']);
        $this->assertNull($cybersource['xid']);
        $this->assertNull($cybersource['eci']);
    }
}
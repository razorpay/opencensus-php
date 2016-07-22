<?php

namespace RZP\Tests\Functional\Gateway\Ebs;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class EbsGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EbsGatewayTestData.php';
        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'ebs';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('ebs', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentEbsEntity'], $payment);
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);
        $this->refundPayment($payment['id']);
        $refund = $this->getLastEntity('ebs', true);
        $this->assertTestResponse($refund);
    }

    public function testPaymentRefundWithoutCapture()
    {
        $this->setExpectedException('RZP\Exception\BadRequestException');
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doauthpayment($payment);

        $payment = $this->getlastentity('payment', true);
        $this->refundpayment($payment['id']);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('ebs', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentRefund'], $refund);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterRefundingAuthorizedPayment'], $txn);
    }

    /*
     * throw Run-time exception if payment method is Card
     * Ebs is enabled for netbanking only
     */
    public function testErrorOnCard()
    {
        $this->setExpectedException('RZP\Exception\RuntimeException');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['method'] = 'card';

        $payment = $this->doAuthPayment($payment);
    }

    public function testPaymentInvalidRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->capturePayment($payment['public_id'],
            $payment['amount']);

        $this->getErrorInRefund();

        try
        {
            $this->refundPayment($payment['id']);
        }
        catch (Exception\GatewayErrorException $e)
        {
        }

        $refund = $this->getLastEntity('ebs', true);

        $this->assertEquals($refund['ErrorCode'], "29");
        $this->assertEquals($refund['ErrorDescription'], "Insufficien");

    }
}

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


        $this->gateway = 'ebs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
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

    public function testPaymentForBankWith302Redirect()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('UBIN');
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);
    }

    public function testPaymentForBankWithFormRedirect()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('YESB');
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);
    }

    public function testPaymentForFirstGatewayRequestFailure()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('CBIN');

        $data = $this->testData['testPaymentForFirstGatewayRequestFailure'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForSecondGatewayRequestFailure()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('CNRB');
        $data = $this->testData['testPaymentForSecondGatewayRequestFailure'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForThirdGatewayRequestFailure()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('JAKA');

        $data = $this->testData['testPaymentForThirdGatewayRequestFailure'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testHackedPayment()
    {
        $this->getHackedResponse();

        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $data = $this->testData['testHackedPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
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
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $data = $this->testData['testPaymentRefundWithoutCapture'];

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
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

    public function testErrorOnCard()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $data = $this->testData['testErrorOnCard'];

        $payment['method'] = 'card';

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentInvalidRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->capturePayment($payment['public_id'],
            $payment['amount']);

        $this->getErrorInRefund();

        $data = $this->testData['testPaymentInvalidRefund'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id']);
        });

        $refund = $this->getLastEntity('ebs', true);
        $this->assertEquals($refund['error_code'], "29");
        $this->assertEquals($refund['error_description'], "Insufficient balance");
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }

    public function testPaymentFailedVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->getErrorInVerify();
        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testPaymentFailedVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->verifyPayment($payment['id']);
        });

        $this->assertSame($payment['verified'], null);
    }

    public function testPaymentFailedVerifyAndRetry()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->getErrorInVerify();
        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testPaymentFailedVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->verifyPayment($payment['id']);
        });

        $this->assertSame($payment['verified'], null);

        $this->resetMockServer();

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }
}

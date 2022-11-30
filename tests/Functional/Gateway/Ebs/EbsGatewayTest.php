<?php

namespace RZP\Tests\Functional\Gateway\Ebs;

use Razorpay\IFSC\Bank;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class EbsGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/EbsGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $this->gateway = 'ebs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
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

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['Amount'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = Bank::MAHB;

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForBankWith302Redirect()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);
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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);

        $data = $this->testData['testHackedPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
          $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $override = [
            '{{transactionId}}' => random_integer(8),
            '{{paymentId}}'     => random_integer(8),
        ];
        $this->mockServerContentFunction(function(& $content) use ($override)
        {
            $content = strtr($content, $override);
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('ebs', true);
        $this->assertTestResponse($refund);

        $this->assertEquals($override['{{transactionId}}'], $refund['transaction_id']);
        $this->assertEquals($override['{{paymentId}}'], $refund['gateway_payment_id']);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthPayment($payment);
        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->refundPayment($payment['id'], 40000);

        $refund = $this->getLastEntity('ebs', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultiplePartialRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthPayment($payment);
        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->refundPayment($payment['id'], 40000);

        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('ebs', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentMultipleInvalidPartialRefund()
    {
        $data = $this->testData['testPaymentMultipleInvalidPartialRefund'];

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);

        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->refundPayment($payment['id'], 40000);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id'], 40000);
        });
    }

    public function testPaymentRefundWithoutCapture()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);

        $data = $this->testData['testPaymentRefundWithoutCapture'];

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

        $payment['card'] = [
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        ];

        $data = $this->testData['testErrorOnCard'];

        $payment['method'] = 'card';

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentInvalidRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->capturePayment($payment['public_id'],
            $payment['amount']);

        $this->getErrorInRefund();

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('ebs', true);
        $this->assertEquals('refund', $refund['action']);
        $this->assertNotEquals(null, $refund['refund_id']);
        $this->assertEquals('29', $refund['error_code']);
        $this->assertEquals('Insufficient balance', $refund['error_description']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }

    public function testPaymentFailedVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->getErrorInVerify();
        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testPaymentFailedVerify'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $this->assertSame($payment['verified'], null);
    }

    public function testPaymentFailedVerifyAndRetry()
    {
        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
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

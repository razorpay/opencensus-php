<?php

namespace RZP\Tests\Functional\Gateway\AxisMigs;

use Mockery;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Tests\Functional\Fixtures;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Error;
use RZP\Error\PublicErrorCode;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        // $this->markTestSkipped('Removed');

        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $migs);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsCaptureEntity'], $migs);
    }

    public function testPaymentBefore1stNov()
    {
        $before1stNov = Carbon::create(2016, 10, 30);

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        // Transaction will not get created as we have changed the
        // gateway to auth and capture.
        $this->assertNull($payment['transaction_id']);

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($payment['id']);

        (new Fixtures\Entity\Base)
            ->editEntity('payment', $paymentId, ['created_at' => $before1stNov->timestamp]);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPayment'], $payment);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $migs);
    }

    public function testMasterCardPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthPayment($payment);
    }

    public function testFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testMaestroOnMigsFailOnLive()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $this->fixtures->on('live')->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1, 'live' => 1, 'pricing_plan_id' => '1hDYlICobzOCYt']);
        // $merchant = $this->fixtures->merchant->activate();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->ba->publicLiveAuth();
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment();
        $this->assertEquals($payment['status'], 'captured');

        $this->verifyPayment($payment['id']);
        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertEquals('capture', $payment['vpc_Command']);
    }

    public function testPaymentVerifyFailed()
    {
        $payment = $this->doAuthPayment();
        $pid = $payment['razorpay_payment_id'];

        $this->fixtures->payment->edit($pid, ['status' => 'failed', 'authorized_at' => null]);

        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content)
                        {
                            $content['vpc_DRExists'] = 'Y';
                        })->mock();

        $this->setMockServer($server);

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($data, function() use ($pid)
        {
            $this->verifyPayment($pid);
        });
    }

    public function testAuthorizeFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->resetMockServer();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testForceAuthorizePayment()
    {
        $payment = $this->doAuthPayment();
        $migs = $this->getLastEntity('axis_migs', true);
        $txnNo = (int) $migs['vpc_TransactionNo'] - 1;

        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('axis_migs', true);
        $pid1 = 'pay_'.$payment['payment_id'];
        $txnNoNew = $payment['vpc_TransactionNo'];

        $this->fixtures->edit('axis_migs', $payment['id'], ['received' => '0']);

        $this->resetMockServer();

        $this->forceAuthorizeFailedPayment($pid1, ['vpc_TransactionNo' => $txnNo]);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($payment['vpc_TransactionNo'], $txnNoNew);
    }

    public function testFailureWhen3DSFailsForDomesticMerchant()
    {
        $this->fixtures->merchant->disableInternational();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
	        $payment = $this->getDefaultPaymentArray();
	        $payment['card']['number'] = '55553555655655';
	        $payment = $this->doAuthPayment($payment);
	    });
    }

    public function testFailureWhen3DSFailsForRiskyMerchant()
    {
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->enableRisky();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '55553555655655';
            $payment = $this->doAuthPayment($payment);
        });
    }

}
